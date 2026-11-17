@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
])

@php
    $variants = [
        'primary' => 'bg-navy-900 text-white hover:bg-navy-800 focus-visible:outline-navy-600',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:outline-slate-400',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-600',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus-visible:outline-slate-400',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
    ];

    $base = 'inline-flex items-center justify-center gap-2 rounded-md font-medium transition-colors duration-150 '
        .'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 '
        .'disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer';

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
