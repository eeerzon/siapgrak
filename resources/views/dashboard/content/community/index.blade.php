@extends('layouts.dashboard')

@section('title', 'Konten — Prestasi & Kabar')

@section('content')
    <x-layout.page-header title="Events" description="Kelola konten yang tampil di halaman publik Siapgrak.">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.content.community.create') }}">+ Tambah Kabar</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @include('dashboard.content.partials.tabs')
    @include('dashboard.content.partials.search', ['placeholder' => 'Cari judul atau ringkasan'])

    @if ($communityPosts->isEmpty() && $search)
        <x-ui.empty-state title="Tidak ada yang cocok dengan “{{ $search }}”." />
    @elseif ($communityPosts->isEmpty())
        <x-ui.empty-state title="Belum ada prestasi atau kabar." description="Tambahkan kabar pertama untuk ditampilkan di halaman utama." />
    @else
        <div class="hidden md:block">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <x-ui.table-th>Judul</x-ui.table-th>
                        <x-ui.table-th>Jenis</x-ui.table-th>
                        <x-ui.table-th>Terbit</x-ui.table-th>
                        <x-ui.table-th>Status</x-ui.table-th>
                        <x-ui.table-th align="right">Aksi</x-ui.table-th>
                    </tr>
                </x-slot:head>
                @foreach ($communityPosts as $communityPost)
                    <tr class="hover:bg-slate-50">
                        <x-ui.table-td>
                            <a href="{{ route('dashboard.content.community.edit', $communityPost) }}" class="font-medium text-slate-800 hover:underline">{{ $communityPost->title }}</a>
                        </x-ui.table-td>
                        <x-ui.table-td muted>{{ $communityPost->type->label() }}</x-ui.table-td>
                        <x-ui.table-td muted>{{ $communityPost->published_at?->copy()->locale('id')->translatedFormat('j M Y') ?? '—' }}</x-ui.table-td>
                        <x-ui.table-td><x-ui.status-badge :status="$communityPost->status" /></x-ui.table-td>
                        <x-ui.table-td align="right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('dashboard.content.community.edit', $communityPost) }}" class="text-sm font-medium text-sky-700 hover:underline">Edit</a>
                                @include('dashboard.content.partials.delete-form', ['action' => route('dashboard.content.community.destroy', $communityPost)])
                            </div>
                        </x-ui.table-td>
                    </tr>
                @endforeach
            </x-ui.table>
        </div>

        <div class="space-y-3 md:hidden">
            @foreach ($communityPosts as $communityPost)
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium text-slate-800">{{ $communityPost->title }}</span>
                        <x-ui.status-badge :status="$communityPost->status" />
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $communityPost->type->label() }}</p>
                    <div class="mt-3 flex items-center gap-4">
                        <a href="{{ route('dashboard.content.community.edit', $communityPost) }}" class="text-sm font-medium text-sky-700 hover:underline">Edit</a>
                        @include('dashboard.content.partials.delete-form', ['action' => route('dashboard.content.community.destroy', $communityPost)])
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $communityPosts->links('partials.pagination') }}</div>
    @endif
@endsection
