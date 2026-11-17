{{-- $tabs: list of [label, url, isActive]. --}}
@props(['tabs'])

<div class="mt-4 flex flex-wrap gap-2 text-sm font-medium">
    @foreach ($tabs as [$label, $url, $isActive])
        <a href="{{ $url }}"
           @if ($isActive) aria-current="page" @endif
           class="rounded-full border px-3.5 py-1.5 {{ $isActive ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
