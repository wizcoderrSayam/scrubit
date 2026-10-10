<?php

namespace Tests\Feature\Providers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gate_allows_administrators(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->can('admin'));
    }

    public function test_admin_gate_denies_customers(): void
    {
        $this->assertFalse(User::factory()->create()->can('admin'));
    }
}
