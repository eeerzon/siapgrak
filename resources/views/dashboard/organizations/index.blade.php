@extends('layouts.dashboard')

@section('title', 'Organizations')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-800">Organisasi Anda</h2>
        <a href="{{ route('dashboard.organizations.create') }}"
           class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
            + Buat Organisasi
        </a>
    </div>

    @if ($organizations->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-base font-medium text-slate-700">Anda belum tergabung di organisasi manapun.</p>
            <p class="mt-1 text-sm text-slate-500">Buat organisasi untuk mulai mengelola event LKBB/Paskibra.</p>
            <a href="{{ route('dashboard.organizations.create') }}"
               class="mt-4 inline-block rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                Buat Organisasi Pertama
            </a>
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($organizations as $organization)
                <a href="{{ route('dashboard.organizations.show', $organization) }}"
                   class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow">
                    <div class="font-medium text-slate-800">{{ $organization->name }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $organization->type->value }}</div>
                    <div class="mt-2 text-xs uppercase tracking-wide text-slate-400">
                        {{ $organization->pivot->role->value }}
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
