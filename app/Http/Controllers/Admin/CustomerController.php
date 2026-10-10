<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        return view('admin.customers.index', [
            'customers' => User::query()
                ->customers()
                ->withCount(['bookings' => fn ($query) => $query->submitted()])
                ->when($term !== '', fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")))
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'term' => $term,
        ]);
    }

    public function show(User $customer): View
    {
        abort_if($customer->isAdmin(), 404);

        return view('admin.customers.show', [
            'customer' => $customer->load('addresses'),
            'bookings' => $customer->bookings()->submitted()->with('service')->latest('submitted_at')->paginate(15),
        ]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        abort_if($customer->isAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]{7,30}$/'],
        ]);

        if (strtolower($validated['email']) !== strtolower($customer->email)) {
            $customer->forceFill(['email_verified_at' => null]);
        }

        $customer->fill($validated)->save();

        return to_route('admin.customers.show', $customer)->with('status', 'Customer details saved.');
    }
}
