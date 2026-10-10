@props(['business'])

@php
    $phone = $business['business.phone'];
    $phoneHref = 'tel:'.preg_replace('/[^0-9+]/', '', $phone);
    $links = [
        ['label' => 'Home', 'route' => 'home', 'active' => 'home'],
        ['label' => 'About', 'route' => 'about', 'active' => 'about'],
    ];
    $servicesActive = request()->routeIs('services.*');
@endphp

<div class="sticky top-0 z-40">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
        Skip to content
    </a>

    <div class="border-b border-brand-100 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-end gap-x-5 gap-y-1 px-4 py-2 text-xs text-ink-500 sm:px-6 lg:px-8">
            <a href="{{ $phoneHref }}" class="font-medium text-ink-700 hover:text-brand-800">{{ $phone }}</a>
            <span class="text-brand-200" aria-hidden="true">/</span>
            <a href="mailto:{{ $business['business.email'] }}" class="hover:text-brand-800">{{ $business['business.email'] }}</a>
        </div>
    </div>

    <header x-data="{ open: false }" class="border-b border-brand-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="shrink-0">
                <x-site.logo class="h-9 w-auto sm:h-11" :alt="$business['business.name'].' home'" />
            </a>

            <nav class="hidden items-center gap-6 text-sm font-medium text-ink-700 lg:flex" aria-label="Main">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" @class(['hover:text-brand-700', 'text-brand-700' => request()->routeIs($link['active'])])
                        @if (request()->routeIs($link['active'])) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach

                <div class="relative" x-data="{ servicesOpen: false }" x-on:click.outside="servicesOpen = false" x-on:keydown.escape="servicesOpen = false">
                    <button type="button" class="inline-flex items-center gap-1 hover:text-brand-700" x-bind:class="{ 'text-brand-700': servicesOpen || {{ $servicesActive ? 'true' : 'false' }} }"
                        x-on:click="servicesOpen = ! servicesOpen" x-bind:aria-expanded="servicesOpen.toString()" aria-controls="services-menu">
                        Services
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                    </button>
                    <div id="services-menu" x-show="servicesOpen" x-cloak
                        class="absolute left-0 top-full z-50 mt-3 max-h-80 w-72 overflow-y-auto rounded-2xl border border-brand-100 bg-white p-2 shadow-lg">
                        @foreach ($services as $service)
                            <a href="{{ route('services.show', $service) }}" class="block px-4 py-2 text-ink-700 hover:bg-brand-50 hover:text-brand-800">{{ $service->name }}</a>
                        @endforeach
                        <a href="{{ route('services.index') }}" class="mt-1 block border-t border-brand-100 px-4 py-2 font-semibold text-brand-800 hover:bg-brand-50">View all services</a>
                    </div>
                </div>

                <a href="{{ route('contact') }}" @class(['hover:text-brand-700', 'text-brand-700' => request()->routeIs('contact')])
                    @if (request()->routeIs('contact')) aria-current="page" @endif>Contact</a>
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                @auth
                    @can('admin')
                        <x-ui.button :href="route('admin.dashboard')" variant="secondary" size="sm">Admin</x-ui.button>
                    @endcan
                    <x-ui.button :href="route('account.bookings')" variant="secondary" size="sm">My bookings</x-ui.button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button variant="ghost" size="sm">Log out</x-ui.button>
                    </form>
                @else
                    <x-ui.button :href="route('login')" variant="ghost" size="sm">Log in</x-ui.button>
                    <x-ui.button :href="route('services.index')" size="sm">Book a cleaning</x-ui.button>
                @endauth
            </div>

            <button type="button" class="inline-flex items-center justify-center rounded-md p-2 text-ink-700 lg:hidden"
                x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-controls="mobile-menu">
                <span class="sr-only">Toggle menu</span>
                <svg x-show="! open" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                <svg x-show="open" x-cloak class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div id="mobile-menu" x-show="open" x-cloak class="border-t border-brand-100 bg-white lg:hidden">
            <nav class="flex flex-col gap-1 px-4 py-4 text-base font-medium text-ink-700" aria-label="Mobile">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" class="rounded-md px-3 py-2 hover:bg-brand-50">{{ $link['label'] }}</a>
                @endforeach
                <div x-data="{ servicesOpen: false }">
                    <button type="button" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left hover:bg-brand-50"
                        x-on:click="servicesOpen = ! servicesOpen" x-bind:aria-expanded="servicesOpen.toString()" aria-controls="mobile-services-menu">
                        Services
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                    </button>
                    <div id="mobile-services-menu" x-show="servicesOpen" x-cloak class="mt-1 space-y-1 pl-3">
                        @foreach ($services as $service)
                            <a href="{{ route('services.show', $service) }}" class="block rounded-md px-3 py-2 text-sm hover:bg-brand-50">{{ $service->name }}</a>
                        @endforeach
                        <a href="{{ route('services.index') }}" class="block rounded-md px-3 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-50">View all services</a>
                    </div>
                </div>
                <a href="{{ route('contact') }}" class="rounded-md px-3 py-2 hover:bg-brand-50">Contact</a>
                @auth
                    @can('admin')
                        <a href="{{ route('admin.dashboard') }}" class="rounded-md px-3 py-2 hover:bg-brand-50">Admin</a>
                    @endcan
                    <a href="{{ route('account.bookings') }}" class="rounded-md px-3 py-2 hover:bg-brand-50">My bookings</a>
                    <a href="{{ route('account') }}" class="rounded-md px-3 py-2 hover:bg-brand-50">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-md px-3 py-2 text-left hover:bg-brand-50">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-2 hover:bg-brand-50">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-md px-3 py-2 hover:bg-brand-50">Create account</a>
                @endauth
            </nav>
        </div>
    </header>
</div>
