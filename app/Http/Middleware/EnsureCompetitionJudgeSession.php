<?php

namespace App\Http\Middleware;

use App\Enums\Status;
use App\Models\CompetitionJudge;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Judge panel for Info Lomba judging (/juri). Uses its own session key so it
 * never mixes with the organizer-event judge panel (/judge, session 'judge_id').
 */
class EnsureCompetitionJudgeSession
{
    public const SESSION_KEY = 'competition_judge_id';

    public function handle(Request $request, Closure $next): Response
    {
        $judge = ($judgeId = $request->session()->get(self::SESSION_KEY))
            ? CompetitionJudge::find($judgeId)
            : null;

        if (! $judge || $judge->status !== Status::Active) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('juri.access.create');
        }

        $request->attributes->set('competitionJudge', $judge);

        return $next($request);
    }
}
