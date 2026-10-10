<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\AddonPricingMethod;
use App\Enums\BookingStatus;
use App\Enums\ServicePricingMethod;
use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\ImportExport\Dataset;
use App\Services\ImportExport\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportExportControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function formats(): array
    {
        return [
            'csv' => ['csv'],
            'xlsx' => ['xlsx'],
        ];
    }

    #[DataProvider('formats')]
    public function test_exports_categories_services_and_addons(string $format): void
    {
        $admin = User::factory()->admin()->create();
        $parent = ServiceCategory::factory()->create(['name' => 'Residential', 'slug' => 'residential']);
        ServiceCategory::factory()->forParent($parent)->create(['name' => 'Kitchens', 'slug' => 'kitchens']);
        $service = Service::factory()->for($parent, 'category')->fixed(14999)->create([
            'name' => 'Window Cleaning',
            'slug' => 'window-cleaning',
            'inclusions' => ['Interior glass', 'Frames'],
            'exclusions' => ['Skylights'],
        ]);
        $addon = ServiceAddon::factory()->create(['name' => 'Inside fridge', 'slug' => 'inside-fridge', 'price_cents' => 2500]);
        $addon->services()->sync([$service->id]);

        $categories = $this->sheet($this->actingAs($admin)->get(route('admin.import-export.export', [
            'dataset' => 'categories',
            'format' => $format,
        ])), Dataset::Categories, $format);
        $kitchens = collect($categories)->firstWhere('slug', 'kitchens');
        $this->assertSame('residential', $kitchens['parent_slug']);
        $this->assertSame('1', $kitchens['is_active']);

        $services = $this->sheet($this->actingAs($admin)->get(route('admin.import-export.export', [
            'dataset' => 'services',
            'format' => $format,
        ])), Dataset::Services, $format);
        $this->assertSame('residential', $services[0]['category_slug']);
        $this->assertSame('Interior glass|Frames', $services[0]['inclusions']);
        $this->assertSame('Skylights', $services[0]['exclusions']);
        $this->assertSame('149.99', $services[0]['price']);
        $this->assertSame(ServicePricingMethod::Fixed->value, $services[0]['pricing_method']);

        $addons = $this->sheet($this->actingAs($admin)->get(route('admin.import-export.export', [
            'dataset' => 'addons',
            'format' => $format,
        ])), Dataset::Addons, $format);
        $this->assertSame('window-cleaning', $addons[0]['service_slugs']);
        $this->assertSame('25.00', $addons[0]['price']);
        $this->assertSame(AddonPricingMethod::Fixed->value, $addons[0]['pricing_method']);
    }

    public function test_booking_export_filters_by_date_and_status_and_includes_contact_details(): void
    {
        $kept = Booking::factory()->priced(10000, 500)->submitted(BookingStatus::Confirmed)->create([
            'reference' => 'SCR-KEEP',
            'service_name' => 'Window Cleaning',
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'contact_phone' => '604-555-0199',
            'submitted_at' => '2026-03-15 09:00:00',
            'preferred_date' => '2026-03-20',
        ]);
        $kept->address()->update(['line1' => '123 Main Street', 'line2' => null, 'city' => 'Vancouver', 'province' => 'BC', 'postal_code' => 'V6B 1A1']);
        Booking::factory()->submitted(BookingStatus::PendingQuote)->create([
            'reference' => 'SCR-OTHER',
            'submitted_at' => '2026-04-02 09:00:00',
        ]);
        Booking::factory()->create(['reference' => 'SCR-DRAFT']);

        $rows = $this->sheet($this->actingAs(User::factory()->admin()->create())->get(route('admin.import-export.export', [
            'dataset' => 'bookings',
            'format' => 'csv',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'status' => BookingStatus::Confirmed->value,
        ])), Dataset::Bookings, 'csv');

        $this->assertCount(1, $rows);
        $this->assertSame('SCR-KEEP', $rows[0]['reference']);
        $this->assertSame('confirmed', $rows[0]['status']);
        $this->assertSame('Window Cleaning', $rows[0]['service']);
        $this->assertSame('Ada Lovelace', $rows[0]['contact_name']);
        $this->assertSame('ada@example.com', $rows[0]['contact_email']);
        $this->assertSame('604-555-0199', $rows[0]['contact_phone']);
        $this->assertSame('100.00', $rows[0]['subtotal']);
        $this->assertSame('105.00', $rows[0]['total']);
        $this->assertSame('CAD', $rows[0]['currency']);
        $this->assertStringContainsString('123 Main Street', $rows[0]['address']);
    }

    #[DataProvider('formats')]
    public function test_export_prefixes_cells_that_start_with_a_formula_character(string $format): void
    {
        ServiceCategory::factory()->create(['name' => '=1+1', 'slug' => 'formula']);

        $rows = $this->sheet($this->actingAs(User::factory()->admin()->create())->get(route('admin.import-export.export', [
            'dataset' => 'categories',
            'format' => $format,
        ])), Dataset::Categories, $format);

        $this->assertSame("'=1+1", $rows[0]['name']);
    }

    public function test_blank_template_contains_only_the_header_row(): void
    {
        $rows = $this->sheet($this->actingAs(User::factory()->admin()->create())->get(route('admin.import-export.template', [
            'dataset' => 'categories',
            'format' => 'csv',
        ])), Dataset::Categories, 'csv');

        $this->assertSame([], $rows);
    }

    public function test_import_creates_and_updates_categories_by_slug(): void
    {
        $admin = User::factory()->admin()->create();
        ServiceCategory::factory()->create(['name' => 'Old name', 'slug' => 'kitchens', 'description' => 'Old']);

        $this->actingAs($admin)
            ->post(route('admin.import-export.import'), [
                'dataset' => 'categories',
                'file' => $this->csv('categories.csv', Dataset::Categories, [
                    ['Residential', 'residential', '', 'Homes', '', '', '0', '1'],
                    ['Kitchens', 'kitchens', 'residential', 'Updated', '', '', '1', '0'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Imported 1 new categories and updated 1.');

        $parent = ServiceCategory::query()->where('slug', 'residential')->sole();
        $child = ServiceCategory::query()->where('slug', 'kitchens')->sole();
        $this->assertNull($parent->parent_id);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame('Updated', $child->description);
        $this->assertFalse($child->is_active);
    }

    #[DataProvider('formats')]
    public function test_reimporting_an_export_does_not_change_categories(string $format): void
    {
        $admin = User::factory()->admin()->create();
        $parent = ServiceCategory::factory()->create([
            'name' => 'Residential',
            'slug' => 'residential',
            'description' => 'Homes',
            'seo_title' => 'Home cleaning',
            'seo_description' => 'Meta',
            'sort_order' => 2,
        ]);
        $child = ServiceCategory::factory()->forParent($parent)->inactive()->create([
            'name' => 'Kitchens',
            'slug' => 'kitchens',
            'description' => null,
            'sort_order' => 1,
        ]);
        $parentStamp = $parent->fresh()->updated_at;
        $childStamp = $child->fresh()->updated_at;

        $export = $this->actingAs($admin)->get(route('admin.import-export.export', [
            'dataset' => 'categories',
            'format' => $format,
        ]));
        $export->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.import-export.import'), [
                'dataset' => 'categories',
                'file' => UploadedFile::fake()->createWithContent('categories.'.$format, $export->streamedContent()),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Imported 0 new categories and updated 0.');

        $parent->refresh();
        $child->refresh();
        $this->assertTrue($parent->updated_at->equalTo($parentStamp));
        $this->assertTrue($child->updated_at->equalTo($childStamp));
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame('Homes', $parent->description);
        $this->assertFalse($child->is_active);
        $this->assertSame(2, ServiceCategory::query()->count());
    }

    public function test_unknown_parent_imports_nothing(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.import-export.import'), [
                'dataset' => 'categories',
                'file' => $this->csv('categories.csv', Dataset::Categories, [
                    ['Residential', 'residential', '', '', '', '', '0', '1'],
                    ['Kitchens', 'kitchens', 'missing', '', '', '', '0', '1'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertSame(0, ServiceCategory::query()->count());
    }

    public function test_unknown_category_or_pricing_method_imports_nothing(): void
    {
        ServiceCategory::factory()->create(['slug' => 'residential']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.import-export.import'), [
                'dataset' => 'services',
                'file' => $this->csv('services.csv', Dataset::Services, [
                    $this->serviceRow('window-cleaning', 'residential', 'fixed'),
                    $this->serviceRow('oven-cleaning', 'missing', 'nope'),
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertSame(0, Service::query()->count());
    }

    public function test_import_rejects_more_than_2000_rows(): void
    {
        $rows = [];

        foreach (range(1, 2001) as $index) {
            $rows[] = ["Category {$index}", "category-{$index}", '', '', '', '', '0', '1'];
        }

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.import-export.import'), [
                'dataset' => 'categories',
                'file' => $this->csv('categories.csv', Dataset::Categories, $rows),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, ServiceCategory::query()->count());
    }

    public function test_import_rejects_files_over_5_mb(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.import-export.import'), [
                'dataset' => 'categories',
                'file' => UploadedFile::fake()->create('categories.csv', 5121, 'text/csv'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_import_splits_pipe_lists_and_syncs_addon_services(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create(['slug' => 'residential']);
        $windows = Service::factory()->for($category, 'category')->create(['slug' => 'window-cleaning']);
        $ovens = Service::factory()->for($category, 'category')->create(['slug' => 'oven-cleaning']);

        $this->actingAs($admin)
            ->post(route('admin.import-export.import'), [
                'dataset' => 'services',
                'file' => $this->csv('services.csv', Dataset::Services, [
                    $this->serviceRow('fridge-cleaning', 'residential', 'fixed', 'Doors|Shelves', 'Food'),
                ]),
            ])
            ->assertRedirect();

        $service = Service::query()->where('slug', 'fridge-cleaning')->sole();
        $this->assertSame(['Doors', 'Shelves'], $service->inclusions);
        $this->assertSame(['Food'], $service->exclusions);
        $this->assertSame(15000, $service->price_cents);

        $this->actingAs($admin)
            ->post(route('admin.import-export.import'), [
                'dataset' => 'addons',
                'file' => $this->csv('addons.csv', Dataset::Addons, [
                    ['Inside fridge', 'inside-fridge', 'Empty it first', 'fixed', '25.00', '1', '0', 'window-cleaning|oven-cleaning'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Imported 1 new add-ons and updated 0.');

        $addon = ServiceAddon::query()->where('slug', 'inside-fridge')->sole();
        $this->assertEqualsCanonicalizing([$windows->id, $ovens->id], $addon->services()->pluck('services.id')->all());

        $this->actingAs($admin)
            ->post(route('admin.import-export.import'), [
                'dataset' => 'addons',
                'file' => $this->csv('addons.csv', Dataset::Addons, [
                    ['Inside fridge', 'inside-fridge', 'Empty it first', 'fixed', '25.00', '1', '0', 'window-cleaning'],
                ]),
            ])
            ->assertSessionHas('status', 'Imported 0 new add-ons and updated 1.');

        $this->assertEquals([$windows->id], $addon->services()->pluck('services.id')->all());
    }

    public function test_import_restores_a_deleted_service_without_replacing_its_chat_script(): void
    {
        $category = ServiceCategory::factory()->create(['slug' => 'residential']);
        $service = Service::factory()->for($category, 'category')->create([
            'slug' => 'window-cleaning',
            'name' => 'Window Cleaning',
            'chat_script' => ['greeting' => 'Keep me', 'needs_prompt' => null, 'suggested_prompts' => []],
        ]);
        $service->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.import-export.import'), [
                'dataset' => 'services',
                'file' => $this->csv('services.csv', Dataset::Services, [
                    $this->serviceRow('window-cleaning', 'residential', 'custom_quote'),
                ]),
            ])
            ->assertSessionHas('status', 'Imported 0 new services and updated 1.');

        $restored = Service::query()->where('slug', 'window-cleaning')->sole();
        $this->assertSame('Window Cleaning', $restored->name);
        $this->assertSame('Keep me', $restored->chat_script['greeting']);
        $this->assertNull($restored->deleted_at);
    }

    public function test_customer_cannot_import_or_export(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.import-export.export', ['dataset' => 'categories', 'format' => 'csv']))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.import-export.import'), [
                'dataset' => 'categories',
                'file' => $this->csv('categories.csv', Dataset::Categories, [
                    ['Residential', 'residential', '', '', '', '', '0', '1'],
                ]),
            ])
            ->assertForbidden();

        $this->assertSame(0, ServiceCategory::query()->count());
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function csv(string $filename, Dataset $dataset, array $rows): UploadedFile
    {
        $lines = [implode(',', $dataset->headers())];

        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn (string $cell): string => '"'.str_replace('"', '""', $cell).'"', $row));
        }

        return UploadedFile::fake()->createWithContent($filename, implode("\n", $lines)."\n");
    }

    /**
     * @return list<string>
     */
    private function serviceRow(string $slug, string $categorySlug, string $pricingMethod, string $inclusions = '', string $exclusions = ''): array
    {
        $name = str_replace('-', ' ', $slug);

        return [
            ucwords($name), $slug, $categorySlug, 'A short description', '', $inclusions, $exclusions, '',
            $pricingMethod, $pricingMethod === 'fixed' ? '150.00' : '', '', '', '0', '', '', '1', '0',
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function sheet(TestResponse $response, Dataset $dataset, string $format): array
    {
        $response->assertOk();
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'scrubit-'.uniqid().'.'.$format;
        file_put_contents($path, $response->streamedContent());

        try {
            return app(SpreadsheetReader::class)->rows($path, $format, $dataset->headers());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
