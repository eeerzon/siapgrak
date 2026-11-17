@extends('layouts.site')

@section('content')
    <section class="relative overflow-hidden bg-navy-900 text-white">
        {{-- Diagonal red-white stripes, echoing the corner ribbons on siapgrak.id posters. --}}
        <div class="pointer-events-none absolute -right-32 -bottom-16 hidden w-[36rem] -rotate-45 md:block" aria-hidden="true">
            <div class="h-8 bg-brand-600"></div>
            <div class="mt-3 h-3 bg-white/80"></div>
            <div class="mt-3 h-8 bg-brand-600"></div>
        </div>

        @php($featured = $competitions->first(fn ($competition) => $competition->coverUrl()))

        <div class="relative mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 md:px-6 md:py-20 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <div>
                <p class="text-xs font-semibold tracking-[0.2em] text-brand-500 uppercase">Paskibra · LKBB · PBB</p>
                <h1 class="mt-3 max-w-2xl text-3xl leading-tight font-bold tracking-tight md:text-5xl">
                    Cari lomba baris-berbaris, cek jadwalnya, langsung daftar.
                </h1>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-300">
                    Info kejuaraan, batas pendaftaran, sampai kontak panitia dari lomba-lomba yang kami liput di @siapgrak.id.
                </p>

                <form method="GET" action="{{ route('site.competitions.index') }}" class="mt-8 flex max-w-xl gap-2" role="search">
                    <label for="hero-q" class="sr-only">Cari lomba</label>
                    <div class="relative flex-1">
                        <svg class="pointer-events-none absolute top-1/2 left-3.5 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
                        </svg>
                        <input id="hero-q" name="q" type="search" placeholder="Nama lomba, sekolah, atau kota" class="block w-full rounded-md border-0 py-3 pr-3 pl-11 text-slate-900">
                    </div>
                    <button type="submit" class="cursor-pointer rounded-md bg-brand-600 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-700">Cari</button>
                </form>

                <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-4">
                    <div>
                        <dt class="text-sm text-slate-400">Pendaftaran masih buka</dt>
                        <dd class="text-3xl font-bold">{{ $openCompetitionsCount }} <span class="text-base font-medium text-slate-400">lomba</span></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-400">Agenda mendatang</dt>
                        <dd class="text-3xl font-bold">{{ $upcomingAgendaCount }} <span class="text-base font-medium text-slate-400">kegiatan</span></dd>
                    </div>
                </dl>
            </div>

            @if ($featured)
                <a href="{{ route('site.competitions.show', $featured->slug) }}" class="group relative hidden lg:block">
                    <p class="mb-3 text-xs font-semibold tracking-widest text-slate-400 uppercase">Lomba terdekat</p>
                    <img src="{{ $featured->coverUrl() }}" alt="Poster {{ $featured->title }}"
                         class="aspect-[4/5] w-full max-w-sm rotate-2 rounded-lg object-cover shadow-2xl ring-4 ring-white/10 transition group-hover:rotate-0">
                    <p class="mt-4 max-w-sm font-semibold text-white group-hover:underline">{{ $featured->title }}</p>
                    <p class="text-sm text-slate-400"><x-site.date-range :start="$featured->start_date" :end="$featured->end_date" /></p>
                </a>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-6xl space-y-14 px-4 py-12 md:px-6">
        <section id="info-lomba">
            @include('site.partials.section-heading', [
                'title' => 'Info Lomba & Kejuaraan',
                'description' => 'Diurutkan dari tanggal lomba yang paling dekat.',
                'href' => route('site.competitions.index'),
            ])
            @if ($competitions->isEmpty())
                <x-ui.empty-state title="Belum ada lomba yang dijadwalkan." description="Info lomba baru biasanya kami unggah begitu panitia membuka pendaftaran." />
            @else
                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    @foreach ($competitions as $competition)
                        @include('site.partials.competition-card')
                    @endforeach
                </div>
            @endif
        </section>

        <section id="agenda">
            @include('site.partials.section-heading', [
                'title' => 'Agenda & Kegiatan',
                'description' => 'Technical meeting, latihan gabungan, workshop juri, dan kegiatan lainnya.',
                'href' => route('site.agenda.index'),
            ])
            @if ($agendaItems->isEmpty())
                <x-ui.empty-state title="Belum ada agenda dalam waktu dekat." />
            @else
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($agendaItems as $agendaItem)
                        @include('site.partials.agenda-row')
                    @endforeach
                </div>
            @endif
        </section>

        <section id="kabar">
            @include('site.partials.section-heading', [
                'title' => 'Prestasi & Kabar Komunitas',
                'description' => 'Kabar juara dan cerita dari satuan-satuan Paskibra.',
                'href' => route('site.community.index'),
            ])
            @if ($communityPosts->isEmpty())
                <x-ui.empty-state title="Belum ada kabar yang diterbitkan." />
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($communityPosts as $communityPost)
                        @include('site.partials.community-card')
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
