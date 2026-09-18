<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('registration'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_type.required' => 'Jenis dokumen wajib diisi.',
            'file.required' => 'File dokumen wajib diunggah.',
            'file.mimes' => 'Dokumen harus PDF atau gambar (JPG/PNG).',
            'file.max' => 'Ukuran dokumen maksimal 5 MB.',
        ];
    }
}
