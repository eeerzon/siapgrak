<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Enums\ScoreSheetStatus;
use App\Enums\Status;
use App\Exceptions\InvalidEventStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Requests\Event\UpdateEventStatusRequest;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Services\EventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function __construct(protected EventService $events) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Event::class);

        $organizationIds = $this->events->assignableOrganizationIds($request->user());

        $events = Event::query()
            ->whereIn('organization_id', $organizationIds)
            ->with('organization')
            ->withCount(['categories', 'staff'])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.events.index', compact('events'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Event::class);

        $organizations = Organization::whereIn('id', $this->events->assignableOrganizationIds($request->user()))
            ->orderBy('name')
            ->get();

        return view('dashboard.events.create', compact('organizations'));
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $event = $this->events->create(
            data: collect($data)->except('organization_id')->all(),
            creator: $request->user(),
            organizationId: (int) $data['organization_id'],
        );

        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', 'Event berhasil dibuat.');
    }

    public function show(Event $event): View
    {
        $this->authorize('view', $event);

        $event->load([
            'organization',
            'categories.registrations',
            'categories.competitionEntries.team',
            'categories.judgingRounds.criteria.children',
            'categories.judgingRounds.assignments.judge',
            'categories.judgingRounds.scoreSheets.judge',
            'categories.judgingRounds.scoreSheets.entry.team',
            'categories.judgingRounds.results.entry.team',
            'awards.entry.team',
            'awards.category',
            'staff.user',
            'scheduleSlots.category',
            'scheduleSlots.entry.team',
        ])->loadCount('registrations');

        $assignedUserIds = $event->staff->pluck('user_id');
        $eligibleStaffUsers = $event->organization->users()
            ->wherePivot('status', Status::Active)
            ->whereNotIn('users.id_user', $assignedUserIds)
            ->orderBy('name')
            ->get();

        $organizationJudges = $event->organization->judges()->orderBy('name')->get();

        $registrationCounts = $event->registrations()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dashboard.events.show', [
            'event' => $event,
            'allowedTransitions' => $this->events->allowedTransitions($event->status),
            'eligibleStaffUsers' => $eligibleStaffUsers,
            'organizationJudges' => $organizationJudges,
            'registrationCounts' => $registrationCounts,
            'readiness' => $this->readiness($event, $registrationCounts),
            'nextAction' => $this->nextAction($event, $registrationCounts),
            'judgingReadiness' => $event->categories->mapWithKeys(
                fn ($category) => [$category->id => $this->judgingReadinessFor($category)],
            ),
        ]);
    }

    protected function judgingReadinessFor(EventCategory $category): array
    {
        $criteriaCount = $category->judgingRounds->flatMap(fn ($round) => $round->criteria)->count();
        $judgeCount = $category->judgingRounds
            ->flatMap(fn ($round) => $round->assignments)
            ->pluck('judge_id')
            ->unique()
            ->count();

        return [
            ['label' => $category->judgingRounds->isNotEmpty() ? 'Round configured' : 'Round belum dibuat', 'ok' => $category->judgingRounds->isNotEmpty()],
            ['label' => "{$criteriaCount} kriteria", 'ok' => $criteriaCount > 0],
            ['label' => "{$judgeCount} juri", 'ok' => $judgeCount > 0],
        ];
    }

    protected function readiness(Event $event, $registrationCounts): array
    {
        $categoriesCount = $event->categories->count();
        $scheduleCount = $event->scheduleSlots->count();

        $assignedJudgeCount = $event->categories
            ->flatMap(fn ($category) => $category->judgingRounds)
            ->flatMap(fn ($round) => $round->assignments)
            ->pluck('judge_id')
            ->unique()
            ->count();

        $scoreSheets = $event->categories
            ->flatMap(fn ($category) => $category->judgingRounds)
            ->flatMap(fn ($round) => $round->scoreSheets);
        $scoreSheetsTotal = $scoreSheets->count();
        $scoreSheetsDone = $scoreSheets->whereIn('status', [ScoreSheetStatus::Submitted, ScoreSheetStatus::Locked])->count();

        return [
            ['label' => 'Kategori', 'value' => (string) $categoriesCount, 'ok' => $categoriesCount > 0],
            ['label' => 'Peserta', 'value' => (string) $event->registrations_count, 'ok' => $event->registrations_count > 0],
            ['label' => 'Jadwal', 'value' => $scheduleCount.' slot', 'ok' => $scheduleCount > 0],
            ['label' => 'Juri', 'value' => (string) $assignedJudgeCount, 'ok' => $assignedJudgeCount > 0],
            [
                'label' => 'Penilaian',
                'value' => $scoreSheetsTotal === 0 ? 'Belum mulai' : "{$scoreSheetsDone}/{$scoreSheetsTotal} selesai",
                'ok' => $scoreSheetsTotal > 0,
            ],
        ];
    }

    protected function nextAction(Event $event, $registrationCounts): ?array
    {
        $pending = $registrationCounts[RegistrationStatus::Submitted->value] ?? 0;

        if ($pending > 0) {
            return ['message' => "{$pending} registrasi menunggu direview.", 'tab' => 'participants', 'cta' => 'Review Peserta'];
        }

        if ($event->categories->isEmpty()) {
            return ['message' => 'Belum ada kategori kompetisi.', 'tab' => 'categories', 'cta' => 'Tambah Kategori'];
        }

        if ($event->scheduleSlots->isEmpty()) {
            return ['message' => 'Jadwal event belum dibuat.', 'tab' => 'schedule', 'cta' => 'Buat Jadwal'];
        }

        $hasRounds = $event->categories->contains(fn ($category) => $category->judgingRounds->isNotEmpty());
        $hasAssignments = $event->categories
            ->flatMap(fn ($category) => $category->judgingRounds)
            ->flatMap(fn ($round) => $round->assignments)
            ->isNotEmpty();

        if ($hasRounds && ! $hasAssignments) {
            return ['message' => 'Belum ada juri yang ditugaskan.', 'tab' => 'judging', 'cta' => 'Tugaskan Juri'];
        }

        return null;
    }

    public function edit(Event $event): View
    {
        $this->authorize('update', $event);

        return view('dashboard.events.edit', compact('event'));
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->events->update($event, $request->validated());

        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', 'Event berhasil diperbarui.');
    }

    public function updateStatus(UpdateEventStatusRequest $request, Event $event): RedirectResponse
    {
        try {
            $this->events->changeStatus($event, EventStatus::from($request->validated('status')));
        } catch (InvalidEventStatusTransition $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', 'Status event diperbarui.');
    }
}
