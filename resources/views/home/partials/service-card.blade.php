<article @class([
    'group flex flex-col overflow-hidden rounded-[20px] border border-brand-100 bg-white transition duration-300 motion-reduce:transform-none hover:-translate-y-1 hover:shadow-[0_16px_50px_rgba(12,56,112,.10)]',
    'sm:col-span-2 lg:col-span-2' => $featured ?? false,
])>
    <a href="{{ route('services.show', $service) }}" @class([
        'block overflow-hidden bg-brand-50',
        'aspect-[16/10]' => $featured ?? false,
        'aspect-[4/3]' => ! ($featured ?? false),
    ]) tabindex="-1" aria-hidden="true">
        @if ($service->featuredImageUrl())
            <img src="{{ $service->featuredImageUrl() }}" alt="" loading="lazy" decoding="async" class="size-full object-cover transition duration-500 motion-safe:group-hover:scale-[1.03]">
        @else
            <div class="flex size-full items-center justify-center bg-brand-100/60">
                <x-site.logo class="h-10 w-auto opacity-40" alt="" />
            </div>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-6">
        <h3 class="text-lg font-bold">
            <a href="{{ route('services.show', $service) }}" class="hover:text-brand-700">{{ $service->name }}</a>
        </h3>
        <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $service->short_description }}</p>
        <div class="mt-auto flex items-center justify-between gap-3 pt-5">
            <span class="font-display text-base font-bold text-brand-800">{{ $service->priceLabel() }}</span>
            <a href="{{ route('services.show', $service) }}" class="grid size-10 place-items-center rounded-full bg-brand-50 text-lg text-brand-800 transition group-hover:bg-brand-600 group-hover:text-white" aria-label="View {{ $service->name }}">
                <span aria-hidden="true">&nearr;</span>
            </a>
        </div>
    </div>
</article>
