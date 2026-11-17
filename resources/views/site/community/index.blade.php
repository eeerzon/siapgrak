@extends('layouts.site')

@section('title', 'Prestasi & Kabar Komunitas')

@php($searchQuery = array_filter(['q' => $search]))

@section('content')
    <x-site.page-intro title="Prestasi & Kabar Komunitas" description="Kabar juara, cerita latihan, dan tulisan seputar dunia baris-berbaris.">
        <x-site.filter-tabs :tabs="[
            ['Semua', route('site.community.index', $searchQuery), ! $type],
            ...collect(\App\Enums\CommunityPostType::cases())->map(fn ($case) => [
                $case->label(),
                route('site.community.index', ['tipe' => $case->value] + $searchQuery),
                $type === $case,
            ])->all(),
        ]" />

        <form method="GET" action="{{ route('site.community.index') }}" data-auto-filter class="mt-4 max-w-xl" role="search">
            @if ($type)
                <input type="hidden" name="tipe" value="{{ $type->value }}">
            @endif
            @include('site.partials.search-field', ['value' => $search, 'placeholder' => 'Cari judul atau isi kabar'])
            <noscript><button type="submit" class="mt-2 rounded-md bg-navy-900 px-4 py-2 text-sm font-medium text-white">Cari</button></noscript>
        </form>
        @include('site.partials.auto-filter-script')
    </x-site.page-intro>

    <div class="mx-auto max-w-6xl px-4 py-8 md:px-6">
        @if ($communityPosts->isEmpty())
            @if ($search)
                <x-ui.empty-state title="Tidak ada kabar dengan kata kunci “{{ $search }}”." description="Coba kata kunci lain." />
            @else
                <x-ui.empty-state title="Belum ada tulisan di kategori ini." />
            @endif
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($communityPosts as $communityPost)
                    @include('site.partials.community-card')
                @endforeach
            </div>
            <div class="mt-6">{{ $communityPosts->links('partials.pagination') }}</div>
        @endif
    </div>
@endsection
