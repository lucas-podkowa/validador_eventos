<?php

namespace App\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $routeName = $request->user()?->dashboardRouteName();

        if ($routeName) {
            return redirect()->route($routeName);
        }

        return redirect()->route('welcome');
    }
}
