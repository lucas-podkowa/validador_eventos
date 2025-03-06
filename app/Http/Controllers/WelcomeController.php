<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function index()
    {
        $eventosEnCurso = Evento::where('estado', 'en curso')->get();
        return view('welcome', ['eventosEnCurso' => $eventosEnCurso]);
    }
}
