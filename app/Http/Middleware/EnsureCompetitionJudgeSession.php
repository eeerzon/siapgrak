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

    /** Fingerprint of the access code used to log in: a new code ends every session made with the old one. */
    public const CODE_KEY = 'competition_judge_code';

    public static function fingerprint(CompetitionJudge $judge): string
    {
        return hash_hmac('sha256', (string) $judge->access_code, (string) config('app.key'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $judge = ($judgeId = $request->session()->get(self::SESSION_KEY))
            ? CompetitionJudge::find($judgeId)
            : null;

        $codeMatches = $judge && hash_equals(self::fingerprint($judge), (string) $request->session()->get(self::CODE_KEY));

        if (! $judge || ! $codeMatches || $judge->status !== Status::Active) {
            $request->session()->forget([self::SESSION_KEY, self::CODE_KEY]);

            return redirect()->route('juri.access.create');
        }

        $request->attributes->set('competitionJudge', $judge);

        return $next($request);
    }
}
