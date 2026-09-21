<?php

namespace App\Http\Requests\ScoreSheet;

use Illuminate\Foundation\Http\FormRequest;

/**
 * authorize() is handled in ScoreSheetController::reopen (differs by
 * sheet.status — Keputusan A, fr-score-sheet-lifecycle.md), not here.
 */
class ReopenScoreSheetRequest extends FormRequest
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
            // RULE-3: reason is mandatory, never a silent reopen.
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
