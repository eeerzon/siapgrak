<div class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200 text-sm font-medium">
    @foreach ([
        ['Info Lomba', 'dashboard.content.competitions.index', 'dashboard.content.competitions.*', null],
        ['Agenda', 'dashboard.content.agenda.index', 'dashboard.content.agenda.*', null],
        ['Prestasi & Kabar', 'dashboard.content.community.index', 'dashboard.content.community.*', 'Kabar'],
        ['Juri', 'dashboard.content.judges.index', 'dashboard.content.judges.*', null],
    ] as [$label, $route, $pattern, $short])
        <a href="{{ route($route) }}"
           class="-mb-px shrink-0 whitespace-nowrap border-b-2 px-2.5 py-2 sm:px-3 {{ request()->routeIs($pattern) ? 'border-brand-600 text-navy-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            {{-- Shorter label on phones so all four tabs fit without scrolling. --}}
            @if ($short)
                <span class="sm:hidden">{{ $short }}</span><span class="hidden sm:inline">{{ $label }}</span>
            @else
                {{ $label }}
            @endif
        </a>
    @endforeach
</div>
