@props(['service', 'currency' => 'CAD'])

<article class="flex flex-col overflow-hidden rounded-[20px] border border-brand-100 bg-white transition duration-300 motion-reduce:transform-none hover:-translate-y-1 hover:shadow-[0_16px_50px_rgba(12,56,112,.10)]">
    <a href="{{ route('services.show', $service) }}" class="block aspect-[4/3] bg-brand-50" tabindex="-1" aria-hidden="true">
        @if ($service->featuredImageUrl())
            <img src="{{ $service->featuredImageUrl() }}" alt="" loading="lazy" decoding="async" class="size-full object-cover">
        @else
            <div class="flex size-full items-center justify-center">
                <x-site.logo class="h-10 w-auto opacity-40" alt="" />
            </div>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-bold">
            <a href="{{ route('services.show', $service) }}" class="hover:text-brand-700">{{ $service->name }}</a>
        </h3>
        <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-500">{{ $service->short_description }}</p>
        <p class="mt-4 text-sm font-semibold text-brand-800">{{ $service->priceLabel($currency) }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button :href="route('booking.start', $service)" size="sm">Customize &amp; Book</x-ui.button>
            <x-ui.button :href="route('services.show', $service)" variant="secondary" size="sm">Details</x-ui.button>
        </div>
    </div>
</article>
