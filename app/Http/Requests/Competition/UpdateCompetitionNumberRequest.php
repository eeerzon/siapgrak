<?php

namespace App\Http\Requests\Competition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetitionNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageCompetition', $this->route('entry')->event);
    }

    public function rules(): array
    {
        $entry = $this->route('entry');

        return [
            'competition_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('competition_entries', 'competition_number')
                    ->where('event_category_id', $entry->event_category_id)
                    ->ignore($entry->id),
            ],
            'performance_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'competition_number.unique' => 'Nomor ini sudah dipakai entry lain di kategori yang sama.',
        ];
    }
}
