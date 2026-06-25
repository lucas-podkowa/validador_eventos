<?php

namespace App\Http\Controllers;

use App\Models\Evento;

class WelcomeController extends Controller
{
    public function index()
    {
        $eventosEnCurso = Evento::where('estado', 'en curso')->get();

        return view('welcome', ['eventosEnCurso' => $eventosEnCurso]);
    }
}
