@props(['current' => 1])

@php
    $steps = ['Select service', 'Customize', 'Review', 'Payment', 'Confirmation'];
    $current = max(1, min(count($steps), (int) $current));
@endphp

<nav aria-label="Booking progress">
    <p class="mb-2 text-sm font-medium text-ink-700 sm:hidden">
        Step {{ $current }} of {{ count($steps) }}: <span class="text-brand-800">{{ $steps[$current - 1] }}</span>
    </p>
    <div class="h-1.5 overflow-hidden rounded-full bg-brand-100 sm:hidden" aria-hidden="true">
        <div class="h-full rounded-full bg-brand-600" style="width: {{ ($current / count($steps)) * 100 }}%"></div>
    </div>

    <ol class="hidden items-center gap-2 sm:flex">
        @foreach ($steps as $index => $label)
            @php($number = $index + 1)
            <li class="flex flex-1 items-center gap-2" @if ($number === $current) aria-current="step" @endif>
                <span @class([
                    'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                    'bg-brand-600 text-white' => $number <= $current,
                    'border border-brand-200 bg-white text-ink-500' => $number > $current,
                ])>
                    @if ($number < $current)
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                        <span class="sr-only">Completed:</span>
                    @else
                        {{ $number }}
                    @endif
                </span>
                <span @class([
                    'text-sm font-medium',
                    'text-brand-800' => $number === $current,
                    'text-ink-700' => $number < $current,
                    'text-ink-500' => $number > $current,
                ])>{{ $label }}</span>
                @if (! $loop->last)
                    <span @class(['h-px flex-1', 'bg-brand-600' => $number < $current, 'bg-brand-100' => $number >= $current]) aria-hidden="true"></span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
