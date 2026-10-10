<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Booking {{ $booking->reference }} | {{ $business['business.name'] }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white">
    <main class="mx-auto max-w-3xl space-y-6 px-6 py-10">
        <header class="flex items-start justify-between gap-6 border-b border-line pb-6">
            <div>
                <x-site.logo class="h-10 w-auto" :alt="$business['business.name']" />
                <p class="mt-3 text-sm text-ink-700">{{ $business['business.phone'] }} · {{ $business['business.email'] }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-ink-500">Booking reference</p>
                <p class="font-display text-xl font-bold">{{ $booking->reference }}</p>
                <p class="mt-1 text-sm">{{ $booking->status->label() }}</p>
            </div>
        </header>

        @include('bookings.partials.details')

        <p class="text-xs text-ink-500">Printed {{ now()->format('F j, Y g:i a') }}</p>
        <button type="button" onclick="window.print()" class="no-print rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Print</button>
    </main>
</body>
</html>
