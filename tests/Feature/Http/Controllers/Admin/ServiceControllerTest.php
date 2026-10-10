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
            'service_category_id' => ServiceCategory::factory()->create()->id,
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
