@props(['alt' => 'Scrubit'])

@php($customLogo = app(\App\Services\SettingsService::class)->brandImageUrl('logo'))

@if ($customLogo)
    <img
        src="{{ $customLogo }}"
        alt="{{ $alt }}"
        {{ $attributes->merge(['class' => 'h-10 w-auto']) }}
    >
@else
    <img
        src="{{ asset('images/brand/logo.webp') }}"
        srcset="{{ asset('images/brand/logo.webp') }} 1x, {{ asset('images/brand/logo@2x.webp') }} 2x"
        width="480"
        height="163"
        alt="{{ $alt }}"
        {{ $attributes->merge(['class' => 'h-10 w-auto']) }}
    >
@endif
