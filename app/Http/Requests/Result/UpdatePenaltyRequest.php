<?php

namespace App\Http\Requests\Result;

use App\Models\CompetitionResult;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePenaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CompetitionResult $result */
        $result = $this->route('result');

        return $this->user()->can('manageResults', $result->round->category->event);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'penalty' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'penalty.required' => 'Nilai penalti wajib diisi (boleh 0).',
            'penalty.min' => 'Penalti tidak boleh negatif.',
        ];
    }
}
