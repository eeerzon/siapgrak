{{--
    Same convention as modal.blade.php: reads/writes `open` from the nearest
    x-data ancestor. Slides in from the right — used for quick-view (e.g.
    Participant detail, per frontend-plan.md F6) rather than full navigation.
--}}
@props([
    'title' => null,
])

<div x-show="open" x-cloak x-effect="document.body.classList.toggle('overflow-hidden', open)" class="fixed inset-0 z-50">
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/40"
        @click="open = false"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @keydown.escape.window="open = false"
        class="fixed inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-lg"
    >
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-base font-semibold text-slate-800">{{ $title }}</h3>
            <button type="button" @click="open = false" class="-m-2 cursor-pointer p-2 text-slate-400 hover:text-slate-600" aria-label="Tutup">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-4">
            {{ $slot }}
        </div>
    </div>
</div>
