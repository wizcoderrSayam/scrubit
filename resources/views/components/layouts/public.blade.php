@props(['title' => null, 'description' => null, 'canonical' => null, 'noindex' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', compact('business', 'title', 'description', 'canonical', 'noindex'))
    </head>
    <body class="flex min-h-screen flex-col">
        <x-site.marketing-header :business="$business" />

        <main id="main" class="flex-1">
            @if (session('status') || $errors->has('booking'))
                <div class="mx-auto max-w-7xl space-y-3 px-4 pt-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <x-ui.alert :status="session('status')" />
                    @endif
                    <x-ui.errors />
                </div>
            @endif

            {{ $slot }}
        </main>

        <x-site.marketing-footer :business="$business" />
    </body>
</html>
