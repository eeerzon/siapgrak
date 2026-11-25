<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * A trimmed query-string value, or null when it is missing, blank, or not a
     * plain string (e.g. `?q[]=x`, which would otherwise crash string handling).
     */
    protected function queryText(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * "%term%" for `like ? escape '!'`, with % and _ in the term matched literally.
     */
    protected function containsPattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }

    /**
     * A page number past the end (an old link, or ?page=999) would show "nothing here yet"
     * although there is content, so send the visitor to the last page instead.
     */
    protected function redirectPastLastPage(LengthAwarePaginator $paginator): ?RedirectResponse
    {
        return $paginator->isEmpty() && $paginator->currentPage() > 1 && $paginator->total() > 0
            ? redirect($paginator->url($paginator->lastPage()))
            : null;
    }
}
