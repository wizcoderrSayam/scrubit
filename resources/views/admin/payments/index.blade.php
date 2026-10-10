<x-layouts.admin title="Payments">
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div>
            <label for="status" class="block text-xs font-medium text-ink-500">Status</label>
            <select id="status" name="status" class="mt-1 rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <option value="">All</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="attention" value="1" @checked(request()->boolean('attention')) class="rounded border-line"> Needs attention only ({{ $attentionCount }})</label>
        <x-ui.button size="sm">Filter</x-ui.button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-sm font-medium text-ink-500">
                <tr><th class="px-4 py-3">Booking</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Refunded</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Provider ref</th><th class="px-4 py-3">Created</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $payment)
                    <tr @class(['bg-red-50' => $payment->needs_attention])>
                        <td class="px-4 py-3"><a href="{{ route('admin.bookings.show', $payment->booking) }}" class="font-medium text-admin-accent hover:underline">{{ $payment->booking->reference }}</a>
                            @if ($payment->needs_attention)<span class="block text-xs text-red-800">{{ $payment->meta['attention_reason'] ?? 'Needs attention' }}</span>@endif</td>
                        <td class="px-4 py-3">{{ \App\Support\Money::format($payment->amount_cents, $payment->currency) }}</td>
                        <td class="px-4 py-3">{{ $payment->refunded_cents ? \App\Support\Money::format($payment->refunded_cents, $payment->currency) : '—' }}</td>
                        <td class="px-4 py-3"><x-ui.badge :status="$payment->status" /></td>
                        <td class="px-4 py-3 text-xs text-ink-500">{{ $payment->provider }} · {{ $payment->provider_ref }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $payment->created_at->format('M j, Y g:i a') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
</x-layouts.admin>
