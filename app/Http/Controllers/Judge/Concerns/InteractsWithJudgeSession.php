<?php

namespace App\Http\Controllers\Judge\Concerns;

use App\Models\Judge;
use Illuminate\Http\Request;

/**
 * Resolves the Judge that EnsureJudgeSession already validated and attached
 * to the request — never trust a route parameter or input for this, the
 * judge identity only ever comes from the session (fr-scoring.md Keputusan A).
 */
trait InteractsWithJudgeSession
{
    protected function currentJudge(Request $request): Judge
    {
        return $request->attributes->get('judge');
    }
}
