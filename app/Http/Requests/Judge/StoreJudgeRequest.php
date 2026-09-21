<?php

namespace App\Http\Requests\Judge;

use App\Models\Judge;
use App\Services\JudgeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJudgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Judge::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $assignableOrganizationIds = app(JudgeService::class)->assignableOrganizationIds($this->user());

        return [
            // RULE-1 (fr-judging-setup.md): organization_id required at
            // validation even though the column is nullable at schema level.
            'organization_id' => ['required', Rule::in($assignableOrganizationIds)],
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.required' => 'Pilih organisasi yang menaungi juri ini.',
            'organization_id.in' => 'Anda harus menjadi anggota aktif organisasi yang dipilih.',
            'name.required' => 'Nama juri wajib diisi.',
        ];
    }
}
