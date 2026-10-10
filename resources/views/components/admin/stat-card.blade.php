@props(['label', 'value', 'href' => null, 'tone' => 1])

@php
    $toneClass = [
        1 => 'text-admin-stat-1',
        2 => 'text-admin-stat-2',
        3 => 'text-admin-stat-3',
        4 => 'text-admin-stat-4',
        5 => 'text-admin-stat-5',
        6 => 'text-admin-stat-6',
    ][$tone] ?? 'text-admin-stat-1';
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'block rounded-xl border border-slate-200 bg-white px-5 py-5 transition-colors'.($href ? ' hover:border-slate-300' : '')]) }}>
    <p class="text-sm text-ink-500">{{ $label }}</p>
    <p class="mt-2 font-admin-display text-3xl font-bold {{ $toneClass }}">{{ $value }}</p>
    @isset($footer)
        <div class="mt-2 text-xs text-ink-500">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
