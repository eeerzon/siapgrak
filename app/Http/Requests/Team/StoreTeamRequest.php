<?php

namespace App\Http\Requests\Team;

use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Team::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $assignableOrganizationIds = app(TeamService::class)->assignableOrganizationIds($this->user());

        return [
            // RULE-1 (fr-team-directory.md): organization_id required at
            // validation even though the column is nullable at schema level.
            'organization_id' => ['required', Rule::in($assignableOrganizationIds)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('teams', 'slug')],
            'short_name' => ['nullable', 'string', 'max:255'],
            'province_code' => ['nullable', 'string', 'max:20'],
            'city_code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.required' => 'Pilih organisasi pemilik team ini.',
            'organization_id.in' => 'Anda harus menjadi anggota aktif organisasi yang dipilih.',
            'name.required' => 'Nama team wajib diisi.',
            'slug.unique' => 'Slug sudah dipakai team lain.',
        ];
    }
}
