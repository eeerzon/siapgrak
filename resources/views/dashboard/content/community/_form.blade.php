<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4"
      onsubmit="this.querySelector('button[type=submit]').disabled = true;">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <x-ui.select name="type" label="Jenis" required>
        @foreach (\App\Enums\CommunityPostType::cases() as $case)
            <option value="{{ $case->value }}" @selected(old('type', $communityPost->type?->value) === $case->value)>{{ $case->label() }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.input name="title" label="Judul" :value="$communityPost->title" required autofocus />
    <x-ui.textarea name="excerpt" label="Ringkasan (opsional)" :value="$communityPost->excerpt" rows="2" />
    <x-ui.textarea name="body" label="Isi" :value="$communityPost->body" rows="10" required />
    @include('dashboard.content.partials.cover-input', ['currentUrl' => $communityPost->coverUrl()])
    @include('dashboard.content.partials.status-select', ['status' => $communityPost->status])

    <div class="flex items-center gap-3">
        <x-ui.button type="submit">Simpan</x-ui.button>
        <a href="{{ route('dashboard.content.community.index') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
    </div>
</form>
