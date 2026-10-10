@php
    $phoneHref = 'tel:'.preg_replace('/[^0-9+]/', '', $business['business.phone']);
    $heroService = $services->first(fn ($service) => $service->featuredImageUrl());
    $steps = [
        ['title' => 'Choose Your Service', 'body' => 'Select from recurring, one-time, home, commercial, or custom cleaning plans.'],
        ['title' => 'Schedule & Customize', 'body' => 'Pick a convenient time and personalize your cleaning based on your needs.'],
        ['title' => 'Relax While We Clean', 'body' => 'Our professional team delivers a spotless, fresh space every time, guaranteed'],
    ];
    $stats = [
        ['label' => 'Houses Cleaned', 'value' => (int) ($business['home.stats.houses_cleaned'] ?? 0), 'plus' => true],
        ['label' => 'Happy Customers', 'value' => (int) ($business['home.stats.happy_customers'] ?? 0), 'plus' => true],
        ['label' => 'Expert Cleaners', 'value' => (int) ($business['home.stats.expert_cleaners'] ?? 0), 'plus' => false],
        ['label' => 'Years of Experience', 'value' => (int) ($business['home.stats.years_experience'] ?? 0), 'plus' => true],
    ];
@endphp

<x-layouts.public
    description="{{ $business['business.name'] }} provides professional cleaning services for homes and businesses: recurring, one-time, move-in/move-out, janitorial and commercial cleaning."
