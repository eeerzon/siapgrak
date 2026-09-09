{{--
    Self-contained account dropdown (own x-data, unlike ui.modal/ui.drawer which
    share state with an ancestor). Pass the visible trigger via <x-slot:trigger>;
    the panel (name/email + Logout) is identical everywhere it's used — sidebar
    (desktop) and mobile-nav's "Lainnya" tab. Settings/Account links go in the
    panel once those routes exist (F-later) — do not add them speculatively now.
--}}
@props(['align' => 'start'])

<div x-data="{ open: false }" @click.outside="open = false" {{ $attributes->merge(['class' => 'relative']) }}>
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @keydown.escape.window="open = false"
        class="absolute bottom-full z-50 mb-2 w-56 rounded-md border border-slate-200 bg-white p-1 shadow-lg {{ $align === 'end' ? 'right-0' : 'left-0' }}"
    >
        <div class="px-3 py-2">
            <p class="truncate text-sm font-medium text-slate-800">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
        </div>

        <div class="my-1 border-t border-slate-100"></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full cursor-pointer items-center gap-2 rounded px-3 py-2 text-left text-sm text-slate-600 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H3" />
                </svg>
                Logout
            </button>
        </form>
    </div>
</div>
