<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Siapgrak</title>
    @include('partials.head-fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden bg-slate-50 text-slate-900 antialiased">
    @php
        // Computed once and shared with sidebar + topbar so the org-context
        // query doesn't run twice per request (only one of the two renders
        // per breakpoint, but Blade compiles both branches server-side).
        $shellOrganizations = auth()->user()->organizations()->orderBy('name')->get();
    @endphp

    <div class="flex min-h-screen">
        <x-layout.sidebar :organizations="$shellOrganizations" />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layout.topbar :organizations="$shellOrganizations" />

            <main class="flex-1 px-4 py-6 pb-24 md:px-8 md:py-8 md:pb-8">
                @include('partials.flash-messages')
                @yield('content')
            </main>
        </div>
    </div>

    <x-layout.mobile-nav />
</body>
</html>
