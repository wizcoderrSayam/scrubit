@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6']) }}>
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
