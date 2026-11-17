<?php

namespace App\Http\Requests\Content;

use App\Enums\CompetitionLevel;
use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCompetitionListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:ratio=4/5'],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', Rule::in(config('siapgrak.provinces'))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'levels' => ['nullable', 'array'],
            'levels.*' => [Rule::enum(CompetitionLevel::class)],
            'registration_deadline' => ['nullable', 'date'],
            'technical_meeting_date' => ['nullable', 'date'],
            'technical_meeting_location' => ['nullable', 'string', 'max:255'],
            'contact_persons' => ['nullable', 'array', 'max:3'],
            'contact_persons.*.name' => ['nullable', 'string', 'max:100', 'required_with:contact_persons.*.phone'],
            'contact_persons.*.phone' => ['nullable', 'string', 'regex:/^\+?[0-9][0-9\s\-]{7,19}$/', 'required_with:contact_persons.*.name'],
            'is_managed' => ['required', 'boolean'],
            'kreen_slug' => [
                'exclude_unless:is_managed,1', 'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('competition_listings', 'kreen_slug')->ignore($this->route('competition')),
            ],
            'external_url' => ['exclude_if:is_managed,1', 'nullable', 'url:http,https', 'max:2048'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'kreen_slug.required' => 'Slug Kreenconnect wajib diisi untuk event yang kita kelola.',
            'kreen_slug.unique' => 'Slug Kreenconnect sudah dipakai info lomba lain.',
            'kreen_slug.alpha_dash' => 'Slug Kreenconnect hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'external_url.url' => 'Link harus berupa URL http atau https yang valid.',
            'cover.dimensions' => 'Poster harus berasio 4:5 seperti feed Instagram (contoh 1080×1350 piksel).',
            'contact_persons.*.name.required_with' => 'Nama contact person wajib diisi.',
            'contact_persons.*.phone.required_with' => 'Nomor WhatsApp contact person wajib diisi.',
            'contact_persons.*.phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda hubung, dan + di depan.',
        ];
    }

    public function payload(): array
    {
        $data = $this->validated() + ['kreen_slug' => null, 'external_url' => null, 'levels' => null];

        $contacts = collect($data['contact_persons'] ?? [])
            ->filter(fn (array $contact) => filled($contact['name'] ?? null))
            ->map(fn (array $contact) => ['name' => $contact['name'], 'phone' => $contact['phone']])
            ->values()
            ->all();

        $data['contact_persons'] = $contacts ?: null;
        $data['levels'] = $data['levels'] ?: null;

        return $data;
    }
}
