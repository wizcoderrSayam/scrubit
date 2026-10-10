<x-layouts.public title="My bookings" :noindex="true">
    <div class="mx-auto max-w-4xl space-y-8 px-4 py-10 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold">My bookings</h1>
                <p class="mt-1 text-ink-500">Track quotes, payments and upcoming visits.</p>
            </div>
            <x-ui.button :href="route('services.index')">New booking</x-ui.button>
        </div>

        <x-account.nav />

        @if ($drafts->isNotEmpty())
            <x-ui.card title="Unfinished bookings">
                <ul class="divide-y divide-brand-100">
                    @foreach ($drafts as $draft)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                            <span><span class="font-medium">{{ $draft->service_name }}</span> · started {{ $draft->created_at->diffForHumans() }}</span>
                            <a href="{{ route('booking.customize', $draft) }}" class="font-semibold text-brand-700 hover:text-brand-900">Continue</a>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif

        @if ($bookings->isEmpty())
            <div class="rounded-2xl border border-dashed border-brand-200 p-10 text-center">
                <p class="font-semibold">You have no bookings yet.</p>
                <p class="mt-1 text-sm text-ink-700">Choose a service to get started.</p>
            </div>
        @else
            <ul class="space-y-4">
                @foreach ($bookings as $booking)
                    <li>
                        <a href="{{ route('bookings.show', $booking) }}" class="block rounded-2xl border border-brand-100 bg-white p-5 transition hover:border-brand-300">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ $booking->service_name }}</p>
                                    <p class="text-sm text-ink-500">{{ $booking->reference }} · {{ $booking->preferred_date?->format('M j, Y') }}</p>
                                </div>
                                <x-ui.badge :status="$booking->status" />
                            </div>
                            @if ($booking->currentQuote && $booking->status === \App\Enums\BookingStatus::AwaitingQuotePayment)
                                <p class="mt-3 text-sm text-brand-800">Quote ready: {{ \App\Support\Money::format($booking->currentQuote->total_cents, $booking->currentQuote->currency) }}. Review and pay</p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            {{ $bookings->links() }}
        @endif
    </div>
</x-layouts.public>
