<?php

namespace App\Http\Requests\Event;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageCategories', $this->route('event'));
    }

    public function rules(): array
    {
        $event = $this->route('event');
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable', 'string', 'alpha_dash', 'max:50',
                Rule::unique('event_categories', 'code')
                    ->where('event_id', $event->id)
                    ->ignore($category->id),
            ],
            'description' => ['nullable', 'string'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'min_members' => ['nullable', 'integer', 'min:1'],
            'max_members' => ['nullable', 'integer', 'min:1'],
            'registration_start_at' => ['nullable', 'date'],
            'registration_end_at' => ['nullable', 'date', 'after_or_equal:registration_start_at'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = $this->input('min_members');
            $max = $this->input('max_members');

            if ($min !== null && $max !== null && (int) $max < (int) $min) {
                $validator->errors()->add('max_members', 'Maksimal anggota tidak boleh kurang dari minimal anggota.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'code.unique' => 'Kode kategori sudah dipakai di event ini.',
            'registration_end_at.after_or_equal' => 'Penutupan pendaftaran tidak boleh sebelum pembukaan.',
        ];
    }
}
