<?php

namespace Tests\Feature\Registration;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_outsider_organizer_cannot_access_another_organizations_import_token(): void
    {
        Storage::fake('local');

        $ownerA = User::factory()->create();
        $organizationA = Organization::factory()->create();
        $organizationA->users()->attach($ownerA, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $eventA = Event::factory()->create(['organization_id' => $organizationA->id]);
        $categoryA = EventCategory::factory()->create(['event_id' => $eventA->id]);

        $upload = $this->actingAs($ownerA)->post(route('dashboard.events.registrations.import.store', $eventA), [
            'event_category_id' => $categoryA->id,
            'file' => UploadedFile::fake()->createWithContent('peserta.csv', "Tim,Nama\nTeam A,Andi\n"),
        ]);
        preg_match('#/import/([^/]+)/map#', (string) $upload->headers->get('Location'), $matches);
        $token = $matches[1];

        $ownerB = User::factory()->create();
        $organizationB = Organization::factory()->create();
        $organizationB->users()->attach($ownerB, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $this->actingAs($ownerB)
            ->get(route('dashboard.events.registrations.import.map', [$eventA, $token]))
            ->assertForbidden();

        $this->actingAs($ownerB)->post(route('dashboard.events.registrations.import.preview', [$eventA, $token]), [
            'event_category_id' => $categoryA->id,
            'team_name' => 'Tim',
            'member_name' => 'Nama',
        ])->assertForbidden();

        $this->actingAs($ownerB)->post(route('dashboard.events.registrations.import.commit', [$eventA, $token]), [
            'event_category_id' => $categoryA->id,
            'team_name' => 'Tim',
            'member_name' => 'Nama',
        ])->assertForbidden();
    }
}
