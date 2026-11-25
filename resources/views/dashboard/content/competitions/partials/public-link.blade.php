{{--
    "Salin Link Hasil Publik". Shown once at least one judge has sent a score sheet.
    navigator.clipboard only exists on https/localhost and can still be refused, so any
    failure falls back to selecting the field + execCommand('copy'), then to "Ctrl+C".
--}}
@php
    // One link for every category plus one per category (only categories with a round have results).
    $links = collect([['label' => 'Semua kategori', 'url' => route('site.competitions.results', $competition->slug)]])
        ->merge($competition->categories->filter(fn ($category) => $category->rounds->isNotEmpty())->map(fn ($category) => [
            'label' => $category->name,
            'url' => route('site.competitions.results.category', [$competition->slug, $category->id]),
        ]))
        ->values();
    // With a single category the per-category link is the same page, so only the main link is shown.
    if ($links->count() === 2) {
        $links = $links->take(1);
    }
    // A draft listing 404s publicly, so don't let admins share a dead link.
    $isPublic = $competition->status === \App\Enums\ContentStatus::Published;
@endphp

<div class="mb-6 rounded-lg border border-navy-100 bg-navy-50 p-4">
    <p class="font-semibold text-navy-900">Link hasil publik</p>
    <p class="text-sm text-slate-600">Bagikan ke peserta. Nilai baru tampil untuk babak yang sudah dipublikasikan di tab Hasil.@if ($links->count() > 1) Tiap kategori punya link sendiri.@endif</p>

    <div class="mt-3 space-y-2">
        @foreach ($links as $link)
            <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-3"
                 x-data="{
                     label: 'Salin Link',
                     async copy() {
                         const field = this.$refs.link;
                         let ok = false;
                         try {
                             await navigator.clipboard.writeText(field.value);
                             ok = true;
                         } catch {
                             field.select();
                             try { ok = document.execCommand('copy'); } catch {}
                         }
                         field.select();
                         this.label = ok ? 'Tersalin ✓' : 'Tekan Ctrl+C';
                         setTimeout(() => this.label = 'Salin Link', 2500);
                     },
                 }">
                <span class="text-xs font-semibold text-slate-600 sm:w-44 sm:shrink-0 sm:truncate" title="{{ $link['label'] }}">{{ $link['label'] }}</span>
                <div class="flex min-w-0 flex-1 flex-wrap gap-2 sm:flex-nowrap">
                    <input x-ref="link" type="text" value="{{ $link['url'] }}" readonly aria-label="Link hasil publik {{ $link['label'] }}" class="block w-full min-w-0 basis-full text-sm sm:basis-auto" @focus="$el.select()">
                    <button type="button" @disabled(! $isPublic) class="flex-1 cursor-pointer rounded-md bg-navy-900 px-4 py-2 sm:flex-none text-sm font-semibold whitespace-nowrap text-white hover:bg-navy-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                            @click="copy()" x-text="label">Salin Link</button>
                    @if ($isPublic)
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-center text-sm font-medium whitespace-nowrap sm:flex-none text-slate-700 hover:bg-slate-50">Buka ↗</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @unless ($isPublic)
        <p class="mt-2 text-sm font-medium text-amber-800">Info lomba ini masih draft, jadi link belum bisa dibuka publik dan belum bisa disalin. <a href="{{ route('dashboard.content.competitions.edit', $competition) }}" class="underline">Ubah statusnya ke published</a> dulu.</p>
    @endunless
</div>
