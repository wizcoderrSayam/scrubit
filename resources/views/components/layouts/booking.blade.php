@props(['title' => null, 'step' => 1])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['business' => $business, 'title' => $title, 'description' => null, 'canonical' => null, 'noindex' => true])
    </head>
    <body class="flex min-h-screen flex-col bg-brand-50">
        <x-site.header :business="$business" />

        <div class="border-b border-brand-100 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6">
                <x-booking.progress :current="$step" />
            </div>
        </div>

        <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
            @if (session('status'))
                <x-ui.alert :status="session('status')" type="info" class="mb-6" />
            @endif
            <x-ui.errors class="mb-6" />
            {{ $slot }}
        </main>

        <x-site.footer :business="$business" />
    </body>
</html>
