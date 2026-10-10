<article @class(['group flex flex-col', 'sm:col-span-2 lg:col-span-2' => $featured ?? false])>
    <a href="{{ route('services.show', $service) }}" @class([
        'block overflow-hidden rounded-3xl bg-brand-50',
        'aspect-[16/10]' => $featured ?? false,
        'aspect-[4/3]' => ! ($featured ?? false),
    ]) tabindex="-1" aria-hidden="true">
        @if ($service->featuredImageUrl())
            <img src="{{ $service->featuredImageUrl() }}" alt="" loading="lazy" decoding="async" class="size-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <div class="flex size-full items-center justify-center bg-brand-100/60">
                <x-site.logo class="h-10 w-auto opacity-40" alt="" />
            </div>
        @endif
    </a>
    <div class="mt-4 max-w-xl">
        <h3 class="text-lg font-semibold">
            <a href="{{ route('services.show', $service) }}" class="hover:text-brand-700">{{ $service->name }}</a>
        </h3>
        <p class="mt-2 text-sm leading-relaxed text-ink-700">{{ $service->short_description }}</p>
        <a href="{{ route('services.show', $service) }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-brand-800 hover:text-brand-600">Read more <span aria-hidden="true">&rarr;</span></a>
    </div>
</article>
