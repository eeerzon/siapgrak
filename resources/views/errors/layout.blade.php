{{-- Standalone on purpose: no auth, DB or shared layout, so it still renders when those are what failed. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Siapgrak</title>
    @include('partials.head-icons')
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-900 antialiased">
    <div class="h-1 bg-brand-600"></div>
    <main class="flex flex-1 items-center justify-center px-4 py-16">
        <div class="max-w-md text-center">
            <a href="{{ url('/') }}" class="inline-block">
                <img src="{{ asset('images/siapgrak-logo.png') }}" alt="Siapgrak" class="mx-auto h-16 w-16">
            </a>
            <p class="mt-6 text-sm font-semibold tracking-widest text-brand-600">@yield('code')</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-navy-900">@yield('heading')</h1>
            <p class="mt-3 text-slate-500">@yield('message')</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ url('/') }}" class="rounded-md bg-navy-900 px-4 py-2 text-sm font-medium text-white hover:bg-navy-800">Ke beranda</a>
                <a href="{{ url('/info-lomba') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Lihat info lomba</a>
            </div>
        </div>
    </main>
</body>
</html>
