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

    public function rules(): array
    {
        $event = $this->route('event');

        $organizationMemberIds = $event->organization->users()
            ->wherePivot('status', Status::Active)
            ->pluck('users.id_user');

        return [
            'user_id' => [
                'required',
                Rule::in($organizationMemberIds),
                Rule::unique('event_staff', 'user_id')->where('event_id', $event->id),
            ],
            'role' => ['required', Rule::enum(EventStaffRole::class)],
        ];
    }

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
