{{-- Basic info only; the full poster details live on the detail page. --}}
<a href="{{ route('site.competitions.show', $competition->slug) }}"
   class="group flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <x-site.cover :url="$competition->coverUrl()" ratio="aspect-[4/5]" />
    <div class="flex flex-1 flex-col p-3 sm:p-4">
        <p class="text-[11px] font-semibold text-brand-700 sm:text-xs">
            <x-site.date-range :start="$competition->start_date" :end="$competition->end_date" />
        </p>
        <h3 class="mt-1 line-clamp-3 text-sm leading-snug font-semibold text-navy-900 group-hover:text-brand-700 sm:text-base" title="{{ $competition->title }}">{{ $competition->title }}</h3>
        <p class="mt-1 truncate text-xs text-slate-500 sm:text-sm">
            {{ collect([$competition->location, $competition->province])->filter()->unique()->implode(', ') ?: 'Lokasi belum diumumkan' }}
        </p>

        @if ($competition->levels)
            <div class="mt-2 flex flex-wrap gap-1">
                @foreach ($competition->levelLabels() as $levelLabel)
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600 sm:text-xs">{{ $levelLabel }}</span>
                @endforeach
            </div>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1.5 pt-3 text-[11px] sm:text-xs">
            @if ($competition->isRegistrationOpen())
                <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ $competition->registration_deadline ? 'Buka s.d. '.$competition->registration_deadline->locale('id')->translatedFormat('j M') : 'Pendaftaran buka' }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 text-slate-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                    Pendaftaran tutup
                </span>
            @endif
            @if ($competition->is_managed)
                <span class="rounded-full bg-navy-50 px-2 py-0.5 font-medium whitespace-nowrap text-navy-700">Dikelola Siapgrak</span>
            @endif
        </div>
    </div>
</a>
