@php
    $phoneHref = 'tel:'.preg_replace('/[^0-9+]/', '', $business['business.phone']);
    $steps = [
        ['title' => 'Choose Your Service', 'body' => 'Select from recurring, one-time, home, commercial, or custom cleaning plans.'],
        ['title' => 'Schedule & Customize', 'body' => 'Pick a convenient time and personalize your cleaning based on your needs.'],
        ['title' => 'Relax While We Clean', 'body' => 'Our professional team delivers a spotless, fresh space every time, guaranteed'],
    ];
    $currency = $business['business.currency'];
@endphp

<x-layouts.public
    description="{{ $business['business.name'] }} provides professional cleaning services for homes and businesses: recurring, one-time, move-in/move-out, janitorial and commercial cleaning."
>
    <section class="bg-brand-50">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">{{ $business['home.hero_eyebrow'] }}</p>
                <h1 class="mt-3 text-4xl font-bold leading-tight sm:text-5xl">{{ $business['business.tagline'] }}</h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-700">{{ $business['home.intro'] }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-ui.button :href="route('services.index')" size="lg">Book a cleaning</x-ui.button>
                    <x-ui.button :href="route('quote.create')" variant="secondary" size="lg">Request a free quote</x-ui.button>
                </div>
                <p class="mt-5 text-sm text-ink-700">Prefer to talk? Call <a href="{{ $phoneHref }}" class="font-semibold text-brand-800 hover:text-brand-600">{{ $business['business.phone'] }}</a></p>
            </div>
            <div class="relative hidden lg:block" aria-hidden="true">
                <div class="absolute -right-6 -top-6 size-24 rounded-full bg-bubble-400/30"></div>
                <div class="absolute -bottom-8 left-10 size-16 rounded-full bg-brand-300/40"></div>
                @php($heroService = $services->first(fn ($service) => $service->featuredImageUrl()))
                @if ($heroService)
                    <img src="{{ $heroService->featuredImageUrl() }}" alt="" class="relative aspect-[4/3] w-full rounded-3xl object-cover shadow-sm" fetchpriority="high">
                @else
                    <div class="relative rounded-3xl bg-white p-10 shadow-sm">
                        <x-site.logo class="mx-auto h-auto w-full max-w-sm" alt="" />
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="how-it-works">
        <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">How it works</p>
        <h2 id="how-it-works" class="mt-2 text-3xl font-semibold">Our Simple Cleaning Process</h2>
        <ol class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ($steps as $step)
                <li class="rounded-2xl border border-brand-100 bg-white p-6">
                    <span class="flex size-10 items-center justify-center rounded-full bg-brand-600 font-display font-semibold text-white">{{ $loop->iteration }}</span>
                    <h3 class="mt-4 text-lg font-semibold">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-700">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    @if ($services->isNotEmpty())
        <section class="bg-brand-50/60" aria-labelledby="our-services">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Our Services</p>
                        <h2 id="our-services" class="mt-2 text-3xl font-semibold">Our Cleaning Solutions</h2>
                    </div>
                    <x-ui.button :href="route('services.index')" variant="secondary">View all</x-ui.button>
                </div>
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services->take(6) as $service)
                        <x-site.service-card :service="$service" :currency="$currency" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (! empty($business['home.why_items']))
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="why-us">
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Why Choose Us</p>
            <h2 id="why-us" class="mt-2 text-3xl font-semibold">What Makes Us Different</h2>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($business['home.why_items'] as $item)
                    <div class="rounded-2xl border border-brand-100 p-6">
                        <div class="size-2 rounded-full bg-bubble-500" aria-hidden="true"></div>
                        <h3 class="mt-4 font-semibold">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm text-ink-700">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        <section class="bg-brand-50/60" aria-labelledby="testimonials">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Testimonials</p>
                <h2 id="testimonials" class="mt-2 text-3xl font-semibold">Client Feedback</h2>
                <div class="mt-10 grid gap-6 md:grid-cols-2">
                    @foreach ($testimonials as $testimonial)
                        <figure class="rounded-2xl bg-white p-6 shadow-xs">
                            <blockquote class="text-ink-700">&ldquo;{{ $testimonial->body }}&rdquo;</blockquote>
                            <figcaption class="mt-4 font-semibold text-ink-900">{{ $testimonial->author_name }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="bg-brand-800">
        <div class="mx-auto flex max-w-7xl flex-col items-start gap-6 px-4 py-14 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <div>
                <h2 class="text-2xl font-semibold text-white">{{ $business['home.cta_heading'] }}</h2>
                <p class="mt-2 text-brand-100">{{ $business['home.cta_text'] }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="route('services.index')" variant="secondary" size="lg">Book trusted cleaners</x-ui.button>
                <a href="{{ $phoneHref }}" class="inline-flex items-center rounded-lg px-4 py-3 font-semibold text-white hover:bg-white/10">Call {{ $business['business.phone'] }}</a>
            </div>
        </div>
    </section>
</x-layouts.public>
