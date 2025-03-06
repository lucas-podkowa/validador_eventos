<div
    style="
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%; 
    height: 100%; 
    background-image: url('{{ $path_fondo }}'); 
    background-position: center center;
    background-size: contain; 
    background-repeat: no-repeat;
    box-sizing: border-box;
    ">
    <div
        style="
            font-family: Roboto, sans-serif;
            font-weight: 400;
            font-style: normal;
            font-size:1.4rem;
            padding: 10px;
            overflow: hidden;
            box-sizing: border-box;">

        @if ($evento && $participante)
            <h2>{{ $evento->tipoEvento->nombre }}: {{ $evento->nombre }}</h2>
            <p><strong>Participante:</strong> {{ $participante->nombre }}
                {{ $participante->apellido }}</p>
            <p><strong>Email:</strong> {{ $participante->email }}</p>
            <p><strong>DNI:</strong> {{ $participante->dni }}</p>
        @endif
    </div>
</div>
