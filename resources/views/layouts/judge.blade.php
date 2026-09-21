<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Penilaian') — Siapgrak Judge</title>
    @include('partials.head-fonts')
    @vite(['resources/css/app.css'])
</head>
{{-- Deliberately no sidebar/topbar/settings — terpisah total dari dashboard
     organizer (coding-standards.md, fr-scoring.md). Juri: awam, cuma aktif
     hari-H — nilai -> simpan draft -> lanjut, tanpa navigasi tambahan. --}}
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white px-4 py-3">
        <div class="mx-auto flex max-w-lg items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-800">Siapgrak Judge</div>
                @isset($judge)
                    <div class="text-xs text-slate-400">{{ $judge->name }}</div>
                @endisset
            </div>
            @isset($judge)
                <form method="POST" action="{{ route('judge.logout') }}">
                    @csrf
                    <button type="submit" class="cursor-pointer text-xs font-medium text-slate-500 hover:underline">Keluar</button>
                </form>
            @endisset
        </div>
    </header>

    <main class="mx-auto max-w-lg px-4 py-6">
        @include('partials.flash-messages')
        @yield('content')
    </main>
</body>
</html>
