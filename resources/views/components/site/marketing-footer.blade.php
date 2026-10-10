@props(['business'])

@php
    $phone = $business['business.phone'];
    $socialLinks = array_filter([
        'Facebook' => $business['social.facebook'] ?? null,
        'Instagram' => $business['social.instagram'] ?? null,
        'YouTube' => $business['social.youtube'] ?? null,
    ]);
    $quickLinks = [
        'Home' => route('home'),
        'About' => route('about'),
        'FAQ' => route('faq'),
        'Contact' => route('contact'),
    ];
@endphp

<footer class="mt-auto border-t border-brand-100 bg-white">
    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-12 lg:px-8">
        <div class="space-y-4 lg:col-span-4">
            <p class="font-display text-lg font-semibold text-ink-900">About</p>
            <p class="max-w-sm text-sm leading-relaxed text-ink-700">
                {{ $business['business.name'] }} is a professional cleaning company committed to delivering reliable, high-quality cleaning services for homes and businesses.
            </p>
            <a href="{{ route('about') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-800 hover:text-brand-600">Read more <span aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="lg:col-span-2">
            <p class="text-sm font-semibold text-ink-900">Quick links</p>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($quickLinks as $label => $url)
                    <li><a href="{{ $url }}" class="text-ink-700 hover:text-brand-800">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-3">
            <p class="text-sm font-semibold text-ink-900">Services</p>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($services as $service)
                    <li><a href="{{ route('services.show', $service) }}" class="text-ink-700 hover:text-brand-800">{{ $service->name }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-3">
            <p class="text-sm font-semibold text-ink-900">Contact</p>
            <ul class="mt-4 space-y-2 text-sm text-ink-700">
                <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hover:text-brand-800">{{ $phone }}</a></li>
                <li><a href="mailto:{{ $business['business.email'] }}" class="hover:text-brand-800">{{ $business['business.email'] }}</a></li>
                @if ($business['business.location'])
                    <li>{{ $business['business.location'] }}</li>
                @endif
            </ul>
            @if ($socialLinks)
                <p class="mt-8 text-sm font-semibold text-ink-900">Follow us</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($socialLinks as $network => $url)
                        <li><a href="{{ $url }}" class="text-ink-700 hover:text-brand-800" rel="noopener noreferrer" target="_blank">{{ $network }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="border-t border-brand-100">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-5 gap-y-2 px-4 py-5 text-xs text-ink-500 sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} {{ $business['business.name'] }}. All rights reserved.</p>
            <a href="{{ route('pages.show', 'terms') }}" class="hover:text-brand-800">Terms</a>
            <a href="{{ route('pages.show', 'privacy') }}" class="hover:text-brand-800">Privacy</a>
            <a href="{{ route('pages.show', 'cancellation-policy') }}" class="hover:text-brand-800">Cancellation</a>
        </div>
    </div>
</footer>
