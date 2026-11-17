<?php

namespace App\Http\Requests\Competition;

use App\Enums\CompetitionEntryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetitionEntryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageCompetition', $this->route('entry')->event);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(CompetitionEntryStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status tujuan wajib dipilih.',
        ];
    }
}
