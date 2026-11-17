<div>
    <label for="cover" class="block text-sm font-medium text-slate-700">{{ $label ?? 'Gambar Sampul' }} (opsional)</label>
    @if ($currentUrl)
        <img src="{{ $currentUrl }}" alt="Sampul saat ini" class="mt-2 h-28 rounded-md border border-slate-200 object-cover">
    @endif
    <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp"
           class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm">
    <p class="mt-1 text-xs text-slate-400">JPG, PNG, atau WEBP, maksimal 2 MB.{{ isset($hint) ? ' '.$hint : '' }}</p>
    @error('cover')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
