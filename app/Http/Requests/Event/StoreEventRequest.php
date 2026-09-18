<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use App\Services\EventService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Event::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $assignableOrganizationIds = app(EventService::class)->assignableOrganizationIds($this->user());

        return [
            // Keputusan F: any active member of the org may create; the org
            // itself must be one the user actually belongs to.
            'organization_id' => ['required', Rule::in($assignableOrganizationIds)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('events', 'slug')],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_start_at' => ['nullable', 'date'],
            'registration_end_at' => ['nullable', 'date', 'after_or_equal:registration_start_at'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'province_code' => ['nullable', 'string', 'max:20'],
            'city_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.required' => 'Pilih organisasi penyelenggara.',
            'organization_id.in' => 'Anda harus menjadi anggota aktif organisasi yang dipilih.',
            'name.required' => 'Nama event wajib diisi.',
            'slug.unique' => 'Slug sudah dipakai event lain.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'registration_end_at.after_or_equal' => 'Penutupan pendaftaran tidak boleh sebelum pembukaan.',
        ];
    }
}
