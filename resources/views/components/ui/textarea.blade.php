@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 3,
])

@php
    $inputId = $attributes->get('id', $name);
    $errorMessage = $errors->first($name);
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif

    <textarea
        id="{{ $inputId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->except(['id'])->merge([
            'class' => 'block w-full rounded-md border shadow-sm text-sm focus:ring-slate-500 '
                .($errorMessage ? 'border-red-400 focus:border-red-500' : 'border-slate-300 focus:border-slate-500')
                .($label ? ' mt-1' : ''),
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($errorMessage)
        <p class="mt-1 text-sm text-red-600">{{ $errorMessage }}</p>
    @endif
</div>
