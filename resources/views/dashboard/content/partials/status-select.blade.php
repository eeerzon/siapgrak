<x-ui.select name="status" label="Status" required>
    <option value="draft" @selected(old('status', $status?->value ?? 'draft') === 'draft')>Draft (belum tampil di publik)</option>
    <option value="published" @selected(old('status', $status?->value) === 'published')>Published (tampil di publik)</option>
</x-ui.select>
