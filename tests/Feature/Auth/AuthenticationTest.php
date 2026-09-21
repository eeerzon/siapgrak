<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Ersa',
            'email' => 'ersa@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        // A brand-new user has zero organizations yet — sent to the
        // Organizations index (not the Dashboard) so the empty state can
        // prompt "create your first organization".
        $response->assertRedirect(route('dashboard.organizations.index'));
        $this->assertDatabaseHas('users', ['email' => 'ersa@example.com']);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard.index'));
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Regression: home route unconditionally redirected to /login, and the
     * `guest` middleware's default fallback also points to route('home') for
     * already-authenticated users — visiting / or /login while logged in
     * looped forever (ERR_TOO_MANY_REDIRECTS) until the home route branched
     * on auth()->check(). See routes/web.php comment on the `home` route.
     */
    public function test_authenticated_user_visiting_home_is_not_caught_in_a_redirect_loop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard.index'));
    }

    public function test_authenticated_user_visiting_login_is_not_caught_in_a_redirect_loop(): void
    {
        $user = User::factory()->create();

        // guest middleware sends this to route('home') (a single 302 hop, per
        // Laravel's RedirectIfAuthenticated::defaultRedirectUri()) — the loop
        // this regression guards against only appears when that hop is
        // followed and home() bounces back to /login. Assert both hops.
        $response = $this->actingAs($user)->get(route('login'));
        $response->assertRedirect(route('home'));

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertRedirect(route('dashboard.index'));
    }

    public function test_guest_visiting_home_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
