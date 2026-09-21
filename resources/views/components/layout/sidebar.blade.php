{{--
    Desktop-only (md:+). Nav items are route-gated — only add an item here once
    its route actually exists (see CLAUDE.md "Sidebar dashboard" rule). $organizations
    is passed down from layouts.dashboard so the org-context query runs once,
    shared with topbar.blade.php.
--}}
@props(['organizations'])

@php
    $primaryOrganization = $organizations->first();
@endphp

<aside class="hidden md:sticky md:top-0 md:flex md:h-screen md:w-60 md:shrink-0 md:flex-col md:border-r md:border-slate-200 md:bg-white">
    <div class="px-4 pt-5 pb-3">
        <div class="text-lg font-semibold text-slate-800">Siapgrak</div>

        <div class="mt-3 rounded-md bg-slate-50 px-3 py-2">
            <p class="truncate text-sm font-medium text-slate-700">
                {{ $primaryOrganization?->name ?? 'Belum ada organisasi' }}
            </p>
            <p class="text-xs text-slate-400">
                {{ $organizations->count() > 1 ? $organizations->count().' organisasi' : 'Organization' }}
            </p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 px-2 py-2">
        <a href="{{ route('dashboard.index') }}"
           class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard.index') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
            </svg>
            Dashboard
        </a>
        <a href="{{ route('dashboard.events.index') }}"
           class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard.events.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
            Events
        </a>
        <a href="{{ route('dashboard.teams.index') }}"
           class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard.teams.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.7M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
            Teams
        </a>
    </nav>

    <div class="border-t border-slate-200 px-2 py-2">
        <x-layout.account-menu>
            <x-slot:trigger>
                <button type="button" class="flex w-full cursor-pointer items-center gap-2.5 rounded-md px-3 py-2 text-left hover:bg-slate-50">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">
                        {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">{{ auth()->user()->name }}</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                    </svg>
                </button>
            </x-slot:trigger>
        </x-layout.account-menu>
    </div>
</aside>
