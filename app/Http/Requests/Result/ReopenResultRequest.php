<?php

namespace App\Http\Requests\Result;

use Illuminate\Foundation\Http\FormRequest;

/**
 * authorize() is handled in ResultController::reopen (differs by current
 * result status — Keputusan B, fr-awards-finalization.md), not here.
 */
class ReopenResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan reopen wajib diisi.',
        ];
    }
}
