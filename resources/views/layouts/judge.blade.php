<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Penilaian') · Panel Juri Siapgrak</title>
    @include('partials.head-fonts')
    @include('partials.head-icons')
    @vite(['resources/css/app.css'])
</head>
{{-- Deliberately no sidebar/topbar/settings — terpisah total dari dashboard
     organizer (coding-standards.md, fr-scoring.md). Juri: awam, cuma aktif
     hari-H — nilai -> simpan draft -> lanjut, tanpa navigasi tambahan. --}}
@php
    // Shared by the organizer-event panel (/judge) and the Info Lomba panel (/juri).
    $panel = request()->routeIs('juri.*') ? 'juri' : 'judge';
    $judge ??= null;
@endphp
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="sticky top-0 z-30 bg-navy-950 text-white">
        <div class="h-1 bg-brand-600"></div>
        <div class="mx-auto flex max-w-lg items-center justify-between gap-3 px-4 py-3">
            <a href="{{ $judge ? route($panel.'.dashboard') : route($panel.'.access.create') }}" class="flex min-w-0 items-center gap-2.5">
                <img src="{{ asset('images/siapgrak-logo.png') }}" alt="" class="h-9 w-9 shrink-0">
                <span class="min-w-0 leading-tight">
                    <span class="block text-sm font-bold">Panel Juri</span>
                    <span class="block truncate text-xs text-slate-400">{{ $judge ? $judge->name : 'Siapgrak' }}</span>
                </span>
            </a>
            @if ($judge)
                <form method="POST" action="{{ route($panel.'.logout') }}">
                    @csrf
                    <button type="submit" class="cursor-pointer rounded-md border border-white/20 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-white/10">Keluar</button>
                </form>
            @endif
        </div>
    </header>

    <main class="mx-auto max-w-lg px-4 py-6">
        @include('partials.flash-messages')
        @yield('content')
    </main>
</body>
</html>
