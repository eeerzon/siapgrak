<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\StoreRegistrationDocumentRequest;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents live on the PRIVATE 'local' disk (storage/app/private). There is
 * no permanent public URL — download() is Policy-checked and streams the file
 * (fr-registration.md Keputusan E, security-baseline.md §File Upload).
 */
class RegistrationDocumentController extends Controller
{
    protected const DISK = 'local';

    public function store(StoreRegistrationDocumentRequest $request, Registration $registration): RedirectResponse
    {
        abort_unless($registration->isEditable(), 403);

        $file = $request->file('file');
        $path = $file->storeAs(
            "registration-documents/{$registration->id}",
            Str::ulid().'.'.$file->getClientOriginalExtension(),
            self::DISK,
        );

        // file_path is set on the instance, not mass-assigned — it is a
        // server-computed path, never request input.
        $document = new RegistrationDocument([
            'document_type' => $request->validated('document_type'),
            'title' => $request->validated('title'),
        ]);
        $document->file_path = $path;
        $registration->documents()->save($document);

        return $this->back($registration, 'Dokumen diunggah.');
    }

    public function download(Registration $registration, RegistrationDocument $document): StreamedResponse
    {
        $this->authorize('view', $registration);
        $this->assertBelongsToRegistration($registration, $document);

        abort_unless(Storage::disk(self::DISK)->exists($document->file_path), 404);

        $name = ($document->title ?: $document->document_type).'.'.pathinfo($document->file_path, PATHINFO_EXTENSION);

        return Storage::disk(self::DISK)->download($document->file_path, $name);
    }

    public function destroy(Registration $registration, RegistrationDocument $document): RedirectResponse
    {
        $this->authorize('updateStatus', $registration);
        $this->assertBelongsToRegistration($registration, $document);
        abort_unless($registration->isEditable(), 403);

        Storage::disk(self::DISK)->delete($document->file_path);
        $document->delete();

        return $this->back($registration, 'Dokumen dihapus.');
    }

    protected function assertBelongsToRegistration(Registration $registration, RegistrationDocument $document): void
    {
        abort_unless($document->registration_id === $registration->id, 404);
    }

    protected function back(Registration $registration, string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.registrations.show', [$registration->event_id, $registration])
            ->with('success', $message);
    }
}
