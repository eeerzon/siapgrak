@extends('layouts.site')

@section('title', $communityPost->title)
@section('description', $communityPost->excerpt ?? 'Prestasi dan kabar komunitas di Siapgrak.')

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-8 md:px-6">
        <a href="{{ route('site.community.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">← Prestasi &amp; Kabar</a>

        <p class="mt-4 text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $communityPost->type->label() }}</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-navy-900 md:text-3xl">{{ $communityPost->title }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $communityPost->published_at->copy()->locale('id')->translatedFormat('j F Y') }}</p>

        @if ($communityPost->coverUrl())
            <x-site.cover :url="$communityPost->coverUrl()" class="mt-6 rounded-lg" />
        @endif

        @if ($communityPost->excerpt)
            <p class="mt-6 text-lg text-slate-600">{{ $communityPost->excerpt }}</p>
        @endif
        <x-site.article-body :text="$communityPost->body" />
    </article>
@endsection
