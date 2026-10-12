@props(['name', 'label', 'value' => null, 'hint' => null, 'rows' => 4, 'bag' => 'default'])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $error = $errors->getBag($bag)->first($name);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-900">
        {{ $label }}
        @if ($attributes->has('required'))
            <span class="text-red-700" aria-hidden="true">*</span>
        @endif
    </label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($hint || $error) aria-describedby="{{ trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : '')) }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->except(['class', 'id'])->class([
            'block w-full rounded-xl border px-3 py-2.5 text-sm text-ink-900 shadow-xs focus:outline-2 focus:outline-offset-0 focus:outline-brand-600',
            'border-red-600' => $error,
            'border-line focus:border-brand-600' => ! $error,
        ]) }}
    >{{ old($name, $value) }}</textarea>
    @if ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-ink-500">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="text-sm text-red-700">{{ $error }}</p>
    @endif
</div>
