<?php

namespace App\Http\Controllers\Judge;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Judge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    public function create(): View
    {
        return view('judge.access');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $judge = Judge::query()
            ->where('access_code', strtoupper(trim($data['code'])))
            ->first();

        if (! $judge || $judge->status !== Status::Active) {
            return back()
                ->withErrors(['code' => 'Kode akses tidak valid.'])
                ->onlyInput('code');
        }

        $request->session()->regenerate();
        $request->session()->put('judge_id', $judge->id);

        return redirect()->route('judge.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('judge_id');
        $request->session()->regenerate();

        return redirect()->route('judge.access.create');
    }
}
