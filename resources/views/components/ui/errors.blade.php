@if ($errors->has('booking') || $errors->has('category'))
    <div role="alert" {{ $attributes->merge(['class' => 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800']) }}>
        {{ $errors->first('booking') ?: $errors->first('category') }}
    </div>
@endif
