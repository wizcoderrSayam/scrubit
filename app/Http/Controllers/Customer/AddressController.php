<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SaveAddressRequest;
use App\Models\CustomerAddress;
use App\Support\Provinces;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses', [
            'addresses' => $request->user()->addresses()->get(),
            'provinces' => Provinces::ALL,
        ]);
    }

    public function store(SaveAddressRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            $makeDefault = $request->boolean('is_default') || ! $user->addresses()->exists();

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create([
                ...$request->safe()->except('is_default'),
                'postal_code' => strtoupper($request->validated('postal_code')),
                'is_default' => $makeDefault,
            ]);
        });

        return to_route('account.addresses.index')->with('status', 'Address saved.');
    }

    public function update(SaveAddressRequest $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        DB::transaction(function () use ($request, $address): void {
            if ($request->boolean('is_default')) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update([
                ...$request->safe()->except('is_default'),
                'postal_code' => strtoupper($request->validated('postal_code')),
                'is_default' => $request->boolean('is_default') || $address->is_default,
            ]);
        });

        return to_route('account.addresses.index')->with('status', 'Address updated.');
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $address->delete();

        if ($address->is_default) {
            $request->user()->addresses()->first()?->update(['is_default' => true]);
        }

        return to_route('account.addresses.index')->with('status', 'Address removed.');
    }

    private function authorizeOwner(Request $request, CustomerAddress $address): void
    {
        abort_unless($address->user_id === $request->user()->getKey(), 404);
    }
}
