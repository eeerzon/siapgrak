@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white'.($padding ? ' p-4 sm:p-6' : '')]) }}>
    {{ $slot }}
</div>
