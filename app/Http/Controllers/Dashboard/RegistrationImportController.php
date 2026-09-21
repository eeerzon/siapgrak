<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\ApplyImportMappingRequest;
use App\Http\Requests\Registration\UploadImportRequest;
use App\Models\Event;
use App\Models\EventCategory;
use App\Services\RegistrationImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * 3-step wizard (fr-registration-import.md): upload -> map -> preview/commit.
 * Gated behind manageRegistrations throughout, same as the manual
 * registration entry flow it complements.
 */
class RegistrationImportController extends Controller
{
    public function __construct(protected RegistrationImportService $imports) {}

    public function create(Event $event): View
    {
        $this->authorize('manageRegistrations', $event);

        return view('dashboard.registrations.import.upload', [
            'event' => $event,
            'categories' => $event->categories,
        ]);
    }

    public function store(UploadImportRequest $request, Event $event): RedirectResponse
    {
        $result = $this->imports->storeUpload($event, $request->file('file'));

        return redirect()
            ->route('dashboard.events.registrations.import.map', [
                $event,
                $result['token'],
                'category' => $request->validated('event_category_id'),
            ])
            ->with('success', 'File terunggah. Cocokkan kolom di bawah.');
    }

    public function map(Event $event, string $token): View
    {
        $this->authorize('manageRegistrations', $event);
        abort_unless($this->imports->fileExists($event, $token), 404);

        $headers = $this->imports->headers($event, $token);

        return view('dashboard.registrations.import.map', [
            'event' => $event,
            'token' => $token,
            'headers' => $headers,
            'guessed' => $this->imports->guessMapping($headers),
            'categoryId' => request('category'),
        ]);
    }

    public function preview(ApplyImportMappingRequest $request, Event $event, string $token): View
    {
        abort_unless($this->imports->fileExists($event, $token), 404);

        $category = EventCategory::findOrFail($request->validated('event_category_id'));
        $mapping = $this->mappingFrom($request);

        $groups = $this->imports->validateRows($event, $token, $mapping, $category);

        return view('dashboard.registrations.import.preview', [
            'event' => $event,
            'category' => $category,
            'token' => $token,
            'mapping' => $mapping,
            'groups' => $groups,
        ]);
    }

    public function commit(ApplyImportMappingRequest $request, Event $event, string $token): RedirectResponse
    {
        abort_unless($this->imports->fileExists($event, $token), 404);

        $category = EventCategory::findOrFail($request->validated('event_category_id'));
        $mapping = $this->mappingFrom($request);

        $result = $this->imports->commit($event, $token, $mapping, $category, $request->user());

        return redirect()
            ->route('dashboard.events.registrations.index', $event)
            ->with('success', "{$result['created']} registrasi berhasil diimpor, {$result['skipped']} kelompok dilewati karena error.");
    }

    /**
     * @return array<string, string|null>
     */
    protected function mappingFrom(ApplyImportMappingRequest $request): array
    {
        return [
            'team_name' => $request->validated('team_name'),
            'member_name' => $request->validated('member_name'),
            'gender' => $request->validated('gender'),
            'member_role' => $request->validated('member_role'),
        ];
    }
}
