<?php

namespace App\Http\Requests\Judge;

use App\Services\ScoreSheetService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * authorize() stays true — EnsureJudgeSession already gates the whole /judge/*
 * group. Ownership (this judge, this round, this entry, these criteria) is
 * re-checked here anyway (RULE-2/3/4/5, fr-scoring.md) since that's business
 * logic, not route access.
 */
class SaveScoreDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scores' => ['present', 'array'],
            'scores.*.judging_criteria_id' => ['required', 'integer'],
            'scores.*.score' => ['nullable', 'numeric'],
            'scores.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $judge = $this->attributes->get('judge');
            $round = $this->route('round');
            $entry = $this->route('entry');

            if ($entry->event_category_id !== $round->event_category_id) {
                $validator->errors()->add('scores', 'Peserta ini bukan bagian dari kategori round ini.');

                return;
            }

            if (! $round->assignments()->where('judge_id', $judge->id)->exists()) {
                $validator->errors()->add('scores', 'Anda tidak ditugaskan di round ini.');

                return;
            }

            $scoreable = app(ScoreSheetService::class)->scoreableCriteriaFor($round, $judge)->keyBy('id');

            foreach ($this->input('scores', []) as $index => $row) {
                $criteriaId = (int) ($row['judging_criteria_id'] ?? 0);

                if (! $scoreable->has($criteriaId)) {
                    $validator->errors()->add("scores.$index.judging_criteria_id", 'Kriteria ini bukan tanggung jawab Anda.');

                    continue;
                }

                $score = $row['score'] ?? null;

                if ($score === null || $score === '') {
                    continue;
                }

                $criteria = $scoreable->get($criteriaId);

                if ((float) $score < (float) $criteria->minimum_score || (float) $score > (float) $criteria->maximum_score) {
                    $validator->errors()->add(
                        "scores.$index.score",
                        "Skor harus antara {$criteria->minimum_score} dan {$criteria->maximum_score}.",
                    );
                }
            }
        });
    }
}
