@extends('layouts.dashboard')

@section('title', 'Events')

@section('content')
    <x-layout.page-header title="Events" description="Event LKBB/Paskibra yang dikelola organisasi Anda.">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.events.create') }}">+ Buat Event</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @php($isFiltered = request()->filled('search') || request()->filled('status'))

    <form method="GET" action="{{ route('dashboard.events.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-64">
            <x-ui.input name="search" label="Cari" :value="request('search')" placeholder="Nama event" />
        </div>
        <div class="w-full sm:w-52">
            <x-ui.select name="status" label="Status">
                <option value="">Semua status</option>
                @foreach (\App\Enums\EventStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(request('status') === $case->value)>
                        {{ \Illuminate\Support\Str::headline($case->value) }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        @if ($isFiltered)
            <a href="{{ route('dashboard.events.index') }}" class="pb-2 text-sm text-slate-500 hover:underline">Reset</a>
        @endif
    </form>

    @if ($events->isEmpty())
        <x-ui.empty-state
            :title="$isFiltered ? 'Tidak ada event yang cocok dengan filter.' : 'Belum ada event.'"
            :description="$isFiltered
                ? 'Coba ubah kata kunci atau status, atau reset filter.'
                : 'Buat event untuk mulai menyusun kategori, jadwal, dan penjurian.'"
        >
            @unless ($isFiltered)
                <x-slot:action>
                    <x-ui.button href="{{ route('dashboard.events.create') }}">Buat Event</x-ui.button>
                </x-slot:action>
            @endunless
        </x-ui.empty-state>
    @else
        {{-- Desktop: table (>= md). Mobile: card list below. --}}
        <div class="hidden md:block">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <x-ui.table-th>Event</x-ui.table-th>
                        <x-ui.table-th>Tanggal</x-ui.table-th>
                        <x-ui.table-th>Venue</x-ui.table-th>
                        <x-ui.table-th>Status</x-ui.table-th>
                        <x-ui.table-th align="right">Kategori</x-ui.table-th>
                        <x-ui.table-th align="right">Staff</x-ui.table-th>
                    </tr>
                </x-slot:head>

                @foreach ($events as $event)
                    <tr class="hover:bg-slate-50">
                        <x-ui.table-td>
                            <a href="{{ route('dashboard.events.show', $event) }}" class="font-medium text-slate-800 hover:underline">
                                {{ $event->name }}
                            </a>
                            <div class="text-xs text-slate-400">{{ $event->organization?->name ?? '—' }}</div>
                        </x-ui.table-td>
                        <x-ui.table-td muted>@include('dashboard.events.partials.date-range', ['start' => $event->start_date, 'end' => $event->end_date])</x-ui.table-td>
                        <x-ui.table-td muted>{{ $event->venue_name ?: '—' }}</x-ui.table-td>
                        <x-ui.table-td><x-ui.status-badge :status="$event->status" /></x-ui.table-td>
                        <x-ui.table-td align="right" muted>{{ $event->categories_count }}</x-ui.table-td>
                        <x-ui.table-td align="right" muted>{{ $event->staff_count }}</x-ui.table-td>
                    </tr>
                @endforeach
            </x-ui.table>
        </div>

        {{-- Mobile: cards (< md) --}}
        <div class="space-y-3 md:hidden">
            @foreach ($events as $event)
                <a href="{{ route('dashboard.events.show', $event) }}"
                   class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium text-slate-800">{{ $event->name }}</span>
                        <x-ui.status-badge :status="$event->status" />
                    </div>
                    <dl class="mt-2 space-y-1 text-sm text-slate-500">
                        <div class="flex justify-between gap-2">
                            <dt>Tanggal</dt>
                            <dd class="text-right text-slate-600">@include('dashboard.events.partials.date-range', ['start' => $event->start_date, 'end' => $event->end_date])</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt>Venue</dt>
                            <dd class="text-right text-slate-600">{{ $event->venue_name ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt>Kategori / Staff</dt>
                            <dd class="text-right text-slate-600">{{ $event->categories_count }} / {{ $event->staff_count }}</dd>
                        </div>
                    </dl>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $events->links() }}
        </div>
    @endif
@endsection
