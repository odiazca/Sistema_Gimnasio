<?php
class Rutina
{
    public const GRUPOS = ['Pecho', 'Espalda', 'Hombros', 'Bíceps', 'Tríceps', 'Piernas', 'Glúteos', 'Abdomen', 'Cardio', 'Cuerpo completo'];
    public const OBJETIVOS = ['Hipertrofia', 'Pérdida de grasa', 'Fuerza', 'Resistencia', 'Tonificación', 'Salud general'];
    public const NIVELES = ['Principiante', 'Intermedio', 'Avanzado'];

    public static function ensure(): void
    {
        static $done = false;
        if ($done) return;
        $pdo = Database::conn();
        $nuevo = !$pdo->query("SHOW TABLES LIKE 'ejercicios'")->fetchColumn();
        $pdo->exec("CREATE TABLE IF NOT EXISTS ejercicios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL, grupo VARCHAR(40) NOT NULL,
            equipo VARCHAR(60) NULL, video_url VARCHAR(255) NULL, estado TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS rutinas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL, objetivo VARCHAR(40) NULL, nivel VARCHAR(20) NULL,
            descripcion VARCHAR(255) NULL, estado TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS rutina_ejercicios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, rutina_id INT UNSIGNED NOT NULL, ejercicio_id INT UNSIGNED NOT NULL,
            dia TINYINT UNSIGNED NOT NULL DEFAULT 1, orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            series TINYINT UNSIGNED NOT NULL DEFAULT 3, repeticiones VARCHAR(20) NOT NULL DEFAULT '12', descanso_seg SMALLINT UNSIGNED NULL, notas VARCHAR(150) NULL,
            INDEX idx_rut (rutina_id, dia, orden),
            FOREIGN KEY (rutina_id) REFERENCES rutinas(id) ON DELETE CASCADE,
            FOREIGN KEY (ejercicio_id) REFERENCES ejercicios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS rutina_socios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, socio_id INT UNSIGNED NOT NULL, rutina_id INT UNSIGNED NOT NULL,
            fecha_inicio DATE NOT NULL, fecha_fin DATE NULL, estado ENUM('activa','finalizada') NOT NULL DEFAULT 'activa',
            INDEX idx_socio (socio_id, estado),
            FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
            FOREIGN KEY (rutina_id) REFERENCES rutinas(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
        if ($nuevo) self::sembrar();
    }

    private static function sembrar(): void
    {
        $cat = [
            ['Press de banca plano','Pecho','Barra'],['Press inclinado con mancuernas','Pecho','Mancuernas'],['Aperturas en polea','Pecho','Polea'],['Flexiones','Pecho','Peso corporal'],
            ['Dominadas asistidas','Espalda','Máquina'],['Remo con barra','Espalda','Barra'],['Jalón al pecho','Espalda','Polea'],['Remo sentado en polea','Espalda','Polea'],
            ['Press militar','Hombros','Barra'],['Elevaciones laterales','Hombros','Mancuernas'],['Curl con barra','Bíceps','Barra'],['Curl martillo','Bíceps','Mancuernas'],
            ['Extensión de tríceps en polea','Tríceps','Polea'],['Fondos en banco','Tríceps','Banco'],['Sentadilla libre','Piernas','Barra'],['Prensa de piernas','Piernas','Máquina'],
            ['Peso muerto rumano','Piernas','Barra'],['Zancadas','Piernas','Mancuernas'],['Hip thrust','Glúteos','Barra'],['Patada de glúteo en polea','Glúteos','Polea'],
            ['Plancha abdominal','Abdomen','Peso corporal'],['Crunch en polea','Abdomen','Polea'],['Trote en cinta','Cardio','Cinta'],['Bicicleta estática','Cardio','Bicicleta'],
            ['Burpees','Cuerpo completo','Peso corporal'],['Kettlebell swing','Cuerpo completo','Kettlebell'],
        ];
        $st = Database::conn()->prepare('INSERT INTO ejercicios (nombre,grupo,equipo) VALUES (?,?,?)');
        foreach ($cat as $c) $st->execute($c);
        $id = fn($n) => (int)Database::val('SELECT id FROM ejercicios WHERE nombre = ?', [$n]);
        $plan = [
            ['Full Body Principiante', 'Salud general', 'Principiante', 'Rutina de 3 días para empezar con todo el cuerpo.', [
                [1,'Sentadilla libre',3,'12',60],[1,'Press de banca plano',3,'12',60],[1,'Remo sentado en polea',3,'12',60],[1,'Plancha abdominal',3,'30 s',45],
                [2,'Prensa de piernas',3,'12',60],[2,'Press militar',3,'10',60],[2,'Jalón al pecho',3,'12',60],[2,'Curl martillo',2,'12',45],
                [3,'Peso muerto rumano',3,'10',75],[3,'Press inclinado con mancuernas',3,'10',60],[3,'Extensión de tríceps en polea',2,'12',45],[3,'Bicicleta estática',1,'15 min',0]]],
            ['Hipertrofia Push / Pull / Legs', 'Hipertrofia', 'Intermedio', 'División empuje, tirón y pierna para ganar masa muscular.', [
                [1,'Press de banca plano',4,'8-10',90],[1,'Press militar',3,'10',75],[1,'Elevaciones laterales',3,'15',45],[1,'Extensión de tríceps en polea',3,'12',45],
                [2,'Remo con barra',4,'8-10',90],[2,'Jalón al pecho',3,'10-12',75],[2,'Curl con barra',3,'10',60],[2,'Curl martillo',3,'12',45],
                [3,'Sentadilla libre',4,'8',120],[3,'Peso muerto rumano',3,'10',90],[3,'Zancadas',3,'12',60],[3,'Hip thrust',3,'12',60]]],
            ['Pérdida de grasa HIIT + Fuerza', 'Pérdida de grasa', 'Intermedio', 'Circuitos de fuerza y cardio para maximizar el gasto calórico.', [
                [1,'Burpees',4,'12',30],[1,'Kettlebell swing',4,'15',30],[1,'Zancadas',3,'12',45],[1,'Plancha abdominal',3,'40 s',30],
                [2,'Sentadilla libre',4,'12',60],[2,'Flexiones',3,'15',45],[2,'Remo sentado en polea',3,'12',45],[2,'Trote en cinta',1,'20 min',0]]],
        ];
        foreach ($plan as $p) {
            Database::exec('INSERT INTO rutinas (nombre,objetivo,nivel,descripcion) VALUES (?,?,?,?)', [$p[0], $p[1], $p[2], $p[3]]);
            $rid = (int)Database::conn()->lastInsertId(); $o = 0;
            foreach ($p[4] as $e) Database::exec('INSERT INTO rutina_ejercicios (rutina_id,ejercicio_id,dia,orden,series,repeticiones,descanso_seg) VALUES (?,?,?,?,?,?,?)', [$rid, $id($e[1]), $e[0], $o++, $e[2], $e[3], $e[4] ?: null]);
        }
    }

    public static function find(int $id): ?array { self::ensure(); return Database::row('SELECT * FROM rutinas WHERE id = ?', [$id]); }

    public static function detalle(int $id): array
    {
        $out = [];
        foreach (Database::all('SELECT re.*, e.nombre, e.grupo, e.equipo, e.video_url FROM rutina_ejercicios re JOIN ejercicios e ON e.id = re.ejercicio_id WHERE re.rutina_id = ? ORDER BY re.dia, re.orden, re.id', [$id]) as $r) $out[(int)$r['dia']][] = $r;
        return $out;
    }

    public static function texto(array $r, array $det): string
    {
        $t = "*" . $r['nombre'] . "*\n";
        foreach ($det as $dia => $ex) {
            $t .= "\n*Día $dia*\n";
            foreach ($ex as $e) $t .= '• ' . $e['nombre'] . ' — ' . $e['series'] . 'x' . $e['repeticiones'] . ($e['descanso_seg'] ? ' (desc. ' . $e['descanso_seg'] . 's)' : '') . "\n";
        }
        return $t;
    }

    public static function activaDe(int $socioId): ?array
    {
        self::ensure();
        return Database::row("SELECT rs.*, r.nombre FROM rutina_socios rs JOIN rutinas r ON r.id = rs.rutina_id WHERE rs.socio_id = ? AND rs.estado = 'activa' ORDER BY rs.id DESC LIMIT 1", [$socioId]);
    }
}
