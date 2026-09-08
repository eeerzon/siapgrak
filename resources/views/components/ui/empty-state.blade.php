@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-slate-300 bg-white px-6 py-12 text-center']) }}>
    <p class="text-base font-medium text-slate-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
