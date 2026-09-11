<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        // El login público está deshabilitado: /login redirige al inicio (el formulario vive en el welcome).
        $response = $this->get('/login');

        $response->assertRedirect(route('welcome'));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        Role::create(['name' => 'Administrador']);

        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('eventos', absolute: false));
    }

    public function test_guest_users_are_redirected_to_public_home_after_login(): void
    {
        Role::create(['name' => 'Invitado']);

        $user = User::factory()->create();
        $user->assignRole('Invitado');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('mis_certificados', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
