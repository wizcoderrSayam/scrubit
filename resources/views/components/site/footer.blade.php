@props(['business'])

@php
    $phone = $business['business.phone'];
    $socialLinks = array_filter([
        'Facebook' => $business['social.facebook'] ?? null,
        'Instagram' => $business['social.instagram'] ?? null,
        'YouTube' => $business['social.youtube'] ?? null,
    ]);
@endphp

<footer class="mt-auto bg-ink-900 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div class="space-y-3">
            <p class="font-display text-lg font-semibold text-white">{{ $business['business.name'] }}</p>
            <p class="text-sm leading-relaxed">
                {{ $business['business.name'] }} is a professional cleaning company committed to delivering reliable, high-quality cleaning services for homes and businesses.
            </p>
        </div>
        <div>
            <p class="font-display text-sm font-semibold uppercase tracking-wide text-white">Contact</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hover:text-white">{{ $phone }}</a></li>
                <li><a href="mailto:{{ $business['business.email'] }}" class="hover:text-white">{{ $business['business.email'] }}</a></li>
                @if ($business['business.location'])
                    <li>{{ $business['business.location'] }}</li>
                @endif
            </ul>
        </div>
        <div>
            <p class="font-display text-sm font-semibold uppercase tracking-wide text-white">Explore</p>
            <ul class="mt-3 grid grid-cols-2 gap-2 text-sm">
                <li><a href="{{ route('services.index') }}" class="hover:text-white">Services</a></li>
                <li><a href="{{ route('quote.create') }}" class="hover:text-white">Request a quote</a></li>
                <li><a href="{{ route('about') }}" class="hover:text-white">About</a></li>
                <li><a href="{{ route('faq') }}" class="hover:text-white">FAQ</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Contact</a></li>
                <li><a href="{{ route('pages.show', 'terms') }}" class="hover:text-white">Terms</a></li>
                <li><a href="{{ route('pages.show', 'privacy') }}" class="hover:text-white">Privacy</a></li>
                <li><a href="{{ route('pages.show', 'cancellation-policy') }}" class="hover:text-white">Cancellation policy</a></li>
            </ul>
        </div>
        @if ($socialLinks)
            <div>
                <p class="font-display text-sm font-semibold uppercase tracking-wide text-white">Follow us</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($socialLinks as $network => $url)
                        <li><a href="{{ $url }}" class="hover:text-white" rel="noopener noreferrer" target="_blank">{{ $network }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    <div class="border-t border-white/10">
        <p class="mx-auto max-w-7xl px-4 py-5 text-xs text-slate-400 sm:px-6 lg:px-8">
            &copy; {{ now()->year }} {{ $business['business.name'] }}. All rights reserved.
        </p>
    </div>
</footer>
