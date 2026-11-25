<?php

namespace App\Http\Controllers\Dashboard\Content\Judging;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\CompetitionJudge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The logged-in admin's own judges for Info Lomba judging.
 */
class JudgeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $this->queryText($request, 'q');
        $judges = CompetitionJudge::ownedBy($request->user())
            ->when($search, fn ($query) => $query->whereRaw("name like ? escape '!'", [$this->containsPattern($search)]))
            ->withCount('rounds')
            ->orderBy('name')
            ->get();

        return view('dashboard.content.judges.index', compact('judges', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        CompetitionJudge::create($this->validated($request) + ['id_user' => $request->user()->id_user]);

        return redirect()->route('dashboard.content.judges.index')->with('success', 'Juri ditambahkan. Kode aksesnya sudah dibuat otomatis.');
    }

    public function update(Request $request, CompetitionJudge $judge): RedirectResponse
    {
        $this->ensureOwner($request, $judge);
        $judge->update($this->validated($request));

        return redirect()->route('dashboard.content.judges.index')->with('success', 'Data juri diperbarui.');
    }

    public function regenerateAccessCode(Request $request, CompetitionJudge $judge): RedirectResponse
    {
        $this->ensureOwner($request, $judge);
        $judge->forceFill(['access_code' => CompetitionJudge::generateAccessCode()])->save();

        return redirect()->route('dashboard.content.judges.index')->with('success', "Kode akses {$judge->name} diganti. Kode lama tidak berlaku lagi.");
    }

    private function ensureOwner(Request $request, CompetitionJudge $judge): void
    {
        abort_unless((int) $judge->id_user === $request->user()->id_user, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(Status::class)],
        ], [], ['name' => 'nama juri', 'bio' => 'keterangan']);
    }
}
