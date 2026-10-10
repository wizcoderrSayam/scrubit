<x-layouts.admin :title="$customer->name">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Booking history">
                @if ($bookings->isEmpty())
                    <p class="text-sm text-ink-500">No bookings yet.</p>
                @else
                    <ul class="divide-y divide-slate-100 text-sm">
                        @foreach ($bookings as $booking)
                            <li class="flex flex-wrap items-center gap-3 py-3">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="font-medium text-admin-accent hover:underline">{{ $booking->reference }}</a>
                                <span>{{ $booking->service_name }} · {{ $booking->preferred_date?->format('M j, Y') }}</span>
                                <x-ui.badge :status="$booking->status" class="ml-auto" />
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4">{{ $bookings->links() }}</div>
                @endif
            </x-ui.card>

            <x-ui.card title="Saved addresses">
                @forelse ($customer->addresses as $address)
                    <p class="text-sm">{{ $address->label ?: 'Address' }}{{ $address->is_default ? ' (default)' : '' }}: {{ $address->oneLine() }}</p>
                @empty
                    <p class="text-sm text-ink-500">No saved addresses.</p>
                @endforelse
            </x-ui.card>
        </div>

        <x-ui.card title="Contact details">
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-ui.input name="name" label="Name" :value="$customer->name" required />
                <x-ui.input name="email" label="Email" type="email" :value="$customer->email" required hint="Changing the email requires the customer to verify it again." />
                <x-ui.input name="phone" label="Phone" :value="$customer->phone" />
                <p class="text-xs text-ink-500">Joined {{ $customer->created_at->format('M j, Y') }} · {{ $customer->email_verified_at ? 'Email verified' : 'Email not verified' }}</p>
                <x-ui.button class="w-full">Save customer</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.admin>
