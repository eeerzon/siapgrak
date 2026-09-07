<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Siapgrak</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col md:flex-row">
        <aside class="w-full shrink-0 border-b border-slate-200 bg-white md:w-56 md:border-b-0 md:border-r">
            <div class="px-4 py-4 text-lg font-semibold text-slate-800">Siapgrak</div>
            <nav class="space-y-1 px-2 pb-4">
                {{-- Sidebar final CLAUDE.md: Dashboard/Events/Teams/Organization/Settings.
                     Hanya item dengan route nyata yang dirender (lihat Sprint 1 & 2 Final
                     Report) — Events/Settings menyusul begitu sprint-nya punya route. --}}
                <a href="{{ route('dashboard.teams.index') }}"
                   class="block rounded px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard.teams.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">
                    Teams
                </a>
                <a href="{{ route('dashboard.organizations.index') }}"
                   class="block rounded px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard.organizations.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">
                    Organizations
                </a>
            </nav>
        </aside>

        <div class="flex-1">
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:px-6">
                <h1 class="text-base font-semibold text-slate-800">@yield('title', 'Dashboard')</h1>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 hover:text-slate-800">
                        {{ auth()->user()->name }} — Logout
                    </button>
                </form>
            </header>

            <main class="px-4 py-6 md:px-6">
                @include('partials.flash-messages')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
