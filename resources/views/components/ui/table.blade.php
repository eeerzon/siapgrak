@props([
    'head' => null,
])

<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-slate-200 text-sm']) }}>
        @if ($head)
            <thead class="bg-slate-50">
                {{ $head }}
            </thead>
        @endif
        <tbody class="divide-y divide-slate-100">
            {{ $slot }}
        </tbody>
    </table>
</div>
