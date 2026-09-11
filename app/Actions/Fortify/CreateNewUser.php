<?php

namespace App\Actions\Fortify;

use App\Actions\VincularParticipante;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'dni' => ['required', 'digits_between:6,12', 'unique:users,dni'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'dni' => $input['dni'],
            'password' => Hash::make($input['password']),
        ]);

        // Asignar rol 'Invitado' al nuevo usuario
        $user->assignRole('Invitado');

        // Vincular con su participante existente (si DNI y correo coinciden)
        app(VincularParticipante::class)->vincular($user);

        return $user;
    }
}
