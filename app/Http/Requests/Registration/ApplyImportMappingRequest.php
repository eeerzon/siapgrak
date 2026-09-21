<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by both the preview step (map -> POST renders preview) and the
 * commit step — same input shape, both re-derive the actual column indexes
 * from the file rather than trusting the submitted header names directly
 * (fr-registration-import.md RULE-6).
 */
class ApplyImportMappingRequest extends FormRequest
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

        return [
            'event_category_id' => [
                'required',
                Rule::exists('event_categories', 'id')->where('event_id', $event->id),
            ],
            'team_name' => ['required', 'string'],
            'member_name' => ['required', 'string'],
            'gender' => ['nullable', 'string'],
            'member_role' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'team_name.required' => 'Pilih kolom untuk Nama Tim.',
            'member_name.required' => 'Pilih kolom untuk Nama Anggota.',
        ];
    }
}
