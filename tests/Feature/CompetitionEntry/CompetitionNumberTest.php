<?php

namespace Tests\Feature\CompetitionEntry;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionNumberTest extends TestCase
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

    protected function entry(): CompetitionEntry
    {
        return CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);
    }

    public function test_owner_can_assign_a_manual_number(): void
    {
        $entry = $this->entry();

        $this->actingAs($this->owner)
            ->patch(route('dashboard.competition-entries.number', $entry), ['competition_number' => 'A01'])
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $this->assertSame('A01', $entry->fresh()->competition_number);
    }

    /**
     * competition_number isn't mass-assignable — assign it directly, same as
     * CompetitionNumberService::assignManual() does.
     */
    protected function withNumber(CompetitionEntry $entry, string $number): CompetitionEntry
    {
        $entry->competition_number = $number;
        $entry->save();

        return $entry;
    }

    public function test_duplicate_number_within_the_same_category_is_rejected(): void
    {
        $this->withNumber($this->entry(), 'A01');
        $entry2 = $this->entry();

        $this->actingAs($this->owner)
            ->patch(route('dashboard.competition-entries.number', $entry2), ['competition_number' => 'A01'])
            ->assertSessionHasErrors('competition_number');

        $this->assertNull($entry2->fresh()->competition_number);
    }

    public function test_same_number_is_allowed_in_a_different_category(): void
    {
        $this->withNumber($this->entry(), 'A01');
        $otherCategory = EventCategory::factory()->create(['event_id' => $this->event->id]);
        $entryElsewhere = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $otherCategory->id,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('dashboard.competition-entries.number', $entryElsewhere), ['competition_number' => 'A01'])
            ->assertSessionHasNoErrors();
    }

    public function test_sequential_generation_numbers_only_unnumbered_entries_in_order(): void
    {
        $already = $this->withNumber($this->entry(), 'A99');
        $first = $this->entry();
        $second = $this->entry();

        $this->actingAs($this->owner)->post(route('dashboard.events.competition.numbers.generate', $this->event), [
            'event_category_id' => $this->category->id,
            'prefix' => 'A',
            'start' => 1,
            'digits' => 2,
        ]);

        $this->assertSame('A99', $already->fresh()->competition_number); // untouched
        $this->assertSame('A01', $first->fresh()->competition_number);
        $this->assertSame('A02', $second->fresh()->competition_number);
    }

    public function test_member_role_cannot_generate_or_assign_numbers(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $entry = $this->entry();

        $this->actingAs($member)
            ->patch(route('dashboard.competition-entries.number', $entry), ['competition_number' => 'A01'])
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('dashboard.events.competition.numbers.generate', $this->event), [
                'event_category_id' => $this->category->id,
                'start' => 1,
                'digits' => 2,
            ])
            ->assertForbidden();
    }
}
