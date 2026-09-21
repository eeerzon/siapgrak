<?php

namespace Tests\Feature\ScoreSheet;

use App\Enums\Status;
use App\Models\Judge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgeSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_code_logs_the_judge_in(): void
    {
        $judge = Judge::factory()->create();

        $response = $this->post(route('judge.access.store'), ['code' => $judge->access_code]);

        $response->assertRedirect(route('judge.dashboard'));
        $this->assertSame($judge->id, session('judge_id'));
    }

    public function test_code_is_case_insensitive_and_trims_whitespace(): void
    {
        $judge = Judge::factory()->create();

        $this->post(route('judge.access.store'), ['code' => ' '.strtolower($judge->access_code).' '])
            ->assertRedirect(route('judge.dashboard'));
    }

    public function test_invalid_code_is_rejected(): void
    {
        $this->post(route('judge.access.store'), ['code' => 'NOTREAL1'])
            ->assertSessionHasErrors('code');

        $this->assertNull(session('judge_id'));
    }

    public function test_inactive_judge_is_rejected_even_with_the_right_code(): void
    {
        $judge = Judge::factory()->create(['status' => Status::Inactive]);

        $this->post(route('judge.access.store'), ['code' => $judge->access_code])
            ->assertSessionHasErrors('code');

        $this->assertNull(session('judge_id'));
    }

    public function test_judge_area_redirects_to_access_page_without_a_session(): void
    {
        $this->get(route('judge.dashboard'))
            ->assertRedirect(route('judge.access.create'));
    }

    public function test_logout_clears_the_session(): void
    {
        $judge = Judge::factory()->create();
        $this->withSession(['judge_id' => $judge->id]);

        $this->post(route('judge.logout'))
            ->assertRedirect(route('judge.access.create'));

        $this->get(route('judge.dashboard'))
            ->assertRedirect(route('judge.access.create'));
    }

    public function test_a_stale_session_pointing_to_a_deleted_judge_is_rejected(): void
    {
        $this->withSession(['judge_id' => 999999]);

        $this->get(route('judge.dashboard'))
            ->assertRedirect(route('judge.access.create'));
    }
}
