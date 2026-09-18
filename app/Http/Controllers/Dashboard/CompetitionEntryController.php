<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\CompetitionEntryStatus;
use App\Exceptions\InvalidCompetitionEntryStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\GenerateCompetitionNumbersRequest;
use App\Http\Requests\Competition\UpdateCompetitionEntryStatusRequest;
use App\Http\Requests\Competition\UpdateCompetitionNumberRequest;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Services\CompetitionNumberService;
use App\Services\CompetitionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Entries themselves are never created here — CompetitionService creates them
 * from an approved Registration (fr-competition-entry.md Keputusan A). This
 * controller only assigns numbers and progresses the event-day status.
 * Gated through EventPolicy::manageCompetition — no separate CompetitionEntryPolicy
 * (Keputusan H, same pattern as Sprint 3/4).
 */
class CompetitionEntryController extends Controller
{
    public function __construct(
        protected CompetitionNumberService $numbers,
        protected CompetitionService $competition,
    ) {}

    public function generateNumbers(GenerateCompetitionNumbersRequest $request, Event $event): RedirectResponse
    {
        $category = EventCategory::findOrFail($request->validated('event_category_id'));

        try {
            $entries = $this->numbers->generateSequential(
                category: $category,
                prefix: (string) $request->validated('prefix'),
                start: (int) $request->validated('start'),
                digits: (int) $request->validated('digits'),
            );
        } catch (UniqueConstraintViolationException) {
            return redirect()
                ->route('dashboard.events.show', $event)
                ->with('error', 'Gagal generate nomor — nomor mulai bentrok dengan yang sudah ada. Coba nomor mulai lain.');
        }

        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', "{$entries->count()} entry diberi nomor.");
    }

    public function updateNumber(UpdateCompetitionNumberRequest $request, CompetitionEntry $entry): RedirectResponse
    {
        $this->numbers->assignManual($entry, $request->validated('competition_number'));

        if ($request->filled('performance_order')) {
            $entry->update(['performance_order' => $request->validated('performance_order')]);
        }

        return redirect()
            ->route('dashboard.events.show', $entry->event)
            ->with('success', 'Nomor entry diperbarui.');
    }

    public function updateStatus(UpdateCompetitionEntryStatusRequest $request, CompetitionEntry $entry): RedirectResponse
    {
        try {
            $this->competition->changeStatus($entry, CompetitionEntryStatus::from($request->validated('status')));
        } catch (InvalidCompetitionEntryStatusTransition $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard.events.show', $entry->event)
            ->with('success', 'Status entry diperbarui.');
    }
}
