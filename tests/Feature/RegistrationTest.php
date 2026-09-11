<?php

namespace Tests\Feature;

use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SpatieRole::create(['name' => 'Invitado', 'guard_name' => 'web']);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_register_with_dni(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'dni' => '30111222',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('30111222', (string) $user->dni);
        $this->assertTrue($user->hasRole('Invitado'));
    }

    public function test_registration_requires_dni(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertSessionHasErrors('dni');
        $this->assertGuest();
    }

    public function test_registration_links_participant_when_dni_and_email_match(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
        ]);

        $this->post('/register', [
            'name' => 'Pepito Perez',
            'email' => 'pepito@example.com',
            'dni' => '30111222',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $user = User::where('email', 'pepito@example.com')->firstOrFail();

        $this->assertSame($user->id, $participante->fresh()->user_id);
    }

    public function test_registration_does_not_link_participant_when_email_differs(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'otro@example.com',
            'telefono' => '3764000000',
        ]);

        $this->post('/register', [
            'name' => 'Pepito Perez',
            'email' => 'pepito@example.com',
            'dni' => '30111222',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertNull($participante->fresh()->user_id);
    }
}
