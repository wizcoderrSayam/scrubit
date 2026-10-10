<x-layouts.admin title="Quotes">
    <x-ui.card title="Waiting for a quote" description="Oldest requests first. Open a booking to review the conversation and send a quote.">
        @if ($pending->isEmpty())
            <p class="text-sm text-ink-500">No bookings are waiting for a quote.</p>
        @else
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($pending as $booking)
                    <li class="flex flex-wrap items-center gap-3 py-3">
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="font-medium text-admin-accent hover:underline">{{ $booking->reference }}</a>
                        <span>{{ $booking->contact_name }} · {{ $booking->service_name }}</span>
                        <span class="ml-auto text-ink-500">Submitted {{ $booking->submitted_at?->diffForHumans() }} · visit {{ $booking->preferred_date?->format('M j') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-sm font-medium text-ink-500">
                <tr><th class="px-4 py-3">Booking</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Sent</th><th class="px-4 py-3">Expires</th><th class="px-4 py-3">Accepted</th><th class="px-4 py-3">By</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($quotes as $quote)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('admin.bookings.show', $quote->booking) }}" class="font-medium text-admin-accent hover:underline">{{ $quote->booking->reference }}</a></td>
                        <td class="px-4 py-3">{{ \App\Support\Money::format($quote->total_cents, $quote->currency) }}</td>
                        <td class="px-4 py-3">{{ $quote->isExpired() && $quote->status->isCurrent() ? 'Expired' : $quote->status->label() }}</td>
                        <td class="px-4 py-3">{{ $quote->sent_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3">{{ $quote->expires_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3">{{ $quote->accepted_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $quote->creator?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-ink-500">No quotes have been sent yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $quotes->links() }}</div>
</x-layouts.admin>
