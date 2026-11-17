@extends('layouts.dashboard')

@section('title', 'Penjurian — '.$competition->title)

@php
    $categories = $competition->categories;
    $rounds = $categories->flatMap->rounds;
    $participantCount = $categories->sum(fn ($category) => $category->participants->count());
    $expectedSheets = $rounds->sum(fn ($round) => $round->category->participants->count() * $round->judges->count());
    $sentSheets = $rounds->sum(fn ($round) => $round->judgings->whereIn('status.value', ['submitted', 'locked'])->count());
    $number = fn ($value) => $value === null ? null : rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    $field = 'block w-full text-sm';
    $label = 'block text-xs font-medium text-slate-600';
    $sheetBadge = fn (?string $status) => match ($status) {
        'locked' => ['Dikunci', 'bg-emerald-100 text-emerald-800'],
        'submitted' => ['Terkirim', 'bg-emerald-50 text-emerald-700'],
        'draft' => ['Draft', 'bg-amber-50 text-amber-800'],
        default => ['Belum', 'bg-slate-100 text-slate-500'],
    };
    // Same rule as CompetitionJudgingService::isReadyForJudging().
    $isReady = fn ($round) => $round->criteria->isNotEmpty()
        && $round->criteria->every(fn ($criterion) => $criterion->weight !== null)
        && abs($round->criteria->sum(fn ($criterion) => (float) $criterion->weight) - 100) < 0.001;
    // Participants missing from the last calculated ranking, with the reason (admin-only, so judge names are fine).
    $unranked = function ($round) {
        $ranked = $round->results->pluck('competition_participant_id');

        return $round->category->participants->whereNotIn('id', $ranked)->map(function ($participant) use ($round) {
            $sheets = $round->judgings->where('competition_participant_id', $participant->id);
            $sent = $sheets->filter(fn ($sheet) => ! $sheet->isDraft());
            $scored = $sent->flatMap->scores->pluck('competition_criterion_id')->unique();
            $missing = $round->criteria->whereNotIn('id', $scored)->pluck('name');
            $waiting = $round->judges->map(function ($judge) use ($sheets) {
                $sheet = $sheets->firstWhere('competition_judge_id', $judge->id);

                return match (true) {
                    $sheet === null => $judge->name.' (belum menilai)',
                    $sheet->isDraft() => $judge->name.' (masih draft)',
                    default => null,
                };
            })->filter();

            return [
                'participant' => $participant,
                'reason' => match (true) {
                    $sent->isEmpty() => 'Belum ada nilai yang dikirim juri.',
                    $missing->isNotEmpty() => 'Belum ada nilai terkirim untuk: '.$missing->implode(', ').'.',
                    default => 'Nilainya sudah lengkap. Tekan "Hitung Ulang" supaya masuk peringkat.',
                },
                'waiting' => $waiting,
            ];
        });
    };
