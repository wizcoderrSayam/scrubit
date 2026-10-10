@php
    $currency = $booking->currency;
    $requirements = $booking->conversation->filter(fn ($message) => $message->sender->value === 'customer');
@endphp

<div class="grid gap-6 md:grid-cols-2">
    <x-ui.card title="Service">
        <p class="font-semibold">{{ $booking->service_name }}</p>
        @if ($booking->pricing_terms['description'] ?? null)
            <p class="mt-1 text-sm text-ink-700">{{ $booking->pricing_terms['description'] }}</p>
        @endif
        <p class="mt-4 text-sm"><span class="text-ink-500">Preferred visit:</span><br>{{ $booking->preferred_date?->format('l, F j, Y') }}, {{ $booking->time_window?->label() }}</p>
        @if ($booking->scheduled_at)
            <p class="mt-2 text-sm"><span class="text-ink-500">Confirmed arrival:</span> {{ $booking->scheduled_at->format('l, F j, Y g:i a') }}</p>
        @endif
        @if ($booking->frequency)
            <p class="mt-2 text-sm"><span class="text-ink-500">Frequency:</span> {{ $booking->frequency->label() }}</p>
        @endif
    </x-ui.card>

    <x-ui.card title="Contact & address">
        <p class="text-sm">{{ $booking->contact_name }}<br>{{ $booking->contact_email }}<br>{{ $booking->contact_phone }}</p>
        @if ($booking->address)
            <p class="mt-4 text-sm">{{ $booking->address->oneLine() }}</p>
        @endif
    </x-ui.card>
</div>

<x-ui.card title="Your requirements">
    <ul class="space-y-2">
        @forelse ($requirements as $message)
            <li class="rounded-xl bg-brand-50 px-4 py-2.5 text-sm whitespace-pre-line">{{ $message->body }}</li>
        @empty
            <li class="text-sm text-ink-700">No requirements recorded.</li>
        @endforelse
    </ul>
    @if ($booking->addons->isNotEmpty())
        <h3 class="mt-5 text-sm font-semibold">Add-ons &amp; extra requests</h3>
        <ul class="mt-2 space-y-1 text-sm">
            @foreach ($booking->addons as $addon)
                <li>{{ $addon->name }}{{ $addon->needs_quote ? ' (quoted)' : '' }}@if ($addon->source->value === 'chat_flag'): {{ $addon->description }}@endif</li>
            @endforeach
        </ul>
    @endif
</x-ui.card>

<x-ui.card title="Payment">
    @if ($booking->successfulPayment)
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt>Amount paid</dt><dd class="font-semibold">{{ \App\Support\Money::format($booking->successfulPayment->amount_cents, $booking->successfulPayment->currency) }}</dd></div>
            <div class="flex justify-between"><dt>Paid on</dt><dd>{{ $booking->successfulPayment->paid_at?->format('F j, Y') }}</dd></div>
            <div class="flex justify-between"><dt>Status</dt><dd><x-ui.badge :status="$booking->successfulPayment->status" /></dd></div>
            @if ($booking->successfulPayment->refunded_cents > 0)
                <div class="flex justify-between"><dt>Refunded</dt><dd>{{ \App\Support\Money::format($booking->successfulPayment->refunded_cents, $currency) }}</dd></div>
            @endif
        </dl>
    @elseif ($quote)
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt>Quoted total</dt><dd class="font-semibold">{{ \App\Support\Money::format($quote->total_cents, $quote->currency) }}</dd></div>
            <div class="flex justify-between"><dt>Quote status</dt><dd>{{ $quote->isExpired() ? 'Expired' : $quote->status->label() }}</dd></div>
            <div class="flex justify-between"><dt>Valid until</dt><dd>{{ $quote->expires_at->format('F j, Y') }}</dd></div>
        </dl>
    @elseif (! $booking->quote_required)
        <p class="text-sm">Amount due: <span class="font-semibold">{{ \App\Support\Money::format($booking->total_cents, $currency) }}</span></p>
    @else
        <p class="text-sm text-ink-700">No payment has been taken. Your quote will appear here once our team has reviewed your requirements.</p>
    @endif
</x-ui.card>
