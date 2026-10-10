<?php

namespace Tests\Feature\Http\Controllers\Customer;

use App\Models\Booking;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_address_becomes_the_default(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post(route('account.addresses.store'), [
            'line1' => '10 Main St', 'city' => 'Vancouver', 'province' => 'BC', 'postal_code' => 'v6b1a1',
        ])->assertRedirect(route('account.addresses.index'));

        $address = $customer->addresses()->sole();
        $this->assertTrue($address->is_default);
        $this->assertSame('V6B1A1', $address->postal_code);
    }

    public function test_customers_cannot_change_other_customers_addresses(): void
    {
        $address = CustomerAddress::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('account.addresses.destroy', $address))
            ->assertNotFound();

        $this->assertModelExists($address);
    }

    public function test_invalid_postal_codes_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('account.addresses.store'), ['line1' => '1 A St', 'city' => 'X', 'province' => 'BC', 'postal_code' => '90210'])
            ->assertSessionHasErrors(['postal_code' => 'Enter a valid Canadian postal code, e.g. V6B 1A1.']);
    }

    public function test_booking_history_lists_only_the_customers_own_submitted_bookings(): void
    {
        $customer = User::factory()->create();
        $mine = Booking::factory()->submitted()->create(['user_id' => $customer->id]);
        $theirs = Booking::factory()->submitted()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($customer)->get(route('account.bookings'))
            ->assertOk()
            ->assertSee($mine->reference)
            ->assertDontSee($theirs->reference);
    }
}
