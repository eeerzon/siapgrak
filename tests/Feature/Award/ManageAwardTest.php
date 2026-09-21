<?php

namespace Tests\Feature\Award;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Award;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageAwardTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
    }

    public function test_owner_can_create_an_award(): void
    {
        $this->actingAs($this->owner)
            ->post(route('dashboard.events.awards.store', $this->event), [
                'name' => 'Juara 1',
                'award_type' => 'ranking',
            ])
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $this->assertDatabaseHas('awards', ['event_id' => $this->event->id, 'name' => 'Juara 1', 'source' => 'manual']);
    }

    public function test_award_can_be_linked_to_a_competition_entry(): void
    {
        $entry = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);

        $this->actingAs($this->owner)->post(route('dashboard.events.awards.store', $this->event), [
            'name' => 'Juara 1',
            'award_type' => 'ranking',
            'event_category_id' => $this->category->id,
            'competition_entry_id' => $entry->id,
        ]);

        $this->assertDatabaseHas('awards', [
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'competition_entry_id' => $entry->id,
        ]);
    }

    public function test_competition_entry_from_a_different_event_is_rejected(): void
    {
        $foreignEntry = CompetitionEntry::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('dashboard.events.awards.store', $this->event), [
                'name' => 'Juara 1',
                'award_type' => 'ranking',
                'competition_entry_id' => $foreignEntry->id,
            ])
            ->assertSessionHasErrors('competition_entry_id');
    }

    public function test_owner_can_update_an_award(): void
    {
        $award = Award::factory()->create(['event_id' => $this->event->id, 'name' => 'Old Name']);

        $this->actingAs($this->owner)->put(route('dashboard.awards.update', $award), [
            'name' => 'New Name',
            'award_type' => 'special',
        ]);

        $this->assertSame('New Name', $award->fresh()->name);
    }

    public function test_owner_can_delete_an_award(): void
    {
        $award = Award::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->owner)->delete(route('dashboard.awards.destroy', $award));

        $this->assertDatabaseMissing('awards', ['id' => $award->id]);
    }

    public function test_deleting_a_linked_entry_does_not_delete_the_award(): void
    {
        $entry = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);
        $award = Award::factory()->create([
            'event_id' => $this->event->id,
            'competition_entry_id' => $entry->id,
        ]);

        $entry->delete();

        $award->refresh();
        $this->assertNull($award->competition_entry_id);
        $this->assertDatabaseHas('awards', ['id' => $award->id]);
    }

    public function test_member_role_cannot_manage_awards(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $this->actingAs($member)
            ->post(route('dashboard.events.awards.store', $this->event), ['name' => 'Nope', 'award_type' => 'ranking'])
            ->assertForbidden();
    }
}
