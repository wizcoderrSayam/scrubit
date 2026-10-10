<x-layouts.admin title="Bookings">
    <form method="GET" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <label for="q" class="block text-xs font-medium text-ink-500">Search</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Reference, name, email, phone" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label for="status" class="block text-xs font-medium text-ink-500">Status</label>
            <select id="status" name="status" class="mt-1 block w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="service" class="block text-xs font-medium text-ink-500">Service</label>
            <select id="service" name="service" class="mt-1 block w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <option value="">All services</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected((string) ($filters['service'] ?? '') === (string) $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="from" class="block text-xs font-medium text-ink-500">Visit from</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label for="to" class="block text-xs font-medium text-ink-500">Visit to</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div class="flex gap-2 lg:col-span-6">
            <x-ui.button size="sm">Filter</x-ui.button>
            <x-ui.button :href="route('admin.bookings.index')" variant="ghost" size="sm">Reset</x-ui.button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-sm font-medium text-ink-500">
                <tr>
                    <th class="px-4 py-3">Reference</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Visit</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($bookings as $booking)
                    @php
                        $amount = $booking->successfulPayment?->amount_cents ?? $booking->currentQuote?->total_cents ?? ($booking->quote_required ? null : $booking->total_cents);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3"><a href="{{ route('admin.bookings.show', $booking) }}" class="font-semibold text-admin-accent hover:underline">{{ $booking->reference }}</a></td>
                        <td class="px-4 py-3">{{ $booking->contact_name }}<span class="block text-xs text-ink-500">{{ $booking->contact_email }}</span></td>
                        <td class="px-4 py-3">{{ $booking->service_name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $booking->preferred_date?->format('M j, Y') }}<span class="block text-xs text-ink-500">{{ $booking->time_window?->label() }}</span></td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $amount !== null ? \App\Support\Money::format($amount, $booking->currency) : 'Quote pending' }}</td>
                        <td class="px-4 py-3">@if ($booking->successfulPayment)<x-ui.badge :status="$booking->successfulPayment->status" />@else<span class="text-xs text-ink-500">Unpaid</span>@endif</td>
                        <td class="px-4 py-3"><x-ui.badge :status="$booking->status" /></td>
                        <td class="px-4 py-3 whitespace-nowrap text-ink-500">{{ $booking->submitted_at?->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-ink-500">No bookings match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</x-layouts.admin>
