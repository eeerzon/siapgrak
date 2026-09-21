<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Judge\Concerns\InteractsWithJudgeSession;
use App\Services\ScoreSheetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use InteractsWithJudgeSession;

    public function __construct(protected ScoreSheetService $scoreSheets) {}

    public function index(Request $request): View
    {
        $judge = $this->currentJudge($request);
        $rounds = $this->scoreSheets->assignedRounds($judge);

        return view('judge.dashboard', compact('judge', 'rounds'));
    }
}
