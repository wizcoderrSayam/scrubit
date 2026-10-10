@php
    $currency = $business['business.currency'];
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $service->name,
        'description' => $service->short_description,
        'serviceType' => $service->name,
        'url' => route('services.show', $service),
        'provider' => [
            '@type' => 'LocalBusiness',
            'name' => $business['business.name'],
            'telephone' => $business['business.phone'],
            'email' => $business['business.email'],
            'url' => route('home'),
        ],
    ];
@endphp

<x-layouts.public
    :title="$service->seo_title ?: $service->name"
    :description="$service->seo_description ?: $service->short_description"
    :canonical="route('services.show', $service)"
>
    <script type="application/ld+json">@json($structuredData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)</script>

    <section class="border-b border-brand-100">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-2 lg:px-8">
            <div>
                <nav aria-label="Breadcrumb" class="text-sm text-ink-500">
                    <a href="{{ route('services.index') }}" class="hover:text-brand-700">Services</a>
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('services.category', $service->category) }}" class="hover:text-brand-700">{{ $service->category->name }}</a>
                </nav>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">{{ $service->name }}</h1>
                <p class="mt-4 text-lg leading-relaxed text-ink-700">{{ $service->short_description }}</p>
                <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                    <div>
                        <dt class="text-ink-500">Pricing</dt>
                        <dd class="font-semibold text-brand-800">{{ $service->priceLabel($currency) }}</dd>
                    </div>
                    @if ($service->estimated_minutes)
                        <div>
                            <dt class="text-ink-500">Estimated duration</dt>
                            <dd class="font-semibold">{{ intdiv($service->estimated_minutes, 60) }}h{{ $service->estimated_minutes % 60 ? ' '.($service->estimated_minutes % 60).'m' : '' }}</dd>
                        </div>
                    @endif
                    @if ($service->allows_recurring)
                        <div>
                            <dt class="text-ink-500">Scheduling</dt>
                            <dd class="font-semibold">Weekly, bi-weekly or monthly</dd>
                        </div>
                    @endif
                </dl>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-ui.button :href="route('booking.start', $service)" size="lg">Customize &amp; Book</x-ui.button>
                    <x-ui.button :href="route('quote.create', ['service' => $service->slug])" variant="secondary" size="lg">Ask a question</x-ui.button>
                </div>
            </div>
            <div>
                @if ($service->featuredImageUrl())
                    <img src="{{ $service->featuredImageUrl() }}" alt="{{ $service->name }}" class="aspect-[4/3] w-full rounded-3xl object-cover shadow-sm" fetchpriority="high">
                @endif
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-3 lg:px-8">
        <div class="space-y-10 lg:col-span-2">
            @if ($service->description)
                <section class="prose-content">
                    <h2 class="text-2xl font-semibold">About this service</h2>
                    <div class="mt-4 space-y-4 leading-relaxed text-ink-700">{{ \App\Support\Markdown::render($service->description) }}</div>
                </section>
            @endif

            @if (! empty($service->inclusions))
                <section>
                    <h2 class="text-2xl font-semibold">What is included</h2>
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($service->inclusions as $item)
                            <li class="flex gap-3 rounded-xl border border-brand-100 bg-white p-4 text-sm">
                                <svg class="mt-0.5 size-5 shrink-0 text-brand-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (! empty($service->exclusions))
                <section>
                    <h2 class="text-xl font-semibold">Not included</h2>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-ink-700">
                        @foreach ($service->exclusions as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($service->instructions)
                <section class="rounded-2xl bg-brand-50 p-6">
                    <h2 class="text-lg font-semibold">Before your visit</h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $service->instructions }}</p>
                </section>
            @endif

            @if ($service->images->isNotEmpty())
                <section>
                    <h2 class="text-2xl font-semibold">Gallery</h2>
                    <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3">
                        @foreach ($service->images as $image)
                            <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $service->name }}" loading="lazy" decoding="async" class="aspect-square w-full rounded-xl object-cover">
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <div class="rounded-2xl border border-brand-100 bg-white p-6 lg:sticky lg:top-24">
                <h2 class="text-lg font-semibold">Ready to book?</h2>
                <p class="mt-2 text-sm text-ink-700">Describe your space and priorities in a quick chat. We confirm the price before you pay.</p>
                @if ($service->addons->isNotEmpty())
                    <h3 class="mt-5 text-sm font-semibold">Available add-ons</h3>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach ($service->addons as $addon)
                            <li class="flex justify-between gap-3"><span>{{ $addon->name }}</span><span class="text-ink-500">{{ $addon->priceLabel($currency) }}</span></li>
                        @endforeach
                    </ul>
                @endif
                <x-ui.button :href="route('booking.start', $service)" class="mt-6 w-full">Customize &amp; Book</x-ui.button>
            </div>

            @if ($related->isNotEmpty())
                <div>
                    <h2 class="text-lg font-semibold">Related services</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($related as $item)
                            <li><a href="{{ route('services.show', $item) }}" class="text-brand-700 hover:text-brand-900">{{ $item->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.public>
