<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4"
      x-data="{ managed: {{ old('is_managed', $competition->is_managed) ? 'true' : 'false' }} }"
      onsubmit="this.querySelector('button[type=submit]').disabled = true;">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <x-ui.input name="title" label="Judul" :value="$competition->title" required autofocus />
    <x-ui.textarea name="excerpt" label="Ringkasan (opsional)" :value="$competition->excerpt" rows="2" />
    <x-ui.textarea name="body" label="Isi Artikel (opsional)" :value="$competition->body" rows="10" />
    @include('dashboard.content.partials.cover-input', [
        'currentUrl' => $competition->coverUrl(),
        'label' => 'Poster',
        'hint' => 'Rasio 4:5 seperti feed Instagram (contoh 1080×1350 piksel).',
    ])

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.input name="organizer_name" label="Penyelenggara (opsional)" :value="$competition->organizer_name" />
        <x-ui.input name="location" label="Kota / Tempat (opsional)" :value="$competition->location" placeholder="contoh: GOR Contoh, Bandung" />
        <x-ui.select name="province" label="Provinsi (opsional)">
            <option value="">— Pilih provinsi —</option>
            @foreach (config('siapgrak.provinces') as $province)
                <option value="{{ $province }}" @selected(old('province', $competition->province) === $province)>{{ $province }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input name="registration_deadline" type="date" label="Batas Pendaftaran (opsional)" :value="$competition->registration_deadline?->format('Y-m-d')" />
        <x-ui.input name="start_date" type="date" label="Tanggal Mulai" :value="$competition->start_date?->format('Y-m-d')" required />
        <x-ui.input name="end_date" type="date" label="Tanggal Selesai (opsional)" :value="$competition->end_date?->format('Y-m-d')" />
        <x-ui.input name="technical_meeting_date" type="date" label="Tanggal Technical Meeting (opsional)" :value="$competition->technical_meeting_date?->format('Y-m-d')" />
        <x-ui.input name="technical_meeting_location" label="Tempat Technical Meeting (opsional)" :value="$competition->technical_meeting_location" placeholder="contoh: SMKN 21 Jakarta / Daring" />
    </div>

    <fieldset>
        <legend class="text-sm font-medium text-slate-700">Jenjang Peserta (opsional)</legend>
        @php($selectedLevels = old('levels', $competition->levels ?? []))
        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2">
            @foreach (App\Enums\CompetitionLevel::cases() as $level)
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="levels[]" value="{{ $level->value }}" @checked(in_array($level->value, $selectedLevels, true)) class="h-4 w-4 rounded border-slate-300">
                    {{ $level->label() }}
                </label>
            @endforeach
        </div>
        @error('levels.*')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </fieldset>

    <fieldset class="rounded-md border border-slate-200 bg-white p-4">
        <legend class="px-1 text-sm font-medium text-slate-700">Pengelolaan Event</legend>
        <input type="hidden" name="is_managed" value="0">
        <label class="flex cursor-pointer items-start gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_managed" value="1" x-model="managed" class="mt-0.5 h-4 w-4 rounded border-slate-300">
            <span>
                Event dikelola oleh Kreen
                <span class="block text-xs text-slate-500">Pendaftaran dan transaksi berjalan di Kreenconnect.</span>
            </span>
        </label>

        <div class="mt-4" x-show="managed" x-cloak>
            <x-ui.input name="kreen_slug" label="Slug Event Kreenconnect" :value="$competition->kreen_slug" placeholder="contoh: lkbb-nasional-2026" />
            @if ($competition->kreenUrl())
                <a href="{{ $competition->kreenUrl() }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-medium text-sky-700 hover:underline">Buka event di Kreenconnect ↗</a>
            @endif
            @unless (config('siapgrak.kreenconnect_event_base_url'))
                <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">KREENCONNECT_EVENT_BASE_URL belum diisi, jadi tombol daftar belum tampil di halaman publik.</p>
            @endunless
        </div>

        <div class="mt-4" x-show="!managed">
            <x-ui.input name="external_url" type="url" label="Link Pendaftaran (opsional)" :value="$competition->external_url" placeholder="https://"
                        hint="Kosongkan kalau pendaftaran lewat contact person." />
        </div>
    </fieldset>

    <fieldset class="rounded-md border border-slate-200 bg-white p-4">
        <legend class="px-1 text-sm font-medium text-slate-700">Contact Person (opsional)</legend>
        <p class="text-xs text-slate-500">Tampil sebagai tombol "Hubungi Contact Person" di halaman detail. Nomor WhatsApp, boleh diawali 08 atau +62.</p>
        <div class="mt-3 space-y-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-ui.input name="contact_persons[{{ $i }}][name]" errorKey="contact_persons.{{ $i }}.name" id="contact_name_{{ $i }}"
                                :value="$competition->contact_persons[$i]['name'] ?? null" placeholder="Nama CP {{ $i + 1 }}" aria-label="Nama contact person {{ $i + 1 }}" />
                    <x-ui.input name="contact_persons[{{ $i }}][phone]" errorKey="contact_persons.{{ $i }}.phone" id="contact_phone_{{ $i }}" type="tel"
                                :value="$competition->contact_persons[$i]['phone'] ?? null" placeholder="08xx-xxxx-xxxx" aria-label="Nomor WhatsApp contact person {{ $i + 1 }}" />
                </div>
            @endfor
        </div>
        @error('contact_persons')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </fieldset>

    @include('dashboard.content.partials.status-select', ['status' => $competition->status])

    <div class="flex items-center gap-3">
        <x-ui.button type="submit">Simpan</x-ui.button>
        <a href="{{ route('dashboard.content.competitions.index') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
    </div>
</form>
