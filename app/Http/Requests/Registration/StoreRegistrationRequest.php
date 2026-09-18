<?php

namespace App\Http\Requests\Registration;

use App\Services\EventService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageRegistrations', $this->route('event'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = $this->route('event');
        $assignableOrganizationIds = app(EventService::class)->assignableOrganizationIds($this->user());

        return [
            // RULE-1: the team must belong to an organization the user is an
            // active member of — you cannot attach a foreign team.
            'team_id' => [
                'required',
                Rule::exists('teams', 'id')->whereIn('organization_id', $assignableOrganizationIds->all()),
                // RULE-3 / Keputusan J: one team per category, once.
                Rule::unique('registrations', 'team_id')
                    ->where('event_category_id', (int) $this->input('event_category_id')),
            ],
            // RULE-2: category must belong to THIS event.
            'event_category_id' => [
                'required',
                Rule::exists('event_categories', 'id')->where('event_id', $event->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'team_id.required' => 'Pilih team peserta.',
            'team_id.exists' => 'Team harus milik organisasi tempat Anda menjadi anggota aktif.',
            'team_id.unique' => 'Team ini sudah terdaftar di kategori tersebut.',
            'event_category_id.required' => 'Pilih kategori lomba.',
            'event_category_id.exists' => 'Kategori tidak ditemukan di event ini.',
        ];
    }
}
