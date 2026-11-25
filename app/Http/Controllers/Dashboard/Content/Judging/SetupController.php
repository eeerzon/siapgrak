<?php

namespace App\Http\Controllers\Dashboard\Content\Judging;

use App\Enums\Status;
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
use Illuminate\Support\Facades\DB;
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

        $category = $competition->categories()->create($data + ['display_order' => $competition->categories()->count() + 1]);

        return $this->backToTab($competition, 'peserta', 'Kategori ditambahkan.', ['kategori' => $category->id]);
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
        $firstRound = CompetitionCategory::find($data['competition_category_id'])->rounds->first();

        if ($firstRound && $error = $this->judging->teamListClosedReason($firstRound)) {
            return back()->withErrors(['participant' => $error])->withInput();
        }

        $participant = $competition->participants()->create($data);
        $note = $firstRound ? $this->resetLaterRounds($firstRound, [$participant->id]) : '';

        return $this->backToTab($competition, 'peserta', 'Peserta ditambahkan.'.$note, ['kategori' => $data['competition_category_id']]);
    }

    public function updateParticipant(Request $request, CompetitionListing $competition, CompetitionParticipant $participant): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $participant->competition_listing_id);
        $data = $this->validateParticipant($request, $competition, $participant);

        $movesCategory = (int) $data['competition_category_id'] !== (int) $participant->competition_category_id;

        if ($participant->judgings()->exists() && $movesCategory) {
            return back()->withErrors(['participant' => 'Peserta yang sudah dinilai tidak bisa dipindah kategori.']);
        }

        // Moving in counts as a new team of the destination's first round.
        $firstRound = $movesCategory ? CompetitionCategory::find($data['competition_category_id'])->rounds->first() : null;

        if ($firstRound && $error = $this->judging->teamListClosedReason($firstRound)) {
            return back()->withErrors(['participant' => $error]);
        }

        DB::transaction(function () use ($participant, $data, $movesCategory): void {
            // Rounds belong to a category: a team moved elsewhere leaves the old category's later rounds (it has no sheets there).
            if ($movesCategory) {
                $participant->rounds()->detach();
            }

            $participant->update($data);
        });

        $note = $firstRound ? $this->resetLaterRounds($firstRound, [$participant->id]) : '';

        return $this->backToTab($competition, 'peserta', 'Data peserta diperbarui.'.$note, ['kategori' => $participant->competition_category_id]);
    }

    public function destroyParticipant(CompetitionListing $competition, CompetitionParticipant $participant): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $participant->competition_listing_id);

        if ($participant->judgings()->exists()) {
            return back()->withErrors(['participant' => 'Peserta yang sudah dinilai juri tidak bisa dihapus.']);
        }

        // Round membership is not history (no sheet exists), so it goes with the team.
        $participant->rounds()->detach();
        $participant->delete();

        return $this->backToTab($competition, 'peserta', 'Peserta dihapus.', ['kategori' => $participant->competition_category_id]);
    }

    public function storeRound(Request $request, CompetitionListing $competition): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $data = $request->validate([
            'competition_category_id' => ['required', Rule::exists('competition_categories', 'id')->where('competition_listing_id', $competition->id)],
            'name' => ['required', 'string', 'max:100'],
        ], [], ['competition_category_id' => 'kategori', 'name' => 'nama babak']);

        CompetitionRound::create($data + ['display_order' => CompetitionRound::where('competition_category_id', $data['competition_category_id'])->count() + 1]);

        return $this->backToTab($competition, 'penjurian', 'Babak ditambahkan.', ['kategori' => $data['competition_category_id']]);
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
        $round->participants()->detach();
        $round->delete();

        return $this->backToTab($competition, 'penjurian', 'Babak dihapus.', ['kategori' => $round->competition_category_id]);
    }

    public function storeCriterion(Request $request, CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        if ($this->judging->hasStarted($round)) {
            return back()->withErrors(['criterion' => 'Penilaian babak ini sudah dimulai, jadi kriteria tidak bisa ditambah lagi.']);
        }

        $round->criteria()->create($this->validateCriterion($request) + ['display_order' => $round->criteria()->count() + 1]);

        return $this->backToTab($competition, 'penjurian', 'Kriteria ditambahkan.', ['kategori' => $round->competition_category_id]);
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

        return $this->backToTab($competition, 'penjurian', 'Kriteria diperbarui.', ['kategori' => $criterion->round->competition_category_id]);
    }

    public function destroyCriterion(CompetitionListing $competition, CompetitionCriterion $criterion): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $criterion->round->category->competition_listing_id);

        if ($this->judging->hasStarted($criterion->round)) {
            return back()->withErrors(['criterion' => 'Penilaian babak ini sudah dimulai, jadi kriteria tidak bisa dihapus.']);
        }

        $criterion->delete();

        return $this->backToTab($competition, 'penjurian', 'Kriteria dihapus.', ['kategori' => $criterion->round->competition_category_id]);
    }

    /**
     * Moves chosen teams of the category into a later round. Adding is always allowed.
     */
    public function addRoundParticipants(Request $request, CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);
        $this->ensureLaterRound($round);
        if ($error = $this->previousRoundStillRunning($round) ?? $this->judging->teamListClosedReason($round)) {
            return back()->withErrors(['round_participants' => $error]);
        }

        // Only teams that took part in the previous round can move on.
        $previousTeamIds = $this->judging->entrants($this->judging->previousRound($round))->pluck('id')->all();
        $data = $request->validate([
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer', Rule::in($previousTeamIds)],
        ], [
            'participant_ids.required' => 'Pilih minimal satu peserta.',
            'participant_ids.*.in' => 'Peserta harus dari '.$this->judging->previousRound($round)->name.'.',
        ], ['participant_ids' => 'peserta', 'participant_ids.*' => 'peserta']);

        $added = $round->participants()->syncWithoutDetaching($data['participant_ids'])['attached'];

        return $this->backToTab($competition, 'penjurian', count($added).' peserta ditambahkan ke '.$round->name.'.'.$this->resetLaterRounds($round, $added), ['kategori' => $round->competition_category_id]);
    }

    /**
     * "Ambil N teratas": the teams ranked 1..N in the previous round (ties at N all go through).
     */
    public function promoteTopParticipants(Request $request, CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);
        $this->ensureLaterRound($round);
        if ($error = $this->previousRoundStillRunning($round) ?? $this->judging->teamListClosedReason($round)) {
            return back()->withErrors(['round_participants' => $error]);
        }
        $data = $request->validate(['top' => ['required', 'integer', 'min:1', 'max:999']], [], ['top' => 'jumlah peserta']);

        $previous = $this->judging->previousRound($round);
        $ids = $previous->results()->where('rank', '<=', $data['top'])->pluck('competition_participant_id');

        if ($ids->isEmpty()) {
            return back()->withErrors(['round_participants' => "{$previous->name} belum punya peringkat. Hitung hasilnya dulu di tab Hasil."]);
        }

        $added = $round->participants()->syncWithoutDetaching($ids->all())['attached'];
        $tieNote = $ids->count() > $data['top'] ? ' (ada nilai seri di batas, jadi semua yang seri ikut lolos)' : '';

        return $this->backToTab($competition, 'penjurian', "{$ids->count()} peserta teratas {$previous->name} masuk ke {$round->name}{$tieNote}.".$this->resetLaterRounds($round, $added), ['kategori' => $round->competition_category_id]);
    }

    /**
     * Taking a team out is refused once it has a sheet in this round, so no score is orphaned.
     */
    public function removeRoundParticipant(CompetitionListing $competition, CompetitionRound $round, CompetitionParticipant $participant): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);
        // The team must be of this round's category, otherwise 404 (no name of another listing leaks into the message).
        abort_unless((int) $participant->competition_category_id === (int) $round->competition_category_id, 404);
        $this->ensureLaterRound($round);

        if ($round->judgings()->where('competition_participant_id', $participant->id)->exists()) {
            return back()->withErrors(['round_participants' => "{$participant->name} sudah dinilai di {$round->name}, jadi tidak bisa dikeluarkan."]);
        }

        $round->participants()->detach($participant->id);

        return $this->backToTab($competition, 'penjurian', "{$participant->name} dikeluarkan dari {$round->name}.", ['kategori' => $round->competition_category_id]);
    }

    public function assignJudge(Request $request, CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        // A round every judge has finished is closed to new judges (they would have nothing judged).
        if ($this->judging->isComplete($round)) {
            return back()->withErrors(['judge' => "{$round->name} sudah selesai dinilai semua juri, jadi juri baru tidak bisa ditugaskan."]);
        }

        // A public round cannot be judged, so a new judge there could never send a sheet.
        if ($round->isPublished()) {
            return back()->withErrors(['judge' => "Hasil {$round->name} sudah dipublikasikan. Tarik dulu dari publik sebelum menugaskan juri baru."]);
        }

        // Only the admin's own judges can be assigned.
        $data = $request->validate([
            'competition_judge_id' => [
                'required',
                Rule::exists('competition_judges', 'id')->where('id_user', $request->user()->id_user)->where('status', Status::Active->value),
                Rule::unique('competition_judge_assignments')->where('competition_round_id', $round->id),
            ],
        ], ['competition_judge_id.unique' => 'Juri ini sudah ditugaskan di babak tersebut.', 'competition_judge_id.exists' => 'Juri tidak ditemukan atau sedang nonaktif.'], ['competition_judge_id' => 'juri']);

        $round->judges()->attach($data['competition_judge_id']);

        $note = $this->judging->isReadyForJudging($round) ? '' : ' Catatan: total bobot babak ini belum 100%, jadi juri belum bisa mulai menilai.';

        return $this->backToTab($competition, 'penjurian', 'Juri ditugaskan.'.$note, ['kategori' => $round->competition_category_id]);
    }

    public function unassignJudge(CompetitionListing $competition, CompetitionRound $round, CompetitionJudge $judge): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        if ($round->judgings()->where('competition_judge_id', $judge->id)->exists()) {
            return back()->withErrors(['judge' => 'Juri yang sudah mulai menilai di babak ini tidak bisa dilepas.']);
        }

        $round->judges()->detach($judge->id);

        return $this->backToTab($competition, 'penjurian', 'Juri dilepas dari babak.', ['kategori' => $round->competition_category_id]);
    }

    /**
     * New teams in a round change its ranking, so the team lists of the rounds after it (picked from the
     * old ranking) are cleared; the admin picks them again once this round is judged and recalculated.
     * Only reached when the next round has not started (teamListClosedReason), so no sheet is affected.
     *
     * @param  array<int, int>  $added  ids actually attached
     */
    private function resetLaterRounds(CompetitionRound $round, array $added): string
    {
        $cleared = $added === [] ? [] : $this->judging->clearLaterRoundTeams($round);

        return $cleared === [] ? '' : ' Peserta '.implode(', ', $cleared).' dikosongkan karena dipilih dari peringkat lama; pilih ulang setelah '.$round->name.' selesai dinilai.';
    }

    /**
     * Teams for a later round are chosen from a previous round that is finished AND published,
     * so the ranking they come from is the official one.
     */
    private function previousRoundStillRunning(CompetitionRound $round): ?string
    {
        $previous = $this->judging->previousRound($round);

        return match (true) {
            ! $previous => null,
            // Published too early: judges cannot finish a public round, so say how to get out of it.
            ! $this->judging->isComplete($previous) && $previous->isPublished() => "Hasil {$previous->name} dipublikasikan sebelum semua juri selesai menilai. Tarik dulu hasilnya di tab Hasil supaya juri bisa menyelesaikan penilaian, lalu publikasikan lagi.",
            ! $this->judging->isComplete($previous) => "{$previous->name} masih berlangsung. Peserta {$round->name} bisa dipilih setelah semua juri selesai menilai {$previous->name}.",
            ! $previous->isPublished() => "Hasil {$previous->name} belum dipublikasikan. Publikasikan dulu di tab Hasil, lalu pilih peserta {$round->name}.",
            default => null,
        };
    }

    /**
     * The first round is open to the whole category, so it has no team list to edit.
     */
    private function ensureLaterRound(CompetitionRound $round): void
    {
        abort_if($this->judging->isFirstRound($round), 404);
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
            'weight' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:100'],
            'minimum_score' => ['required', 'numeric', 'min:0'],
            'maximum_score' => ['required', 'numeric', 'gt:minimum_score', 'max:1000'],
        ], ['weight.decimal' => 'Bobot maksimal 2 angka di belakang koma.'], [
            'name' => 'nama kriteria',
            'weight' => 'bobot',
            'minimum_score' => 'nilai minimum',
            'maximum_score' => 'nilai maksimum',
        ]);
    }
}
