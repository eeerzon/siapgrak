<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Siapgrak')</title>
    @include('partials.head-fonts')
    @include('partials.head-icons')
    @vite(['resources/css/app.css'])
</head>
<body class="bg-navy-950 text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a href="{{ route('home') }}" class="mb-8 flex flex-col items-center gap-3 text-xl font-semibold text-white">
            <img src="{{ asset('images/siapgrak-logo.png') }}" alt="" class="h-24 w-24">
            Siapgrak
        </a>
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-sm">
            @include('partials.flash-messages')
            @yield('content')
        </div>
    </div>
</body>
</html>
