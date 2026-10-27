<?php
class Usuario
{
    public static function findByLogin(string $login): ?array
    {
        return Database::row(
            'SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE (u.usuario = ? OR u.email = ?) AND u.estado = 1 LIMIT 1',
            [$login, $login]
        );
    }

    public static function touchLogin(int $id): void
    {
        Database::exec('UPDATE usuarios SET ultimo_login = ? WHERE id = ?', [date('Y-m-d H:i:s'), $id]);
    }
}
