{{--
    Usage: wrap trigger + this component in a shared `x-data="{ open: false }"`
    ancestor. This component reads/writes that `open` variable by convention —
    it does not declare its own x-data, so it always shares state with its
    nearest x-data parent. See /dev/ui for a working example.
--}}
@props([
    'title' => null,
])

<div
    x-show="open"
    x-cloak
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
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
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @keydown.escape.window="open = false"
        class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-lg"
    >
        @if ($title)
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-800">{{ $title }}</h3>
                <button type="button" @click="open = false" class="-m-2 cursor-pointer p-2 text-slate-400 hover:text-slate-600" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
