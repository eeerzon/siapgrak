<?php

namespace App\Http\Requests\Judging;

use App\Models\JudgeAssignment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJudgeAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageJudging', $this->route('round')->category->event);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $round = $this->route('round');
        $organizationId = $round->category->event->organization_id;

        return [
            // RULE-1: judge must belong to this event's organization
            // (fr-judge-assignment.md Keputusan C — no cross-org sharing yet).
            'judge_id' => [
                'required',
                Rule::exists('judges', 'id')->where('organization_id', $organizationId),
            ],
            // RULE-2: criteria (if any) must belong to THIS round.
            'judging_criteria_id' => [
                'nullable',
                Rule::exists('judging_criteria', 'id')->where('judging_round_id', $round->id),
            ],
        ];
    }

    /**
     * RULE-3 (Keputusan B): a judge gets either ONE whole-round assignment
     * (criteria_id null) OR one-or-more specific-criteria assignments — never
     * a mix, and never two whole-round rows. MySQL's UNIQUE constraint can't
     * catch the NULL case (NULL != NULL), so this is enforced here.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $round = $this->route('round');
            $judgeId = $this->input('judge_id');
            $criteriaId = $this->input('judging_criteria_id');

            if (! $judgeId) {
                return;
            }

            $existing = JudgeAssignment::query()
                ->where('judging_round_id', $round->id)
                ->where('judge_id', $judgeId)
                ->get();

            if ($criteriaId === null && $existing->isNotEmpty()) {
                $validator->errors()->add('judge_id', 'Juri ini sudah punya penugasan di round ini.');
            } elseif ($criteriaId !== null) {
                if ($existing->contains(fn ($assignment) => $assignment->judging_criteria_id === null)) {
                    $validator->errors()->add('judge_id', 'Juri ini sudah ditugaskan untuk seluruh round — tidak bisa ditambah kriteria spesifik.');
                } elseif ($existing->contains('judging_criteria_id', (int) $criteriaId)) {
                    $validator->errors()->add('judging_criteria_id', 'Juri ini sudah ditugaskan ke kriteria ini.');
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judge_id.required' => 'Pilih juri.',
            'judge_id.exists' => 'Juri harus dari organisasi penyelenggara event ini.',
            'judging_criteria_id.exists' => 'Kriteria tidak ditemukan di round ini.',
        ];
    }
}
