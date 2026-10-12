@php
    $editLink = fn (string $step) => route('booking.customize', ['booking' => $booking, 'edit' => $step]);
    $customerMessages = $booking->conversation->where('sender.value', 'customer')->where('type.value', 'text');
@endphp

<x-layouts.booking title="Review your booking" :step="3">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div>
                <h1 class="text-2xl font-extrabold">Review your booking</h1>
                <p class="mt-1 text-ink-700">Check everything below. You can edit any section before continuing.</p>
            </div>

            <x-ui.card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $booking->service->name }}</h2>
                        <p class="mt-1 text-sm text-ink-700">{{ $booking->service->short_description }}</p>
                    </div>
                    <a href="{{ route('services.index') }}" class="shrink-0 text-sm font-medium text-brand-700 hover:text-brand-900">Change service</a>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">Your requirements</h2>
                    <a href="{{ route('booking.customize', $booking) }}" class="text-sm font-medium text-brand-700 hover:text-brand-900">Edit</a>
                </div>
                <ul class="mt-4 space-y-3">
                    @foreach ($customerMessages as $message)
                        <li class="rounded-xl bg-brand-50 px-4 py-3 text-sm whitespace-pre-line">{{ $message->body }}</li>
                    @endforeach
                </ul>
            </x-ui.card>

            <div class="grid gap-6 md:grid-cols-2">
                <x-ui.card>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-semibold">Preferred visit</h2>
                        <a href="{{ $editLink('schedule') }}" class="text-sm font-medium text-brand-700 hover:text-brand-900">Edit</a>
                    </div>
                    <p class="mt-3 text-sm">{{ $booking->preferred_date->format('l, F j, Y') }}<br>{{ $booking->time_window->label() }}</p>
                    @if ($booking->frequency)
                        <p class="mt-2 text-sm">Frequency: <strong>{{ $booking->frequency->label() }}</strong></p>
                    @endif
                    <p class="mt-3 text-xs text-ink-500">We will confirm the exact arrival time with you.</p>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-semibold">Address &amp; contact</h2>
                        <a href="{{ $editLink('details') }}" class="text-sm font-medium text-brand-700 hover:text-brand-900">Edit</a>
                    </div>
                    <p class="mt-3 text-sm">{{ $booking->address->oneLine() }}</p>
                    @if ($booking->address->notes)
                        <p class="mt-1 text-xs text-ink-500">{{ $booking->address->notes }}</p>
                    @endif
                    <p class="mt-3 border-t border-brand-100 pt-3 text-sm">{{ $booking->contact_name }}<br>{{ $booking->contact_email }}<br>{{ $booking->contact_phone }}</p>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-semibold">Add-ons &amp; extra requests</h2>
                        <a href="{{ $editLink('extras') }}" class="text-sm font-medium text-brand-700 hover:text-brand-900">Edit</a>
                    </div>
                    @if ($booking->addons->isEmpty())
                        <p class="mt-3 text-sm text-ink-700">None</p>
                    @else
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach ($booking->addons as $addon)
                                <li>
                                    <span class="font-medium">{{ $addon->name }}</span>
                                    @if ($addon->needs_quote)<span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-900">To be quoted</span>@endif
                                    @if ($addon->source->value === 'chat_flag')<span class="block text-xs text-ink-500">{{ $addon->description }}</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            </div>
        </div>

        <aside class="lg:sticky lg:top-24 lg:self-start">
            <x-ui.card title="Price summary">
                <x-booking.price-summary :price="$price" />

                <form method="POST" action="{{ route('booking.submit', $booking) }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="flex items-start gap-2.5 text-sm">
                            <input type="checkbox" name="accept_terms" value="1" class="mt-0.5 size-4 rounded border-line text-brand-600" @checked(old('accept_terms')) required>
                            <span>I agree to the <a href="{{ route('pages.show', 'terms') }}" target="_blank" class="font-medium text-brand-700 underline">Terms and Conditions</a>,
                                <a href="{{ route('pages.show', 'privacy') }}" target="_blank" class="font-medium text-brand-700 underline">Privacy Policy</a> and
                                <a href="{{ route('pages.show', 'cancellation-policy') }}" target="_blank" class="font-medium text-brand-700 underline">Cancellation Policy</a>.</span>
                        </label>
                        @error('accept_terms')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-ui.button class="w-full" size="lg">{{ $price->quoteRequired ? 'Submit for quote' : 'Continue to payment' }}</x-ui.button>
                    <p class="text-center text-xs text-ink-500">{{ $price->quoteRequired ? 'No payment is taken now.' : 'You will review the amount once more before paying.' }}</p>
                </form>
            </x-ui.card>
        </aside>
    </div>
</x-layouts.booking>
