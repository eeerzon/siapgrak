<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidRegistrationStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\StoreRegistrationRequest;
use App\Http\Requests\Registration\UpdateRegistrationStatusRequest;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Team;
use App\Services\EventService;
use App\Services\RegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventRegistrationController extends Controller
{
    public function __construct(
        protected RegistrationService $registrations,
        protected EventService $events,
    ) {}

    public function index(Request $request, Event $event): View
    {
        $this->authorize('view', $event);

        $registrations = $event->registrations()
            ->with(['team', 'category', 'entry', 'documents'])
            ->withCount('members')
            ->when($this->queryText($request, 'status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->filled('category'), fn ($query) => $query->where('event_category_id', $request->integer('category')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.registrations.index', [
            'event' => $event,
            'registrations' => $registrations,
        ]);
    }

    public function create(Request $request, Event $event): View
    {
        $this->authorize('manageRegistrations', $event);

        $organizationIds = $this->events->assignableOrganizationIds($request->user());

        return view('dashboard.registrations.create', [
            'event' => $event,
            'categories' => $event->categories,
            'teams' => Team::whereIn('organization_id', $organizationIds)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRegistrationRequest $request, Event $event): RedirectResponse
    {
        $registration = $this->registrations->create(
            event: $event,
            eventCategoryId: (int) $request->validated('event_category_id'),
            teamId: (int) $request->validated('team_id'),
            enteredBy: $request->user(),
        );

        return redirect()
            ->route('dashboard.events.registrations.show', [$event, $registration])
            ->with('success', 'Pendaftaran dibuat sebagai draft. Lengkapi roster & dokumen lalu submit.');
    }

    public function show(Event $event, Registration $registration): View
    {
        $this->assertBelongsToEvent($event, $registration);
        $this->authorize('view', $registration);

        $registration->load([
            'team', 'category', 'submitter', 'reviewer',
            'members.teamMember', 'documents',
        ]);

        return view('dashboard.registrations.show', [
            'event' => $event,
            'registration' => $registration,
            'allowedTransitions' => $this->registrations->allowedTransitions($registration->status),
        ]);
    }

    public function updateStatus(UpdateRegistrationStatusRequest $request, Event $event, Registration $registration): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $registration);

        try {
            $this->registrations->changeStatus(
                registration: $registration,
                to: RegistrationStatus::from($request->validated('status')),
                actor: $request->user(),
                reviewNotes: $request->validated('review_notes'),
            );
        } catch (InvalidRegistrationStatusTransition $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard.events.registrations.show', [$event, $registration])
            ->with('success', 'Status pendaftaran diperbarui.');
    }

    protected function assertBelongsToEvent(Event $event, Registration $registration): void
    {
        abort_unless($registration->event_id === $event->id, 404);
    }
}
