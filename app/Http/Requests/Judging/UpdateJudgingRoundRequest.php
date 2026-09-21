<?php

namespace App\Http\Requests\Judging;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJudgingRoundRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama round wajib diisi.',
        ];
    }
}
