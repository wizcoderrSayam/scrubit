@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'bag' => 'default',
    'prefix' => null,
])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $error = $errors->getBag($bag)->first($name);
    $currentValue = $type === 'password' ? null : old($name, $value);
    $describedBy = trim(($hint ? "{$id}-hint " : '').($error ? "{$id}-error" : ''));
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-900">
        {{ $label }}
        @if ($attributes->has('required'))
            <span class="text-red-700" aria-hidden="true">*</span>
        @endif
    </label>

    <div @class(['flex items-stretch' => $prefix])>
        @if ($prefix)
            <span class="inline-flex items-center rounded-l-xl border border-r-0 border-line bg-slate-50 px-3 text-sm text-ink-500">{{ $prefix }}</span>
        @endif
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($currentValue !== null) value="{{ $currentValue }}" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->except(['class', 'id'])->class([
                'block w-full border px-3 py-2.5 text-sm text-ink-900 shadow-xs placeholder:text-ink-500 focus:outline-2 focus:outline-offset-0 focus:outline-brand-600',
                'rounded-xl' => ! $prefix,
                'rounded-r-xl' => $prefix,
                'border-red-600' => $error,
                'border-line focus:border-brand-600' => ! $error,
            ]) }}
        >
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-ink-500">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="text-sm text-red-700">{{ $error }}</p>
    @endif
</div>
