@props([
    'title' => 'Gagal memuat data',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-red-200 bg-red-50 px-6 py-12 text-center']) }}>
    <p class="text-base font-medium text-red-800">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-red-600">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