@endphp

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Info Lomba', 'href' => route('dashboard.content.competitions.index')],
        ['label' => $competition->title, 'href' => route('dashboard.content.competitions.show', $competition)],
        ['label' => 'Penjurian'],
    ]" />

    <x-layout.page-header :title="'Penjurian · '.$competition->title" description="Atur kategori, peserta, babak, kriteria, dan juri. Nilai diisi juri lewat Panel Juri di /juri.">
        <x-slot:actions>
            <x-ui.button :href="route('dashboard.content.judges.index')" variant="secondary">Kelola Juri</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Kategori', $categories->count(), null],
            ['Peserta', $participantCount, null],
            ['Babak', $rounds->count(), null],
            ['Lembar terkirim', $sentSheets, $expectedSheets ? 'dari '.$expectedSheets.' lembar' : 'belum ada juri ditugaskan'],
        ] as [$statLabel, $statValue, $statHint])
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm text-slate-500">{{ $statLabel }}</p>
                <p class="mt-1 text-2xl font-bold text-navy-900">{{ $statValue }}</p>
                @if ($statHint)
                    <p class="text-xs text-slate-400">{{ $statHint }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <nav class="mt-6 mb-6 flex gap-1 overflow-x-auto border-b border-slate-200 text-sm font-medium" aria-label="Bagian penjurian">
        @foreach (\App\Http\Controllers\Dashboard\Content\Judging\WorkspaceController::TABS as $key => $tabLabel)
            <a href="{{ route('dashboard.content.competitions.judging.show', [$competition, 'tab' => $key]) }}"
               @if ($tab === $key) aria-current="page" @endif
               class="-mb-px border-b-2 px-3 py-2 whitespace-nowrap {{ $tab === $key ? 'border-brand-600 text-navy-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </nav>

    {{-- ───────────── Kategori & Peserta ───────────── --}}
    @if ($tab === 'peserta')
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-4">
                @forelse ($categories as $category)
                    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                            <div>
                                <h2 class="font-semibold text-navy-900">{{ $category->name }}</h2>
                                <p class="text-xs text-slate-500">{{ $category->participants->count() }} peserta</p>
                            </div>
                            @if ($category->participants->isEmpty() && $category->rounds->isEmpty())
                                <form method="POST" action="{{ route('dashboard.content.competitions.judging.categories.destroy', [$competition, $category]) }}" onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="cursor-pointer text-sm text-red-600 hover:underline">Hapus</button>
                                </form>
                            @endif
                        </header>

                        @if ($category->participants->isEmpty())
                            <p class="px-4 py-6 text-center text-sm text-slate-500">Belum ada peserta di kategori ini.</p>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @foreach ($category->participants as $participant)
                                    <li class="px-4 py-3">
                                        <details class="group">
                                            <summary class="flex cursor-pointer list-none items-center gap-3 [&::-webkit-details-marker]:hidden">
                                                <span class="flex h-10 min-w-14 items-center justify-center rounded-md bg-navy-50 px-2 text-sm font-bold whitespace-nowrap text-navy-800">{{ $participant->competition_number ?? '—' }}</span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-sm font-semibold text-navy-900">{{ $participant->name }}</span>
                                                    <span class="block text-xs text-slate-500">{{ $participant->institution ?: 'Asal belum diisi' }}{{ $participant->performance_order ? ' · urutan tampil '.$participant->performance_order : '' }}</span>
                                                </span>
                                                <span class="text-xs font-medium text-sky-700 group-open:hidden">Edit</span>
                                                <span class="hidden text-xs font-medium text-slate-500 group-open:inline">Tutup</span>
                                            </summary>

                                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.participants.update', [$competition, $participant]) }}" class="mt-3 grid gap-3 rounded-md bg-slate-50 p-3 sm:grid-cols-2">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="competition_category_id" value="{{ $category->id }}">
                                                <label class="{{ $label }}">Nama tim<input name="name" value="{{ $participant->name }}" required class="{{ $field }} mt-1"></label>
                                                <label class="{{ $label }}">Asal sekolah/instansi<input name="institution" value="{{ $participant->institution }}" class="{{ $field }} mt-1"></label>
                                                <label class="{{ $label }}">Nomor tampil<input name="competition_number" required value="{{ $participant->competition_number }}" class="{{ $field }} mt-1"></label>
                                                <label class="{{ $label }}">Urutan tampil<input name="performance_order" type="number" min="1" value="{{ $participant->performance_order }}" class="{{ $field }} mt-1"></label>
                                                <div class="flex items-center gap-3 sm:col-span-2">
                                                    <x-ui.button type="submit" size="sm">Simpan</x-ui.button>
                                                    <button type="submit" form="delete-participant-{{ $participant->id }}" class="cursor-pointer text-xs text-red-600 hover:underline">Hapus peserta</button>
                                                </div>
                                            </form>
                                            <form id="delete-participant-{{ $participant->id }}" method="POST" action="{{ route('dashboard.content.competitions.judging.participants.destroy', [$competition, $participant]) }}" onsubmit="return confirm('Hapus {{ $participant->name }}?')">
                                                @csrf @method('DELETE')
                                            </form>
                                        </details>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @empty
                    <x-ui.empty-state title="Belum ada kategori." description="Mulai dengan menambah kategori, misalnya SMA/SMK Sederajat. Setelah itu peserta bisa ditambahkan." />
                @endforelse
            </div>

            <aside class="space-y-4">
                <form method="POST" action="{{ route('dashboard.content.competitions.judging.categories.store', $competition) }}" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                    @csrf
                    <h3 class="font-semibold text-navy-900">Tambah kategori</h3>
                    <label class="{{ $label }}">Nama kategori<input name="name" required placeholder="mis. SMA/SMK Sederajat" class="{{ $field }} mt-1"></label>
                    <x-ui.button type="submit" size="sm">Tambah Kategori</x-ui.button>
                </form>

                @if ($categories->isNotEmpty())
                    <form method="POST" action="{{ route('dashboard.content.competitions.judging.participants.store', $competition) }}" class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                        @csrf
                        <h3 class="font-semibold text-navy-900">Tambah peserta</h3>
                        <label class="{{ $label }}">Kategori
                            <select name="competition_category_id" required class="{{ $field }} mt-1">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('competition_category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="{{ $label }}">Nama tim <span class="text-red-600">*</span><input name="name" value="{{ old('name') }}" required placeholder="mis. Paskibra SMAN 1" class="{{ $field }} mt-1"></label>
                        <label class="{{ $label }}">Asal sekolah/instansi (opsional)<input name="institution" value="{{ old('institution') }}" class="{{ $field }} mt-1"></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="{{ $label }}">Nomor tampil <span class="text-red-600">*</span><input name="competition_number" value="{{ old('competition_number') }}" placeholder="SMA-01" required class="{{ $field }} mt-1"></label>
                            <label class="{{ $label }}">Urutan<input name="performance_order" type="number" min="1" value="{{ old('performance_order') }}" class="{{ $field }} mt-1"></label>
                        </div>
                        <x-ui.button type="submit" size="sm">Tambah Peserta</x-ui.button>
                    </form>
                @endif
            </aside>
        </div>
    @endif

    {{-- ───────────── Babak & Juri ───────────── --}}
    @if ($tab === 'penjurian')
        @if ($judges->isEmpty())
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Anda belum punya juri. <a href="{{ route('dashboard.content.judges.index') }}" class="font-semibold underline">Tambah juri dulu</a>, lalu tugaskan ke babak di sini.
            </div>
        @endif

        @if ($categories->isEmpty())
            <x-ui.empty-state title="Belum ada kategori." description="Babak dibuat per kategori. Tambahkan kategori di tab Kategori & Peserta.">
                <x-slot:action>
                    <x-ui.button :href="route('dashboard.content.competitions.judging.show', [$competition, 'tab' => 'peserta'])" size="sm">Ke Kategori & Peserta</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <form method="POST" action="{{ route('dashboard.content.competitions.judging.rounds.store', $competition) }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4">
                @csrf
                <label class="{{ $label }} min-w-48 flex-1">Kategori
                    <select name="competition_category_id" required class="{{ $field }} mt-1">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="{{ $label }} min-w-48 flex-1">Nama babak<input name="name" required placeholder="mis. Babak Final" class="{{ $field }} mt-1"></label>
                <x-ui.button type="submit">Tambah Babak</x-ui.button>
            </form>

            <div class="space-y-6">
                @foreach ($categories as $category)
                    @foreach ($category->rounds as $round)
                        @php
                            $weightTotal = $round->criteria->sum(fn ($criterion) => (float) ($criterion->weight ?? 0));
                            // Same rules as CompetitionJudgingService: judgeable at exactly 100%, frozen once any sheet exists.
                            $ready = $round->criteria->isNotEmpty() && $round->criteria->every(fn ($criterion) => $criterion->weight !== null) && abs($weightTotal - 100) < 0.001;
                            $started = $round->judgings->isNotEmpty();
                        @endphp
                        <section class="rounded-lg border border-slate-200 bg-white">
                            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                <div>
                                    <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $category->name }}</p>
                                    <h2 class="font-semibold text-navy-900">{{ $round->name }}</h2>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    @if ($started)
                                        <span class="rounded-full bg-navy-50 px-2.5 py-1 font-medium text-navy-700">Penilaian berjalan · kriteria terkunci</span>
                                    @elseif ($ready)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700">Siap dinilai · bobot 100%</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-800">Belum siap · bobot {{ $number($weightTotal) ?: 0 }}% dari 100%</span>
                                    @endif
                                    <span class="rounded-full px-2.5 py-1 font-medium {{ $round->judges->isNotEmpty() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $round->judges->count() }} juri</span>
                                    @if ($round->judgings->isEmpty())
                                        <form method="POST" action="{{ route('dashboard.content.competitions.judging.rounds.destroy', [$competition, $round]) }}" onsubmit="return confirm('Hapus babak {{ $round->name }} beserta kriterianya?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="cursor-pointer text-red-600 hover:underline">Hapus babak</button>
                                        </form>
                                    @endif
                                </div>
                            </header>

                            <div class="grid gap-6 p-4 lg:grid-cols-2">
                                <div>
                                    <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Kriteria</h3>
                                    @if ($started)
                                        <p class="mt-1 text-xs text-navy-700">Juri sudah mulai menilai, jadi kriteria terkunci: tidak bisa ditambah, diubah, atau dihapus.</p>
                                    @elseif (! $ready)
                                        <p class="mt-1 text-xs text-amber-700">Juri baru bisa menilai setelah total bobot tepat 100%. Sekarang {{ $number($weightTotal) ?: 0 }}%{{ $weightTotal < 100 ? ', kurang '.$number(100 - $weightTotal).'%' : ', lebih '.$number($weightTotal - 100).'%' }}.</p>
                                    @endif
                                    <ul class="mt-2 space-y-2">
                                        @forelse ($round->criteria as $criterion)
                                            <li class="rounded-md border border-slate-200 px-3 py-2">
                                                @if ($started)
                                                    {{-- Judging started: the criterion is frozen, so no Edit (the controller rejects changes too). --}}
                                                    <span class="block text-sm font-medium text-navy-900">{{ $criterion->name }}</span>
                                                    <span class="block text-xs text-slate-500">Nilai {{ $number($criterion->minimum_score) }}–{{ $number($criterion->maximum_score) }}{{ $criterion->weight !== null ? ' · bobot '.$number($criterion->weight).'%' : '' }}</span>
                                                    @if ($criterion->description)
                                                        <span class="mt-0.5 block text-xs text-slate-400">{{ $criterion->description }}</span>
                                                    @endif
                                                @else
                                                <details class="group">
                                                    <summary class="flex cursor-pointer list-none items-start justify-between gap-2 [&::-webkit-details-marker]:hidden">
                                                        <span>
                                                            <span class="block text-sm font-medium text-navy-900">{{ $criterion->name }}</span>
                                                            <span class="block text-xs text-slate-500">Nilai {{ $number($criterion->minimum_score) }}–{{ $number($criterion->maximum_score) }}{{ $criterion->weight !== null ? ' · bobot '.$number($criterion->weight).'%' : '' }}</span>
                                                        </span>
                                                        <span class="text-xs font-medium text-sky-700">Edit</span>
                                                    </summary>
                                                    <form method="POST" action="{{ route('dashboard.content.competitions.judging.criteria.update', [$competition, $criterion]) }}" class="mt-3 grid grid-cols-3 gap-2">
                                                        @csrf @method('PUT')
                                                        <label class="{{ $label }} col-span-3">Nama<input name="name" value="{{ $criterion->name }}" required class="{{ $field }} mt-1"></label>
                                                        <label class="{{ $label }} col-span-3">Keterangan untuk juri<input name="description" value="{{ $criterion->description }}" class="{{ $field }} mt-1"></label>
                                                        <label class="{{ $label }}">Bobot %<input name="weight" type="number" step="0.01" min="0.01" max="100" value="{{ $criterion->weight !== null ? (float) $criterion->weight : '' }}" required class="{{ $field }} mt-1"></label>
                                                        <label class="{{ $label }}">Min<input name="minimum_score" type="number" step="0.001" min="0" value="{{ (float) $criterion->minimum_score }}" required class="{{ $field }} mt-1"></label>
                                                        <label class="{{ $label }}">Maks<input name="maximum_score" type="number" step="0.001" min="0" value="{{ (float) $criterion->maximum_score }}" required class="{{ $field }} mt-1"></label>
                                                        <div class="col-span-3 flex items-center gap-3">
                                                            <x-ui.button type="submit" size="sm">Simpan</x-ui.button>
                                                            <button type="submit" form="delete-criterion-{{ $criterion->id }}" class="cursor-pointer text-xs text-red-600 hover:underline">Hapus</button>
                                                        </div>
                                                    </form>
                                                    <form id="delete-criterion-{{ $criterion->id }}" method="POST" action="{{ route('dashboard.content.competitions.judging.criteria.destroy', [$competition, $criterion]) }}" onsubmit="return confirm('Hapus kriteria {{ $criterion->name }}?')">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                </details>
                                                @endif
                                            </li>
                                        @empty
                                            <li class="text-sm text-slate-500">Belum ada kriteria.</li>
                                        @endforelse
                                    </ul>

                                    @unless ($started)
                                    <form method="POST" action="{{ route('dashboard.content.competitions.judging.criteria.store', [$competition, $round]) }}" class="mt-3 grid grid-cols-3 gap-2 rounded-md bg-slate-50 p-3">
                                        @csrf
                                        <label class="{{ $label }} col-span-3">Kriteria baru<input name="name" required placeholder="mis. Kerapian barisan" class="{{ $field }} mt-1"></label>
                                        <label class="{{ $label }}">Bobot %<input name="weight" type="number" step="0.01" min="0.01" max="100" required @if (! $ready && $weightTotal < 100) value="{{ (float) round(100 - $weightTotal, 2) }}" @endif class="{{ $field }} mt-1"></label>
                                        <label class="{{ $label }}">Min<input name="minimum_score" type="number" step="0.001" min="0" value="0" required class="{{ $field }} mt-1"></label>
                                        <label class="{{ $label }}">Maks<input name="maximum_score" type="number" step="0.001" min="0" value="100" required class="{{ $field }} mt-1"></label>
                                        <div class="col-span-3"><x-ui.button type="submit" size="sm" variant="secondary">+ Tambah Kriteria</x-ui.button></div>
                                    </form>
                                    @endunless
                                </div>

                                <div>
                                    <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Juri yang menilai</h3>
                                    <ul class="mt-2 divide-y divide-slate-100 rounded-md border border-slate-200">
                                        @forelse ($round->judges as $roundJudge)
                                            <li class="flex items-center justify-between gap-3 px-3 py-2">
                                                <span>
                                                    <span class="block text-sm font-medium text-navy-900">{{ $roundJudge->name }}</span>
                                                    <span class="block font-mono text-xs tracking-widest text-slate-500">Kode: {{ $roundJudge->access_code }}</span>
                                                </span>
                                                @unless ($round->judgings->contains('competition_judge_id', $roundJudge->id))
                                                    <form method="POST" action="{{ route('dashboard.content.competitions.judging.judges.unassign', [$competition, $round, $roundJudge]) }}">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="cursor-pointer text-xs text-red-600 hover:underline">Lepas</button>
                                                    </form>
                                                @endunless
                                            </li>
                                        @empty
                                            <li class="px-3 py-3 text-sm text-slate-500">Belum ada juri ditugaskan.</li>
                                        @endforelse
                                    </ul>

                                    @php($available = $judges->whereNotIn('id', $round->judges->pluck('id')))
                                    @if ($available->isNotEmpty())
                                        <form method="POST" action="{{ route('dashboard.content.competitions.judging.judges.assign', [$competition, $round]) }}" class="mt-3 flex items-end gap-2">
                                            @csrf
                                            <label class="{{ $label }} flex-1">Tugaskan juri
                                                <select name="competition_judge_id" required class="{{ $field }} mt-1">
                                                    @foreach ($available as $availableJudge)
                                                        <option value="{{ $availableJudge->id }}">{{ $availableJudge->name }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <x-ui.button type="submit" size="sm">Tugaskan</x-ui.button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </section>
                    @endforeach
                @endforeach

                @if ($rounds->isEmpty())
                    <x-ui.empty-state title="Belum ada babak." description="Tambahkan babak di atas, misalnya Babak Final, lalu isi kriteria dan tugaskan juri." />
                @endif
            </div>
        @endif
    @endif

    {{-- ───────────── Lembar Nilai ───────────── --}}
    @if ($tab === 'lembar')
        @if ($sentSheets > 0)
            @include('dashboard.content.competitions.partials.public-link')
        @endif
        <p class="mb-4 text-sm text-slate-500">Status lembar nilai tiap juri. Lembar yang sudah dikirim bisa dikunci, atau dibuka ulang dengan alasan kalau juri perlu memperbaiki nilai. Setiap pembukaan ulang tercatat.</p>

        @forelse ($rounds as $round)
            @php($sheets = $round->judgings->groupBy('competition_participant_id'))
            <section class="mb-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
                <header class="border-b border-slate-100 px-4 py-3">
                    <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $round->category->name }}</p>
                    <h2 class="font-semibold text-navy-900">{{ $round->name }}</h2>
                    @php($ready = $isReady($round))
                    <div class="mt-2 flex flex-wrap gap-1.5 text-xs font-medium">
                        @foreach ([
                            [$ready, $ready ? 'Bobot 100%' : 'Bobot belum 100%'],
                            [$round->judges->isNotEmpty(), $round->judges->count().' juri ditugaskan'],
                            [$round->category->participants->isNotEmpty(), $round->category->participants->count().' peserta'],
                        ] as [$ok, $checkLabel])
                            <span class="rounded-full px-2.5 py-1 {{ $ok ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $ok ? '✓' : '!' }} {{ $checkLabel }}</span>
                        @endforeach
                    </div>
                    @unless ($ready)
                        <p class="mt-1.5 text-xs text-amber-800">Juri belum bisa menilai babak ini. <a href="{{ route('dashboard.content.competitions.judging.show', [$competition, 'tab' => 'penjurian']) }}" class="font-semibold underline">Atur bobot kriteria</a>.</p>
                    @endunless
                    @if ($round->isPublished())
                        <p class="mt-1 text-xs text-slate-500">Hasil babak ini sudah dipublikasikan. Untuk membuka ulang lembar nilai, tarik dulu hasilnya dari publik di tab Hasil.</p>
                    @endif
                </header>

                @if ($round->judges->isEmpty() || $round->category->participants->isEmpty())
                    <p class="px-4 py-6 text-center text-sm text-slate-500">Babak ini belum punya {{ $round->judges->isEmpty() ? 'juri' : 'peserta' }}.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs text-slate-500">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Peserta</th>
                                    @foreach ($round->judges as $roundJudge)
                                        <th class="px-4 py-2 font-medium whitespace-nowrap">{{ $roundJudge->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($round->category->participants as $participant)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <span class="font-semibold text-navy-900">{{ $participant->competition_number ?? '—' }}</span>
                                            <span class="text-slate-600">· {{ $participant->name }}</span>
                                        </td>
                                        @foreach ($round->judges as $roundJudge)
                                            @php($sheet = ($sheets[$participant->id] ?? collect())->firstWhere('competition_judge_id', $roundJudge->id))
                                            @php([$badgeLabel, $badgeClass] = $sheetBadge($sheet?->status->value))
                                            <td class="px-4 py-3 align-top">
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                                    {{ $badgeLabel }}{{ $sheet?->isDraft() ? ' '.$sheet->scores_count.'/'.$round->criteria->count() : '' }}
                                                </span>
                                                @if ($sheet && $sheet->revision_count)
                                                    <span class="ml-1 text-xs text-slate-400">dibuka ulang {{ $sheet->revision_count }}×</span>
                                                @endif
                                                @if ($sheet && ! $sheet->isDraft())
                                                    <div class="mt-1.5 flex items-center gap-3 text-xs">
                                                        @if ($sheet->status->value === 'submitted')
                                                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.judgings.lock', [$competition, $sheet]) }}">
                                                                @csrf
                                                                <button type="submit" class="cursor-pointer font-medium text-navy-700 hover:underline">Kunci</button>
                                                            </form>
                                                        @endif
                                                        @unless ($round->isPublished())
                                                        <details class="relative">
                                                            <summary class="cursor-pointer list-none font-medium text-red-600 hover:underline [&::-webkit-details-marker]:hidden">Buka ulang</summary>
                                                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.judgings.reopen', [$competition, $sheet]) }}" class="absolute z-10 mt-1 w-64 space-y-2 rounded-md border border-slate-200 bg-white p-3 shadow-lg">
                                                                @csrf
                                                                <label class="{{ $label }}">Alasan (wajib, tercatat)<textarea name="reason" rows="2" required class="{{ $field }} mt-1"></textarea></label>
                                                                <x-ui.button type="submit" size="sm" variant="danger">Buka ulang</x-ui.button>
                                                            </form>
                                                        </details>
                                                        @else
                                                        <span class="cursor-not-allowed text-slate-400" title="Tarik hasil babak ini dari publik dulu (tab Hasil)">Buka ulang</span>
                                                        @endunless
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @empty
            <x-ui.empty-state title="Belum ada babak." description="Lembar nilai muncul setelah babak, peserta, dan juri disiapkan." />
        @endforelse
    @endif

    {{-- ───────────── Hasil ───────────── --}}
    @if ($tab === 'hasil')
        @if ($sentSheets > 0)
            @include('dashboard.content.competitions.partials.public-link')
        @endif
        <p class="mb-4 text-sm text-slate-500">"Publikasikan Hasil" menghitung ulang lalu menampilkan peringkat di halaman publik. Hasil dihitung dari lembar yang sudah dikirim atau dikunci. Nilai tiap kriteria dirata-rata dari semua juri, lalu dikalikan bobot. Peserta yang nilainya belum lengkap tidak ikut diperingkat. Hitung ulang kapan saja setelah ada nilai baru.</p>

        @forelse ($rounds as $round)
            <section class="mb-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $round->category->name }}</p>
                        <h2 class="font-semibold text-navy-900">{{ $round->name }}</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($round->isPublished())
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Tampil di publik sejak {{ $round->results_published_at->locale('id')->translatedFormat('j M, H.i') }}</span>
                        @endif
                        <form method="POST" action="{{ route('dashboard.content.competitions.judging.results.calculate', [$competition, $round]) }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="secondary">{{ $round->results->isEmpty() ? 'Hitung Hasil' : 'Hitung Ulang' }}</x-ui.button>
                        </form>
                        @if ($round->isPublished())
                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.results.unpublish', [$competition, $round]) }}" onsubmit="return confirm('Tarik hasil {{ $round->name }} dari halaman publik?')">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" size="sm" variant="ghost">Tarik dari publik</x-ui.button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.results.publish', [$competition, $round]) }}" onsubmit="return confirm('Publikasikan hasil {{ $round->name }}? Peringkat dan nilai (juri anonim) akan tampil di halaman publik.')">
                                @csrf
                                <x-ui.button type="submit" size="sm">Publikasikan Hasil</x-ui.button>
                            </form>
                        @endif
                    </div>
                </header>

                @if ($round->results->isEmpty())
                    <p class="px-4 py-6 text-center text-sm text-slate-500">Belum dihitung.</p>
                @else
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs text-slate-500">
                            <tr>
                                <th class="w-20 px-4 py-2 font-medium">Peringkat</th>
                                <th class="px-4 py-2 font-medium">Peserta</th>
                                <th class="px-4 py-2 text-right font-medium">Jumlah nilai</th>
                                <th class="px-4 py-2 text-right font-medium">Nilai akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($round->results as $result)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $result->rank <= 3 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $result->rank }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-navy-900">{{ $result->participant->competition_number ?? '—' }}</span>
                                        <span class="text-slate-600">· {{ $result->participant->name }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600 tabular-nums">{{ $number($result->raw_score) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-navy-900 tabular-nums">{{ $number($result->weighted_score) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-400">Dihitung {{ $round->results->first()->updated_at->locale('id')->translatedFormat('j M Y, H.i') }}</p>
                @endif

                @php($notRanked = $unranked($round))
                @if ($notRanked->isNotEmpty())
                    <div class="border-t border-slate-100 bg-amber-50/40 px-4 py-3">
                        <h3 class="text-xs font-semibold tracking-wide text-amber-800 uppercase">Belum diperingkat ({{ $notRanked->count() }})</h3>
                        <ul class="mt-2 space-y-2">
                            @foreach ($notRanked as $item)
                                <li class="text-sm">
                                    <span class="font-semibold text-navy-900">{{ $item['participant']->competition_number ?? '—' }}</span>
                                    <span class="text-slate-700">· {{ $item['participant']->name }}</span>
                                    <span class="block text-xs text-slate-600">{{ $item['reason'] }}</span>
                                    @if ($item['waiting']->isNotEmpty())
                                        <span class="block text-xs text-slate-500">Menunggu: {{ $item['waiting']->implode(', ') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        @empty
            <x-ui.empty-state title="Belum ada babak." />
        @endforelse
    @endif
@endsection
