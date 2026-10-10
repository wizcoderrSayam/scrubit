@props(['status' => null])

@php
    $classes = $status instanceof \BackedEnum && method_exists($status, 'badgeClasses') ? $status->badgeClasses() : 'bg-slate-100 text-slate-700';
    $label = $status instanceof \BackedEnum && method_exists($status, 'label') ? $status->label() : null;
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {$classes}"]) }}>
    {{ $label ?? $slot }}
</span>
