<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm('Hapus konten ini? Tindakan ini tidak bisa dibatalkan.');">
    @csrf
    @method('DELETE')
    <button type="submit" class="cursor-pointer text-sm font-medium text-red-600 hover:underline">Hapus</button>
</form>