>
    <section class="overflow-hidden border-b border-brand-100">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-12 lg:gap-16 lg:px-8 lg:py-24">
            <div class="lg:col-span-7">
                <p class="text-sm font-medium text-brand-700">{{ $business['home.hero_eyebrow'] }}</p>
                <h1 class="mt-5 max-w-3xl text-4xl font-semibold leading-[1.08] tracking-tight sm:text-6xl">{{ $business['business.tagline'] }}</h1>
                <div class="mt-10 flex flex-wrap items-center gap-6">
                    <x-ui.button href="#quote" size="lg">Get in touch</x-ui.button>
                    <a href="{{ $phoneHref }}" class="text-sm font-semibold text-ink-700 hover:text-brand-800">{{ $business['business.phone'] }}</a>
                </div>
            </div>
            <div class="lg:col-span-5">
                @if ($heroService)
                    <img src="{{ $heroService->featuredImageUrl() }}" alt="" class="aspect-[4/5] w-full rounded-[2rem] object-cover sm:aspect-[5/4] lg:aspect-[4/5]" fetchpriority="high">
                @else
                    <div class="flex aspect-[4/5] flex-col justify-between rounded-[2rem] bg-brand-900 p-8 text-white sm:aspect-[5/4] lg:aspect-[4/5] sm:p-10">
                        <p class="text-sm text-brand-200">Call the studio</p>
                        <a href="{{ $phoneHref }}" class="text-3xl font-semibold tracking-tight text-white hover:text-brand-100 sm:text-4xl">{{ $business['business.phone'] }}</a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-12 lg:gap-16 lg:px-8" aria-labelledby="about-teaser">
        <div class="lg:col-span-5">
            <h2 id="about-teaser" class="text-3xl font-semibold tracking-tight sm:text-4xl">About Our Company</h2>
        </div>
        <div class="lg:col-span-7 lg:pt-2">
            <p class="text-lg leading-relaxed text-ink-700">{{ $business['home.intro'] }}</p>
            <a href="{{ route('about') }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-800 hover:text-brand-600">Discover more <span aria-hidden="true">&rarr;</span></a>
        </div>
    </section>

    <section id="quote" class="scroll-mt-32 bg-brand-900 text-white" aria-labelledby="get-a-quote">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-12 lg:gap-16 lg:px-8">
            <div class="lg:col-span-5">
                <h2 id="get-a-quote" class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Get a Quote</h2>
                <p class="mt-4 max-w-md text-brand-100">Tell us what you need and we will send a personalized cleaning quote.</p>
                <dl class="mt-10 space-y-6 border-t border-white/15 pt-8">
                    <div>
                        <dt class="text-sm text-brand-200">Phone</dt>
                        <dd class="mt-1 text-2xl font-semibold tracking-tight"><a href="{{ $phoneHref }}" class="text-white hover:text-brand-100">{{ $business['business.phone'] }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-brand-200">Email</dt>
                        <dd class="mt-1 text-lg font-semibold"><a href="mailto:{{ $business['business.email'] }}" class="text-white hover:text-brand-100">{{ $business['business.email'] }}</a></dd>
                    </div>
                </dl>
            </div>
            <form method="POST" action="{{ route('quote.store') }}" class="grid gap-5 rounded-[2rem] bg-white p-6 text-ink-900 sm:grid-cols-2 sm:p-8 lg:col-span-7">
                @csrf
                @include('pages.partials.quote-fields')
                <div class="sm:col-span-2">
                    <x-ui.button>Get a quote</x-ui.button>
                </div>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8" aria-labelledby="how-it-works">
        <h2 id="how-it-works" class="max-w-xl text-3xl font-semibold tracking-tight sm:text-4xl">Our Simple Cleaning Process</h2>
        <ol class="mt-12 border-t border-brand-100">
            @foreach ($steps as $step)
                <li class="grid gap-3 border-b border-brand-100 py-8 md:grid-cols-12 md:items-baseline md:gap-8">
                    <span class="text-sm font-semibold text-brand-700 md:col-span-2">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="text-xl font-semibold md:col-span-4">{{ $step['title'] }}</h3>
                    <p class="leading-relaxed text-ink-700 md:col-span-6">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    @if ($services->isNotEmpty())
        <section class="border-t border-brand-100 bg-brand-50/40" aria-labelledby="our-services">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <h2 id="our-services" class="text-3xl font-semibold tracking-tight sm:text-4xl">Our Cleaning Solutions</h2>
                    <a href="{{ route('services.index') }}" class="text-sm font-semibold text-brand-800 hover:text-brand-600">View all <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="mt-12 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services->take(6) as $service)
                        @include('home.partials.service-card', ['service' => $service, 'featured' => $loop->first])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (collect($stats)->contains(fn (array $stat): bool => $stat['value'] > 0))
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8" aria-labelledby="cleaning-stats">
            <h2 id="cleaning-stats" class="text-3xl font-semibold tracking-tight sm:text-4xl">Numbers That Speak</h2>
            <dl class="mt-12 grid grid-cols-2 gap-x-8 gap-y-10 border-t border-brand-100 pt-10 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <div>
                        <dd class="text-4xl font-semibold tracking-tight text-brand-800 sm:text-5xl">{{ number_format($stat['value']) }}@if ($stat['plus'] && $stat['value'] > 0)+@endif</dd>
                        <dt class="mt-2 text-sm text-ink-500">{{ $stat['label'] }}</dt>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    @if (! empty($business['home.why_items']))
        <section class="border-t border-brand-100" aria-labelledby="why-us">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <h2 id="why-us" class="max-w-xl text-3xl font-semibold tracking-tight sm:text-4xl">What Makes Us Different</h2>
                <div class="mt-12 grid gap-x-16 gap-y-10 sm:grid-cols-2">
                    @foreach ($business['home.why_items'] as $item)
                        <div class="border-t border-brand-200 pt-6">
                            <h3 class="text-lg font-semibold">{{ $item['title'] }}</h3>
                            <p class="mt-2 leading-relaxed text-ink-700">{{ $item['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        <section class="bg-brand-50/40" aria-labelledby="testimonials">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <h2 id="testimonials" class="text-3xl font-semibold tracking-tight sm:text-4xl">Client Feedback</h2>
                <div class="mt-12 grid gap-x-12 gap-y-12 md:grid-cols-2">
                    @foreach ($testimonials as $testimonial)
                        <figure class="border-t border-brand-200 pt-6">
                            <blockquote class="text-lg leading-relaxed text-ink-700">&ldquo;{{ $testimonial->body }}&rdquo;</blockquote>
                            <figcaption class="mt-5 text-sm font-semibold text-ink-900">{{ $testimonial->author_name }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 rounded-[2rem] bg-brand-900 px-8 py-12 sm:px-12 sm:py-16 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-xl">
                <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">{{ $business['home.cta_heading'] }}</h2>
                <p class="mt-3 text-lg text-brand-100">{{ $business['home.cta_text'] }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="route('services.index')" variant="secondary" size="lg">Book trusted cleaners</x-ui.button>
                <a href="{{ $phoneHref }}" class="inline-flex items-center rounded-lg px-4 py-3 font-semibold text-white hover:bg-white/10">Call {{ $business['business.phone'] }}</a>
            </div>
        </div>
    </section>
</x-layouts.public>
