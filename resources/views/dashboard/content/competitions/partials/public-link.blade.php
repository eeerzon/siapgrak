{{--
    "Salin Link Hasil Publik". Shown once at least one judge has sent a score sheet.
    navigator.clipboard only exists on https/localhost and can still be refused, so any
    failure falls back to selecting the field + execCommand('copy'), then to "Ctrl+C".
--}}
@php
    $publicUrl = route('site.competitions.results', $competition->slug);
    // A draft listing 404s publicly, so don't let admins share a dead link.
    $isPublic = $competition->status === \App\Enums\ContentStatus::Published;
@endphp

<div class="mb-6 rounded-lg border border-navy-100 bg-navy-50 p-4"
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
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-semibold text-navy-900">Link hasil publik</p>
            <p class="text-sm text-slate-600">Bagikan ke peserta. Nilai baru tampil untuk babak yang sudah dipublikasikan di tab Hasil.</p>
        </div>
        <div class="flex w-full gap-2 sm:w-auto">
            <input x-ref="link" type="text" value="{{ $publicUrl }}" readonly aria-label="Link hasil publik" class="block w-full min-w-0 text-sm sm:w-80" @focus="$el.select()">
            <button type="button" @disabled(! $isPublic) class="cursor-pointer rounded-md bg-navy-900 px-4 py-2 text-sm font-semibold whitespace-nowrap text-white hover:bg-navy-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                    @click="copy()" x-text="label">Salin Link</button>
            @if ($isPublic)
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium whitespace-nowrap text-slate-700 hover:bg-slate-50">Buka ↗</a>
            @endif
        </div>
    </div>
    @unless ($isPublic)
        <p class="mt-2 text-sm font-medium text-amber-800">Info lomba ini masih draft, jadi link belum bisa dibuka publik dan belum bisa disalin. <a href="{{ route('dashboard.content.competitions.edit', $competition) }}" class="underline">Ubah statusnya ke published</a> dulu.</p>
    @endunless
</div>
