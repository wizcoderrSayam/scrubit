@php
    use App\Support\Money;

    $headline = [
        ['label' => 'Services', 'value' => $metrics['services'], 'href' => route('admin.services.index'), 'tone' => 1],
        ['label' => 'Bookings', 'value' => $metrics['total'], 'href' => route('admin.bookings.index'), 'tone' => 2],
        ['label' => 'Pending quotes', 'value' => $metrics['pendingQuotes'], 'href' => route('admin.quotes.index'), 'tone' => 3],
        ['label' => 'Customers', 'value' => $metrics['customers'], 'href' => route('admin.customers.index'), 'tone' => 4],
        ['label' => 'Revenue', 'value' => Money::format($metrics['revenueCents'], $currency), 'href' => route('admin.payments.index'), 'tone' => 6],
    ];
    $statuses = [
        ["Today's visits", $metrics['today'], route('admin.bookings.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()])],
        ['Upcoming confirmed', $metrics['upcoming'], route('admin.bookings.index', ['status' => 'confirmed', 'from' => today()->toDateString()])],
        ['Awaiting payment', $metrics['awaitingPayment'], route('admin.bookings.index', ['status' => 'awaiting_quote_payment'])],
        ['Confirmed / in progress', $metrics['confirmed'], route('admin.bookings.index', ['status' => 'confirmed'])],
        ['Completed', $metrics['completed'], route('admin.bookings.index', ['status' => 'completed'])],
        ['Cancelled', $metrics['cancelled'], route('admin.bookings.index', ['status' => 'cancelled'])],
    ];
@endphp

<x-layouts.admin title="Dashboard" subtitle="Business overview">
    @if ($metrics['paymentsNeedingAttention'] || $metrics['cancellationRequests'] || $metrics['openEnquiries'])
        <div class="mb-6 flex flex-wrap gap-3 text-sm font-medium">
            @if ($metrics['paymentsNeedingAttention'])
                <a href="{{ route('admin.payments.index', ['attention' => 1]) }}" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-red-800 hover:bg-red-100">{{ $metrics['paymentsNeedingAttention'] }} payment(s) need attention</a>
            @endif
            @if ($metrics['cancellationRequests'])
                <a href="{{ route('admin.bookings.index', ['status' => 'cancellation_requested']) }}" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-amber-900 hover:bg-amber-100">{{ $metrics['cancellationRequests'] }} cancellation request(s)</a>
            @endif
            @if ($metrics['openEnquiries'])
                <a href="{{ route('admin.enquiries.index') }}" class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-2.5 text-teal-900 hover:bg-teal-100">{{ $metrics['openEnquiries'] }} open enquiry(ies)</a>
            @endif
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($headline as $card)
            <x-admin.stat-card :label="$card['label']" :value="is_int($card['value']) ? number_format($card['value']) : $card['value']" :href="$card['href']" :tone="$card['tone']" />
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($statuses as [$label, $value, $href])
            <a href="{{ $href }}" class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:border-slate-300">
                <p class="text-xs text-ink-500">{{ $label }}</p>
                <p class="mt-1 text-lg font-semibold text-ink-900">{{ number_format($value) }}</p>
            </a>
        @endforeach
    </div>

    <x-admin.table title="Recent bookings" :action-href="route('admin.bookings.index')" class="mt-8">
        <x-slot:head>
            <th scope="col" class="px-5 py-3 font-medium">Booking</th>
            <th scope="col" class="px-5 py-3 font-medium">Customer</th>
            <th scope="col" class="px-5 py-3 font-medium">Service</th>
            <th scope="col" class="px-5 py-3 font-medium">Total</th>
            <th scope="col" class="px-5 py-3 font-medium">Status</th>
        </x-slot:head>
        @forelse ($recentBookings as $booking)
            <tr>
                <td class="px-5 py-3.5"><a href="{{ route('admin.bookings.show', $booking) }}" class="text-admin-accent hover:underline">{{ $booking->reference }}</a></td>
                <td class="px-5 py-3.5 text-ink-900">{{ $booking->contact_name }}</td>
                <td class="px-5 py-3.5 text-ink-700">{{ $booking->service_name }}</td>
                <td class="px-5 py-3.5 text-ink-900">{{ $booking->quote_required && $booking->total_cents === 0 ? 'Quote pending' : Money::format($booking->total_cents, $booking->currency) }}</td>
                <td class="px-5 py-3.5 text-ink-900">{{ $booking->status->label() }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-8 text-center text-ink-500">No bookings yet.</td></tr>
        @endforelse
    </x-admin.table>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <x-admin.table title="Recent payment activity" :action-href="route('admin.payments.index')" class="xl:col-span-2">
            <x-slot:head>
                <th scope="col" class="px-5 py-3 font-medium">Booking</th>
                <th scope="col" class="px-5 py-3 font-medium">Amount</th>
                <th scope="col" class="px-5 py-3 font-medium">When</th>
                <th scope="col" class="px-5 py-3 font-medium">Status</th>
            </x-slot:head>
            @forelse ($recentPayments as $payment)
                <tr>
                    <td class="px-5 py-3.5"><a href="{{ route('admin.bookings.show', $payment->booking) }}" class="text-admin-accent hover:underline">{{ $payment->booking->reference }}</a></td>
                    <td class="px-5 py-3.5 text-ink-900">{{ Money::format($payment->amount_cents, $payment->currency) }}</td>
                    <td class="px-5 py-3.5 text-ink-700">{{ $payment->created_at->diffForHumans() }}</td>
                    <td class="px-5 py-3.5 text-ink-900">{{ $payment->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">No payments yet.</td></tr>
            @endforelse
        </x-admin.table>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-base font-semibold text-ink-900">Add-on pricing note</h2>
            <p class="mt-1 text-xs text-ink-500">Shown to customers on the booking chat</p>
            <p class="mt-4 font-admin-display text-xl font-bold text-admin-accent">{{ $addonNote }}</p>
            <a href="{{ route('admin.settings.business.edit') }}" class="mt-4 inline-block text-sm font-medium text-admin-accent hover:underline">Edit in settings</a>
        </section>
    </div>
</x-layouts.admin>
