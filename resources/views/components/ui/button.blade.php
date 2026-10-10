@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit'])

@php
    $variantClasses = match ($variant) {
        'secondary' => 'border border-brand-200 bg-white text-brand-800 hover:border-brand-300 hover:bg-brand-50',
        'ghost' => 'text-ink-700 hover:bg-slate-100',
        'danger' => 'bg-red-700 text-white hover:bg-red-800',
        default => 'bg-brand-600 text-white shadow-sm hover:bg-brand-800',
    };

    $sizeClasses = match ($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
        default => 'px-4 py-2.5 text-sm',
    };

    $classes = "inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60 {$variantClasses} {$sizeClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
