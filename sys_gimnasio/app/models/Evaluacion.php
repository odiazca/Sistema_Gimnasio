<?php
class Evaluacion
{
    public static function imc(?float $peso, ?float $talla): ?float
    {
        return ($peso && $talla && $talla > 0.5) ? round($peso / ($talla * $talla), 1) : null;
    }

    /** [etiqueta, clase badge] según OMS */
    public static function categoria(?float $imc): array
    {
        if ($imc === null) return ['—', 'secondary'];
        if ($imc < 18.5) return ['Bajo peso', 'info'];
        if ($imc < 25) return ['Normal', 'success'];
        if ($imc < 30) return ['Sobrepeso', 'warning'];
        return ['Obesidad', 'danger'];
    }

    public static function deSocio(int $socioId): array
    {
        return Database::all('SELECT * FROM evaluaciones WHERE socio_id = ? ORDER BY fecha, id', [$socioId]);
    }
}
