<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('registration'));
    }

    /**
     * The "registration must still be editable" guard lives in the controller
     * (fr-registration.md RULE-6).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $registration = $this->route('registration');

        return [
            'name' => ['required', 'string', 'max:255'],
            'member_role' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            // Optional provenance — if given, must be a member of THIS
            // registration's team.
            'team_member_id' => [
                'nullable',
                Rule::exists('team_members', 'id')->where('team_id', $registration->team_id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama anggota wajib diisi.',
            'team_member_id.exists' => 'Anggota tidak ditemukan di roster team ini.',
        ];
    }
}
