@props(['name', 'label', 'checked' => false, 'value' => '1', 'hint' => null])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name).($value !== '1' ? '_'.$value : ''));
    $isChecked = old() ? (bool) old($name) : (bool) $checked;
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="inline-flex items-start gap-2.5 text-sm text-ink-900">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @checked($isChecked)
            class="mt-0.5 size-4 rounded border-line text-brand-600 focus:outline-brand-600" {{ $attributes->except(['class', 'id']) }}>
        <span>
            {{ $label }}
            @if ($hint)
                <span class="block text-xs text-ink-500">{{ $hint }}</span>
            @endif
        </span>
    </label>
    @error($name)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
