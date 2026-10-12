@php($currency = $quote->currency ?? $booking->currency)

<x-layouts.booking title="Payment" :step="4">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold">Secure payment</h1>
            <p class="mt-1 text-ink-700">Booking <span class="font-semibold">{{ $booking->reference }}</span> · {{ $booking->service_name }}</p>
        </div>

        @if ($lastFailed)
            <x-ui.alert type="error">Your last payment attempt did not go through{{ $lastFailed->failure_reason ? ': '.$lastFailed->failure_reason : '' }}. You can try again below.</x-ui.alert>
        @endif

        <x-ui.card>
            @if ($quote)
                <h2 class="font-semibold">Your approved quote</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $quote->description }}</p>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ \App\Support\Money::format($quote->subtotal_cents, $currency) }}</dd></div>
                    @foreach ((array) $quote->tax_breakdown as $tax)
                        <div class="flex justify-between"><dt>{{ $tax['name'] }}</dt><dd>{{ \App\Support\Money::format($tax['amount_cents'], $currency) }}</dd></div>
                    @endforeach
                    <div class="flex justify-between border-t border-brand-100 pt-2 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Money::format($quote->total_cents, $currency) }}</dd></div>
                </dl>
                <p class="mt-3 text-xs text-ink-500">Quote valid until {{ $quote->expires_at->format('F j, Y') }}.</p>
            @else
                <h2 class="font-semibold">Amount due</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ \App\Support\Money::format($booking->subtotal_cents, $currency) }}</dd></div>
                    @foreach ((array) $booking->tax_breakdown as $tax)
                        <div class="flex justify-between"><dt>{{ $tax['name'] }}</dt><dd>{{ \App\Support\Money::format($tax['amount_cents'], $currency) }}</dd></div>
                    @endforeach
                    <div class="flex justify-between border-t border-brand-100 pt-2 text-base font-semibold"><dt>Total</dt><dd>{{ \App\Support\Money::format($booking->total_cents, $currency) }}</dd></div>
                </dl>
            @endif

            @if ($amount !== null && $canAct)
                <form method="POST" action="{{ $quote ? route('bookings.quote.accept', $booking) : route('checkout.start', $booking) }}" class="mt-6">
                    @csrf
                    <x-ui.button size="lg" class="w-full">{{ $quote ? 'Accept quote & pay '.\App\Support\Money::format($amount, $currency) : 'Pay '.\App\Support\Money::format($amount, $currency) }}</x-ui.button>
                </form>
                <p class="mt-3 text-center text-xs text-ink-500">You will be redirected to our secure payment page. We never see or store your card details.</p>
            @elseif ($amount === null)
                <x-ui.alert type="info" class="mt-6">This quote is no longer payable. <a href="{{ route('bookings.show', $booking) }}" class="font-semibold underline">View your booking</a> to request a new quote.</x-ui.alert>
            @endif
        </x-ui.card>

        <p class="text-center text-sm"><a href="{{ route('bookings.show', $booking) }}" class="text-brand-700 hover:text-brand-900">View booking details</a></p>
    </div>
</x-layouts.booking>
