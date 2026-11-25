{{--
    Dropdown with a search box inside (Alpine, see resources/js/combobox.js).
    $options: [value => label]. The first entry is usually the "empty" choice, e.g. ['' => 'Semua provinsi'].
    Submits like a normal field through a hidden input named $name.
--}}
@props([
    'name',
    'options' => [],
    'value' => '',
    'label' => null,
    // Screen-reader label when no visible label is shown.
    'srLabel' => null,
    'searchable' => true,
    'searchPlaceholder' => 'Cari...',
    'emptyText' => 'Tidak ada yang cocok.',
    'errorKey' => null,
])

@php
    // With a visible label it sits in a dashboard form, so it matches x-ui.input's height.
    $inputId = $attributes->get('id', $name);
    $errorMessage = $errors->first($errorKey ?? $name);
    $items = collect($options)->map(fn ($text, $key) => ['value' => (string) $key, 'label' => (string) $text])->values();
    $current = (string) ($value ?? '');
    $currentLabel = $items->firstWhere('value', $current)['label'] ?? $items->first()['label'] ?? '';
@endphp

<div {{ $attributes->except(['id'])->merge(['class' => 'relative']) }}
     x-data="combobox({ options: @js($items), value: @js($current), searchable: @js((bool) $searchable) })"
     @click.outside="open && close()"
     @keydown.escape.prevent.stop="open && close(true)">
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @else
        <label for="{{ $inputId }}" class="sr-only">{{ $srLabel ?? $name }}</label>
    @endif

    <input type="hidden" name="{{ $name }}" value="{{ $current }}" x-ref="input" :value="value">

    <button type="button" id="{{ $inputId }}" x-ref="button"
            @click="toggle()" @keydown.arrow-down.prevent="show()"
            aria-haspopup="listbox" :aria-expanded="open.toString()"
            class="{{ $label ? 'mt-1 py-2.5 pl-3.5 shadow-sm' : 'py-2 pl-3' }} flex w-full cursor-pointer items-center justify-between gap-2 rounded-md border bg-white pr-3 text-left text-sm focus:ring-2 focus:ring-slate-300 focus:outline-none {{ $errorMessage ? 'border-red-400' : 'border-slate-300 hover:border-slate-400' }}">
        <span class="truncate" :class="value === '' && 'text-slate-500'" x-text="selected?.label">{{ $currentLabel }}</span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition duration-100 ease-out" x-transition:enter-start="-translate-y-1 opacity-0"
         class="absolute inset-x-0 z-40 mt-1 min-w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
        <template x-if="searchable">
            <div class="relative border-b border-slate-100 p-2">
                <svg class="pointer-events-none absolute top-1/2 left-4.5 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
                </svg>
                <input type="text" x-ref="search" x-model="query" @input="active = 0"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter.prevent="choose(filtered[active])" @keydown.tab="close()"
                       placeholder="{{ $searchPlaceholder }}" autocomplete="off" aria-label="{{ $searchPlaceholder }}"
                       class="block w-full rounded-md border border-slate-200 bg-slate-50 py-1.5 pr-2 pl-8 text-sm focus:border-slate-400 focus:bg-white">
            </div>
        </template>

        <ul x-ref="list" role="listbox" tabindex="-1" class="max-h-64 overflow-y-auto py-1 focus:outline-none"
            @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="choose(filtered[active])" @keydown.tab="close()">
            <template x-for="(option, index) in filtered" :key="option.value">
                <li role="option" :data-index="index" :aria-selected="(option.value === value).toString()"
                    @click="choose(option)" @mousemove="active = index"
                    class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm"
                    :class="index === active ? 'bg-slate-100 text-navy-900' : 'text-slate-700'">
                    <span x-text="option.label"></span>
                    <svg x-show="option.value === value" class="h-4 w-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                    </svg>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-3 text-center text-sm text-slate-500">{{ $emptyText }}</li>
        </ul>
    </div>

    @if ($errorMessage)
        <p class="mt-1 text-sm text-red-600">{{ $errorMessage }}</p>
    @endif
</div>
