@props(['title', 'heading' => null, 'subtitle' => null])

@php
    // Sections reached through a tab bar inside Bookings and Settings rather than the sidebar.
    $bookingTabs = [
        ['label' => 'Bookings', 'route' => 'admin.bookings.index', 'active' => ['admin.bookings.*']],
        ['label' => 'Quotes', 'route' => 'admin.quotes.index', 'active' => ['admin.quotes.*']],
        ['label' => 'Payments', 'route' => 'admin.payments.index', 'active' => ['admin.payments.*']],
    ];
    $settingsTabs = [
        ['label' => 'Business', 'route' => 'admin.settings.business.edit', 'active' => ['admin.settings.business.*']],
        ['label' => 'Tax rates', 'route' => 'admin.settings.tax.index', 'active' => ['admin.settings.tax.*']],
        ['label' => 'Availability', 'route' => 'admin.settings.availability.edit', 'active' => ['admin.settings.availability.*']],
        ['label' => 'Payments', 'route' => 'admin.settings.payments', 'active' => ['admin.settings.payments']],
        ['label' => 'Add-ons', 'route' => 'admin.addons.index', 'active' => ['admin.addons.*']],
        ['label' => 'Homepage', 'route' => 'admin.content.home.edit', 'active' => ['admin.content.home.*']],
        ['label' => 'Pages', 'route' => 'admin.pages.index', 'active' => ['admin.pages.*']],
        ['label' => 'FAQs', 'route' => 'admin.faqs.index', 'active' => ['admin.faqs.*']],
        ['label' => 'Testimonials', 'route' => 'admin.testimonials.index', 'active' => ['admin.testimonials.*']],
    ];

    $patterns = fn (array $tabs) => array_merge(...array_column($tabs, 'active'));

    // Mirrors the Elephant sidebar: Orders → Bookings, Products → Services, Contact Messages → Enquiries, Users → Customers.
    $navigation = [[
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => ['admin.dashboard']],
        ['label' => 'Bookings', 'route' => 'admin.bookings.index', 'active' => $patterns($bookingTabs)],
        ['label' => 'Services', 'route' => 'admin.services.index', 'active' => ['admin.services.*']],
        ['label' => 'Categories', 'route' => 'admin.categories.index', 'active' => ['admin.categories.*']],
        ['label' => 'Enquiries', 'route' => 'admin.enquiries.index', 'active' => ['admin.enquiries.*']],
        ['label' => 'Email templates', 'route' => 'admin.email-templates.index', 'active' => ['admin.email-templates.*']],
        ['label' => 'Settings', 'route' => 'admin.settings.business.edit', 'active' => $patterns($settingsTabs)],
        ['label' => 'Customers', 'route' => 'admin.customers.index', 'active' => ['admin.customers.*']],
    ]];

    $tabs = match (true) {
        request()->routeIs(...$patterns($bookingTabs)) => $bookingTabs,
        request()->routeIs(...$patterns($settingsTabs)) => $settingsTabs,
        default => [],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['business' => $business, 'title' => $title.' | Admin', 'description' => null, 'canonical' => null, 'noindex' => true])
    </head>
    <body class="admin-scope min-h-screen bg-admin-canvas"
        x-data="{ sidebarOpen: false, openNav() { this.sidebarOpen = true; this.$nextTick(() => this.$refs.closeNav.focus()); }, closeNav() { this.sidebarOpen = false; this.$nextTick(() => this.$refs.openNav.focus()); } }"
        x-on:keydown.escape.window="if (sidebarOpen) closeNav()">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
            Skip to content
        </a>

        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-ink-900/50 lg:hidden" x-on:click="closeNav()"></div>

        <aside
            class="invisible fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-admin-sidebar-from to-admin-sidebar-to text-white transition-transform lg:visible lg:translate-x-0"
            x-bind:class="{ 'visible translate-x-0': sidebarOpen, 'invisible -translate-x-full': ! sidebarOpen }"
            id="admin-sidebar"
            aria-label="Admin navigation"
        >
            <div class="flex items-start justify-between px-5 pb-6 pt-6">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-md focus-visible:outline-white">
                    <span class="block font-admin-display text-2xl font-bold leading-none text-white">{{ $business['business.name'] }}</span>
                    <span class="mt-2 block text-xs font-semibold uppercase tracking-[0.18em] text-teal-300">Admin panel</span>
                </a>
                <button type="button" class="rounded-md p-1 text-white/80 hover:text-white lg:hidden" x-ref="closeNav" x-on:click="closeNav()" aria-label="Close navigation">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-4 text-sm" aria-label="Admin sections">
                @foreach ($navigation as $items)
                    <div class="contents">
                        @foreach ($items as $item)
                            @php($isActive = request()->routeIs(...$item['active']))
                            <a href="{{ route($item['route']) }}"
                                @class([
                                    'block rounded-lg px-3.5 py-2.5 transition-colors focus-visible:outline-white',
                                    'bg-white/12 font-medium text-white' => $isActive,
                                    'text-white/85 hover:bg-white/8 hover:text-white' => ! $isActive,
                                ])
                                @if ($isActive) aria-current="page" @endif
                            >{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                @endforeach

                <a href="{{ route('home') }}" target="_blank" class="mt-2 block rounded-lg px-3.5 py-2.5 text-white/85 hover:bg-white/8 hover:text-white focus-visible:outline-white">
                    View website <span aria-hidden="true">↗</span>
                </a>
            </nav>

            <div class="px-3 pb-6">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full rounded-lg px-3.5 py-2.5 text-left text-sm text-white/85 hover:bg-white/8 hover:text-white focus-visible:outline-white">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex items-center gap-4 border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                <button type="button" class="rounded-md p-2 text-ink-700 lg:hidden" x-ref="openNav" x-on:click="openNav()" aria-controls="admin-sidebar" aria-label="Open navigation" x-bind:aria-expanded="sidebarOpen.toString()" aria-expanded="false">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
                <div class="min-w-0">
                    <h1 class="truncate font-admin-display text-2xl font-bold text-ink-900">{{ $heading ?? $title }}</h1>
                    <p class="truncate text-sm text-ink-500">{{ $subtitle ?? $business['business.name'].' admin' }}</p>
                </div>
                <span class="ml-auto hidden truncate text-sm text-ink-700 sm:block">{{ auth()->user()->name }}</span>
            </header>

            <main id="main" class="p-4 sm:p-6 lg:p-8">
                @if ($tabs)
                    <nav class="-mx-1 mb-6 flex gap-1 overflow-x-auto border-b border-slate-200 px-1" aria-label="Section">
                        @foreach ($tabs as $tab)
                            @php($tabActive = request()->routeIs(...$tab['active']))
                            <a href="{{ route($tab['route']) }}"
                                @class([
                                    'whitespace-nowrap border-b-2 px-3 pb-2.5 pt-1 text-sm font-medium',
                                    'border-admin-accent text-admin-accent' => $tabActive,
                                    'border-transparent text-ink-500 hover:text-ink-900' => ! $tabActive,
                                ])
                                @if ($tabActive) aria-current="page" @endif>{{ $tab['label'] }}</a>
                        @endforeach
                    </nav>
                @endif

                @if (session('status'))
                    <x-ui.alert :status="session('status')" class="mb-6" />
                @endif
                <x-ui.errors class="mb-6" />

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
