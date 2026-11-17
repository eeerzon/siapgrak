<div class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200 text-sm font-medium">
    @foreach ([
        ['Info Lomba', 'dashboard.content.competitions.index', 'dashboard.content.competitions.*'],
        ['Agenda', 'dashboard.content.agenda.index', 'dashboard.content.agenda.*'],
        ['Prestasi & Kabar', 'dashboard.content.community.index', 'dashboard.content.community.*'],
        ['Juri', 'dashboard.content.judges.index', 'dashboard.content.judges.*'],
    ] as [$label, $route, $pattern])
        <a href="{{ route($route) }}"
           class="-mb-px whitespace-nowrap border-b-2 px-3 py-2 {{ request()->routeIs($pattern) ? 'border-brand-600 text-navy-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
