@php
    use App\Enums\BookingStatus;
    use App\Enums\PaymentStatus;
    use App\Support\Money;
    $currency = $booking->currency;
    $editable = ! $booking->status->isFinal();
@endphp

<x-layouts.admin :title="'Booking '.$booking->reference">
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-ui.badge :status="$booking->status" class="text-sm" />
        <span class="text-sm text-ink-500">Submitted {{ $booking->submitted_at?->format('M j, Y g:i a') }}</span>
        @if ($booking->repeatedFrom)
            <span class="text-sm text-ink-500">· Repeat of <a href="{{ route('admin.bookings.show', $booking->repeatedFrom) }}" class="text-admin-accent hover:underline">{{ $booking->repeatedFrom->reference }}</a></span>
        @endif
        <a href="{{ route('bookings.print', $booking) }}" target="_blank" class="ml-auto text-sm font-medium text-admin-accent hover:underline">Print summary</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Conversation & requirements">
                <div class="max-h-[28rem] space-y-3 overflow-y-auto rounded-xl bg-slate-50 p-4">
                    @foreach ($booking->messages as $message)
                        <div @class(['flex', 'justify-end' => $message->sender->value === 'customer'])>
                            <div @class([
                                'max-w-[85%] rounded-xl px-3 py-2 text-sm',
                                'bg-brand-600 text-white' => $message->sender->value === 'customer',
                                'bg-white text-ink-900' => $message->sender->value === 'assistant',
                                'bg-amber-50 text-amber-900' => $message->sender->value === 'system',
                                'border border-dashed border-amber-400 bg-amber-50 text-amber-900' => $message->type->value === 'internal_note',
                            ])>
                                @if ($message->type->value === 'internal_note')
                                    <p class="text-[11px] font-semibold uppercase">Internal note · {{ $message->user?->name }}</p>
                                @endif
                                <p class="whitespace-pre-line">{{ $message->body }}</p>
                                <p class="mt-1 text-[11px] opacity-70">{{ $message->created_at->format('M j, g:i a') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('admin.bookings.notes.store', $booking) }}" class="mt-4 flex items-end gap-2">
                    @csrf
                    <x-ui.textarea name="note" label="Add an internal note (not visible to the customer)" rows="2" class="flex-1" />
                    <x-ui.button variant="secondary">Add note</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card title="Pricing">
                <x-booking.price-summary :price="$price" />
                @if ($booking->addons->isNotEmpty())
                    <h3 class="mt-5 text-sm font-semibold">Requested add-ons &amp; extras</h3>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($booking->addons as $addon)
                            <li>{{ $addon->name }} @if ($addon->needs_quote)<span class="rounded bg-amber-100 px-1.5 text-xs text-amber-900">needs quote</span>@endif
                                @if ($addon->description && $addon->source->value === 'chat_flag')<span class="block text-xs text-ink-500">{{ $addon->description }}</span>@endif</li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            @if ($canSendQuote)
                <x-ui.card title="{{ $booking->quotes->isEmpty() ? 'Send quote' : 'Send a revised quote' }}" description="Enter the price before tax. Tax is calculated from the active tax rates. The customer receives an email to approve and pay.">
                    <form method="POST" action="{{ route('admin.bookings.quotes.store', $booking) }}" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <x-ui.input name="amount" label="Amount before tax" prefix="$" inputmode="decimal" :value="$suggestedQuoteAmount" required />
                        <x-ui.input name="expires_at" label="Valid until" type="date" :value="$defaultQuoteExpiry" required />
                        <x-ui.textarea name="description" label="Quote description (shown to the customer)" rows="4" required class="sm:col-span-2" />
                        <div class="sm:col-span-2"><x-ui.button>Send quote to customer</x-ui.button></div>
                    </form>
                </x-ui.card>
            @endif

            @if ($booking->quotes->isNotEmpty())
                <x-ui.card title="Quotes">
                    <ul class="divide-y divide-slate-100 text-sm">
                        @foreach ($booking->quotes as $quote)
                            <li class="py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold">{{ Money::format($quote->total_cents, $quote->currency) }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold">{{ $quote->isExpired() && $quote->status->isCurrent() ? 'Expired' : $quote->status->label() }}</span>
                                </div>
                                <p class="mt-1 whitespace-pre-line text-ink-700">{{ $quote->description }}</p>
                                <p class="mt-1 text-xs text-ink-500">Sent {{ $quote->sent_at?->format('M j, Y') }} by {{ $quote->creator?->name }} · expires {{ $quote->expires_at->format('M j, Y') }}{{ $quote->accepted_at ? ' · accepted '.$quote->accepted_at->format('M j, Y') : '' }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            <x-ui.card title="Payments">
                @if ($booking->payments->isEmpty())
                    <p class="text-sm text-ink-500">No payment attempts yet.</p>
                @else
                    <ul class="divide-y divide-slate-100 text-sm">
                        @foreach ($booking->payments as $payment)
                            <li class="space-y-2 py-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="font-semibold">{{ Money::format($payment->amount_cents, $payment->currency) }}</span>
                                    <x-ui.badge :status="$payment->status" />
                                    <span class="text-xs text-ink-500">{{ $payment->provider }} · {{ $payment->provider_ref }}</span>
                                    <span class="ml-auto text-xs text-ink-500">{{ $payment->created_at->format('M j, Y g:i a') }}</span>
                                </div>
                                @if ($payment->needs_attention)
                                    <div class="flex flex-wrap items-center gap-3 rounded-lg bg-red-50 px-3 py-2 text-red-800">
                                        <span>Needs attention: {{ $payment->meta['attention_reason'] ?? 'review required' }}</span>
                                        <form method="POST" action="{{ route('admin.payments.resolve', $payment) }}">@csrf<button class="text-xs font-semibold underline">Mark reviewed</button></form>
                                    </div>
                                @endif
                                @if ($payment->failure_reason && in_array($payment->status, [PaymentStatus::Failed, PaymentStatus::Cancelled], true))
                                    <p class="text-xs text-ink-500">{{ $payment->failure_reason }}</p>
                                @endif
                                @if ($payment->refundableCents() > 0)
                                    <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" class="flex flex-wrap items-end gap-2" x-data x-on:submit="if (! confirm('Issue this refund?')) $event.preventDefault()">
                                        @csrf
                                        <x-ui.input name="amount" label="Refund amount" prefix="$" inputmode="decimal" :value="Money::toDecimal($payment->refundableCents())" />
                                        <x-ui.button variant="secondary" size="sm">Refund</x-ui.button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Status history">
                <ul class="space-y-2 text-sm">
                    @foreach ($booking->statusHistory as $entry)
                        <li class="flex flex-wrap gap-2">
                            <span class="text-ink-500">{{ $entry->created_at->format('M j, Y g:i a') }}</span>
                            <span>{{ $entry->from_status?->label() ?? 'Created' }} → <strong>{{ $entry->to_status->label() }}</strong></span>
                            <span class="text-ink-500">{{ $entry->user?->name ?? 'System' }}{{ $entry->reason ? ' · '.$entry->reason : '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            @if ($transitions)
                <x-ui.card title="Update status">
                    <form method="POST" action="{{ route('admin.bookings.transition', $booking) }}" class="space-y-4" x-data="{ status: '{{ $transitions[0]->value }}' }">
                        @csrf
                        <div class="space-y-1.5">
                            <label for="status" class="block text-sm font-medium">New status</label>
                            <select id="status" name="status" x-model="status" class="block w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                                @foreach ($transitions as $transition)
                                    <option value="{{ $transition->value }}">{{ $transition === BookingStatus::Confirmed ? 'Confirmed (decline cancellation)' : $transition->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-ui.input name="reason" label="Reason / note" />
                        @if ($booking->successfulPayment && $booking->successfulPayment->refundableCents() > 0)
                            <div x-show="status === 'cancelled'">
                                <x-ui.input name="refund" label="Refund on cancellation (optional)" prefix="$" inputmode="decimal"
                                    :hint="'Up to '.Money::format($booking->successfulPayment->refundableCents(), $currency)" />
                            </div>
                        @endif
                        <x-ui.button class="w-full">Update status</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.card title="Customer">
                <p class="text-sm">{{ $booking->contact_name }}<br>{{ $booking->contact_email }}<br>{{ $booking->contact_phone }}</p>
                @if ($booking->user)
                    <a href="{{ route('admin.customers.show', $booking->user) }}" class="mt-2 inline-block text-sm text-admin-accent hover:underline">View customer account</a>
                @else
                    <p class="mt-2 text-xs text-ink-500">Guest booking</p>
                @endif
            </x-ui.card>

            @if ($editable)
                <x-ui.card title="Booking details">
                    <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <x-ui.input name="contact_name" label="Contact name" :value="$booking->contact_name" required />
                        <x-ui.input name="contact_email" label="Contact email" type="email" :value="$booking->contact_email" required />
                        <x-ui.input name="contact_phone" label="Contact phone" :value="$booking->contact_phone" required />
                        <x-ui.input name="preferred_date" label="Preferred date" type="date" :value="$booking->preferred_date?->toDateString()" required />
                        <x-ui.select name="time_window" label="Time window" :options="collect($windows)->mapWithKeys(fn ($w) => [$w->value => $w->label()])->all()" :value="$booking->time_window" required />
                        <x-ui.input name="scheduled_at" label="Confirmed arrival time" type="datetime-local" :value="$booking->scheduled_at?->format('Y-m-d\TH:i')" />
                        <x-ui.select name="frequency" label="Frequency" placeholder="Not recurring" :options="collect($frequencies)->mapWithKeys(fn ($f) => [$f->value => $f->label()])->all()" :value="$booking->frequency" />
                        <x-ui.input name="line1" label="Street address" :value="$booking->address?->line1" required />
                        <x-ui.input name="line2" label="Unit / suite" :value="$booking->address?->line2" />
                        <x-ui.input name="city" label="City" :value="$booking->address?->city" required />
                        <x-ui.select name="province" label="Province" :options="$provinces" :value="$booking->address?->province ?? 'BC'" required />
                        <x-ui.input name="postal_code" label="Postal code" :value="$booking->address?->postal_code" required />
                        <x-ui.input name="notes" label="Access notes" :value="$booking->address?->notes" />
                        <x-ui.button variant="secondary" class="w-full">Save details</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if (in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::InProgress, BookingStatus::Completed], true))
                <x-ui.card title="Repeat booking" description="Create the next visit for this customer. It starts in Pending quote so you can confirm the price.">
                    <form method="POST" action="{{ route('admin.bookings.repeat', $booking) }}" class="space-y-4">
                        @csrf
                        <x-ui.input name="preferred_date" label="Next visit date" type="date" required />
                        <x-ui.select name="time_window" label="Time window" :options="collect($windows)->mapWithKeys(fn ($w) => [$w->value => $w->label()])->all()" :value="$booking->time_window" required />
                        <x-ui.button variant="secondary" class="w-full">Create repeat booking</x-ui.button>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </div>
</x-layouts.admin>
