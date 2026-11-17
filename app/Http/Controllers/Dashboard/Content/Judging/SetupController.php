<?php

namespace App\Http\Controllers\Dashboard\Content\Judging;

use App\Http\Controllers\Controller;
use App\Models\CompetitionCategory;
use App\Models\CompetitionCriterion;
use App\Models\CompetitionJudge;
use App\Models\CompetitionListing;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionRound;
use App\Services\CompetitionJudgingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Categories, participants, rounds, criteria and judge assignments of one Info Lomba.
 * Anything that already has scores cannot be deleted, so judging history is never lost.
 */
class SetupController extends Controller
{
    use ScopesToCompetition;

    public function __construct(private CompetitionJudgingService $judging) {}

    public function storeCategory(Request $request, CompetitionListing $competition): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $data = $request->validate(['name' => ['required', 'string', 'max:100']], [], ['name' => 'nama kategori']);

        $competition->categories()->create($data + ['display_order' => $competition->categories()->count() + 1]);

        return $this->backToTab($competition, 'peserta', 'Kategori ditambahkan.');
    }

    public function destroyCategory(CompetitionListing $competition, CompetitionCategory $category): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $category->competition_listing_id);

        if ($category->participants()->exists() || $category->rounds()->exists()) {
            return back()->withErrors(['category' => 'Kategori yang sudah punya peserta atau babak tidak bisa dihapus.']);
        }

        $category->delete();

        return $this->backToTab($competition, 'peserta', 'Kategori dihapus.');
    }

    public function storeParticipant(Request $request, CompetitionListing $competition): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $data = $this->validateParticipant($request, $competition, requireNumber: true);

        $competition->participants()->create($data);

        return $this->backToTab($competition, 'peserta', 'Peserta ditambahkan.');
    }

    public function updateParticipant(Request $request, CompetitionListing $competition, CompetitionParticipant $participant): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $participant->competition_listing_id);
        $data = $this->validateParticipant($request, $competition, $participant);

        if ($participant->judgings()->exists() && (int) $data['competition_category_id'] !== $participant->competition_category_id) {
            return back()->withErrors(['participant' => 'Peserta yang sudah dinilai tidak bisa dipindah kategori.']);
        }

        $participant->update($data);

        return $this->backToTab($competition, 'peserta', 'Data peserta diperbarui.');
    }

    public function destroyParticipant(CompetitionListing $competition, CompetitionParticipant $participant): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $participant->competition_listing_id);

        if ($participant->judgings()->exists()) {
            return back()->withErrors(['participant' => 'Peserta yang sudah dinilai juri tidak bisa dihapus.']);
        }

        $participant->delete();

        return $this->backToTab($competition, 'peserta', 'Peserta dihapus.');
    }

    public function storeRound(Request $request, CompetitionListing $competition): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $data = $request->validate([
            'competition_category_id' => ['required', Rule::exists('competition_categories', 'id')->where('competition_listing_id', $competition->id)],
            'name' => ['required', 'string', 'max:100'],
        ], [], ['competition_category_id' => 'kategori', 'name' => 'nama babak']);

        CompetitionRound::create($data + ['display_order' => CompetitionRound::where('competition_category_id', $data['competition_category_id'])->count() + 1]);

        return $this->backToTab($competition, 'penjurian', 'Babak ditambahkan.');
    }

    public function destroyRound(CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        if ($round->judgings()->exists()) {
            return back()->withErrors(['round' => 'Babak yang sudah ada penilaiannya tidak bisa dihapus.']);
        }

        $round->criteria()->delete();
        $round->judges()->detach();
        $round->delete();

        return $this->backToTab($competition, 'penjurian', 'Babak dihapus.');
    }

    public function storeCriterion(Request $request, CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        if ($this->judging->hasStarted($round)) {
            return back()->withErrors(['criterion' => 'Penilaian babak ini sudah dimulai, jadi kriteria tidak bisa ditambah lagi.']);
        }

        $round->criteria()->create($this->validateCriterion($request) + ['display_order' => $round->criteria()->count() + 1]);

        return $this->backToTab($competition, 'penjurian', 'Kriteria ditambahkan.');
    }

    public function updateCriterion(Request $request, CompetitionListing $competition, CompetitionCriterion $criterion): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $criterion->round->category->competition_listing_id);
        // After judging starts the criterion is frozen entirely, so every sheet is judged against the same wording and rules.
        if ($this->judging->hasStarted($criterion->round)) {
            return back()->withErrors(['criterion' => 'Penilaian babak ini sudah dimulai, jadi kriteria tidak bisa diubah lagi.']);
        }

        $data = $this->validateCriterion($request);

        $criterion->update($data);

        return $this->backToTab($competition, 'penjurian', 'Kriteria diperbarui.');
    }

    public function destroyCriterion(CompetitionListing $competition, CompetitionCriterion $criterion): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $criterion->round->category->competition_listing_id);

        if ($this->judging->hasStarted($criterion->round)) {
            return back()->withErrors(['criterion' => 'Penilaian babak ini sudah dimulai, jadi kriteria tidak bisa dihapus.']);
        }

        $criterion->delete();

        return $this->backToTab($competition, 'penjurian', 'Kriteria dihapus.');
    }

    public function assignJudge(Request $request, CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        // Only the admin's own judges can be assigned.
        $data = $request->validate([
            'competition_judge_id' => [
                'required',
                Rule::exists('competition_judges', 'id')->where('id_user', $request->user()->id_user),
                Rule::unique('competition_judge_assignments')->where('competition_round_id', $round->id),
            ],
        ], ['competition_judge_id.unique' => 'Juri ini sudah ditugaskan di babak tersebut.'], ['competition_judge_id' => 'juri']);

        $round->judges()->attach($data['competition_judge_id']);

        $note = $this->judging->isReadyForJudging($round) ? '' : ' Catatan: total bobot babak ini belum 100%, jadi juri belum bisa mulai menilai.';

        return $this->backToTab($competition, 'penjurian', 'Juri ditugaskan.'.$note);
    }

    public function unassignJudge(CompetitionListing $competition, CompetitionRound $round, CompetitionJudge $judge): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        if ($round->judgings()->where('competition_judge_id', $judge->id)->exists()) {
            return back()->withErrors(['judge' => 'Juri yang sudah mulai menilai di babak ini tidak bisa dilepas.']);
        }

        $round->judges()->detach($judge->id);

        return $this->backToTab($competition, 'penjurian', 'Juri dilepas dari babak.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateParticipant(Request $request, CompetitionListing $competition, ?CompetitionParticipant $participant = null, bool $requireNumber = false): array
    {
        return $request->validate([
            'competition_category_id' => ['required', Rule::exists('competition_categories', 'id')->where('competition_listing_id', $competition->id)],
            'name' => ['required', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'competition_number' => [
                $requireNumber ? 'required' : 'nullable', 'string', 'max:20',
                Rule::unique('competition_participants')->where('competition_category_id', $request->input('competition_category_id'))->ignore($participant),
            ],
            'performance_order' => ['nullable', 'integer', 'min:1', 'max:999'],
        ], ['competition_number.unique' => 'Nomor tampil sudah dipakai peserta lain di kategori ini.'], [
            'competition_category_id' => 'kategori',
            'name' => 'nama tim',
            'institution' => 'asal sekolah/instansi',
            'competition_number' => 'nomor tampil',
            'performance_order' => 'urutan tampil',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCriterion(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            // Required: a round is only judgeable when its weights total exactly 100%.
            'weight' => ['required', 'numeric', 'gt:0', 'max:100'],
            'minimum_score' => ['required', 'numeric', 'min:0'],
            'maximum_score' => ['required', 'numeric', 'gt:minimum_score', 'max:1000'],
        ], [], [
            'name' => 'nama kriteria',
            'weight' => 'bobot',
            'minimum_score' => 'nilai minimum',
            'maximum_score' => 'nilai maksimum',
        ]);
    }
}
