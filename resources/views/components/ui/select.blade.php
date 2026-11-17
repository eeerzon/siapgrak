@props([
    'name',
    'label' => null,
    // See x-ui.input for why this exists — a page rendering multiple forms
    // for the same field name (F7 Categories, F8 Schedule) needs the
    // old()/error lookup key decoupled from the real submitted field name.
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

    <select
        id="{{ $inputId }}"
        name="{{ $name }}"
        {{ $attributes->except(['id'])->merge([
            'class' => 'block w-full rounded-md border py-2.5 pr-10 pl-3.5 shadow-sm text-sm focus:ring-slate-500 '
                .($errorMessage ? 'border-red-400 focus:border-red-500' : 'border-slate-300 focus:border-slate-500')
                .($label ? ' mt-1' : ''),
        ]) }}
    >
        {{ $slot }}
    </select>

    @if ($errorMessage)
        <p class="mt-1 text-sm text-red-600">{{ $errorMessage }}</p>
    @endif
</div>
