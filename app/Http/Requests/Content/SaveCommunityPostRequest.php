<?php

namespace App\Http\Requests\Content;

use App\Enums\CommunityPostType;
use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCommunityPostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(CommunityPostType::class)],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Pilih jenis kabar.',
            'title.required' => 'Judul wajib diisi.',
            'body.required' => 'Isi kabar wajib diisi.',
        ];
    }
}
