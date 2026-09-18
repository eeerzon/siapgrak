<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStaffRole;
use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageStaff', $this->route('event'));
    }

    /**
     * RULE-10: a staff member must be an active member of the event's owning
     * organization — you cannot inject an arbitrary user as staff (matters
     * once event_staff.role becomes an authz gate in a later sprint).
     * RULE-11: one row per user per event.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = $this->route('event');

        $organizationMemberIds = $event->organization->users()
            ->wherePivot('status', Status::Active)
            ->pluck('users.id');

        return [
            'user_id' => [
                'required',
                Rule::in($organizationMemberIds),
                Rule::unique('event_staff', 'user_id')->where('event_id', $event->id),
            ],
            'role' => ['required', Rule::enum(EventStaffRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Pilih anggota yang akan dijadikan staff.',
            'user_id.in' => 'Staff harus anggota aktif organisasi penyelenggara event ini.',
            'user_id.unique' => 'Anggota ini sudah terdaftar sebagai staff event.',
            'role.required' => 'Peran staff wajib dipilih.',
        ];
    }
}
