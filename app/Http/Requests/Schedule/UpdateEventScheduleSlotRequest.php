<?php

namespace App\Http\Requests\Schedule;

use App\Enums\ScheduleSlotType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventScheduleSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageSchedule', $this->route('event'));
    }

    /**
     * Every field stays editable (fr-schedule.md Keputusan A).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $eventId = $this->route('event')->id;

        return [
            'title' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'venue_area' => ['nullable', 'string', 'max:255'],
            'slot_type' => ['required', Rule::enum(ScheduleSlotType::class)],
            'event_category_id' => ['nullable', Rule::exists('event_categories', 'id')->where('event_id', $eventId)],
            'competition_entry_id' => ['nullable', Rule::exists('competition_entries', 'id')->where('event_id', $eventId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_at.required' => 'Waktu mulai wajib diisi.',
            'end_at.after_or_equal' => 'Waktu selesai tidak boleh sebelum waktu mulai.',
            'slot_type.required' => 'Jenis slot wajib dipilih.',
        ];
    }
}
