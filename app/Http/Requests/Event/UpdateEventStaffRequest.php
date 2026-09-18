<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStaffRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageStaff', $this->route('event'));
    }

    /**
     * user_id is fixed once assigned (RULE-11) — only role/status change here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(EventStaffRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Peran staff wajib dipilih.',
        ];
    }
}
