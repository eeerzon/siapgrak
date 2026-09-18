<?php

namespace App\Http\Requests\Registration;

use App\Enums\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('registration'));
    }

    /**
     * Only checks the value is a known status + that a reason is given for the
     * negative outcomes (RULE-8). Whether the transition is allowed is decided
     * by RegistrationService::changeStatus().
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RegistrationStatus::class)],
            'review_notes' => ['nullable', 'string', 'max:2000', 'required_if:status,rejected,revision_requested'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status tujuan wajib dipilih.',
            'review_notes.required_if' => 'Alasan wajib diisi saat menolak atau meminta revisi.',
        ];
    }
}
