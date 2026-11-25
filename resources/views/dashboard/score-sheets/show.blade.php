@extends('layouts.dashboard')

@section('title', $sheet->judge?->name.' — '.($sheet->entry?->team?->name ?? 'Lembar Skor'))

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => $event->name, 'href' => route('dashboard.events.show', $event)],
        ['label' => 'Lembar Skor'],
    ]" />

    <x-layout.page-header :title="$sheet->judge?->name.' — '.($sheet->entry?->competition_number ?? $sheet->entry?->team?->name)">
        <x-slot:actions>
            <x-ui.status-badge :status="$sheet->status" />
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
        <span>{{ $sheet->round->category->name }} · {{ $sheet->round->name }}</span>
        <span class="text-slate-300">·</span>
        <span>{{ $sheet->entry?->team?->name }}</span>
    </div>

    <x-ui.card>
        <h3 class="mb-3 text-sm font-semibold text-slate-800">Skor per Kriteria</h3>

        @if ($sheet->scores->isEmpty())
            <p class="text-sm text-slate-400">Belum ada skor tersimpan.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="py-2">Kriteria</th>
                        <th class="py-2">Skor</th>
                        <th class="py-2">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sheet->scores as $score)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 text-slate-700">{{ $score->criteria?->name ?? '—' }}</td>
                            <td class="py-2 font-medium text-slate-800">{{ $score->score }}</td>
                            <td class="py-2 text-slate-500">{{ $score->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <dl class="mt-4 grid grid-cols-2 gap-2 text-xs text-slate-500 sm:grid-cols-4">
            <div>
                <dt class="uppercase tracking-wide text-slate-400">Dikirim</dt>
                <dd class="mt-0.5">{{ $sheet->submitted_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="uppercase tracking-wide text-slate-400">Dikunci</dt>
                <dd class="mt-0.5">{{ $sheet->locked_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="uppercase tracking-wide text-slate-400">Direvisi</dt>
                <dd class="mt-0.5">{{ $sheet->revision_count }}x</dd>
            </div>
        </dl>
    </x-ui.card>

    @canany(['manageJudging', 'reopenLockedScoreSheet'], $event)
        <x-ui.card class="mt-4">
            <h3 class="mb-3 text-sm font-semibold text-slate-800">Aksi Panitia</h3>

            <div class="flex flex-wrap gap-3">
                @can('manageJudging', $event)
                    @if ($sheet->status->value === 'submitted')
                        <form method="POST" action="{{ route('dashboard.score-sheets.lock', $sheet) }}"
                              data-confirm-title="Kunci lembar skor?" data-confirm="Juri tidak bisa mengubahnya lagi setelah dikunci." data-confirm-button="Ya, kunci" data-confirm-icon="warning">
                            @csrf
                            <x-ui.button type="submit" size="sm">Kunci</x-ui.button>
                        </form>
                    @endif
                @endcan
            </div>

            @php
                $canReopen = $sheet->status->value === 'submitted'
                    ? auth()->user()->can('manageJudging', $event)
                    : ($sheet->status->value === 'locked' && auth()->user()->can('reopenLockedScoreSheet', $event));
            @endphp

            @if ($canReopen)
                <form method="POST" action="{{ route('dashboard.score-sheets.reopen', $sheet) }}" class="mt-4 space-y-2">
                    @csrf
                    <x-ui.textarea name="reason" label="Alasan Reopen (wajib)" rows="2" required />
                    <x-ui.button type="submit" variant="danger" size="sm">Buka Kembali untuk Juri</x-ui.button>
                </form>
            @elseif ($sheet->status->value === 'locked')
                <p class="mt-3 text-xs text-slate-400">Lembar skor yang sudah dikunci hanya bisa dibuka kembali oleh Owner organisasi.</p>
            @endif
        </x-ui.card>
    @endcanany

    @if ($sheet->revisions->isNotEmpty())
        <x-ui.card class="mt-4">
            <h3 class="mb-3 text-sm font-semibold text-slate-800">Riwayat Reopen</h3>
            <ul class="space-y-3 text-sm">
                @foreach ($sheet->revisions as $revision)
                    <li class="border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                        <div class="text-slate-700">{{ $revision->reason }}</div>
                        <div class="mt-0.5 text-xs text-slate-400">
                            dari status "{{ $revision->previous_status }}" oleh {{ $revision->requestedBy?->name ?? 'user terhapus' }} · {{ $revision->created_at->translatedFormat('d M Y H:i') }}
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
@endsection
