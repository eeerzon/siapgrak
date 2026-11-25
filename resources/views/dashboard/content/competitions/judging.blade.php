@extends('layouts.dashboard')

@section('title', 'Penjurian — '.$competition->title)

@php
    $categories = $competition->categories;
    $rounds = $categories->flatMap->rounds;
    $participantCount = $categories->sum(fn ($category) => $category->participants->count());
    $expectedSheets = $rounds->sum(fn ($round) => $round->entrants->count() * $round->judges->count());
    $sentSheets = $rounds->sum(fn ($round) => $round->judgings->whereIn('status.value', ['submitted', 'locked'])->count());
    $number = fn ($value) => $value === null ? null : rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    $field = 'block w-full text-sm';
    $label = 'block text-xs font-medium text-slate-600';
    $sheetBadge = fn (?string $status) => match ($status) {
        // Lock feature hidden for now (2026-11-19): a locked sheet reads as sent. Was: ['Dikunci', 'bg-emerald-100 text-emerald-800'].
        'locked' => ['Terkirim', 'bg-emerald-50 text-emerald-700'],
        'submitted' => ['Terkirim', 'bg-emerald-50 text-emerald-700'],
        'draft' => ['Draft', 'bg-amber-50 text-amber-800'],
        default => ['Belum', 'bg-slate-100 text-slate-500'],
    };
    // Same rule as CompetitionJudgingService::isReadyForJudging().
    $isReady = fn ($round) => $round->criteria->isNotEmpty()
        && $round->criteria->every(fn ($criterion) => $criterion->weight !== null)
        && abs($round->criteria->sum(fn ($criterion) => (float) $criterion->weight) - 100) < 0.001;
    // Participants missing from the last calculated ranking, with the reason (admin-only, so judge names are fine).
    // Same rule as CompetitionJudgingService::calculateResults(): every assigned judge must have sent their sheet.
    $unranked = function ($round) {
        $ranked = $round->results->pluck('competition_participant_id');

        return $round->entrants->whereNotIn('id', $ranked)->map(function ($participant) use ($round) {
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
                    $waiting->isNotEmpty() => 'Baru '.$sent->count().' dari '.$round->judges->count().' juri yang mengirim nilai.',
                    $missing->isNotEmpty() => 'Belum ada nilai terkirim untuk: '.$missing->implode(', ').'.',
                    default => 'Nilainya sudah lengkap. Tekan "Hitung Ulang" supaya masuk peringkat.',
                },
                'waiting' => $waiting,
            ];
        });
    };
    // Per judge: how many of the round's participants they have sent, still have in draft, or not started.
    $judgeSummary = function ($round) {
        $total = $round->entrants->count();

        return $round->judges->map(function ($judge) use ($round, $total) {
            $sheets = $round->judgings->where('competition_judge_id', $judge->id);
            $draft = $sheets->filter(fn ($sheet) => $sheet->isDraft())->count();
            $sent = $sheets->count() - $draft;

            return ['name' => $judge->name, 'total' => $total, 'sent' => $sent, 'draft' => $draft, 'notStarted' => max(0, $total - $sheets->count())];
        })->filter(fn ($progress) => $progress['sent'] < $progress['total'])->values();
    };
    // Publish confirmation text, from live state (not the last calculation): publishing recalculates from what is sent right now.
    // Returns [text, hasWarnings]; "- " lines become a list in the confirm dialog (resources/js/confirm-dialog.js).
    $publishConfirm = function ($round): array {
        $sentForRound = $round->judgings->filter(fn ($sheet) => ! $sheet->isDraft());
        $unsentSheets = max(0, $round->entrants->count() * $round->judges->count() - $sentForRound->count());
        $draftSheets = $round->judgings->filter(fn ($sheet) => $sheet->isDraft())->count();
        // Ranked only when every assigned judge has sent their sheet for the participant.
        $judgeCount = $round->judges->count();
        $notRankable = $round->entrants->filter(fn ($participant) => $judgeCount === 0
            || $sentForRound->where('competition_participant_id', $participant->id)->pluck('competition_judge_id')->unique()->count() < $judgeCount)->count();
        $publishWarnings = array_filter([
            $unsentSheets > 0 ? "- {$unsentSheets} lembar nilai belum dikirim juri".($draftSheets > 0 ? " ({$draftSheets} masih draft)" : '').'. Lembar ini tidak ikut dihitung.' : null,
            $notRankable > 0 ? "- {$notRankable} peserta belum dinilai semua juri, jadi tidak masuk peringkat." : null,
        ]);
        if ($publishWarnings) {
            return ["Masih ada yang belum beres:\n".implode("\n", $publishWarnings)."\nYang tampil di publik hanya peserta yang sudah dinilai semua juri.", true];
        }

        return ['Peringkat dihitung ulang dari lembar yang sudah dikirim, lalu peringkat dan nilai (juri anonim) tampil di halaman publik.', false];
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
            <a href="{{ route('dashboard.content.competitions.judging.show', [$competition, 'tab' => $key, 'kategori' => $activeCategory?->id]) }}"
               @if ($tab === $key) aria-current="page" @endif
               class="-mb-px shrink-0 border-b-2 px-2.5 py-2 whitespace-nowrap sm:px-3 {{ $tab === $key ? 'border-brand-600 text-navy-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{-- Shorter labels on phones so all four tabs fit without scrolling. --}}
                <span class="sm:hidden">{{ ['peserta' => 'Peserta', 'penjurian' => 'Babak & Juri', 'lembar' => 'Lembar', 'hasil' => 'Hasil'][$key] }}</span><span class="hidden sm:inline">{{ $tabLabel }}</span>
            </a>
        @endforeach
    </nav>

    @if ($categories->isNotEmpty())
        {{-- Every tab shows one category at a time (?kategori=id); the choice is kept when switching tabs. --}}
        <nav class="-mt-2 mb-6 flex gap-2 overflow-x-auto pb-1" aria-label="Kategori">
            @foreach ($categories as $category)
                <a href="{{ route('dashboard.content.competitions.judging.show', [$competition, 'tab' => $tab, 'kategori' => $category->id]) }}"
                   @if ($category->is($activeCategory)) aria-current="page" @endif
                   class="inline-flex shrink-0 items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap transition-colors {{ $category->is($activeCategory) ? 'border-navy-900 bg-navy-900 text-white' : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400 hover:text-navy-900' }}">
                    {{ $category->name }}
                    <span class="rounded-full px-1.5 text-xs {{ $category->is($activeCategory) ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $tab === 'peserta' ? $category->participants->count().' peserta' : $category->rounds->count().' babak' }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    {{-- ───────────── Kategori & Peserta ───────────── --}}
    @if ($tab === 'peserta')
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-4">
                @forelse ($activeCategory ? [$activeCategory] : [] as $category)
                    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                            <div>
                                <h2 class="font-semibold text-navy-900">{{ $category->name }}</h2>
                                <p class="text-xs text-slate-500">{{ $category->participants->count() }} peserta</p>
                            </div>
                            @if ($category->participants->isEmpty() && $category->rounds->isEmpty())
                                <form method="POST" action="{{ route('dashboard.content.competitions.judging.categories.destroy', [$competition, $category]) }}" data-confirm-title="{{ 'Hapus kategori '.$category->name.'?' }}" data-confirm="Kategori yang sudah dihapus tidak bisa dikembalikan." data-confirm-button="Ya, hapus">
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
                                            <form id="delete-participant-{{ $participant->id }}" method="POST" action="{{ route('dashboard.content.competitions.judging.participants.destroy', [$competition, $participant]) }}" data-confirm-title="{{ 'Hapus '.$participant->name.'?' }}" data-confirm="Peserta ini akan dihapus dari daftar peserta." data-confirm-button="Ya, hapus">
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
                        <p class="text-xs text-slate-500">Masuk ke kategori <span class="font-semibold text-navy-900">{{ $activeCategory->name }}</span>. Pilih tab kategori di atas untuk menambah ke kategori lain.</p>
                        <input type="hidden" name="competition_category_id" value="{{ $activeCategory->id }}">
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
                <input type="hidden" name="competition_category_id" value="{{ $activeCategory->id }}">
                <label class="{{ $label }} min-w-48 flex-1">Babak baru untuk {{ $activeCategory->name }}<input name="name" required placeholder="mis. Babak Penyisihan" class="{{ $field }} mt-1"></label>
                <x-ui.button type="submit">Tambah Babak</x-ui.button>
            </form>

            <div class="space-y-6">
                @foreach ([$activeCategory] as $category)
                    @foreach ($category->rounds as $round)
                        @php
                            $weightTotal = $round->criteria->sum(fn ($criterion) => (float) ($criterion->weight ?? 0));
                            // Same rules as CompetitionJudgingService: judgeable at exactly 100%, frozen once any sheet exists.
                            $ready = $round->criteria->isNotEmpty() && $round->criteria->every(fn ($criterion) => $criterion->weight !== null) && abs($weightTotal - 100) < 0.001;
                            $started = $round->judgings->isNotEmpty();
                            $finished = $round->isPublished();
                        @endphp
                        <section class="rounded-lg border border-slate-200 bg-white">
                            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                <div>
                                    <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $category->name }}</p>
                                    <h2 class="font-semibold text-navy-900">{{ $round->name }}</h2>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    @if ($finished)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700">Selesai · hasil diumumkan</span>
                                    @elseif ($round->is_complete)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700">Selesai dinilai · {{ $round->sheets_sent }}/{{ $round->sheets_expected }} lembar terkirim</span>
                                    @elseif ($started)
                                        <span class="rounded-full bg-navy-50 px-2.5 py-1 font-medium text-navy-700">Penilaian berjalan · kriteria terkunci</span>
                                    @elseif ($ready)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700">Siap dinilai · bobot 100%</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-800">Belum siap · bobot {{ $number($weightTotal) ?: 0 }}% dari 100%</span>
                                    @endif
                                    <span class="rounded-full px-2.5 py-1 font-medium {{ $round->judges->isNotEmpty() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $round->judges->count() }} juri</span>
                                    @if ($round->judgings->isEmpty())
                                        <form method="POST" action="{{ route('dashboard.content.competitions.judging.rounds.destroy', [$competition, $round]) }}" data-confirm-title="{{ 'Hapus babak '.$round->name.'?' }}" data-confirm="Semua kriteria di babak ini ikut terhapus." data-confirm-button="Ya, hapus">
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
                                                    <form id="delete-criterion-{{ $criterion->id }}" method="POST" action="{{ route('dashboard.content.competitions.judging.criteria.destroy', [$competition, $criterion]) }}" data-confirm-title="{{ 'Hapus kriteria '.$criterion->name.'?' }}" data-confirm="Kriteria yang sudah dihapus tidak bisa dikembalikan." data-confirm-button="Ya, hapus">
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

                                    @php($available = $judges->where('status', \App\Enums\Status::Active)->whereNotIn('id', $round->judges->pluck('id')))
                                    @if ($round->is_complete)
                                        {{-- Every judge has finished this round: no new judges (they would have nothing judged). --}}
                                        <p class="mt-3 text-xs text-slate-500">Semua juri sudah selesai menilai babak ini, jadi juri baru tidak bisa ditugaskan.</p>
                                    @elseif ($round->isPublished())
                                        {{-- A public round cannot be judged, so a new judge could never send a sheet. --}}
                                        <p class="mt-3 text-xs text-slate-500">Hasil babak ini sedang dipublikasikan. Tarik dulu dari publik di tab Hasil kalau perlu menugaskan juri baru.</p>
                                    @elseif ($available->isNotEmpty())
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

                            {{-- Teams of this round. First round: the whole category. Later rounds: chosen by the admin. --}}
                            <div class="border-t border-slate-100 p-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                    <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Peserta babak ini ({{ $round->entrants->count() }})</h3>
                                    @if ($round->is_first_round)
                                        <p class="text-xs text-slate-500">Babak pertama diikuti semua peserta kategori. Peserta babak berikutnya dipilih dari hasil babak ini.</p>
                                    @endif
                                </div>

                                @unless ($round->is_first_round)
                                    @if ($round->entrants->isEmpty())
                                        <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">Belum ada peserta. Juri belum bisa menilai babak ini sampai pesertanya dipilih.</p>
                                    @else
                                        <ul class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($round->entrantsByRank as $entrant)
                                                <li class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 py-1 pr-1.5 {{ $round->previousResults->has($entrant->id) ? 'pl-1' : 'pl-3' }} text-xs text-slate-700">
                                                    @if ($round->previousResults->has($entrant->id))
                                                        <span class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1 text-[11px] font-bold {{ $round->previousResults[$entrant->id]->rank <= 3 ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-700' }}" title="{{ 'Peringkat '.$round->previousResults[$entrant->id]->rank.' di '.$round->previousRound->name }}">{{ $round->previousResults[$entrant->id]->rank }}</span>
                                                    @endif
                                                    <span class="font-semibold text-navy-900">{{ $entrant->competition_number ?? '—' }}</span>
                                                    <span class="max-w-48 truncate">{{ $entrant->name }}</span>
                                                    @if ($round->judgings->contains('competition_participant_id', $entrant->id))
                                                        <span class="px-1 text-slate-400" title="Sudah dinilai, tidak bisa dikeluarkan">•</span>
                                                    @else
                                                        <form method="POST" action="{{ route('dashboard.content.competitions.judging.round-participants.destroy', [$competition, $round, $entrant]) }}" data-confirm-title="{{ 'Keluarkan '.$entrant->name.'?' }}" data-confirm="{{ 'Peserta ini tidak ikut '.$round->name.'. Bisa ditambahkan lagi nanti.' }}" data-confirm-button="Ya, keluarkan" data-confirm-icon="warning">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="{{ 'Keluarkan '.$entrant->name }}">&times;</button>
                                                        </form>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($round->previousRound && ! $round->previousRound->is_complete)
                                        {{-- Teams are chosen from a finished previous round only (the controller refuses it too). --}}
                                        <div class="mt-3 flex flex-col items-center rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                                            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                            <p class="mt-2 text-sm font-semibold text-navy-900">{{ $round->previousRound->name }} masih berlangsung</p>
                                            @if ($round->previousRound->isPublished())
                                                {{-- Published before every judge finished: judges cannot score a public round, so show the way out. --}}
                                                <p class="mt-1 max-w-md text-xs text-slate-500">Hasil {{ $round->previousRound->name }} dipublikasikan sebelum semua juri selesai ({{ $round->previousRound->sheets_sent }} dari {{ $round->previousRound->sheets_expected }} lembar terkirim). Tarik dulu hasilnya supaya juri bisa menyelesaikan penilaian, lalu publikasikan lagi.</p>
                                                <a href="{{ route('dashboard.content.competitions.judging.show', [$competition, 'tab' => 'hasil', 'kategori' => $round->competition_category_id]) }}" class="mt-3 text-xs font-semibold text-sky-700 hover:underline">Ke tab Hasil →</a>
                                            @else
                                                <p class="mt-1 max-w-md text-xs text-slate-500">Peserta {{ $round->name }} bisa dipilih setelah semua juri selesai mengirim nilai {{ $round->previousRound->name }}. Sekarang {{ $round->previousRound->sheets_sent }} dari {{ $round->previousRound->sheets_expected }} lembar terkirim.</p>
                                            @endif
                                        </div>
                                    @elseif ($round->previousRound && ! $round->previousRound->isPublished())
                                        {{-- Finished but not public yet: teams are picked from the official (published) ranking only. --}}
                                        <div class="mt-3 flex flex-col items-center rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center">
                                            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" /></svg>
                                            <p class="mt-2 text-sm font-semibold text-navy-900">Hasil {{ $round->previousRound->name }} belum dipublikasikan</p>
                                            <p class="mt-1 max-w-md text-xs text-slate-500">{{ $round->previousRound->name }} sudah selesai dinilai. Publikasikan hasilnya dulu, lalu peserta {{ $round->name }} bisa dipilih dari peringkat resminya.</p>
                                            <a href="{{ route('dashboard.content.competitions.judging.show', [$competition, 'tab' => 'hasil', 'kategori' => $round->competition_category_id]) }}" class="mt-3 text-xs font-semibold text-sky-700 hover:underline">Ke tab Hasil →</a>
                                        </div>
                                    @elseif ($round->team_list_closed_reason)
                                        {{-- Team list frozen: the next round started, or this round's results are public. --}}
                                        <p class="mt-3 flex items-start gap-2 rounded-md bg-slate-50 px-3 py-2.5 text-xs text-slate-600">
                                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                            <span>{{ $round->team_list_closed_reason }}</span>
                                        </p>
                                    @else
                                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                                        @if ($round->previousRound)
                                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.round-participants.top', [$competition, $round]) }}" class="rounded-md bg-slate-50 p-3">
                                                @csrf
                                                <label class="{{ $label }}">{{ 'Ambil peserta teratas dari '.$round->previousRound->name }}</label>
                                                <div class="mt-1 flex items-center gap-2">
                                                    <input name="top" type="number" min="1" max="999" required placeholder="mis. 10" class="{{ $field }} w-28" @disabled($round->previousRound->results->isEmpty())>
                                                    <x-ui.button type="submit" size="sm" :disabled="$round->previousRound->results->isEmpty()">Ambil</x-ui.button>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    @if ($round->previousRound->results->isEmpty())
                                                        {{ $round->previousRound->name }} belum punya peringkat. Hitung hasilnya dulu di tab Hasil.
                                                    @else
                                                        Berdasarkan peringkat {{ $round->previousRound->name }} ({{ $round->previousRound->results->count() }} peserta diperingkat). Peserta yang seri di batas ikut semua.
                                                    @endif
                                                </p>
                                            </form>
                                        @endif

                                        @if ($round->candidates->isNotEmpty())
                                            <details class="rounded-md bg-slate-50 p-3">
                                                <summary class="cursor-pointer text-xs font-medium text-slate-600">Pilih manual ({{ $round->candidates->count() }} peserta {{ $round->previousRound->name }} lainnya{{ $round->previousResults->isNotEmpty() ? ', urut peringkat' : '' }})</summary>
                                                <form method="POST" action="{{ route('dashboard.content.competitions.judging.round-participants.store', [$competition, $round]) }}" class="mt-2">
                                                    @csrf
                                                    <div class="max-h-64 space-y-1 overflow-y-auto rounded-md border border-slate-200 bg-white p-2">
                                                        @foreach ($round->candidates as $candidate)
                                                            <label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-sm hover:bg-slate-50">
                                                                <input type="checkbox" name="participant_ids[]" value="{{ $candidate->id }}" class="rounded border-slate-300">
                                                                @if ($round->previousResults->has($candidate->id))
                                                                    <span class="inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-full px-1.5 text-xs font-bold {{ $round->previousResults[$candidate->id]->rank <= 3 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700' }}" title="{{ 'Peringkat '.$round->previousResults[$candidate->id]->rank.' di '.$round->previousRound->name }}">{{ $round->previousResults[$candidate->id]->rank }}</span>
                                                                @elseif ($round->previousRound)
                                                                    <span class="inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-full bg-slate-50 px-1.5 text-xs text-slate-400" title="{{ 'Tidak diperingkat di '.$round->previousRound->name }}">–</span>
                                                                @endif
                                                                <span class="font-semibold text-navy-900">{{ $candidate->competition_number ?? '—' }}</span>
                                                                <span class="min-w-0 flex-1 truncate text-slate-700">{{ $candidate->name }}</span>
                                                                @if ($round->previousResults->has($candidate->id))
                                                                    <span class="shrink-0 text-xs font-semibold text-navy-900 tabular-nums">{{ $number($round->previousResults[$candidate->id]->weighted_score) }}</span>
                                                                @elseif ($round->previousRound)
                                                                    <span class="shrink-0 text-xs text-slate-400">belum diperingkat</span>
                                                                @endif
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    <x-ui.button type="submit" size="sm" class="mt-2">Tambahkan ke {{ $round->name }}</x-ui.button>
                                                </form>
                                            </details>
                                        @endif
                                    </div>
                                    @endif
                                @endunless
                            </div>
                        </section>
                    @endforeach
                @endforeach

                @if ($activeCategory->rounds->isEmpty())
                    <x-ui.empty-state title="{{ 'Belum ada babak di '.$activeCategory->name.'.' }}" description="Tambahkan babak di atas, misalnya Babak Penyisihan atau Babak Final, lalu isi kriteria dan tugaskan juri." />
                @endif
            </div>
        @endif
    @endif

    {{-- ───────────── Lembar Nilai ───────────── --}}
    @if ($tab === 'lembar')
        @if ($sentSheets > 0)
            @include('dashboard.content.competitions.partials.public-link')
        @endif
        <p class="mb-4 text-sm text-slate-500">Status lembar nilai tiap juri. Lembar yang sudah dikirim bisa dibuka ulang dengan alasan kalau juri perlu memperbaiki nilai. Setiap pembukaan ulang tercatat.</p>

        @forelse ($activeCategory?->rounds ?? collect() as $round)
            @php($sheets = $round->judgings->groupBy('competition_participant_id'))
            {{-- Finished (published) rounds start collapsed to a summary (click anywhere on the card to open); rounds still running are shown in full. --}}
            <section class="mb-6 overflow-hidden rounded-lg border border-slate-200 bg-white transition-colors" x-data="{ open: {{ $round->isPublished() ? 'false' : 'true' }} }"
                     @if ($round->isPublished()) @click="! open && ! $event.target.closest('a, button, form, input, select, textarea, summary') && (open = true)" :class="! open && 'cursor-pointer hover:border-slate-300 hover:shadow-sm'" @endif>
                <header class="border-b border-slate-100 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div @if ($round->isPublished()) @click="open = ! open" class="cursor-pointer" @endif>
                            <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $round->category->name }}</p>
                            <h2 class="font-semibold text-navy-900">{{ $round->name }}</h2>
                        </div>
                        @if ($round->isPublished())
                        <button type="button" @click="open = ! open" :aria-expanded="open" class="inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-sky-700 hover:bg-sky-50">
                            <span x-text="open ? 'Ringkas' : 'Tampilkan detail'">Tampilkan detail</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        @endif
                    </div>
                    @php($ready = $isReady($round))
                    <div class="mt-2 flex flex-wrap gap-1.5 text-xs font-medium">
                        @foreach ([
                            [$ready, $ready ? 'Bobot 100%' : 'Bobot belum 100%'],
                            [$round->judges->isNotEmpty(), $round->judges->count().' juri ditugaskan'],
                            [$round->entrants->isNotEmpty(), $round->entrants->count().' peserta'],
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

                @if ($round->isPublished())
                    <div x-show="! open" class="flex flex-wrap gap-x-6 gap-y-1 px-4 py-3 text-sm text-slate-600">
                        <span><span class="font-semibold text-navy-900">{{ $round->judgings->filter(fn ($sheet) => ! $sheet->isDraft())->count() }}/{{ $round->entrants->count() * $round->judges->count() }}</span> lembar terkirim</span>
                        {{-- Lock feature hidden for now (2026-11-19):
                        <span><span class="font-semibold text-navy-900">{{ $round->judgings->filter(fn ($sheet) => $sheet->status->value === 'locked')->count() }}</span> dikunci</span>
                        --}}
                        <span><span class="font-semibold text-navy-900">{{ $round->judges->count() }}</span> juri · <span class="font-semibold text-navy-900">{{ $round->entrants->count() }}</span> peserta</span>
                        <span>Diumumkan {{ $round->results_published_at->locale('id')->translatedFormat('j M Y, H.i') }}</span>
                    </div>
                @endif

                <div @if ($round->isPublished()) x-show="open" x-cloak @endif>
                @if ($round->judges->isEmpty() || $round->entrants->isEmpty())
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
                                @foreach ($round->entrants as $participant)
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
                                                        {{-- Lock feature hidden for now (2026-11-19); route and service stay, so it can come back.
                                                        @if ($sheet->status->value === 'submitted')
                                                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.judgings.lock', [$competition, $sheet]) }}">
                                                                @csrf
                                                                <button type="submit" class="cursor-pointer font-medium text-navy-700 hover:underline">Kunci</button>
                                                            </form>
                                                        @endif
                                                        --}}
                                                        @unless ($round->isPublished())
                                                        {{-- Centered modal (Batal, ×, Esc, click outside close it); a popover inside the scrolling table got clipped. --}}
                                                        <div x-data="{ open: false }">
                                                            <button type="button" @click="open = true" class="cursor-pointer font-medium text-red-600 hover:underline">Buka ulang</button>
                                                            <x-ui.modal title="Buka ulang lembar nilai">
                                                                <form method="POST" action="{{ route('dashboard.content.competitions.judging.judgings.reopen', [$competition, $sheet]) }}" class="space-y-3 text-left">
                                                                    @csrf
                                                                    <p class="text-sm text-slate-600">
                                                                        Lembar <span class="font-semibold text-navy-900">{{ $roundJudge->name }}</span> untuk
                                                                        <span class="font-semibold text-navy-900">{{ $participant->competition_number }} · {{ $participant->name }}</span>
                                                                        kembali menjadi draft supaya juri bisa memperbaiki nilainya. Nilai yang sudah diisi tetap tersimpan, dan pembukaan ulang ini tercatat.
                                                                    </p>
                                                                    <label class="{{ $label }}">Alasan (wajib, tercatat)<textarea name="reason" rows="3" required maxlength="500" placeholder="mis. Juri salah ketik nilai Formasi" class="{{ $field }} mt-1" x-effect="open && $nextTick(() => $el.focus())"></textarea></label>
                                                                    <div class="flex justify-end gap-2">
                                                                        <x-ui.button type="button" size="sm" variant="secondary" @click="open = false">Batal</x-ui.button>
                                                                        <x-ui.button type="submit" size="sm" variant="danger">Buka ulang</x-ui.button>
                                                                    </div>
                                                                </form>
                                                            </x-ui.modal>
                                                        </div>
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
                </div>
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
        <p class="mb-4 text-sm text-slate-500">"Publikasikan Hasil" menghitung ulang lalu menampilkan peringkat di halaman publik. Hasil dihitung dari lembar yang sudah dikirim. Peserta baru masuk peringkat setelah semua juri di babak itu mengirim nilainya. Nilai tiap kriteria dirata-rata dari semua juri, lalu dikalikan bobot. Hitung ulang kapan saja setelah ada nilai baru.</p>

        @forelse ($activeCategory?->rounds ?? collect() as $round)
            {{-- Finished (published) rounds start collapsed to the top 3 (click anywhere on the card to open); rounds still running are shown in full. --}}
            <section class="mb-6 overflow-hidden rounded-lg border border-slate-200 bg-white transition-colors" x-data="{ open: {{ $round->isPublished() ? 'false' : 'true' }} }"
                     @if ($round->isPublished()) @click="! open && ! $event.target.closest('a, button, form, input, select, textarea, summary') && (open = true)" :class="! open && 'cursor-pointer hover:border-slate-300 hover:shadow-sm'" @endif>
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div @if ($round->isPublished()) @click="open = ! open" class="cursor-pointer" @endif>
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
                        @if ($round->isPublished() && $round->next_round_started)
                            {{-- The next round is judged from this ranking: it stays public for good. --}}
                            <span class="cursor-not-allowed px-3 py-1.5 text-xs font-medium text-slate-400" title="{{ $round->next_round_name.' sudah mulai dinilai memakai peringkat ini, jadi hasilnya tidak bisa ditarik lagi.' }}">Tarik dari publik</span>
                        @elseif ($round->isPublished())
                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.results.unpublish', [$competition, $round]) }}" data-confirm-title="{{ 'Tarik hasil '.$round->name.' dari publik?' }}" data-confirm="{{ 'Halaman publik akan kembali menampilkan "masih berlangsung" untuk babak ini. Peringkat yang sudah dihitung tetap tersimpan.'.($round->next_round_has_teams ? ' Peserta '.$round->next_round_name.' yang sudah dipilih akan dikosongkan, karena dipilih dari peringkat ini.' : '') }}" data-confirm-button="Ya, tarik" data-confirm-icon="warning">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" size="sm" variant="ghost">Tarik dari publik</x-ui.button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('dashboard.content.competitions.judging.results.publish', [$competition, $round]) }}" @php([$publishText, $publishHasWarnings] = $publishConfirm($round)) data-confirm-title="{{ 'Publikasikan hasil '.$round->name.'?' }}" data-confirm="{{ $publishText }}" data-confirm-button="{{ $publishHasWarnings ? 'Tetap publikasikan' : 'Ya, publikasikan' }}" data-confirm-icon="{{ $publishHasWarnings ? 'warning' : 'question' }}">
                                @csrf
                                <x-ui.button type="submit" size="sm">Publikasikan Hasil</x-ui.button>
                            </form>
                        @endif
                        @if ($round->isPublished())
                        <button type="button" @click="open = ! open" :aria-expanded="open" class="inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-sky-700 hover:bg-sky-50">
                            <span x-text="open ? 'Ringkas' : 'Tampilkan detail'">Tampilkan detail</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        @endif
                    </div>
                </header>

                @if ($round->isPublished())
                    <div x-show="! open" class="px-4 py-3 text-sm">
                        <ol class="flex flex-wrap gap-x-6 gap-y-1">
                            @foreach ($round->results->take(3) as $result)
                                <li class="inline-flex min-w-0 items-center gap-2">
                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $result->rank }}</span>
                                    <span class="truncate"><span class="font-semibold text-navy-900">{{ $result->participant->competition_number ?? '—' }}</span> <span class="text-slate-600">{{ $result->participant->name }}</span></span>
                                    <span class="font-bold text-navy-900 tabular-nums">{{ $number($result->weighted_score) }}</span>
                                </li>
                            @endforeach
                        </ol>
                        <p class="mt-1.5 text-xs text-slate-500">{{ $round->results->count() }} peserta diperingkat · diumumkan {{ $round->results_published_at->locale('id')->translatedFormat('j M Y, H.i') }}</p>
                    </div>
                @endif

                <div @if ($round->isPublished()) x-show="open" x-cloak @endif>
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
                    @php($judgeProgress = $judgeSummary($round))
                    {{-- Long lists stay short: per-judge summary first, then 5 rows and a toggle; the full list scrolls inside the card. --}}
                    <div class="border-t border-slate-100 bg-amber-50/40 px-4 py-3" x-data="{ all: false }">
                        <h3 class="text-xs font-semibold tracking-wide text-amber-800 uppercase">Belum diperingkat ({{ $notRanked->count() }})</h3>

                        @if ($judgeProgress->isNotEmpty())
                            <dl class="mt-2 divide-y divide-amber-100 rounded-md border border-amber-100 bg-white text-sm">
                                @foreach ($judgeProgress as $progress)
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5 px-3 py-2">
                                        <dt class="font-medium text-navy-900">{{ $progress['name'] }}</dt>
                                        <dd class="text-xs text-slate-600">
                                            <span class="font-semibold text-slate-800">{{ $progress['sent'] }}/{{ $progress['total'] }}</span> terkirim
                                            @if ($progress['notStarted'] > 0)
                                                · {{ $progress['notStarted'] }} belum menilai
                                            @endif
                                            @if ($progress['draft'] > 0)
                                                · <span class="text-amber-700">{{ $progress['draft'] }} masih draft</span>
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        <ul class="mt-3 divide-y divide-amber-100 text-sm" :class="all && 'max-h-[60vh] overflow-y-auto pr-1'">
                            @foreach ($notRanked->values() as $index => $item)
                                <li class="py-1.5 md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)_minmax(0,2fr)] md:gap-4" @if ($index >= 5) x-show="all" x-cloak @endif>
                                    <span class="block min-w-0 truncate">
                                        <span class="font-semibold text-navy-900">{{ $item['participant']->competition_number ?? '—' }}</span>
                                        <span class="text-slate-700">· {{ $item['participant']->name }}</span>
                                    </span>
                                    <span class="block text-xs text-slate-600 md:pt-0.5">{{ $item['reason'] }}</span>
                                    <span class="block text-xs text-slate-500 md:pt-0.5">{{ $item['waiting']->isNotEmpty() ? 'Menunggu: '.$item['waiting']->implode(', ') : '' }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if ($notRanked->count() > 5)
                            <button type="button" @click="all = ! all" class="mt-2 inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-brand-700 hover:underline">
                                <span x-text="all ? 'Ringkas' : @js('Tampilkan semua ('.$notRanked->count().')')">Tampilkan semua ({{ $notRanked->count() }})</span>
                                <svg class="h-3.5 w-3.5 transition-transform" :class="all && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                        @endif
                    </div>
                @endif
                </div>
            </section>
        @empty
            <x-ui.empty-state title="Belum ada babak." />
        @endforelse
    @endif
@endsection
