<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logos/icono.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logos/icono.png') }}">

    <title>Validador</title>

    @vite('resources/css/app.css')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .landing-split {
            display: flex;
            min-height: 100vh;
        }

        /* ── Left Panel ── */
        .landing-left {
            flex: 1 1 50%;
            position: relative;
            background-image: url('{{ asset('logos/fondo_azul_inicio.png') }}');
            /* background-image: url('{{ asset('logos/fondo_azul_acreditar.png') }}'); */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #ffffff;
            padding: 2.5rem 3rem;
            overflow: hidden;
        }

        .landing-left::after {
            content: '';
            position: absolute;
            inset: 0;
            
            z-index: 0;
        }

        .landing-left>* {
            position: relative;
            z-index: 1;
        }

        .left-nav {
            position: absolute;
            top: calc(2.5rem + 20px);
            left: 3rem;
            right: 3rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem 2rem;
            justify-content: center;
        }

        .left-nav a {
            color: #ffffff;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: color 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-family: 'Roboto', sans-serif;
        }

        .left-nav a:hover {
            color: #00ffff;
        }

        .left-body {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
            max-width: 100%;
            align-self: center;
        }

        .left-logo {
            width: 100%;
            height: 400px;
            object-fit: cover;
            object-position: calc(50% - 1cm) center;
            margin-bottom: 1.5rem;
        }

        .left-title {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.15;
            margin-bottom: 0.75rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
        }

        .left-subtitle {
            font-size: 2.3rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.5;
            padding-left: 8rem;
            transform: translateX(60px);
        }

        .left-footer {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.55);
            align-self: flex-start;
        }

        /* ── Right Panel ── */
        .landing-right {
            flex: 1 1 50%;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 2.5rem 3rem;
        }

        .right-logos {
            max-height: 80px;
            width: auto;
            margin-bottom: 2.5rem;
        }

        .right-card {
            width: 100%;
            max-width: 420px;
            margin-top: auto;
            margin-bottom: auto;
            font-family: 'Roboto', sans-serif;
        }

        .right-card .welcome-header {
            text-align: left;
            margin-bottom: 1.75rem;
        }

        .right-card .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: black;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Roboto', sans-serif;
        }

        .right-card .welcome-title .blue-square {
            display: inline-block;
            width: 18px;
            height: 18px;
            background-color: #003366;
            margin-right: 0.5rem;
            flex-shrink: 0;
            margin-left: -30px;
        }

        .right-card .welcome-text {
            font-size: 0.9rem;
            color: #555555;
            font-family: 'Roboto', sans-serif;
        }

        .right-card h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #003366;
            margin-bottom: 1.75rem;
            text-align: center;
        }

        .right-card .form-group {
            margin-bottom: 1.25rem;
        }

        .right-card label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: black;
            margin-bottom: 0.35rem;
        }

        .right-card input[type="email"],
        .right-card input[type="password"] {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 1px solid rgba(0, 51, 102, 0.2);
            border-radius: 0.5rem;
            font-size: 0.95rem;
            color: #000000;
            background: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            outline: none;
        }

        .right-card input[type="email"]:focus,
        .right-card input[type="password"]:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }

        .right-card .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            color: #333333;
        }

        .right-card .remember-row input[type="checkbox"] {
            accent-color: #003366;
            width: 1rem;
            height: 1rem;
            cursor: pointer;
        }

        .right-card .btn-submit {
            width: 100%;
            padding: 0.8rem;
            background-color: #003366;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .right-card .btn-submit:hover {
            background-color: rgba(0, 51, 102, 0.88);
        }

        .right-card .extra-links {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.85rem;
        }

        .right-card .extra-links a {
            color: #003366;
            text-decoration: none;
            font-weight: 500;
        }

        .right-card .extra-links a:hover {
            text-decoration: underline;
        }

        .right-card .register-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #555555;
        }

        .right-card .register-link a {
            color: #003366;
            font-weight: 600;
            text-decoration: none;
        }

        .right-card .register-link a:hover {
            text-decoration: underline;
        }

        /* Auth state block */
        .right-auth-card {
            text-align: center;
            width: 100%;
            max-width: 420px;
            margin-top: auto;
            margin-bottom: auto;
        }

        .right-auth-card h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #003366;
            margin-bottom: 1.25rem;
        }

        .right-auth-card .btn-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem;
            background-color: #003366;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .right-auth-card .btn-dashboard:hover {
            background-color: rgba(0, 51, 102, 0.88);
        }

        .right-auth-card .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            margin-top: 1rem;
            background-color: transparent;
            color: #dc2626;
            border: 1px solid #dc2626;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .right-auth-card .btn-logout:hover {
            background-color: rgba(220, 38, 38, 0.06);
        }

        /* Validation errors */
        .login-errors {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            color: #dc2626;
            font-size: 0.85rem;
            list-style: none;
        }

        .login-errors li {
            margin-bottom: 0.25rem;
        }

        .login-errors li:last-child {
            margin-bottom: 0;
        }

        /* ── Modal (Events) ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 100;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: #ffffff;
            border-radius: 0.75rem;
            max-width: 900px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .modal-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-box thead {
            background-color: #003366;
        }

        .modal-box thead th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .modal-box tbody tr {
            border-bottom: 1px solid rgba(0, 51, 102, 0.1);
        }

        .modal-box tbody tr:hover {
            background-color: rgba(0, 51, 102, 0.04);
        }

        .modal-box tbody td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #000000;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0, 51, 102, 0.1);
        }

        .modal-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #003366;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #000000;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            transition: background-color 0.15s ease;
        }

        .modal-close:hover {
            background-color: rgba(0, 51, 102, 0.08);
        }

        .no-events {
            padding: 2rem;
            text-align: center;
            color: rgba(0, 0, 0, 0.5);
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .landing-split {
                flex-direction: column;
            }

            .landing-left {
                flex: 1 1 auto;
                min-height: 100vh;
                padding: 1.5rem;
            }

            .landing-right {
                flex: 1 1 auto;
                min-height: 100vh;
                padding: 1.5rem;
            }

            .left-nav {
                gap: 0.75rem 1.25rem;
                justify-content: center;
                font-size: 0.85rem;
            }

            .left-body {
                align-items: center;
                text-align: center;
                max-width: 100%;
            }

            .left-title {
                font-size: 1.75rem;
                text-align: center;
            }

            .left-logo {
                object-position: center;
            }

            .left-subtitle {
                text-align: center;
                padding-left: 0;
                transform: none;
            }

            .left-footer {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="landing-split">
        {{-- ──────────────── LEFT PANEL ──────────────── --}}
        <div class="landing-left">
            <nav class="left-nav">
                <a href="https://www.fio.unam.edu.ar/" target="_blank" rel="noopener noreferrer">
                    Facultad de Ingenieria
                </a>
                <a href="https://pps.fio.unam.edu.ar/login" target="_blank" rel="noopener noreferrer">
                    Sistema PPS
                </a>
                <a href="#" onclick="openEventosModal(event)">
                    Eventos Activos
                </a>
                <a href="#">
                    Tutorial
                </a>
            </nav>

            <div class="left-body">
                <img src="{{ asset('logos/logo_acreditar.png') }}" alt="Acreditar" class="left-logo">
                <p class="left-subtitle">
                    Gestión de Eventos y Certificados
                </p>
            </div>

        </div>

        {{-- ──────────────── RIGHT PANEL ──────────────── --}}
        <div class="landing-right">
            <img src="{{ asset('logos/logos_azules_fi_unam.png') }}" alt="Facultad de Ingenieria - UNaM" class="right-logos">

            @auth
                @php
                    $panelRouteName = auth()->user()?->dashboardRouteName();
                @endphp
                <div class="right-auth-card">
                    <h2><i class="fa-solid fa-circle-check" style="color: #22c55e; margin-right: 0.35rem;"></i> Sesion iniciada</h2>
                    <p style="color: #555; font-size: 0.95rem; margin-bottom: 1.5rem;">
                        Bienvenido, <strong>{{ auth()->user()->name }}</strong>
                    </p>
                    @if ($panelRouteName)
                        <a href="{{ route($panelRouteName) }}" class="btn-dashboard">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Ir al Panel
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" style="margin-top: 1rem;">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesion
                        </button>
                    </form>
                </div>
            @else
                <div class="right-card">
                    <x-validation-errors class="login-errors" />

                    @session('status')
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; color: #15803d; font-size: 0.875rem;">
                            {{ $value }}
                        </div>
                    @endsession

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="welcome-header">
                            <h1 class="welcome-title">
                                <span class="blue-square"></span>
                                Bienvenido/a
                            </h1>
                            <p class="welcome-text">Ingresá tus credenciales para acceder al sistema.</p>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="tu@email.com">
                        </div>

                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        </div>

                        <div class="remember-row">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input id="remember_me" type="checkbox" name="remember">
                                <label for="remember_me" style="margin-bottom: 0;">Recordarme</label>
                            </div>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" style="color: #003366; text-decoration: none; font-weight: 500; font-size: 0.85rem;">¿Olvidaste tu contraseña?</a>
                            @endif
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fa-solid fa-sign-in-alt" style="margin-right: 0.4rem;"></i> Ingresar
                        </button>
                    </form>

                    @if (Route::has('register'))
                        <div class="register-link">
                            No tenes cuenta? <a href="{{ route('register') }}">Registrate aca</a>
                        </div>
                    @endif
                </div>
            @endauth
        </div>
    </div>

    {{-- ──────────────── MODAL: Eventos Activos ──────────────── --}}
    <div id="eventosModal" class="modal-overlay" onclick="if(event.target===this)closeEventosModal()">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-calendar-days mr-2"></i>Eventos Activos</h3>
                <button class="modal-close" onclick="closeEventosModal()" aria-label="Cerrar">&times;</button>
            </div>
            <div style="padding: 0;">
                @if ($eventosEnCurso->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo de Evento</th>
                                <th>Fecha de Inicio</th>
                                <th>Inscriptos</th>
                                <th>Inscripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($eventosEnCurso as $evento)
                                <tr>
                                    <td>{{ $evento->nombre }}</td>
                                    <td>{{ $evento->tipoEvento->nombre }}</td>
                                    <td>{{ $evento->fecha_inicio_formatted }}</td>
                                    <td>{{ $evento->inscriptos()->count() }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('inscripcion.evento', ['tipoEvento' => $evento->tipoEvento->nombre, 'eventoId' => $evento->evento_id]) }}"
                                           class="inline-flex items-center justify-center rounded-full bg-blue-600 p-2 text-white transition hover:bg-blue-700"
                                           title="Inscribirse al Evento"
                                           aria-label="Inscribirse al Evento">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-events">
                        <i class="fa-solid fa-calendar-xmark" style="font-size: 2rem; color: rgba(0,0,0,0.2); margin-bottom: 0.5rem; display: block;"></i>
                        No hay eventos activos en este momento.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function openEventosModal(e) {
            e.preventDefault();
            document.getElementById('eventosModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEventosModal() {
            document.getElementById('eventosModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEventosModal();
            }
        });
    </script>
</body>

</html>
