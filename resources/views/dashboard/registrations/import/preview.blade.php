@extends('layouts.dashboard')

@section('title', 'Preview Import — ' . $event->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => $event->name, 'href' => route('dashboard.events.show', $event)],
        ['label' => 'Peserta', 'href' => route('dashboard.events.registrations.index', $event)],
        ['label' => 'Preview'],
    ]" />

    @php($validCount = $groups->where('valid', true)->count())
    @php($invalidCount = $groups->count() - $validCount)

    <x-layout.page-header title="Preview Import" :description="$category->name" />

    <x-ui.card class="mb-4">
        <p class="text-sm text-slate-700">
            <span class="font-medium text-emerald-700">{{ $validCount }} tim siap diimpor</span>
            @if ($invalidCount > 0)
                · <span class="font-medium text-red-600">{{ $invalidCount }} tim dilewati</span>
            @endif
        </p>
    </x-ui.card>

    @if ($groups->isEmpty())
        <x-ui.empty-state title="Tidak ada baris yang bisa dibaca." description="Periksa mapping kolom atau isi file CSV." />
    @else
        <div class="space-y-3">
            @foreach ($groups as $group)
                <x-ui.card>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="font-medium text-slate-800">{{ $group['team_name'] ?: '(nama tim kosong)' }}</span>
                        @if ($group['valid'])
                            <span class="text-xs font-medium text-emerald-700">Siap diimpor</span>
                        @elseif (! $group['exists'])
                            <span class="text-xs font-medium text-red-600">Tim tidak ditemukan</span>
                        @elseif ($group['duplicate'])
                            <span class="text-xs font-medium text-red-600">Sudah terdaftar di kategori ini</span>
                        @else
                            <span class="text-xs font-medium text-red-600">Tidak ada anggota valid</span>
                        @endif
                    </div>

                    <ul class="space-y-1 text-sm text-slate-600">
                        @foreach ($group['members'] as $member)
                            <li class="flex items-center gap-2">
                                @if ($member['valid'])
                                    <span>{{ $member['name'] }}</span>
                                    @if ($member['gender'])
                                        <span class="text-xs text-slate-400">({{ $member['gender'] }})</span>
                                    @endif
                                @else
                                    <span class="text-red-500">Baris dilewati — nama anggota kosong</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endforeach
        </div>

        @if ($validCount > 0)
            <form method="POST" action="{{ route('dashboard.events.registrations.import.commit', [$event, $token]) }}" class="mt-6"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <input type="hidden" name="event_category_id" value="{{ $category->id }}">
                <input type="hidden" name="team_name" value="{{ $mapping['team_name'] }}">
                <input type="hidden" name="member_name" value="{{ $mapping['member_name'] }}">
                <input type="hidden" name="gender" value="{{ $mapping['gender'] }}">
                <input type="hidden" name="member_role" value="{{ $mapping['member_role'] }}">
                <x-ui.button type="submit">Import {{ $validCount }} Tim</x-ui.button>
            </form>
        @endif
    @endif
@endsection
