@php($startAt = $agendaItem->start_at->copy()->locale('id'))
<a href="{{ route('site.agenda.show', $agendaItem->slug) }}"
   class="group flex min-w-0 items-center gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="w-14 shrink-0 overflow-hidden rounded-md border border-slate-200 text-center">
        <p class="bg-brand-600 py-0.5 text-[11px] font-semibold uppercase text-white">{{ $startAt->translatedFormat('M') }}</p>
        <p class="py-1.5 text-xl font-bold leading-none text-navy-900">{{ $startAt->translatedFormat('j') }}</p>
    </div>
    <div class="min-w-0">
        <h3 class="truncate font-semibold text-navy-900 group-hover:text-brand-700">{{ $agendaItem->title }}</h3>
        <p class="mt-0.5 truncate text-sm text-slate-500">
            {{ collect([$startAt->translatedFormat('l, H.i').' WIB', $agendaItem->location])->filter()->implode(' · ') }}
        </p>
    </div>
</a>
