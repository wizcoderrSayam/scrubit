@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit'])

@php
    $isAdmin = request()->routeIs('admin.*');

    $variantClasses = match ($variant) {
        'secondary' => 'border border-brand-200 bg-white text-brand-800 hover:border-brand-300 hover:bg-brand-50',
        'ghost' => $isAdmin ? 'text-ink-700 hover:bg-slate-100' : 'text-ink-700 hover:bg-brand-50',
        'danger' => 'bg-red-700 text-white hover:bg-red-800',
        default => 'bg-brand-600 text-white shadow-sm hover:bg-brand-800'.($isAdmin ? '' : ' hover:-translate-y-0.5'),
    };

    $sizeClasses = match (true) {
        $isAdmin && $size === 'sm' => 'px-3 py-1.5 text-sm',
        $isAdmin && $size === 'lg' => 'px-6 py-3 text-base',
        $isAdmin => 'px-4 py-2.5 text-sm',
        $size === 'sm' => 'px-4 py-2 text-sm',
        $size === 'lg' => 'px-7 py-3.5 text-base',
        default => 'px-5 py-3 text-sm',
    };

    $shapeClass = $isAdmin ? 'rounded-lg' : 'rounded-full';
    $transitionClass = $isAdmin ? 'transition-colors' : 'transition motion-reduce:transform-none';

    $classes = "inline-flex items-center justify-center gap-2 {$shapeClass} font-semibold {$transitionClass} disabled:cursor-not-allowed disabled:opacity-60 {$variantClasses} {$sizeClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
