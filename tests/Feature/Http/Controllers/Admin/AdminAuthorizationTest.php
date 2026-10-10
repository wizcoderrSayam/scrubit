<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every administration page must reject guests and customers.
 */
class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function adminPages(): array
    {
        return collect([
            'admin.dashboard', 'admin.services.index', 'admin.services.create', 'admin.categories.index', 'admin.categories.create',
            'admin.import-export.index',
            'admin.addons.index', 'admin.addons.create', 'admin.bookings.index', 'admin.quotes.index', 'admin.payments.index',
            'admin.customers.index', 'admin.enquiries.index', 'admin.content.home.edit', 'admin.pages.index', 'admin.faqs.index',
            'admin.faqs.create', 'admin.testimonials.index', 'admin.testimonials.create', 'admin.email-templates.index',
            'admin.settings.business.edit', 'admin.settings.tax.index', 'admin.settings.availability.edit', 'admin.settings.payments',
        ])->mapWithKeys(fn (string $name) => [$name => [$name]])->all();
    }

    #[DataProvider('adminPages')]
    public function test_admin_page_redirects_guests_forbids_customers_and_allows_admins(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route($routeName))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route($routeName))->assertOk();
    }

    public function test_every_admin_route_is_protected_by_the_admin_gate(): void
    {
        $unprotected = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'admin'))
            ->reject(fn ($route) => in_array('can:admin', $route->gatherMiddleware(), true))
            ->map(fn ($route) => $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $unprotected);
    }
}
