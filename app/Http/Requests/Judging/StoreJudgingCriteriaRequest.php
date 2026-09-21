<?php

namespace App\Http\Requests\Judging;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJudgingCriteriaRequest extends FormRequest
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

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_score' => ['required', 'numeric'],
            'maximum_score' => ['required', 'numeric', 'gte:minimum_score'],
            // RULE-3 (fr-judging-setup.md): parent must be in the SAME round
            // AND be a root criterion itself — this single check enforces the
            // max-nesting-depth-2 rule (Keputusan D) without extra service code.
            'parent_id' => [
                'nullable',
                Rule::exists('judging_criteria', 'id')
                    ->where('judging_round_id', $round->id)
                    ->whereNull('parent_id'),
            ],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kriteria wajib diisi.',
            'minimum_score.required' => 'Skor minimum wajib diisi.',
            'maximum_score.required' => 'Skor maksimum wajib diisi.',
            'maximum_score.gte' => 'Skor maksimum tidak boleh kurang dari skor minimum.',
            'parent_id.exists' => 'Kriteria induk harus criteria utama (bukan subcriteria) di round yang sama — maksimal 2 level.',
        ];
    }
}
