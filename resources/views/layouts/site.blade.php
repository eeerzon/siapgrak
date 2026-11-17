<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- <title>@yield('title', 'Rumah Informasi Paskibra & LKBB') — Siapgrak</title> -->
    <title>Siapgrak</title>
    <meta name="description" content="@yield('description', 'Info lomba dan kejuaraan, agenda kegiatan, serta prestasi dan kabar komunitas Paskibra dan LKBB.')">
    @include('partials.head-fonts')
    @include('partials.head-icons')
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="h-1 bg-brand-600"></div>
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-8 px-4 md:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 py-2.5">
                <img src="{{ asset('images/siapgrak-logo.png') }}" alt="" class="h-10 w-10">
                <span class="leading-tight">
                    <span class="block text-lg font-bold tracking-tight text-navy-900">Siapgrak</span>
                    <span class="block text-[11px] font-medium text-slate-500">Info Paskibra &amp; LKBB</span>
                </span>
            </a>

            <a href="{{ auth()->check() ? route('dashboard.index') : route('register') }}"
               class="rounded-md bg-gray-200 px-3.5 py-2 text-sm font-medium text-navy-900 hover:bg-slate-200 md:order-last">
                {{ auth()->check() ? 'Dashboard' : 'Daftar' }}
            </a>

            <a href="{{ auth()->check() ? route('dashboard.index') : route('login') }}"
               class="rounded-md bg-navy-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-navy-800 md:order-last">
                {{ auth()->check() ? 'Dashboard' : 'Masuk' }}
            </a>

            <nav class="-mx-1 flex w-full gap-1 overflow-x-auto text-sm font-medium md:mx-0 md:w-auto md:flex-1 md:self-stretch" aria-label="Navigasi utama">
                @foreach ([
                    ['Info Lomba', 'site.competitions.index', 'site.competitions.*'],
                    ['Agenda', 'site.agenda.index', 'site.agenda.*'],
                    ['Prestasi & Kabar', 'site.community.index', 'site.community.*'],
                ] as [$label, $route, $pattern])
                    <a href="{{ route($route) }}"
                       @if (request()->routeIs($pattern)) aria-current="page" @endif
                       class="flex items-center border-b-2 px-3 py-2.5 whitespace-nowrap {{ request()->routeIs($pattern) ? 'border-brand-600 text-navy-900' : 'border-transparent text-slate-600 hover:text-navy-900' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-navy-950 text-slate-300">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)] md:px-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/siapgrak-logo.png') }}" alt="" class="h-10 w-10">
                    <span class="text-lg font-bold text-white">Siapgrak</span>
                </div>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-400">
                    Info lomba, agenda, dan kabar prestasi untuk satuan Paskibra dan pegiat LKBB di seluruh Indonesia.
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Jelajahi</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('site.competitions.index') }}" class="hover:text-white">Info Lomba</a></li>
                    <li><a href="{{ route('site.agenda.index') }}" class="hover:text-white">Agenda</a></li>
                    <li><a href="{{ route('site.community.index') }}" class="hover:text-white">Prestasi &amp; Kabar</a></li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Ikuti Kami</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="https://www.instagram.com/siapgrak.id/" target="_blank" rel="noopener" class="hover:text-white">Instagram @siapgrak.id</a></li>
                    <!-- <li><a href="{{ route('login') }}" class="hover:text-white">Masuk</a></li> -->
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <p class="mx-auto max-w-6xl px-4 py-4 text-xs text-slate-500 md:px-6">&copy; {{ now()->year }} Siapgrak.</p>
        </div>
    </footer>
</body>
</html>
