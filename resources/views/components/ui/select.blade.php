@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $error = $errors->first($name);
    $selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-900">
        {{ $label }}
        @if ($attributes->has('required'))
            <span class="text-red-700" aria-hidden="true">*</span>
        @endif
    </label>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['class', 'id'])->class([
            'block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-ink-900 focus:outline-2 focus:outline-offset-0 focus:outline-brand-600',
            'border-red-600' => $error,
            'border-line' => ! $error,
        ]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($hint)
        <p class="text-xs text-ink-500">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="text-sm text-red-700">{{ $error }}</p>
    @endif
</div>
