<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

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
}
