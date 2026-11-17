<?php

namespace App\Http\Controllers\Judge\Concerns;

use App\Models\Judge;
use Illuminate\Http\Request;

trait InteractsWithJudgeSession
{
    protected function currentJudge(Request $request): Judge
    {
        return $request->attributes->get('judge');
    }
}
