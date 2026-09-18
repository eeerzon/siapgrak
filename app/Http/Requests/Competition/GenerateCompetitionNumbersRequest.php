<?php

namespace App\Http\Requests\Competition;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateCompetitionNumbersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageCompetition', $this->route('event'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'event_category_id' => [
                'required',
                Rule::exists('event_categories', 'id')->where('event_id', $event->id),
            ],
            'prefix' => ['nullable', 'string', 'max:10'],
            'start' => ['required', 'integer', 'min:1'],
            'digits' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_category_id.required' => 'Pilih kategori.',
            'start.required' => 'Nomor mulai wajib diisi.',
            'digits.required' => 'Jumlah digit wajib diisi.',
        ];
    }
}
