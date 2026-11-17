<?php

namespace App\Http\Controllers\Dashboard\Content\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait StoresCoverImage
{
    protected function withStoredCover(Request $request, array $data, ?string $currentPath = null): array
    {
        unset($data['cover']);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('content', 'public');
            $this->deleteCover($currentPath);
        }

        return $data;
    }

    protected function deleteCover(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
