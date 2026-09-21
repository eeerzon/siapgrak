@extends('layouts.dashboard')

@section('title', 'Peserta — ' . $event->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => $event->name, 'href' => route('dashboard.events.show', $event)],
        ['label' => 'Peserta'],
    ]" />

    <x-layout.page-header title="Peserta" :description="$event->name">
        <x-slot:actions>
            @can('manageRegistrations', $event)
                <x-ui.button variant="secondary" href="{{ route('dashboard.events.registrations.import.create', $event) }}">Import CSV</x-ui.button>
                <x-ui.button href="{{ route('dashboard.events.registrations.create', $event) }}">+ Tambah Peserta</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    @php($isFiltered = request()->filled('status') || request()->filled('category'))

    <form method="GET" action="{{ route('dashboard.events.registrations.index', $event) }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-48">
            <x-ui.select name="status" label="Status">
                <option value="">Semua status</option>
                @foreach (\App\Enums\RegistrationStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected(request('status') === $case->value)>
                        {{ \Illuminate\Support\Str::headline($case->value) }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="w-full sm:w-52">
            <x-ui.select name="category" label="Kategori">
                <option value="">Semua kategori</option>
                @foreach ($event->categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        @if ($isFiltered)
            <a href="{{ route('dashboard.events.registrations.index', $event) }}" class="pb-2 text-sm text-slate-500 hover:underline">Reset</a>
        @endif
    </form>

    @if ($registrations->isEmpty())
        <x-ui.empty-state
            :title="$isFiltered ? 'Tidak ada pendaftaran yang cocok dengan filter.' : 'Belum ada pendaftaran.'"
            :description="$isFiltered ? 'Coba ubah filter atau reset.' : 'Tambah peserta untuk mulai mencatat pendaftaran lomba.'"
        >
            @unless ($isFiltered)
                @can('manageRegistrations', $event)
                    <x-slot:action>
                        <x-ui.button href="{{ route('dashboard.events.registrations.create', $event) }}">Tambah Peserta</x-ui.button>
                    </x-slot:action>
                @endcan
            @endunless
        </x-ui.empty-state>
    @else
        {{-- Row click opens a quick-view drawer (frontend-plan.md F6) — the full
             page (roster/dokumen/status) stays one click away from inside it,
             reserved for actual edits rather than the default interaction. --}}
        <div x-data="{ open: false, selected: null }">
            <div class="hidden md:block">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <x-ui.table-th>Team</x-ui.table-th>
                            <x-ui.table-th>Kategori</x-ui.table-th>
                            <x-ui.table-th>No. Kompetisi</x-ui.table-th>
                            <x-ui.table-th align="right">Anggota</x-ui.table-th>
                            <x-ui.table-th>Status</x-ui.table-th>
                            <x-ui.table-th align="right">Aksi</x-ui.table-th>
                        </tr>
                    </x-slot:head>

                    @foreach ($registrations as $registration)
                        <tr class="hover:bg-slate-50">
                            <x-ui.table-td>
                                <button type="button" @click="open = true; selected = {{ $registration->id }}" class="cursor-pointer font-medium text-slate-800 hover:underline">
                                    {{ $registration->team?->name ?? '—' }}
                                </button>
                            </x-ui.table-td>
                            <x-ui.table-td muted>{{ $registration->category?->name ?? '—' }}</x-ui.table-td>
                            <x-ui.table-td muted>{{ $registration->entry?->competition_number ?? '—' }}</x-ui.table-td>
                            <x-ui.table-td align="right" muted>{{ $registration->members_count }}</x-ui.table-td>
                            <x-ui.table-td><x-ui.status-badge :status="$registration->status" /></x-ui.table-td>
                            <x-ui.table-td align="right">
                                <button type="button" @click="open = true; selected = {{ $registration->id }}" class="cursor-pointer text-sm font-medium text-sky-700 hover:underline">Lihat</button>
                            </x-ui.table-td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </div>

            <div class="space-y-3 md:hidden">
                @foreach ($registrations as $registration)
                    <button type="button" @click="open = true; selected = {{ $registration->id }}"
                            class="block w-full cursor-pointer rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-slate-300">
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-medium text-slate-800">{{ $registration->team?->name ?? '—' }}</span>
                            <x-ui.status-badge :status="$registration->status" />
                        </div>
                        <dl class="mt-2 space-y-1 text-sm text-slate-500">
                            <div class="flex justify-between gap-2"><dt>Kategori</dt><dd class="text-right text-slate-600">{{ $registration->category?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between gap-2"><dt>No. Kompetisi</dt><dd class="text-right text-slate-600">{{ $registration->entry?->competition_number ?? '—' }}</dd></div>
                            <div class="flex justify-between gap-2"><dt>Anggota</dt><dd class="text-right text-slate-600">{{ $registration->members_count }}</dd></div>
                        </dl>
                    </button>
                @endforeach
            </div>

            <div class="mt-6">{{ $registrations->links() }}</div>

            <x-ui.drawer title="Detail Pendaftaran">
                @foreach ($registrations as $registration)
                    <div x-show="selected === {{ $registration->id }}" x-cloak class="space-y-5">
                        <div>
                            <p class="text-base font-semibold text-slate-800">{{ $registration->team?->name ?? '—' }}</p>
                            <p class="text-sm text-slate-500">{{ $registration->category?->name ?? '—' }}</p>
                        </div>

                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">Status</dt>
                                <dd><x-ui.status-badge :status="$registration->status" /></dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">No. Kompetisi</dt>
                                <dd class="font-medium text-slate-700">{{ $registration->entry?->competition_number ?? 'Belum ada' }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">Anggota</dt>
                                <dd class="font-medium text-slate-700">{{ $registration->members_count }} orang</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Dokumen ({{ $registration->documents->count() }})</dt>
                                <dd class="mt-1">
                                    @forelse ($registration->documents as $document)
                                        <span class="mr-1 mb-1 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ \Illuminate\Support\Str::headline($document->document_type) }}</span>
                                    @empty
                                        <span class="text-slate-400">Belum ada dokumen diunggah.</span>
                                    @endforelse
                                </dd>
                            </div>
                        </dl>

                        <x-ui.button href="{{ route('dashboard.events.registrations.show', [$event, $registration]) }}" class="w-full">
                            Buka Halaman Lengkap
                        </x-ui.button>
                    </div>
                @endforeach
            </x-ui.drawer>
        </div>
    @endif
@endsection
