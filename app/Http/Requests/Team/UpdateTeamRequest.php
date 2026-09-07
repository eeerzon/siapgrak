<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('team'));
    }

    /**
     * organization_id is intentionally NOT editable here — moving a team
     * between organizations is a tenant-transfer operation not designed in
     * Sprint 2 (fr-team-directory.md). A team's owning organization is fixed
     * at creation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('teams', 'slug')->ignore($team->id)],
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
            'name.required' => 'Nama team wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug sudah dipakai team lain.',
        ];
    }
}
