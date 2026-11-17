<?php

namespace App\Http\Requests\Award;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageResults', $this->route('event'));
    }

    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:255'],
            'award_type' => ['required', 'string', 'max:255'],
            'event_category_id' => [
                'nullable',
                Rule::exists('event_categories', 'id')->where('event_id', $event->id),
            ],
            'competition_entry_id' => [
                'nullable',
                Rule::exists('competition_entries', 'id')->where('event_id', $event->id),
            ],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama penghargaan wajib diisi.',
            'award_type.required' => 'Jenis penghargaan wajib diisi.',
            'event_category_id.exists' => 'Kategori tidak ditemukan di event ini.',
            'competition_entry_id.exists' => 'Peserta tidak ditemukan di event ini.',
        ];
    }
}
