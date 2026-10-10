@php
    $status = $payment->status;
    $isOpen = $status->isOpen();
    $isPaid = in_array($status, \App\Enums\PaymentStatus::collected(), true);
    $canRetry = $booking->status->isPayable() && ! $isOpen;
@endphp

<x-layouts.booking title="Payment status" :step="$isPaid ? 5 : 4">
    @if ($isOpen && ! $cancelled)
        <meta http-equiv="refresh" content="5">
    @endif

    <div class="mx-auto max-w-xl">
        <x-ui.card class="text-center">
            @if ($isPaid)
                <h1 class="text-2xl font-semibold">Payment received</h1>
                <p class="mt-2 text-ink-700">Thank you! Your payment has been confirmed.</p>
                <x-ui.button :href="route('bookings.show', $booking)" class="mt-6">View confirmation</x-ui.button>
            @elseif ($cancelled || $status === \App\Enums\PaymentStatus::Cancelled)
                <h1 class="text-2xl font-semibold">Payment cancelled</h1>
                <p class="mt-2 text-ink-700">No payment was taken. You can try again whenever you are ready.</p>
            @elseif ($status === \App\Enums\PaymentStatus::Failed)
                <h1 class="text-2xl font-semibold">Payment failed</h1>
                <p class="mt-2 text-ink-700">{{ $payment->failure_reason ?: 'Your payment could not be completed.' }} No money was taken.</p>
            @else
                <h1 class="text-2xl font-semibold">Confirming your payment…</h1>
                <p class="mt-2 text-ink-700">We are waiting for confirmation from the payment provider. This page refreshes automatically.</p>
                <div class="mx-auto mt-6 size-8 animate-spin rounded-full border-4 border-brand-100 border-t-brand-600" aria-hidden="true"></div>
            @endif

            @if ($canRetry)
                <form method="POST" action="{{ $booking->status === \App\Enums\BookingStatus::AwaitingQuotePayment ? route('bookings.quote.accept', $booking) : route('checkout.start', $booking) }}" class="mt-6">
                    @csrf
                    <x-ui.button>Try payment again</x-ui.button>
                </form>
            @endif

            <p class="mt-6 text-sm">
                <a href="{{ route('bookings.show', $booking) }}" class="text-brand-700 hover:text-brand-900">Booking {{ $booking->reference }}</a>
                · Need help? Call <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business['business.phone']) }}" class="text-brand-700">{{ $business['business.phone'] }}</a>
            </p>
        </x-ui.card>
    </div>
</x-layouts.booking>
