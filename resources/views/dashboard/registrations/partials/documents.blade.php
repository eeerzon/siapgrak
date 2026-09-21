{{-- Private documents. Upload/remove only while the registration is editable.
     Download always goes through the Policy-checked route. Uses $registration. --}}
@php($canManage = auth()->user()->can('updateStatus', $registration) && $registration->isEditable())

@if ($canManage)
    <x-ui.card class="mb-4">
        <form method="POST" action="{{ route('dashboard.registrations.documents.store', $registration) }}"
              enctype="multipart/form-data" class="space-y-3"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="document_type" class="block text-sm font-medium text-slate-700">Jenis Dokumen</label>
                    <input id="document_type" name="document_type" list="doc-types" required
                           class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <datalist id="doc-types">
                        <option value="surat_tugas"></option>
                        <option value="kartu_pelajar"></option>
                        <option value="pakta_integritas"></option>
                        <option value="surat_sehat"></option>
                    </datalist>
                    @error('document_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <x-ui.input name="title" label="Judul (opsional)" />
            </div>
            <div>
                <label for="file" class="block text-sm font-medium text-slate-700">File (PDF / JPG / PNG, maks 5 MB)</label>
                <input id="file" name="file" type="file" accept=".pdf,image/*" required
                       class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <x-ui.button type="submit">Unggah Dokumen</x-ui.button>
        </form>
    </x-ui.card>
@endif

@if ($registration->documents->isEmpty())
    <x-ui.empty-state title="Belum ada dokumen." description="Unggah dokumen wajib sesuai ketentuan event." />
@else
    <x-ui.table>
        <x-slot:head>
            <tr>
                <x-ui.table-th>Jenis</x-ui.table-th>
                <x-ui.table-th>Judul</x-ui.table-th>
                <x-ui.table-th align="right">Aksi</x-ui.table-th>
            </tr>
        </x-slot:head>
        @foreach ($registration->documents as $document)
            <tr>
                <x-ui.table-td>{{ \Illuminate\Support\Str::headline($document->document_type) }}</x-ui.table-td>
                <x-ui.table-td muted>{{ $document->title ?: '—' }}</x-ui.table-td>
                <x-ui.table-td align="right">
                    <a href="{{ route('dashboard.registrations.documents.download', [$registration, $document]) }}"
                       class="text-sm font-medium text-sky-700 hover:underline">Unduh</a>
                    @if ($canManage)
                        <form method="POST" action="{{ route('dashboard.registrations.documents.destroy', [$registration, $document]) }}"
                              class="ml-3 inline" onsubmit="return confirm('Hapus dokumen ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="cursor-pointer text-sm font-medium text-red-600 hover:underline">Hapus</button>
                        </form>
                    @endif
                </x-ui.table-td>
            </tr>
        @endforeach
    </x-ui.table>
@endif
