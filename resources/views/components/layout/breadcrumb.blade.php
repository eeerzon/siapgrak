{{--
    Usage: <x-layout.breadcrumb :items="[['label' => 'Teams', 'href' => route('dashboard.teams.index')], ['label' => $team->name]]" />
    Last item (or any item without 'href') renders as the current page, not a link.
--}}
@props(['items' => []])

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'mb-3 flex flex-wrap items-center gap-1.5 text-sm text-slate-500']) }}>
    @foreach ($items as $item)
        @if (!$loop->last && ($item['href'] ?? null))
            <a href="{{ $item['href'] }}" class="hover:text-slate-700 hover:underline">{{ $item['label'] }}</a>
            <svg class="h-3.5 w-3.5 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        @else
            <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
