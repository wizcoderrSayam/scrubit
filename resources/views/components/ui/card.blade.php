@props(['title' => null, 'description' => null])

@php
    $cardClasses = request()->routeIs('admin.*')
        ? 'rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6'
        : 'rounded-[22px] border border-brand-100 bg-white p-5 sm:p-7';
@endphp

<section {{ $attributes->merge(['class' => $cardClasses]) }}>
    @if ($title)
        <header class="mb-5">
            <h2 class="text-lg font-semibold">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-sm text-ink-500">{{ $description }}</p>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
