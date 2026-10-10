<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\ServicePricingMethod;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'categories' => [ServiceCategory::factory()->create()->id],
            'name' => 'Window Cleaning',
            'slug' => '',
            'short_description' => 'Streak-free windows inside and out.',
            'description' => "## Sparkling glass\n\nWe clean frames and tracks too.",
            'inclusions' => "Interior glass\nFrames and tracks\n",
            'exclusions' => 'Skylights',
            'pricing_method' => 'fixed',
            'price' => '149.99',
            'chat_greeting' => 'Hi! Tell us about your windows.',
            'suggested_prompts' => "Ten windows, two storeys\nJust the patio doors",
            'is_active' => '1',
            'allows_recurring' => '0',
            ...$overrides,
        ];
    }

    public function test_admin_creates_a_service_with_generated_slug_and_cents_pricing(): void
    {
        Storage::fake('public');
        $addon = ServiceAddon::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), [
                ...$this->payload(),
                'addons' => [$addon->id],
                'featured_image' => UploadedFile::fake()->image('window.jpg', 800, 600),
            ])
            ->assertRedirect();

        $service = Service::query()->where('slug', 'window-cleaning')->sole();
        $this->assertSame(ServicePricingMethod::Fixed, $service->pricing_method);
        $this->assertSame(14999, $service->price_cents);
        $this->assertSame(['Interior glass', 'Frames and tracks'], $service->inclusions);
        $this->assertSame(['Ten windows, two storeys', 'Just the patio doors'], $service->suggestedPrompts());
        $this->assertTrue($service->addons->contains($addon));
        $this->assertStringEndsWith('.webp', $service->featured_image);
        Storage::disk('public')->assertExists($service->featured_image);

        $this->get(route('services.show', $service))->assertOk()->assertSee('Window Cleaning')->assertSee('Frames and tracks');
    }

    public function test_a_service_is_listed_under_every_selected_category(): void
    {
        $residential = ServiceCategory::factory()->create(['name' => 'Residential']);
        $commercial = ServiceCategory::factory()->create(['name' => 'Commercial']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload([
                'categories' => [$residential->id, $commercial->id],
                'name' => 'Shared Cleaning',
                'slug' => 'shared-cleaning',
            ]))
            ->assertRedirect();

        $service = Service::query()->where('slug', 'shared-cleaning')->sole();
        $this->assertSame($residential->id, $service->service_category_id);
        $this->assertEqualsCanonicalizing([$residential->id, $commercial->id], $service->categories->pluck('id')->all());

        $this->get(route('services.category', $residential))->assertOk()->assertSee('Shared Cleaning');
        $this->get(route('services.category', $commercial))->assertOk()->assertSee('Shared Cleaning');
        $this->get(route('services.index'))->assertOk()->assertSee('Residential')->assertSee('Commercial')->assertSee('Shared Cleaning');
    }

    public function test_saving_keeps_the_primary_category_when_it_is_still_selected(): void
    {
        $primary = ServiceCategory::factory()->create();
        $extra = ServiceCategory::factory()->create();
        $service = Service::factory()->for($primary, 'category')->create(['slug' => 'window-cleaning']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.services.update', $service), $this->payload([
                'slug' => 'window-cleaning',
                'categories' => [$extra->id, $primary->id],
            ]))
            ->assertRedirect();

        $service->refresh();
        $this->assertSame($primary->id, $service->service_category_id);
        $this->assertEqualsCanonicalizing([$primary->id, $extra->id], $service->categories->pluck('id')->all());
    }

    public function test_a_service_requires_at_least_one_category(): void
    {
        $payload = $this->payload();
        unset($payload['categories']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $payload)
            ->assertSessionHasErrors(['categories' => 'The categories field is required.']);
    }

    public function test_fixed_pricing_requires_a_price(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload(['price' => '']))
            ->assertSessionHasErrors(['price' => 'A fixed price is required for fixed-price services.']);
    }

    public function test_slugs_must_be_unique(): void
    {
        Service::factory()->create(['slug' => 'window-cleaning']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload())
            ->assertSessionHasErrors('slug');
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), [...$this->payload(), 'featured_image' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])
            ->assertSessionHasErrors('featured_image');
    }

    public function test_unpublishing_hides_the_service_page(): void
    {
        $service = Service::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.services.toggle', $service))->assertRedirect();

        $this->assertFalse($service->fresh()->is_active);
        $this->get(route('services.show', $service))->assertNotFound();
    }

    #[DataProvider('exportFormats')]
    public function test_admin_exports_services(string $format): void
    {
        $category = ServiceCategory::factory()->create(['slug' => 'residential']);
        Service::factory()->for($category, 'category')->fixed(14999)->create([
            'name' => 'Window Cleaning',
            'slug' => 'window-cleaning',
            'inclusions' => ['Interior glass', 'Frames'],
            'exclusions' => ['Skylights'],
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.services.index'))
            ->assertOk()
            ->assertSee('Export')
            ->assertSee('Import');

        $response = $this->actingAs($admin)->get(route('admin.services.export', ['format' => $format]));
        $response->assertOk();
        $rows = $this->serviceSheet($response->streamedContent(), $format);

        $this->assertSame('window-cleaning', $rows[0]['slug']);
        $this->assertSame('residential', $rows[0]['category_slug']);
        $this->assertSame('Interior glass|Frames', $rows[0]['inclusions']);
        $this->assertSame('Skylights', $rows[0]['exclusions']);
        $this->assertSame('149.99', $rows[0]['price']);
        $this->assertSame('fixed', $rows[0]['pricing_method']);
    }

    public function test_export_lists_the_primary_category_before_the_others(): void
    {
        $primary = ServiceCategory::factory()->create(['slug' => 'residential']);
        $extra = ServiceCategory::factory()->create(['slug' => 'commercial']);
        $service = Service::factory()->for($primary, 'category')->create(['slug' => 'window-cleaning']);
        $service->syncCategories([$primary->id, $extra->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.services.export', ['format' => 'csv']));

        $rows = $this->serviceSheet($response->streamedContent(), 'csv');
        $this->assertSame('residential|commercial', $rows[0]['category_slug']);
    }

    public function test_service_export_prefixes_formula_like_names(): void
    {
        Service::factory()->create(['name' => '=1+1', 'slug' => 'formula']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.services.export', ['format' => 'csv']));

        $this->assertStringContainsString("'=1+1", $response->streamedContent());
    }

    public function test_customers_cannot_export_or_import_services(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.services.export', ['format' => 'csv']))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.services.import'), [
                'file' => UploadedFile::fake()->createWithContent('services.csv', "name\n"),
            ])
            ->assertForbidden();
    }

    public function test_import_creates_and_updates_services_by_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create(['slug' => 'residential']);
        Service::factory()->for($category, 'category')->create([
            'name' => 'Old name',
            'slug' => 'window-cleaning',
            'short_description' => 'Old summary',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.services.import'), [
                'file' => $this->serviceCsv([
                    $this->serviceRow('window-cleaning', 'residential', 'fixed', 'Interior glass|Frames'),
                    $this->serviceRow('oven-cleaning', 'residential', 'custom_quote'),
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Imported 1 new services and updated 1.');

        $updated = Service::query()->where('slug', 'window-cleaning')->sole();
        $created = Service::query()->where('slug', 'oven-cleaning')->sole();
        $this->assertSame('Window Cleaning', $updated->name);
        $this->assertSame(['Interior glass', 'Frames'], $updated->inclusions);
        $this->assertSame(15000, $updated->price_cents);
        $this->assertSame($category->id, $created->service_category_id);
    }

    public function test_import_assigns_every_pipe_separated_category(): void
    {
        $residential = ServiceCategory::factory()->create(['slug' => 'residential']);
        $commercial = ServiceCategory::factory()->create(['slug' => 'commercial']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.import'), [
                'file' => $this->serviceCsv([
                    $this->serviceRow('shared-cleaning', 'commercial|residential', 'custom_quote'),
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Imported 1 new services and updated 0.');

        $service = Service::query()->where('slug', 'shared-cleaning')->sole();
        $this->assertSame($commercial->id, $service->service_category_id);
        $this->assertEqualsCanonicalizing([$commercial->id, $residential->id], $service->categories->pluck('id')->all());
    }

    public function test_reimporting_a_service_export_changes_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create(['slug' => 'residential']);
        $service = Service::factory()->for($category, 'category')->fixed(14999)->create([
            'name' => 'Window Cleaning',
            'slug' => 'window-cleaning',
            'short_description' => 'Streak-free glass.',
            'inclusions' => ['Interior glass'],
            'exclusions' => [],
            'chat_script' => ['greeting' => 'Keep me'],
        ]);
        $stamp = $service->fresh()->updated_at;

        $export = $this->actingAs($admin)->get(route('admin.services.export', ['format' => 'csv']));

        $this->actingAs($admin)
            ->post(route('admin.services.import'), [
                'file' => UploadedFile::fake()->createWithContent('services.csv', $export->streamedContent()),
            ])
            ->assertSessionHas('status', 'Imported 0 new services and updated 0.');

        $service->refresh();
        $this->assertTrue($service->updated_at->equalTo($stamp));
        $this->assertSame('Keep me', $service->chat_script['greeting']);
        $this->assertSame(1, Service::query()->count());
    }

    public function test_unknown_category_or_pricing_method_imports_nothing(): void
    {
        ServiceCategory::factory()->create(['slug' => 'residential']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.import'), [
                'file' => $this->serviceCsv([
                    $this->serviceRow('window-cleaning', 'residential', 'fixed'),
                    $this->serviceRow('oven-cleaning', 'missing', 'nope'),
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertSame(0, Service::query()->count());
    }

    public function test_import_restores_a_deleted_service_without_replacing_its_chat_script(): void
    {
        $category = ServiceCategory::factory()->create(['slug' => 'residential']);
        $service = Service::factory()->for($category, 'category')->create([
            'slug' => 'window-cleaning',
            'name' => 'Window Cleaning',
            'short_description' => 'A short description',
            'chat_script' => ['greeting' => 'Keep me'],
        ]);
        $service->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.import'), [
                'file' => $this->serviceCsv([
                    $this->serviceRow('window-cleaning', 'residential', 'custom_quote'),
                ]),
            ])
            ->assertSessionHas('status', 'Imported 0 new services and updated 1.');

        $restored = Service::query()->where('slug', 'window-cleaning')->sole();
        $this->assertSame('Keep me', $restored->chat_script['greeting']);
        $this->assertNull($restored->deleted_at);
    }

    public function test_import_rejects_more_than_2000_rows(): void
    {
        $rows = [];

        foreach (range(1, 2001) as $index) {
            $rows[] = $this->serviceRow("service-{$index}", 'residential', 'custom_quote');
        }

        ServiceCategory::factory()->create(['slug' => 'residential']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.import'), [
                'file' => $this->serviceCsv($rows),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Service::query()->count());
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function serviceCsv(array $rows): UploadedFile
    {
        $headers = [
            'name', 'slug', 'category_slug', 'short_description', 'description', 'inclusions', 'exclusions',
            'instructions', 'pricing_method', 'price', 'hourly_rate', 'estimated_minutes', 'allows_recurring',
            'seo_title', 'seo_description', 'is_active', 'sort_order',
        ];
        $lines = [implode(',', $headers)];

        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn (string $cell): string => '"'.str_replace('"', '""', $cell).'"', $row));
        }

        return UploadedFile::fake()->createWithContent('services.csv', implode("\n", $lines)."\n");
    }

    /**
     * @return list<string>
     */
    private function serviceRow(string $slug, string $categorySlug, string $pricingMethod, string $inclusions = ''): array
    {
        return [
            ucwords(str_replace('-', ' ', $slug)), $slug, $categorySlug, 'A short description', '', $inclusions, '', '',
            $pricingMethod, $pricingMethod === 'fixed' ? '150.00' : '', '', '', '0', '', '', '1', '0',
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function exportFormats(): array
    {
        return [
            'csv' => ['csv'],
            'xlsx' => ['xlsx'],
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function serviceSheet(string $contents, string $format): array
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'scrubit-services-'.uniqid().'.'.$format;
        file_put_contents($path, $contents);

        try {
            if ($format === 'csv') {
                $lines = array_values(array_filter(preg_split('/\r\n|\n|\r/', $contents) ?: []));
                $headers = str_getcsv(preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]) ?? $lines[0]);
                $parsed = [];

                foreach (array_slice($lines, 1) as $line) {
                    $parsed[] = array_combine($headers, str_getcsv($line));
                }

                return $parsed;
            }

            $reader = new XlsxReader;
            $reader->open($path);
            $parsed = [];
            $headers = null;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = array_map(fn (mixed $value): string => trim((string) $value), $row->toArray());

                    if ($headers === null) {
                        $headers = $values;

                        continue;
                    }

                    $parsed[] = array_combine($headers, $values);
                }

                break;
            }

            $reader->close();

            return $parsed;
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_customers_cannot_create_services(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.services.store'), $this->payload())->assertForbidden();

        $this->assertDatabaseMissing(Service::class, ['name' => 'Window Cleaning']);
    }

    public function test_service_description_markdown_does_not_render_raw_html(): void
    {
        $service = Service::factory()->create(['description' => 'Hello <script>alert(1)</script> **bold**']);

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<strong>bold</strong>', false);
    }
}
