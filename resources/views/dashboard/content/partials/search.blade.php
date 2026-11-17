{{-- Search box above a content list. Expects $search and $placeholder. --}}
<form method="GET" class="mb-4 flex max-w-md gap-2" role="search">
    <label for="q" class="sr-only">Cari</label>
    <input id="q" name="q" type="search" value="{{ $search }}" placeholder="{{ $placeholder }}" class="block w-full text-sm">
    <x-ui.button type="submit" variant="secondary">Cari</x-ui.button>
    @if ($search)
        <x-ui.button :href="url()->current()" variant="ghost">Reset</x-ui.button>
    @endif
</form>
