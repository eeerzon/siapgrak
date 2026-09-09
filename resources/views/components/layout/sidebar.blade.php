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
        <a href="{{ route('dashboard.teams.index') }}"
           class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard.teams.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.7M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
            Teams
        </a>
        <a href="{{ route('dashboard.organizations.index') }}"
           class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard.organizations.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            Organizations
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
