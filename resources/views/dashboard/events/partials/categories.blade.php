{{--
    Event categories: card list + drawer create/edit (F7, frontend-plan.md).
    Status toggle intentionally omitted — deferred since Sprint 3
    (fr-event-category.md), the form request doesn't accept it either.

    Multiple forms (1 create + 1 per category) live on this one page at once,
    all sharing Laravel's single global old()/$errors bag by field name. To
    stop a failed edit on category A from bleeding its rejected values/errors
    into category B's (hidden) form on the same render, every field's
    `errorKey` is only ever the REAL field name for whichever single form
    actually failed — every other form gets a key that can never match
    anything in old()/$errors, so it safely falls back to $category's actual
    persisted value.
--}}
@php
    $failedFormId = old('_category_id');
    $keyFor = function (string $field, ?int $categoryId) use ($failedFormId) {
        $isThisForm = $categoryId === null
            ? $failedFormId === ''
            : ($failedFormId !== null && $failedFormId !== '' && (int) $failedFormId === $categoryId);

        return $isThisForm ? $field : "suppressed_{$field}_".($categoryId ?? 'create');
    };
@endphp

<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }}, editing: {{ $failedFormId !== null && $failedFormId !== '' ? (int) $failedFormId : 'null' }} }">
    @can('manageCategories', $event)
        <div class="mb-4 flex justify-end">
            <x-ui.button @click="open = true; editing = null">+ Tambah Kategori</x-ui.button>
        </div>
    @endcan

    @if ($event->categories->isEmpty())
        <x-ui.empty-state
            title="Belum ada kategori lomba."
            description="Tambah minimal satu kategori sebelum membuka pendaftaran."
        />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($event->categories as $category)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium text-slate-800">{{ $category->name }}</p>
                            @if ($category->code)
                                <p class="text-xs text-slate-400">{{ $category->code }}</p>
                            @endif
                        </div>
                        @can('manageCategories', $event)
                            <button type="button" @click="open = true; editing = {{ $category->id }}" class="shrink-0 cursor-pointer text-sm font-medium text-sky-700 hover:underline">Edit</button>
                        @endcan
                    </div>

                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Tim terdaftar</dt><dd class="text-slate-700">{{ $category->registrations->count() }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Kuota</dt><dd class="text-slate-700">{{ $category->quota ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Anggota</dt><dd class="text-slate-700">{{ $category->min_members ?? '—' }}–{{ $category->max_members ?? '—' }}</dd></div>
                    </dl>

                    @can('manageCategories', $event)
                        <form method="POST" action="{{ route('dashboard.events.categories.destroy', [$event, $category]) }}"
                              onsubmit="return confirm('Hapus kategori ini?');" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cursor-pointer text-sm font-medium text-red-600 hover:underline">Hapus</button>
                        </form>
                    @endcan
                </x-ui.card>
            @endforeach
        </div>
    @endif

    @can('manageCategories', $event)
        <x-ui.drawer title="Kategori">
            {{-- Create --}}
            <form x-show="editing === null" x-cloak method="POST" action="{{ route('dashboard.events.categories.store', $event) }}"
                  class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <input type="hidden" name="_category_id" value="">
                <input type="hidden" name="_tab" value="categories">
                <x-ui.input name="name" label="Nama" required :value="null" :errorKey="$keyFor('name', null)" />
                <x-ui.input name="code" label="Kode (opsional)" :value="null" :errorKey="$keyFor('code', null)" />
                <x-ui.textarea name="description" label="Deskripsi (opsional)" :value="null" :errorKey="$keyFor('description', null)" />
                <x-ui.input name="quota" label="Kuota (opsional)" type="number" min="1" :value="null" :errorKey="$keyFor('quota', null)" />
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-ui.input name="min_members" label="Min Anggota" type="number" min="1" :value="null" :errorKey="$keyFor('min_members', null)" />
                    <x-ui.input name="max_members" label="Max Anggota" type="number" min="1" :value="null" :errorKey="$keyFor('max_members', null)" />
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-ui.input name="registration_start_at" label="Buka Pendaftaran" type="datetime-local" :value="null" :errorKey="$keyFor('registration_start_at', null)" />
                    <x-ui.input name="registration_end_at" label="Tutup Pendaftaran" type="datetime-local" :value="null" :errorKey="$keyFor('registration_end_at', null)" />
                </div>
                <x-ui.input name="display_order" label="Urutan" type="number" min="0" :value="0" :errorKey="$keyFor('display_order', null)" />
                <x-ui.button type="submit" class="w-full">Tambah Kategori</x-ui.button>
            </form>

            {{-- Edit — one form per category, toggled by `editing` --}}
            @foreach ($event->categories as $category)
                <form x-show="editing === {{ $category->id }}" x-cloak method="POST"
                      action="{{ route('dashboard.events.categories.update', [$event, $category]) }}"
                      class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_category_id" value="{{ $category->id }}">
                    <input type="hidden" name="_tab" value="categories">
                    <x-ui.input name="name" label="Nama" required :value="$category->name" :errorKey="$keyFor('name', $category->id)" />
                    <x-ui.input name="code" label="Kode (opsional)" :value="$category->code" :errorKey="$keyFor('code', $category->id)" />
                    <x-ui.textarea name="description" label="Deskripsi (opsional)" :value="$category->description" :errorKey="$keyFor('description', $category->id)" />
                    <x-ui.input name="quota" label="Kuota (opsional)" type="number" min="1" :value="$category->quota" :errorKey="$keyFor('quota', $category->id)" />
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <x-ui.input name="min_members" label="Min Anggota" type="number" min="1" :value="$category->min_members" :errorKey="$keyFor('min_members', $category->id)" />
                        <x-ui.input name="max_members" label="Max Anggota" type="number" min="1" :value="$category->max_members" :errorKey="$keyFor('max_members', $category->id)" />
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <x-ui.input name="registration_start_at" label="Buka Pendaftaran" type="datetime-local" :value="$category->registration_start_at?->format('Y-m-d\TH:i')" :errorKey="$keyFor('registration_start_at', $category->id)" />
                        <x-ui.input name="registration_end_at" label="Tutup Pendaftaran" type="datetime-local" :value="$category->registration_end_at?->format('Y-m-d\TH:i')" :errorKey="$keyFor('registration_end_at', $category->id)" />
                    </div>
                    <x-ui.input name="display_order" label="Urutan" type="number" min="0" :value="$category->display_order" :errorKey="$keyFor('display_order', $category->id)" />
                    <x-ui.button type="submit" class="w-full">Simpan Perubahan</x-ui.button>
                </form>
            @endforeach
        </x-ui.drawer>
    @endcan
</div>
