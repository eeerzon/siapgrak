<?php

namespace App\Http\Controllers\Juri;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureCompetitionJudgeSession;
use App\Models\CompetitionJudge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    public function create(): View
    {
        return view('juri.access');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']], [], ['code' => 'kode akses']);

        // Codes are often read aloud or copied as "GRDA-2345" / "grda 2345": keep letters and digits only.
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['code']));
        $judge = CompetitionJudge::where('access_code', $code)->first();

        if (! $judge || $judge->status !== Status::Active) {
            return back()->withErrors(['code' => 'Kode akses tidak valid.'])->onlyInput('code');
        }

        $request->session()->regenerate();
        $request->session()->put(EnsureCompetitionJudgeSession::SESSION_KEY, $judge->id);
        $request->session()->put(EnsureCompetitionJudgeSession::CODE_KEY, EnsureCompetitionJudgeSession::fingerprint($judge));

        return redirect()->route('juri.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget([EnsureCompetitionJudgeSession::SESSION_KEY, EnsureCompetitionJudgeSession::CODE_KEY]);
        $request->session()->regenerate();

        return redirect()->route('juri.access.create');
    }
}
