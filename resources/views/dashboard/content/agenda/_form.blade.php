<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4"
      onsubmit="this.querySelector('button[type=submit]').disabled = true;">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <x-ui.input name="title" label="Judul" :value="$agendaItem->title" required autofocus />
    <x-ui.textarea name="description" label="Deskripsi (opsional)" :value="$agendaItem->description" rows="6" />
    <x-ui.input name="location" label="Lokasi (opsional)" :value="$agendaItem->location" />
    @include('dashboard.content.partials.cover-input', [
        'currentUrl' => $agendaItem->coverUrl(),
        'label' => 'Poster',
        'hint' => 'Rasio 4:5 seperti feed Instagram (contoh 1080×1350 piksel).',
    ])

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.input name="start_at" type="datetime-local" label="Waktu Mulai" :value="$agendaItem->start_at?->format('Y-m-d\TH:i')" required />
        <x-ui.input name="end_at" type="datetime-local" label="Waktu Selesai (opsional)" :value="$agendaItem->end_at?->format('Y-m-d\TH:i')" />
    </div>

    @include('dashboard.content.partials.status-select', ['status' => $agendaItem->status])

    <div class="flex items-center gap-3">
        <x-ui.button type="submit">Simpan</x-ui.button>
        <a href="{{ route('dashboard.content.agenda.index') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
    </div>
</form>
