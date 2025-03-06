<div>
    <div class="card">
        <h3>{{ $evento->nombre }}</h3>
        <p><strong>Tipo de Evento: </strong>{{ $evento->tipoEvento->nombre }}</p>
        <p><strong>Participante: </strong>{{ $participante->nombre }} {{ $participante->apellido }}</p>
        <p><strong>DNI: </strong>{{ $participante->dni }}</p>

        <div class="qr-code">
            {!! $qrCode !!}
        </div>
    </div>
</div>
