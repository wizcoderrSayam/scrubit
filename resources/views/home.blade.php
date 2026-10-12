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
    $hasStats = collect($stats)->contains(fn (array $stat): bool => $stat['value'] > 0);
    $taglineWords = preg_split('/\s+/', trim((string) $business['business.tagline']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $taglineAccent = count($taglineWords) > 1 ? rtrim((string) array_pop($taglineWords), '.') : '';
    $taglineLead = implode(' ', $taglineWords);
    if ($taglineAccent === '') {
        $taglineLead = (string) $business['business.tagline'];
    }
    $taglineEnd = str_ends_with(trim((string) $business['business.tagline']), '.') ? '.' : '';
    $trustPoints =['Trusted professionals', 'Homes & businesses', 'Flexible scheduling', 'Care in every detail'];
@endphp

<x-layouts.public
    description="{{ $business['business.name'] }} provides professional cleaning services for homes and businesses: recurring, one-time, move-in/move-out, janitorial and commercial cleaning."
>
    <section class="overflow-hidden bg-brand-50/60">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-12 lg:gap-14 lg:px-8 lg:py-24">
            <div class="lg:col-span-6">
                <p class="inline-flex items-center rounded-full bg-brand-100 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-brand-800">{{ $business['home.hero_eyebrow'] }}</p>
                <h1 class="mt-6 max-w-2xl text-4xl font-extrabold leading-[1.08] tracking-[-0.04em] sm:text-6xl lg:text-7xl">{{ $taglineLead }}@if ($taglineAccent !== '') <em class="not-italic text-brand-600">{{ $taglineAccent }}</em>{{ $taglineEnd }}@endif</h1>
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <x-ui.button href="#quote" size="lg">Get in touch <span aria-hidden="true">&nearr;</span></x-ui.button>
                    <x-ui.button :href="route('services.index')" variant="secondary" size="lg">Explore services</x-ui.button>
                </div>
                <p class="mt-6 text-sm text-ink-700">Call us on <a href="{{ $phoneHref }}" class="font-semibold text-ink-700 hover:text-brand-800">{{ $business['business.phone'] }}</a></p>
            </div>
            <div class="relative lg:col-span-6">
                @if ($heroService)
                    <img src="{{ $heroService->featuredImageUrl() }}" alt="" class="aspect-[4/3] w-full rounded-[30px] object-cover lg:aspect-[5/4]" fetchpriority="high">
                @else
                    <div class="flex aspect-[4/3] flex-col justify-between rounded-[30px] bg-brand-900 p-8 text-white sm:p-10 lg:aspect-[5/4]">
                        <p class="text-sm text-brand-200">Call the studio</p>
                        <a href="{{ $phoneHref }}" class="text-3xl font-semibold tracking-tight text-white hover:text-brand-100 sm:text-4xl">{{ $business['business.phone'] }}</a>
                    </div>
                @endif
                <div class="absolute left-3 top-6 hidden rounded-2xl border border-white/70 bg-white px-5 py-4 shadow-[0_16px_50px_rgba(12,56,112,.12)] sm:block lg:-left-8">
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-600">{{ $business['home.hero_eyebrow'] }}</p>
                    <a href="{{ $phoneHref }}" class="mt-1 block text-xl font-extrabold text-ink-900 hover:text-brand-800">{{ $business['business.phone'] }}</a>
                </div>
                <div class="absolute -bottom-5 left-4 flex items-center gap-3 rounded-2xl border border-brand-100 bg-white px-5 py-4 shadow-[0_16px_50px_rgba(12,56,112,.12)] sm:left-auto sm:right-6 lg:-right-6">
                    <span class="grid size-11 place-items-center rounded-2xl bg-brand-100 text-xl font-bold text-brand-800" aria-hidden="true">&check;</span>
                    <div>
                        <p class="text-sm font-bold">{{ $business['business.name'] }}</p>
                        <a href="mailto:{{ $business['business.email'] }}" class="text-xs text-ink-500 hover:text-brand-800">{{ $business['business.email'] }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="border-y border-brand-100 bg-white">
        <ul class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-8 gap-y-3 px-4 py-5 text-sm font-semibold text-ink-700 sm:px-6 lg:px-8">
            @foreach ($trustPoints as $point)
                <li class="flex items-center gap-2"><span class="text-brand-600" aria-hidden="true">&check;</span> {{ $point }}</li>
            @endforeach
        </ul>
    </div>

    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-12 lg:gap-16 lg:px-8" aria-labelledby="about-teaser">
        <div class="lg:col-span-5">
            <h2 id="about-teaser" class="text-3xl font-extrabold tracking-[-0.03em] sm:text-4xl lg:text-5xl">About Our Company</h2>
        </div>
        <div class="lg:col-span-7 lg:pt-2">
            <p class="text-lg leading-relaxed text-ink-700">{{ $business['home.intro'] }}</p>
            <a href="{{ route('about') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-50 px-5 py-2.5 text-sm font-semibold text-brand-800 hover:bg-brand-100">Discover more <span aria-hidden="true">&rarr;</span></a>
        </div>
    </section>

    <section id="quote" class="scroll-mt-32 bg-brand-50" aria-labelledby="get-a-quote">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <p class="inline-flex rounded-full bg-brand-100 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-brand-800">Your booking, your way</p>
                <h2 id="get-a-quote" class="mt-4 text-3xl font-extrabold tracking-[-0.03em] sm:text-4xl lg:text-5xl">Get a Quote</h2>
                <p class="mt-3 text-ink-700">Tell us what you need and we will send a personalized cleaning quote.</p>
            </div>
            <div class="grid items-start gap-6 lg:grid-cols-[1.5fr_.85fr]">
                <form method="POST" action="{{ route('quote.store') }}" class="grid gap-5 rounded-[22px] border border-brand-100 bg-white p-6 text-ink-900 sm:grid-cols-2 sm:p-8">
                    @csrf
                    @include('pages.partials.quote-fields')
                    <div class="sm:col-span-2">
                        <x-ui.button>Get a quote</x-ui.button>
                    </div>
                </form>
                <aside class="rounded-[22px] border border-brand-100 bg-white p-6 sm:p-8 lg:sticky lg:top-28">
                    <h3 class="text-xl font-extrabold">Talk to {{ $business['business.name'] }}</h3>
                    <div class="mt-5 flex items-center gap-3 rounded-2xl bg-brand-50 p-4">
                        <span class="grid size-11 place-items-center rounded-xl bg-white text-xl text-brand-800" aria-hidden="true">&#9990;</span>
                        <a href="{{ $phoneHref }}" class="text-lg font-bold text-brand-800 hover:text-brand-600">{{ $business['business.phone'] }}</a>
                    </div>
                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-700">Email</dt>
                            <dd class="text-right font-semibold"><a href="mailto:{{ $business['business.email'] }}" class="text-brand-800 hover:text-brand-600">{{ $business['business.email'] }}</a></dd>
                        </div>
                        @if ($business['business.location'])
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-700">Location</dt>
                                <dd class="text-right font-semibold">{{ $business['business.location'] }}</dd>
                            </div>
                        @endif
                    </dl>
                    <div class="my-6 border-t border-brand-100"></div>
                    <p class="text-xs leading-relaxed text-ink-700">Prices depend on your property, service requirements and location. We confirm everything in your quote before you pay.</p>
                </aside>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8" aria-labelledby="how-it-works">
        <h2 id="how-it-works" class="max-w-2xl text-3xl font-extrabold tracking-[-0.03em] sm:text-4xl lg:text-5xl">Our Simple Cleaning Process</h2>
        <ol class="mt-12 grid gap-x-10 gap-y-8 md:grid-cols-3">
            @foreach ($steps as $step)
                <li class="border-t-2 border-brand-600 pt-5">
                    <span class="font-display text-4xl font-extrabold tabular-nums tracking-tight text-brand-200">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="mt-3 text-lg font-bold">{{ $step['title'] }}</h3>
                    <p class="mt-2 leading-relaxed text-ink-700">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    @if ($services->isNotEmpty())
        <section class="bg-brand-50" aria-labelledby="our-services">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="inline-flex rounded-full bg-brand-100 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-brand-800">Find your kind of clean</p>
                    <h2 id="our-services" class="mt-4 text-3xl font-extrabold tracking-[-0.03em] sm:text-4xl lg:text-5xl">Our Cleaning Solutions</h2>
                </div>
                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services->take(6) as $service)
                        @include('home.partials.service-card', ['service' => $service, 'featured' => false])
                    @endforeach
                </div>
                <p class="mt-10 text-center"><x-ui.button :href="route('services.index')" variant="secondary">View all services <span aria-hidden="true">&rarr;</span></x-ui.button></p>
            </div>
        </section>
    @endif

    @if ($hasStats)
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8" aria-labelledby="cleaning-stats">
            <h2 id="cleaning-stats" class="max-w-2xl text-3xl font-extrabold tracking-[-0.03em] sm:text-4xl lg:text-5xl">Numbers That Speak</h2>
            <dl class="mt-12 grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="flex flex-col border-t border-brand-200 pt-5">
                        <dt class="order-2 mt-2 text-sm text-ink-700">{{ $stat['label'] }}</dt>
                        <dd class="order-1 font-display text-5xl font-extrabold tabular-nums tracking-tight text-brand-800 sm:text-6xl">{{ number_format($stat['value']) }}@if ($stat['plus'] && $stat['value'] > 0)+@endif</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    @if (! empty($business['home.why_items']))
        <section @class(['border-t border-brand-100' => $hasStats]) aria-labelledby="why-us">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <h2 id="why-us" class="max-w-2xl text-3xl font-extrabold tracking-[-0.03em] sm:text-4xl lg:text-5xl">What Makes Us Different</h2>
                <div class="mt-12 grid gap-x-10 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($business['home.why_items'] as $item)
                        <div class="border-t border-brand-200 pt-5">
                            <h3 class="text-lg font-bold">{{ $item['title'] }}</h3>
                            <p class="mt-2 leading-relaxed text-ink-700">{{ $item['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        <section class="bg-brand-900 text-white" aria-labelledby="testimonials">
            <div class="mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:px-8">
                <h2 id="testimonials" class="text-sm font-bold uppercase tracking-wider text-brand-200">Client Feedback</h2>
                @php($lead = $testimonials->first())
                <figure class="mt-6">
                    <blockquote class="font-display text-2xl font-semibold leading-snug tracking-tight text-white sm:text-3xl">&ldquo;{{ $lead->body }}&rdquo;</blockquote>
                    <figcaption class="mt-5 text-sm font-semibold text-brand-200">{{ $lead->author_name }}</figcaption>
                </figure>
                @if ($testimonials->count() > 1)
                    <div class="mt-12 grid gap-6 text-left md:grid-cols-2">
                        @foreach ($testimonials->skip(1) as $testimonial)
                            <figure class="rounded-[20px] border border-white/15 bg-white/5 p-7">
                                <blockquote class="leading-relaxed text-brand-100">&ldquo;{{ $testimonial->body }}&rdquo;</blockquote>
                                <figcaption class="mt-5 text-sm font-semibold text-white">{{ $testimonial->author_name }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section class="px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 rounded-[30px] bg-brand-50 px-8 py-12 sm:px-12 sm:py-16 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-xl">
                <h2 class="text-3xl font-extrabold tracking-[-0.03em] sm:text-4xl lg:text-5xl">{{ $business['home.cta_heading'] }}</h2>
                <p class="mt-3 text-lg text-ink-700">{{ $business['home.cta_text'] }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="route('services.index')" size="lg">Book trusted cleaners <span aria-hidden="true">&nearr;</span></x-ui.button>
                <x-ui.button :href="$phoneHref" variant="secondary" size="lg">Call {{ $business['business.phone'] }}</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.public>
