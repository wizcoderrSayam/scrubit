<?php

namespace Tests\Feature\Support;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Uses a serializing cache store (like production's database store) to catch values
 * that cannot be restored when cache.serializable_classes is false.
 */
class CatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'file', 'cache.stores.file.path' => storage_path('framework/testing/cache')]);
        Cache::store('file')->flush();
    }

    protected function tearDown(): void
    {
        Cache::store('file')->flush();

        parent::tearDown();
    }

    public function test_cached_public_pages_render_on_repeat_requests(): void
    {
        $category = ServiceCategory::factory()->create(['name' => 'Residential']);
        Service::factory()->for($category, 'category')->create(['name' => 'Home Cleaning']);
        Testimonial::factory()->create(['author_name' => 'Rachel']);

        foreach (range(1, 2) as $request) {
            $this->get(route('home'))->assertOk()->assertSee('Home Cleaning')->assertSee('Rachel');
            $this->get(route('services.index'))->assertOk()->assertSee('Residential')->assertSee('Home Cleaning');
            $this->get(route('services.category', $category))->assertOk()->assertSee('Home Cleaning');
            $this->get(route('sitemap'))->assertOk();
        }
    }

    public function test_saving_a_service_invalidates_the_cached_catalog(): void
    {
        $service = Service::factory()->create(['name' => 'Old Name']);
        $this->get(route('home'))->assertSee('Old Name');

        $service->update(['name' => 'New Name']);

        $this->get(route('home'))->assertSee('New Name')->assertDontSee('Old Name');
    }
}
