@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    // For array-style names (e.g. "scores[5][score]") the HTML name attribute
    // must stay bracket notation so PHP parses it into a nested array, but
    // old()/error lookups need dot notation ("scores.5.score") — pass the
    // dotted form here when name isn't a plain field (judge/entries/show.blade.php).
    'errorKey' => null,
])

@php
    $inputId = $attributes->get('id', $name);
    $lookupKey = $errorKey ?? $name;
    $errorMessage = $errors->first($lookupKey);
@endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-red-600">*</span>
            @endif
        </label>
    @endif

    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($lookupKey, $value) }}"
        {{ $attributes->except(['id'])->merge([
            'class' => 'block w-full rounded-md border shadow-sm text-sm focus:ring-slate-500 '
                .($errorMessage ? 'border-red-400 focus:border-red-500' : 'border-slate-300 focus:border-slate-500')
                .($label ? ' mt-1' : ''),
        ]) }}
    >

    @if ($errorMessage)
        <p class="mt-1 text-sm text-red-600">{{ $errorMessage }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>
