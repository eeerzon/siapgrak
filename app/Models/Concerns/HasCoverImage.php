<?php

namespace App\Models\Concerns;

trait HasCoverImage
{
    /**
     * Built with asset() so it follows the request host instead of APP_URL.
     */
    public function coverUrl(): ?string
    {
        return $this->cover_path ? asset('storage/'.$this->cover_path) : null;
    }
}
