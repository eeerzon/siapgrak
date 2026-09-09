{{--
    Fixed bottom nav, mobile-only. Only 3 tabs (Teams, Organizations, Lainnya) —
    do not add more without a real route behind it (see sidebar.blade.php note).
    Safe-area padding accounts for the home indicator on notched devices.
--}}
<nav class="fixed inset-x-0 bottom-0 z-40 flex border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden">
    <a href="{{ route('dashboard.teams.index') }}"
       class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('dashboard.teams.*') ? 'text-slate-900' : 'text-slate-500' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.7M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
        Teams
    </a>
    <a href="{{ route('dashboard.organizations.index') }}"
       class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('dashboard.organizations.*') ? 'text-slate-900' : 'text-slate-500' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
        </svg>
        Organizations
    </a>
    <x-layout.account-menu align="end" class="flex flex-1">
        <x-slot:trigger>
            <button type="button" class="flex w-full cursor-pointer flex-col items-center gap-0.5 py-2 text-xs font-medium text-slate-500">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
                Lainnya
            </button>
        </x-slot:trigger>
    </x-layout.account-menu>
</nav>
