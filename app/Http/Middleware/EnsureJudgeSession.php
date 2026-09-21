<?php

namespace App\Http\Middleware;

use App\Enums\Status;
use App\Models\Judge;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for /judge/* (fr-scoring.md Keputusan A). Deliberately not an
 * Auth::guard() — judges authenticate via a plain access code, not an
 * Authenticatable account, so this is a bare session flag instead of a new
 * guard/provider for one narrow use case (Karpathy #2 Simplicity First).
 */
class EnsureJudgeSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $judge = ($judgeId = $request->session()->get('judge_id'))
            ? Judge::find($judgeId)
            : null;

        if (! $judge || $judge->status !== Status::Active) {
            $request->session()->forget('judge_id');

            return redirect()->route('judge.access.create');
        }

        $request->attributes->set('judge', $judge);

        return $next($request);
    }
}
