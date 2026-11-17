@extends('layouts.dashboard')

@section('title', ($registration->team?->name ?? 'Pendaftaran') . ' — ' . $event->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => $event->name, 'href' => route('dashboard.events.show', $event)],
        ['label' => 'Peserta', 'href' => route('dashboard.events.registrations.index', $event)],
        ['label' => $registration->team?->name ?? 'Pendaftaran'],
    ]" />

    <x-layout.page-header :title="$registration->team?->name ?? 'Pendaftaran'" :description="$registration->category?->name" />

    <div class="mb-5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
        <x-ui.status-badge :status="$registration->status" />
        <span class="text-slate-300">·</span>
        <span>Input oleh {{ $registration->submitter?->name ?? '—' }}</span>
        @if ($registration->submitted_at)
            <span class="text-slate-300">·</span>
            <span>Submit {{ $registration->submitted_at->format('j M Y, H:i') }}</span>
        @endif
        @if ($registration->reviewed_at)
            <span class="text-slate-300">·</span>
            <span>Direview {{ $registration->reviewer?->name ?? '—' }} · {{ $registration->reviewed_at->format('j M Y, H:i') }}</span>
        @endif
    </div>

    @if ($registration->review_notes)
        <div class="mb-6 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span class="font-medium">Catatan review:</span> {{ $registration->review_notes }}
        </div>
    @endif

    @can('updateStatus', $registration)
        <x-ui.card class="mb-6">
            @if (count($allowedTransitions) === 0)
                <p class="text-sm text-slate-400">Status <strong>{{ \Illuminate\Support\Str::headline($registration->status->value) }}</strong> tidak punya transisi berikutnya.</p>
            @else
                <form method="POST" action="{{ route('dashboard.events.registrations.status', [$event, $registration]) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-slate-700">Ubah status:</span>
                        <select name="status" required
                                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ($allowedTransitions as $target)
                                <option value="{{ $target->value }}">{{ \Illuminate\Support\Str::headline($target->value) }}</option>
                            @endforeach
                        </select>
                        <x-ui.button type="submit" size="sm" variant="secondary">Terapkan</x-ui.button>
                        @error('status')<span class="text-sm text-red-600">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <textarea name="review_notes" rows="2" placeholder="Alasan / catatan — wajib saat menolak atau minta revisi"
                                  class="block w-full rounded-md border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('review_notes') }}</textarea>
                        @error('review_notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </form>
            @endif
        </x-ui.card>
    @endcan

    <div class="grid gap-6 lg:grid-cols-2">
        <div>
            <h2 class="mb-3 text-sm font-medium text-slate-700">Roster Anggota ({{ $registration->members->count() }})</h2>
            @include('dashboard.registrations.partials.roster')
        </div>
        <div>
            <h2 class="mb-3 text-sm font-medium text-slate-700">Dokumen ({{ $registration->documents->count() }})</h2>
            @include('dashboard.registrations.partials.documents')
        </div>
    </div>
@endsection
