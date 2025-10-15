<table>
    <thead>
        <tr>
            <th colspan="5">Listado de inscriptos - {{ $eventoNombre }}</th>
        </tr>
        <tr>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>DNI</th>
            <th>Email</th>
            <th>Teléfono</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($inscriptos as $inscripto)
            <tr>
                <td>{{ $inscripto->participante->nombre }}</td>
                <td>{{ $inscripto->participante->apellido }}</td>
                <td>{{ $inscripto->participante->dni }}</td>
                <td>{{ $inscripto->participante->mail }}</td>
                <td>{{ $inscripto->participante->telefono }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
