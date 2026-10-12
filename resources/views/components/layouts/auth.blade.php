@props(['title', 'heading', 'subheading' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['business' => $business, 'title' => $title, 'description' => null, 'canonical' => null, 'noindex' => true])
    </head>
    <body class="flex min-h-screen flex-col bg-brand-50">
        <main id="main" class="flex flex-1 flex-col items-center justify-center px-4 py-12">
            <a href="{{ route('home') }}" class="mb-8">
                <x-site.logo class="h-12 w-auto" :alt="$business['business.name'].' home'" />
            </a>

            <div class="w-full max-w-md rounded-[22px] border border-brand-100 bg-white p-6 sm:p-8">
                <h1 class="text-2xl font-extrabold tracking-tight">{{ $heading }}</h1>
                @if ($subheading)
                    <p class="mt-2 text-sm text-ink-500">{{ $subheading }}</p>
                @endif

                @if (session('status'))
                    <x-ui.alert :status="session('status')" class="mt-6" />
                @endif

                <div class="mt-6">
                    {{ $slot }}
                </div>
            </div>

            @isset($footer)
                <div class="mt-6 text-center text-sm text-ink-700">
                    {{ $footer }}
                </div>
            @endisset
        </main>
    </body>
</html>
