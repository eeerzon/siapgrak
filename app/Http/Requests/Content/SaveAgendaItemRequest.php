<?php

namespace App\Http\Requests\Content;

use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAgendaItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:ratio=4/5'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'start_at.required' => 'Waktu mulai wajib diisi.',
            'end_at.after_or_equal' => 'Waktu selesai tidak boleh sebelum waktu mulai.',
            'cover.dimensions' => 'Poster harus berasio 4:5 seperti feed Instagram (contoh 1080×1350 piksel).',
        ];
    }
}
