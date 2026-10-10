@php
    use App\Enums\BookingStatus;
    $status = $booking->status;
    $phone = $business['business.phone'];
    $phoneHref = 'tel:'.preg_replace('/[^0-9+]/', '', $phone);
    [$heading, $intro, $nextSteps] = match ($status) {
        BookingStatus::PendingQuote => [
            'Request received: quote pending',
            'Thank you! Your booking request is not confirmed yet. Our team is reviewing your requirements and will send you a quote.',
            ['We review your requirements, usually within one business day.', 'You receive an email with your quote.', 'Approve the quote and pay online to confirm your booking.'],
        ],
        BookingStatus::AwaitingQuotePayment => [
            'Your quote is ready',
            'Review the quoted price below. Your booking is confirmed once you approve it and payment is received.',
            ['Review the quote.', 'Approve and pay securely online.', 'We confirm your arrival time.'],
        ],
        BookingStatus::AwaitingPayment => [
            'Awaiting payment',
            'Your booking is ready for payment. It will be confirmed as soon as payment is received.',
            ['Complete payment.', 'We confirm your arrival time.'],
        ],
        BookingStatus::Confirmed, BookingStatus::InProgress => [
            'Booking confirmed',
            'Your payment has been received and your booking is confirmed.',
            ['We will contact you to confirm the exact arrival time.', 'Make sure we can access the property on the day.', 'Relax while we clean.'],
        ],
        BookingStatus::CancellationRequested => [
            'Cancellation requested',
            'We have received your cancellation request and will confirm it shortly.',
            ['Our team reviews the request according to the cancellation policy.', 'You receive an email once it is processed.'],
        ],
        BookingStatus::Completed => ['Booking completed', 'Thank you for choosing us!', []],
        default => ['Booking cancelled', 'This booking has been cancelled.', []],
    };
@endphp

<x-layouts.booking title="Booking {{ $booking->reference }}" :step="$status === BookingStatus::PendingQuote || $status->isPayable() ? 4 : 5">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-ink-500">Booking reference</p>
                        <p class="font-display text-2xl font-bold tracking-wide">{{ $booking->reference }}</p>
                    </div>
                    <x-ui.badge :status="$status" class="text-sm" />
                </div>
                <h1 class="mt-5 text-2xl font-semibold">{{ $heading }}</h1>
                <p class="mt-2 text-ink-700">{{ $intro }}</p>

                @if ($nextSteps)
                    <h2 class="mt-6 text-sm font-semibold uppercase tracking-wide text-ink-500">What happens next</h2>
                    <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm text-ink-700">
                        @foreach ($nextSteps as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                @endif

                @if ($status === BookingStatus::AwaitingQuotePayment && $quote)
                    <div class="mt-6 rounded-xl border border-brand-200 bg-brand-50 p-5">
                        <div class="flex flex-wrap items-baseline justify-between gap-3">
                            <h2 class="font-semibold">Quoted price</h2>
                            <p class="text-2xl font-bold">{{ \App\Support\Money::format($quote->total_cents, $quote->currency) }}</p>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $quote->description }}</p>
                        <p class="mt-2 text-xs text-ink-500">Includes tax · Valid until {{ $quote->expires_at->format('F j, Y') }}</p>
                        @if ($canAct)
                            @if ($quote->isExpired())
                                <form method="POST" action="{{ route('bookings.quote.requote', $booking) }}" class="mt-4">
                                    @csrf
                                    <x-ui.button>Request a new quote</x-ui.button>
                                </form>
                            @else
                                <x-ui.button :href="route('checkout.show', $booking)" class="mt-4">Review &amp; pay</x-ui.button>
                            @endif
                        @endif
                    </div>
                @elseif ($status === BookingStatus::AwaitingPayment && $canAct)
                    <x-ui.button :href="route('checkout.show', $booking)" class="mt-6">Pay now</x-ui.button>
                @endif
            </x-ui.card>

            @include('bookings.partials.details')
        </div>

        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            <x-ui.card title="Need help?">
                <p class="text-sm text-ink-700">Quote your reference <strong>{{ $booking->reference }}</strong> when you contact us.</p>
                <p class="mt-3 text-sm"><a href="{{ $phoneHref }}" class="font-semibold text-brand-800">{{ $phone }}</a><br>
                    <a href="mailto:{{ $business['business.email'] }}" class="text-brand-700">{{ $business['business.email'] }}</a></p>
            </x-ui.card>

            <x-ui.card>
                <div class="space-y-3">
                    <x-ui.button :href="route('bookings.print', $booking)" variant="secondary" class="w-full" target="_blank">Printable summary</x-ui.button>
                    @auth
                        <x-ui.button :href="route('account.bookings')" variant="ghost" class="w-full">All my bookings</x-ui.button>
                    @else
                        <p class="text-xs text-ink-500">Bookmark this page or use the link in your email to return to this booking.</p>
                    @endauth
                </div>
            </x-ui.card>

            @if ($canAct && $canCancel)
                <x-ui.card title="{{ $status === BookingStatus::Confirmed ? 'Request cancellation' : 'Cancel booking' }}">
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" class="space-y-3"
                        x-data x-on:submit="if (! confirm('Are you sure you want to cancel this booking?')) $event.preventDefault()">
                        @csrf
                        <x-ui.textarea name="reason" label="Reason (optional)" rows="2" />
                        <p class="text-xs text-ink-500">See our <a href="{{ route('pages.show', 'cancellation-policy') }}" class="underline">cancellation policy</a>.</p>
                        <x-ui.button variant="danger" class="w-full">{{ $status === BookingStatus::Confirmed ? 'Request cancellation' : 'Cancel booking' }}</x-ui.button>
                    </form>
                </x-ui.card>
            @endif
        </aside>
    </div>
</x-layouts.booking>
