<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImportRequest extends FormRequest
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
            // RULE-1 (fr-registration-import.md): CSV only, max 2MB — same
            // pattern as StoreRegistrationDocumentRequest's file limit.
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_category_id.required' => 'Pilih kategori tujuan import.',
            'file.required' => 'File CSV wajib diunggah.',
            'file.mimes' => 'File harus berformat CSV.',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ];
    }
}
