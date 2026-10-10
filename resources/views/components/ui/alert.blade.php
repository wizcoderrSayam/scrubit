@props(['status' => null, 'type' => 'success'])

@php
    $messages = [
        'profile-information-updated' => 'Your profile has been updated.',
        'password-updated' => 'Your password has been updated.',
        'verification-link-sent' => 'A new verification link has been sent to your email address.',
        'settings-saved' => 'Settings saved.',
    ];

    $message = $messages[$status] ?? $status;

    $typeClasses = match ($type) {
        'error' => 'border-red-200 bg-red-50 text-red-800',
        'info' => 'border-brand-200 bg-brand-50 text-brand-900',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-800',
    };
@endphp

<div role="status" {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 text-sm {$typeClasses}"]) }}>
    {{ $message ?? $slot }}
</div>
