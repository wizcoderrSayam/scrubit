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

<footer class="mt-auto bg-brand-900 text-brand-100">
    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-12 lg:px-8">
        <div class="space-y-4 lg:col-span-4">
            <p class="font-display text-lg font-semibold text-white">About</p>
            <p class="max-w-sm text-sm leading-relaxed text-brand-200">
                {{ $business['business.name'] }} is a professional cleaning company committed to delivering reliable, high-quality cleaning services for homes and businesses.
            </p>
            <a href="{{ route('about') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white hover:text-brand-200">Read more <span aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="lg:col-span-2">
            <p class="text-sm font-semibold text-white">Quick links</p>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($quickLinks as $label => $url)
                    <li><a href="{{ $url }}" class="text-brand-200 hover:text-white">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-3">
            <p class="text-sm font-semibold text-white">Services</p>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($services as $service)
                    <li><a href="{{ route('services.show', $service) }}" class="text-brand-200 hover:text-white">{{ $service->name }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-3">
            <p class="text-sm font-semibold text-white">Contact</p>
            <ul class="mt-4 space-y-2 text-sm text-brand-200">
                <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hover:text-white">{{ $phone }}</a></li>
                <li><a href="mailto:{{ $business['business.email'] }}" class="hover:text-white">{{ $business['business.email'] }}</a></li>
                @if ($business['business.location'])
                    <li>{{ $business['business.location'] }}</li>
                @endif
            </ul>
            @if ($socialLinks)
                <p class="mt-8 text-sm font-semibold text-white">Follow us</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($socialLinks as $network => $url)
                        <li><a href="{{ $url }}" class="text-brand-200 hover:text-white" rel="noopener noreferrer" target="_blank">{{ $network }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="border-t border-white/15">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-5 gap-y-2 px-4 py-5 text-xs text-brand-200 sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} {{ $business['business.name'] }}. All rights reserved.</p>
            <a href="{{ route('pages.show', 'terms') }}" class="hover:text-white">Terms</a>
            <a href="{{ route('pages.show', 'privacy') }}" class="hover:text-white">Privacy</a>
            <a href="{{ route('pages.show', 'cancellation-policy') }}" class="hover:text-white">Cancellation</a>
        </div>
    </div>
</footer>
