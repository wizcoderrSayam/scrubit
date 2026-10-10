@props(['price'])

@php($currency = $price->currency)

<dl class="space-y-2 text-sm">
    @foreach ($price->lines as $line)
        <div class="flex justify-between gap-4">
            <dt class="text-ink-700">{{ $line['label'] }}</dt>
            <dd class="whitespace-nowrap font-medium">{{ $line['amount_cents'] === null ? 'To be quoted' : \App\Support\Money::format($line['amount_cents'], $currency) }}</dd>
        </div>
    @endforeach

    @if ($price->quoteRequired)
        <div class="mt-3 rounded-lg bg-amber-50 p-3 text-amber-900">
            <dt class="font-semibold">Quote required</dt>
            <dd class="mt-1 space-y-1">
                @foreach ($price->quoteReasons as $reason)
                    <p>{{ $reason }}</p>
                @endforeach
                <p>You will not be charged until you approve the quoted price.</p>
            </dd>
        </div>
    @else
        <div class="flex justify-between gap-4 border-t border-brand-100 pt-2">
            <dt class="text-ink-700">Subtotal</dt>
            <dd>{{ \App\Support\Money::format($price->subtotalCents, $currency) }}</dd>
        </div>
        @if ($price->discountCents > 0)
            <div class="flex justify-between gap-4">
                <dt class="text-ink-700">Discount</dt>
                <dd>-{{ \App\Support\Money::format($price->discountCents, $currency) }}</dd>
            </div>
        @endif
        @foreach ($price->taxes as $tax)
            <div class="flex justify-between gap-4">
                <dt class="text-ink-700">{{ $tax['name'] }}</dt>
                <dd>{{ \App\Support\Money::format($tax['amount_cents'], $currency) }}</dd>
            </div>
        @endforeach
        <div class="flex justify-between gap-4 border-t border-brand-100 pt-2 text-base font-semibold">
            <dt>Total</dt>
            <dd>{{ \App\Support\Money::format($price->totalCents, $currency) }}</dd>
        </div>
    @endif
</dl>
