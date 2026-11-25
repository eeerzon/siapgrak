<?php

namespace Tests\Feature\CompetitionJudging;

use App\Models\CompetitionCategory;
use App\Models\CompetitionJudge;
use App\Models\CompetitionListing;
use App\Models\CompetitionRound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ContentJudgingEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CompetitionListing $listing;

    private CompetitionCategory $category;

    private CompetitionRound $round;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_platform_admin' => true]);
        $this->listing = CompetitionListing::create(['title' => 'LKBB Uji', 'start_date' => '2030-01-01', 'status' => 'draft', 'id_user' => $this->admin->id_user]);
        $this->category = $this->listing->categories()->create(['name' => 'SMA']);
        $this->round = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Penyisihan']);
        $this->actingAs($this->admin);
    }

    private function addTeam(string $number, ?int $categoryId = null): TestResponse
    {
        return $this->post(route('dashboard.content.competitions.judging.participants.store', $this->listing), [
            'competition_category_id' => $categoryId ?? $this->category->id, 'name' => "Tim {$number}", 'competition_number' => $number,
        ]);
    }

    public function test_no_new_team_once_the_first_round_is_public(): void
    {
        $this->round->forceFill(['results_published_at' => now()])->save();

        $this->addTeam('01')->assertSessionHasErrors('participant');
        $this->assertSame(0, $this->listing->participants()->count());

        // Moving a team in from another category counts as a new team too.
        $other = $this->listing->categories()->create(['name' => 'SMP']);
        $this->addTeam('02', $other->id)->assertSessionHasNoErrors();
        $team = $this->listing->participants()->first();
        $this->put(route('dashboard.content.competitions.judging.participants.update', [$this->listing, $team]), [
            'competition_category_id' => $this->category->id, 'name' => $team->name, 'competition_number' => '02',
        ])->assertSessionHasErrors('participant');
        $this->assertSame($other->id, $team->fresh()->competition_category_id);
    }

    public function test_new_team_clears_later_round_lists_picked_from_the_old_ranking(): void
    {
        $this->addTeam('01');
        $final = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Final', 'display_order' => 2]);
        $final->participants()->attach($this->listing->participants()->first());

        $this->addTeam('02')->assertSessionHasNoErrors()->assertSessionHas('success', fn ($message) => str_contains($message, 'Peserta Final dikosongkan'));
        $this->assertSame(0, $final->participants()->count());
    }

    public function test_inactive_judge_cannot_be_assigned(): void
    {
        $inactive = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Cuti', 'status' => 'inactive']);
        CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Aktif', 'status' => 'active']);

        $this->post(route('dashboard.content.competitions.judging.judges.assign', [$this->listing, $this->round]), ['competition_judge_id' => $inactive->id])
            ->assertSessionHasErrors(['competition_judge_id' => 'Juri tidak ditemukan atau sedang nonaktif.']);
        $this->assertSame(0, $this->round->judges()->count());

        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']))
            ->assertSee('<option value="'.CompetitionJudge::where('name', 'Juri Aktif')->value('id').'">Juri Aktif</option>', false)
            ->assertDontSee('>Juri Cuti</option>', false);
    }

    public function test_criterion_weight_has_at_most_two_decimals(): void
    {
        $store = fn (string $weight) => $this->post(route('dashboard.content.competitions.judging.criteria.store', [$this->listing, $this->round]), [
            'name' => 'PBB', 'weight' => $weight, 'minimum_score' => 0, 'maximum_score' => 100,
        ]);

        $store('33.333')->assertSessionHasErrors(['weight' => 'Bobot maksimal 2 angka di belakang koma.']);
        $store('33.33')->assertSessionHasNoErrors();
    }

    public function test_judge_search_treats_percent_literally(): void
    {
        CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri 100% Teliti', 'status' => 'active']);
        CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Biasa', 'status' => 'active']);

        $this->get(route('dashboard.content.judges.index', ['q' => '%']))->assertSee('Juri 100% Teliti')->assertDontSee('Juri Biasa');
    }

    public function test_malformed_contact_persons_are_a_validation_error_not_a_crash(): void
    {
        $this->post(route('dashboard.content.competitions.store'), [
            'title' => 'LKBB', 'start_date' => '2030-01-10', 'is_managed' => '0', 'status' => 'draft', 'contact_persons' => ['abc'],
        ])->assertSessionHasErrors('contact_persons.0');
    }
}
