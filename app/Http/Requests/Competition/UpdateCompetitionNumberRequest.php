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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $entry = $this->route('entry');

        return [
            // RULE-3: unique per category, not per event.
            'competition_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('competition_entries', 'competition_number')
                    ->where('event_category_id', $entry->event_category_id)
                    ->ignore($entry->id),
            ],
            'performance_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'competition_number.unique' => 'Nomor ini sudah dipakai entry lain di kategori yang sama.',
        ];
    }
}
