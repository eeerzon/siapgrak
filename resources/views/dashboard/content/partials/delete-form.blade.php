<form method="POST" action="{{ $action }}" class="inline" data-confirm-title="Hapus konten ini?" data-confirm="Tindakan ini tidak bisa dibatalkan." data-confirm-button="Ya, hapus">
    @csrf
    @method('DELETE')
    <button type="submit" class="cursor-pointer text-sm font-medium text-red-600 hover:underline">Hapus</button>
</form>
