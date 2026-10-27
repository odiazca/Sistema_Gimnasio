<?php
class Database
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    public static function all(string $sql, array $p = []): array
    {
        $st = self::conn()->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function row(string $sql, array $p = []): ?array
    {
        $st = self::conn()->prepare($sql);
        $st->execute($p);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function val(string $sql, array $p = [])
    {
        $st = self::conn()->prepare($sql);
        $st->execute($p);
        $v = $st->fetchColumn();
        return $v === false ? 0 : $v;
    }

    public static function exec(string $sql, array $p = []): int
    {
        $st = self::conn()->prepare($sql);
        $st->execute($p);
        return $st->rowCount();
    }
}
