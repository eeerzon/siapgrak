{{-- Search box used by the public list pages. Expects $value and $placeholder. --}}
<div class="relative">
    <label for="q" class="sr-only">Cari</label>
    <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
    </svg>
    <input id="q" name="q" type="search" value="{{ $value }}" placeholder="{{ $placeholder }}" class="block w-full rounded-md border border-slate-300 bg-white py-2 pr-3 pl-9 text-sm">
</div>
