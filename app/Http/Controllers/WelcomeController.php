<?php

namespace App\Http\Controllers;

use App\Models\Evento;

class WelcomeController extends Controller
{
    public function index()
    {
        $eventosEnCurso = Evento::whereRaw('LOWER(estado) = ?', ['en curso'])->get();

        return view('welcome', ['eventosEnCurso' => $eventosEnCurso]);
    }
}
