<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * CSV-only bulk registration import (fr-registration-import.md). Teams must
 * already exist (Keputusan B) — this never creates a Team, only Registration
 * + RegistrationMember rows for teams that already have a matching name in
 * the event's organization.
 */
class RegistrationImportService
{
    protected const DISK = 'local';

    protected const EXPECTED_FIELDS = ['team_name', 'member_name', 'gender', 'member_role'];

    public function __construct(protected RegistrationService $registrations) {}

    /**
     * @return array{path: string, token: string}
     */
    public function storeUpload(Event $event, UploadedFile $file): array
    {
        $token = (string) Str::ulid();
        $path = $file->storeAs("imports/{$event->id}", "{$token}.csv", self::DISK);

        return ['path' => $path, 'token' => $token];
    }

    public function pathFor(Event $event, string $token): string
    {
        return "imports/{$event->id}/{$token}.csv";
    }

    public function fileExists(Event $event, string $token): bool
    {
        return Storage::disk(self::DISK)->exists($this->pathFor($event, $token));
    }

    public function deleteUpload(Event $event, string $token): void
    {
        Storage::disk(self::DISK)->delete($this->pathFor($event, $token));
    }

    /**
     * @return list<string>
     */
    public function headers(Event $event, string $token): array
    {
        $rows = $this->readRows($event, $token);

        return $rows->first() ?? [];
    }

    /**
     * Best-effort auto-match against common header spellings — organizer can
     * always override via the dropdown, this is just a head start.
     *
     * @param  list<string>  $headers
     * @return array<string, string|null>
     */
    public function guessMapping(array $headers): array
    {
        $patterns = [
            'team_name' => ['team', 'tim', 'nama tim', 'nama_tim'],
            'member_name' => ['name', 'nama', 'nama lengkap', 'nama_lengkap', 'peserta'],
            'gender' => ['gender', 'jk', 'jenis kelamin', 'jenis_kelamin', 'l/p'],
            'member_role' => ['role', 'posisi', 'jabatan', 'peran'],
        ];

        $mapping = [];
        foreach ($patterns as $field => $candidates) {
            $mapping[$field] = collect($headers)->first(
                fn ($header) => in_array(strtolower(trim($header)), $candidates, true),
            );
        }

        return $mapping;
    }

    /**
     * @param  array<string, string|null>  $mapping
     * @return Collection<int, array{team_name: string, exists: bool, team: ?Team, duplicate: bool, members: Collection<int, array{name: string, gender: ?string, member_role: ?string, valid: bool}>}>
     */
    public function validateRows(Event $event, string $token, array $mapping, EventCategory $category): Collection
    {
        $rows = $this->readRows($event, $token);
        $headers = $rows->shift() ?? [];
        $indexes = $this->columnIndexes($headers, $mapping);

        $organizationTeams = Team::where('organization_id', $event->organization_id)
            ->get()
            ->keyBy(fn (Team $team) => strtolower(trim($team->name)));

        $registeredTeamIds = $category->registrations()->pluck('team_id')->all();

        $groups = $rows
            ->map(fn ($row) => $this->extractRow($row, $indexes))
            ->filter(fn ($row) => $row['team_name'] !== '' || $row['member_name'] !== '')
            ->groupBy(fn ($row) => strtolower(trim($row['team_name'])));

        return $groups->map(function (Collection $rowsForTeam, string $key) use ($organizationTeams, $registeredTeamIds) {
            $teamName = trim($rowsForTeam->first()['team_name']);
            $team = $organizationTeams->get($key);

            $members = $rowsForTeam->map(fn ($row) => [
                'name' => trim($row['member_name']),
                'gender' => $row['gender'] !== '' ? trim($row['gender']) : null,
                'member_role' => $row['member_role'] !== '' ? trim($row['member_role']) : null,
                'valid' => trim($row['member_name']) !== '',
            ]);

            $validMembers = $members->filter(fn ($m) => $m['valid']);
            $duplicate = $team !== null && in_array($team->id, $registeredTeamIds, true);

            return [
                'team_name' => $teamName,
                'exists' => $team !== null,
                'team' => $team,
                'duplicate' => $duplicate,
                'members' => $members->values(),
                'valid' => $teamName !== '' && $team !== null && ! $duplicate && $validMembers->isNotEmpty(),
            ];
        })->values();
    }

    /**
     * Re-validates from the file (RULE-6, defense-in-depth) rather than
     * trusting anything the browser posts back from the preview page.
     *
     * @param  array<string, string|null>  $mapping
     * @return array{created: int, skipped: int}
     */
    public function commit(Event $event, string $token, array $mapping, EventCategory $category, User $actor): array
    {
        $groups = $this->validateRows($event, $token, $mapping, $category);

        $created = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            if (! $group['valid']) {
                $skipped++;

                continue;
            }

            $registration = $this->registrations->create(
                event: $event,
                eventCategoryId: $category->id,
                teamId: $group['team']->id,
                enteredBy: $actor,
            );
            $this->registrations->changeStatus($registration, RegistrationStatus::Submitted, $actor);

            foreach ($group['members']->where('valid', true) as $member) {
                $registration->members()->create([
                    'name' => $member['name'],
                    'gender' => $member['gender'],
                    'member_role' => $member['member_role'],
                ]);
            }

            $created++;
        }

        $this->deleteUpload($event, $token);

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * @return Collection<int, list<string>>
     */
    protected function readRows(Event $event, string $token): Collection
    {
        $fullPath = Storage::disk(self::DISK)->path($this->pathFor($event, $token));
        $handle = fopen($fullPath, 'rb');
        abort_if($handle === false, 404);

        // Strip a UTF-8 BOM if present (common in Excel-exported CSVs) by
        // finding the real start-of-content position once, then always
        // seeking back to THAT position — never a bare rewind(), which
        // would undo the BOM skip and feed it into the first header cell.
        $bom = fread($handle, 3);
        $contentStart = $bom === "\xEF\xBB\xBF" ? 3 : 0;
        fseek($handle, $contentStart);

        // Keputusan H: auto-detect comma vs semicolon delimiter by peeking
        // at the header line, then rewinding to parse it for real.
        $firstLine = fgets($handle);
        fseek($handle, $contentStart);
        $delimiter = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';

        $rows = collect();
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows->push($row);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, string|null>  $mapping
     * @return array<string, int|null>
     */
    protected function columnIndexes(array $headers, array $mapping): array
    {
        $indexes = [];
        foreach (self::EXPECTED_FIELDS as $field) {
            $header = $mapping[$field] ?? null;
            $position = $header !== null ? array_search($header, $headers, true) : false;
            $indexes[$field] = $position === false ? null : $position;
        }

        return $indexes;
    }

    /**
     * @param  list<string>  $row
     * @param  array<string, int|null>  $indexes
     * @return array{team_name: string, member_name: string, gender: string, member_role: string}
     */
    protected function extractRow(array $row, array $indexes): array
    {
        $get = fn (?int $index) => $index !== null ? trim((string) ($row[$index] ?? '')) : '';

        return [
            'team_name' => $get($indexes['team_name']),
            'member_name' => $get($indexes['member_name']),
            'gender' => $get($indexes['gender']),
            'member_role' => $get($indexes['member_role']),
        ];
    }
}
