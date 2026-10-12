@php
    $siteName = $business['business.name'] ?? config('app.name');
    $fullTitle = $title ? $title.' | '.$siteName : $siteName.' | '.($business['business.tagline'] ?? '');
    $canonicalUrl = $canonical ?? url()->current();
    $brandSettings = app(\App\Services\SettingsService::class);
    $logoUrl = $brandSettings->brandImageUrl('logo');
    $faviconUrl = $brandSettings->brandImageUrl('favicon');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $fullTitle }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $logoUrl ?? asset('images/brand/logo@2x.webp') }}">
@endif
@if ($faviconUrl)
    <link rel="icon" type="image/webp" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
@else
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/brand/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
@endif
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
