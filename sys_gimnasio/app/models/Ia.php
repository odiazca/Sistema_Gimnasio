<?php
/**
 * Asistente de reportes y Chat con IA · FitCore Gym
 * La IA NUNCA toca las tablas reales: solo ve vistas de lectura (v_ia_*). Su SQL se valida, se ejecuta en una
 * transacción de solo lectura, con límite de tiempo y de filas.
 */
require_once ROOT_PATH . '/app/models/Caja.php';
require_once ROOT_PATH . '/app/models/Crm.php';

class Ia
{
    public const MAX_FILAS = 500;
    public const VERSION_VISTAS = '3';
    public const PRESETS = [
        'gemini'     => ['nombre' => 'Google Gemini (gratis)', 'url' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'modelo' => 'gemini-3.5-flash-lite', 'clave' => true,  'ayuda' => 'Clave gratis en aistudio.google.com/apikey'],
        'groq'       => ['nombre' => 'Groq (gratis)', 'url' => 'https://api.groq.com/openai/v1', 'modelo' => 'llama-3.3-70b-versatile', 'clave' => true, 'ayuda' => 'Clave gratis en console.groq.com/keys'],
        'openrouter' => ['nombre' => 'OpenRouter (modelos :free)', 'url' => 'https://openrouter.ai/api/v1', 'modelo' => 'meta-llama/llama-3.3-70b-instruct:free', 'clave' => true, 'ayuda' => 'Clave gratis en openrouter.ai/keys'],
        'ollama'     => ['nombre' => 'Ollama (local, 100 % gratis y privado)', 'url' => 'http://localhost:11434/v1', 'modelo' => 'qwen2.5-coder:7b', 'clave' => false, 'ayuda' => 'Instala Ollama en tu PC y ejecuta: ollama pull qwen2.5-coder:7b'],
        'otro'       => ['nombre' => 'Otro (compatible con OpenAI)', 'url' => '', 'modelo' => '', 'clave' => true, 'ayuda' => 'Cualquier servicio con API /chat/completions (OpenAI, LM Studio…)'],
    ];

    public const GUIA = "Dashboard: resumen, KPIs y gráficos. Socios: alta, ficha, foto, estado, búsqueda. Planes y membresías: planes y precios, inscribir o renovar. Pagos y cobranzas: registrar cobros (también parciales), recibos y deudas pendientes. Control de asistencia: check-in por DNI, código o QR, aforo en tiempo real, historial y carnet del socio. Clases y horarios: calendario semanal, reservas con control de cupo y asistencia por clase. Entrenadores. Rutinas: ejercicios, rutinas por días y asignación a socios (imprimir o enviar por WhatsApp). Evaluaciones físicas: peso, IMC, medidas y gráficos de progreso. Punto de venta: venta de productos con ticket. Inventario: productos, stock, kardex y alertas de stock bajo. Caja: abrir/cerrar turno y arqueo. Gastos. Anulaciones: registro de pagos y ventas anulados (solo administrador); los pagos y ventas se anulan con el botón de la lista, y las membresías se pueden congelar desde la ficha del socio. Reportes: financiero, socios, asistencia y ventas con exportación CSV/PDF. Notificaciones: recordatorios y cumpleaños por WhatsApp. CRM: embudo Kanban de prospectos, tareas y seguimientos, socios en riesgo de abandono con puntaje, ficha 360° del socio y campañas segmentadas por WhatsApp. Usuarios y roles: usuarios, permisos por rol y bitácora. Configuración: datos del gimnasio, aforo, logo y respaldo.";

    /* ---------- Vistas de solo lectura ---------- */
    public static function vistas(): array
    {
        $soc = "CONCAT(s.nombres,' ',s.apellidos)";
        return [
        'v_ia_socios' => ["Una fila por socio. estado: activo, inactivo o suspendido. estado_membresia: vigente (vence hoy o después), vencida o sin_membresia. vence = fecha de fin de su última membresía. dias_para_vencer es negativo si ya venció.",
          "SELECT x.*, CASE WHEN x.vence IS NULL THEN 'sin_membresia' WHEN x.vence >= CURDATE() THEN 'vigente' ELSE 'vencida' END AS estado_membresia, DATEDIFF(x.vence, CURDATE()) AS dias_para_vencer FROM (SELECT s.id AS socio_id, s.codigo, s.dni, s.nombres, s.apellidos, $soc AS socio, s.genero, s.fecha_nacimiento, TIMESTAMPDIFF(YEAR, s.fecha_nacimiento, CURDATE()) AS edad, s.telefono, s.email, s.objetivo, s.fecha_registro, s.estado, (SELECT p.nombre FROM membresias m JOIN planes p ON p.id = m.plan_id WHERE m.socio_id = s.id AND m.estado <> 'cancelada' ORDER BY m.fecha_fin DESC, m.id DESC LIMIT 1) AS plan_actual, (SELECT MAX(m.fecha_fin) FROM membresias m WHERE m.socio_id = s.id AND m.estado <> 'cancelada') AS vence, (SELECT COUNT(*) FROM asistencias a WHERE a.socio_id = s.id AND a.ingreso >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS visitas_30d, (SELECT MAX(a.ingreso) FROM asistencias a WHERE a.socio_id = s.id) AS ultima_visita FROM socios s) x"],
        'v_ia_membresias' => ["Una fila por membresía vendida. estado: activa, vencida, cancelada o congelada; las válidas NO son cancelada (congelada = pausada por viaje/lesión, no puede ingresar). vigente=1 si no está cancelada ni congelada y hoy está dentro del periodo. total = precio - descuento; saldo = lo que falta pagar.",
          "SELECT m.id AS membresia_id, m.socio_id, $soc AS socio, p.nombre AS plan, m.fecha_inicio, m.fecha_fin, m.precio, m.descuento, (m.precio - m.descuento) AS total, " . Membresia::PAGADO . " AS pagado, ((m.precio - m.descuento) - " . Membresia::PAGADO . ") AS saldo, m.estado, (m.estado NOT IN ('cancelada','congelada') AND m.fecha_inicio <= CURDATE() AND m.fecha_fin >= CURDATE()) AS vigente, m.created_at AS creada_en FROM membresias m JOIN socios s ON s.id = m.socio_id JOIN planes p ON p.id = m.plan_id"],
        'v_ia_planes' => ["Planes de membresía con su desempeño. activo=1 son los planes en venta.",
          "SELECT p.id AS plan_id, p.nombre AS plan, p.duracion_dias, p.precio, p.estado AS activo, (SELECT COUNT(*) FROM membresias m WHERE m.plan_id = p.id AND m.estado <> 'cancelada') AS membresias_vendidas, (SELECT COUNT(*) FROM membresias m WHERE m.plan_id = p.id AND m.estado NOT IN ('cancelada','congelada') AND m.fecha_inicio <= CURDATE() AND m.fecha_fin >= CURDATE()) AS membresias_vigentes FROM planes p"],
        'v_ia_pagos' => ["Pagos de socios (membresías). Una fila por cobro. metodo: efectivo, tarjeta, yape, plin o transferencia. Los ingresos por membresías son SUM(monto).",
          "SELECT pg.id AS pago_id, pg.fecha_pago, DATE(pg.fecha_pago) AS dia, $soc AS socio, pl.nombre AS plan, pg.concepto, pg.metodo, pg.monto, u.nombre AS cajero FROM pagos pg JOIN socios s ON s.id = pg.socio_id LEFT JOIN membresias m ON m.id = pg.membresia_id LEFT JOIN planes pl ON pl.id = m.plan_id LEFT JOIN usuarios u ON u.id = pg.usuario_id"],
        'v_ia_asistencias' => ["Ingresos al gimnasio (check-in). Una fila por visita. salida puede ser NULL si sigue dentro. dia_semana: 1=lunes ... 7=domingo. metodo: dni, qr, huella o manual.",
          "SELECT a.id AS asistencia_id, a.socio_id, $soc AS socio, a.ingreso, DATE(a.ingreso) AS dia, HOUR(a.ingreso) AS hora, WEEKDAY(a.ingreso) + 1 AS dia_semana, a.salida, TIMESTAMPDIFF(MINUTE, a.ingreso, a.salida) AS duracion_min, a.metodo FROM asistencias a JOIN socios s ON s.id = a.socio_id"],
        'v_ia_ventas' => ["Ventas de la tienda (productos), una fila por venta. cliente = 'Cliente libre' si no es socio. metodo: efectivo, tarjeta, yape, plin o transferencia.",
          "SELECT v.id AS venta_id, v.fecha, DATE(v.fecha) AS dia, COALESCE($soc, 'Cliente libre') AS cliente, v.total, v.metodo, u.nombre AS cajero FROM ventas v LEFT JOIN socios s ON s.id = v.socio_id LEFT JOIN usuarios u ON u.id = v.usuario_id"],
        'v_ia_detalle_ventas' => ["Productos vendidos en cada venta de tienda (una fila por producto). importe = ingreso de la línea; utilidad = importe - cantidad*costo. Úsala para productos y categorías más vendidos.",
          "SELECT d.venta_id, v.fecha, DATE(v.fecha) AS dia, pr.id AS producto_id, pr.codigo, pr.nombre AS producto, COALESCE(pr.categoria, 'Sin categoría') AS categoria, d.cantidad, d.precio AS precio_unitario, (d.cantidad * d.precio) AS importe, (d.cantidad * (d.precio - pr.precio_compra)) AS utilidad, v.metodo FROM venta_detalle d JOIN ventas v ON v.id = d.venta_id JOIN productos pr ON pr.id = d.producto_id"],
        'v_ia_productos' => ["Catálogo de productos de la tienda con su stock actual. activo=1 son los activos. stock_bajo=1 si stock <= stock_minimo.",
          "SELECT pr.id AS producto_id, pr.codigo, pr.nombre AS producto, COALESCE(pr.categoria, 'Sin categoría') AS categoria, pr.precio_compra, pr.precio_venta, (pr.precio_venta - pr.precio_compra) AS margen_unitario, pr.stock, pr.stock_minimo, (pr.stock <= pr.stock_minimo) AS stock_bajo, (pr.stock * pr.precio_compra) AS valor_costo, pr.estado AS activo FROM productos pr"],
        'v_ia_gastos' => ["Gastos operativos del gimnasio (alquiler, planilla, servicios...).",
          "SELECT g.id AS gasto_id, g.fecha, g.concepto, g.categoria, g.monto FROM gastos g"],
        'v_ia_clases' => ["Horario semanal de clases grupales. dia_semana: 1=lunes ... 7=domingo. activa=1 si la clase está vigente.",
          "SELECT h.id AS horario_id, c.nombre AS clase, TRIM(CONCAT(COALESCE(e.nombres,''),' ',COALESCE(e.apellidos,''))) AS entrenador, h.dia_semana, h.hora_inicio, h.hora_fin, h.sala, c.cupo, c.estado AS activa FROM horarios_clase h JOIN clases c ON c.id = h.clase_id LEFT JOIN entrenadores e ON e.id = c.entrenador_id"],
        'v_ia_reservas' => ["Reservas de socios en clases. estado: reservada, asistio o cancelada. Las reservas válidas no son canceladas.",
          "SELECT r.id AS reserva_id, r.fecha, c.nombre AS clase, h.hora_inicio, $soc AS socio, r.estado FROM reservas r JOIN horarios_clase h ON h.id = r.horario_id JOIN clases c ON c.id = h.clase_id JOIN socios s ON s.id = r.socio_id"],
        'v_ia_evaluaciones' => ["Evaluaciones físicas de los socios (peso en kg, talla en m, medidas en cm). imc = peso / talla².",
          "SELECT ev.id AS evaluacion_id, $soc AS socio, ev.fecha, ev.peso, ev.talla, ROUND(ev.peso / (ev.talla * ev.talla), 1) AS imc, ev.grasa AS porcentaje_grasa, ev.cintura, ev.pecho, ev.brazo, ev.pierna FROM evaluaciones ev JOIN socios s ON s.id = ev.socio_id"],
        'v_ia_leads' => ["Prospectos del CRM (personas interesadas que aún no son socios). etapa: nuevo, contactado, visita, prueba, negociacion, ganado o perdido; abiertos = etapa distinta de ganado y perdido. fuente: instagram, facebook, tiktok, whatsapp, referido, pasaba, web, volante u otro. convertido=1 si ya se registró como socio.",
          "SELECT l.id AS lead_id, TRIM(CONCAT(l.nombres,' ',COALESCE(l.apellidos,''))) AS prospecto, l.fuente, l.interes, l.etapa, l.valor AS valor_estimado, l.motivo_perdida, u.nombre AS responsable, DATE(l.created_at) AS dia_registro, l.cerrado_en, DATEDIFF(COALESCE(l.cerrado_en, NOW()), l.created_at) AS dias_en_embudo, (l.socio_id IS NOT NULL) AS convertido FROM crm_leads l LEFT JOIN usuarios u ON u.id = l.responsable_id"],
        'v_ia_cajas' => ["Turnos de caja (apertura y cierre). estado: abierta o cerrada. diferencia = contado - esperado.",
          "SELECT ca.id AS caja_id, ca.apertura, ca.cierre, u.nombre AS cajero, ca.monto_inicial, ca.esperado, ca.monto_contado AS contado, ca.diferencia, ca.estado FROM cajas ca LEFT JOIN usuarios u ON u.id = ca.usuario_id"],
        ];
    }

    public static function instalar(bool $force = false): void
    {
        static $hecho = false;
        if ($hecho && !$force) return;
        $pdo = Database::conn();
        Caja::ensure(); Crm::ensure();
        $pdo->exec("CREATE TABLE IF NOT EXISTS ia_consultas (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, pregunta VARCHAR(500) NOT NULL, titulo VARCHAR(160) NULL, sql_texto TEXT NOT NULL, grafico VARCHAR(255) NULL, favorito TINYINT(1) NOT NULL DEFAULT 0, veces INT NOT NULL DEFAULT 1, creado_en DATETIME DEFAULT CURRENT_TIMESTAMP, ultimo_uso DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX (usuario_id, favorito)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $ok = !$force && self::get('ia_vistas_v') === self::VERSION_VISTAS;
        if ($ok) { try { foreach (array_keys(self::vistas()) as $v) $pdo->query("SELECT 1 FROM `$v` LIMIT 1")->fetchAll(); } catch (Throwable $e) { $ok = false; } }
        if (!$ok) foreach (self::vistas() as $v => [, $sql]) $pdo->exec("CREATE OR REPLACE VIEW `$v` AS $sql");
        self::set('ia_vistas_v', self::VERSION_VISTAS);
        $hecho = true;
    }

    public static function esquemaTexto(): string
    {
        $t = '';
        foreach (self::vistas() as $v => [$desc]) {
            $cols = [];
            foreach (Database::all("SHOW COLUMNS FROM `$v`") as $c) $cols[] = $c['Field'] . ' ' . strtolower(preg_replace('/\(.*/', '', $c['Type']));
            $t .= "$v: $desc\n  columnas: " . implode(', ', $cols) . "\n";
        }
        return $t;
    }

    /* ---------- Configuración y clave cifrada ---------- */
    private static function secreto(): string
    {
        $f = ROOT_PATH . '/config/secret.php';
        if (!is_file($f)) @file_put_contents($f, "<?php\nreturn '" . base64_encode(random_bytes(32)) . "';\n");
        $k = is_file($f) ? (string)(include $f) : '';
        return base64_decode($k) ?: str_repeat('x', 32);
    }
    public static function cifrar(string $s): string
    {
        if ($s === '') return '';
        if (!function_exists('openssl_encrypt')) return 'p:' . base64_encode($s);
        $iv = random_bytes(12); $tag = '';
        $c = openssl_encrypt($s, 'aes-256-gcm', self::secreto(), OPENSSL_RAW_DATA, $iv, $tag);
        return 'g:' . base64_encode($iv . $tag . $c);
    }
    public static function descifrar(string $s): string
    {
        if ($s === '') return '';
        if (str_starts_with($s, 'p:')) return (string)base64_decode(substr($s, 2));
        if (!str_starts_with($s, 'g:') || !function_exists('openssl_decrypt')) return '';
        $b = base64_decode(substr($s, 2)); if (strlen($b) < 29) return '';
        return (string)openssl_decrypt(substr($b, 28), 'aes-256-gcm', self::secreto(), OPENSSL_RAW_DATA, substr($b, 0, 12), substr($b, 12, 16));
    }
    public static function get(string $k, string $d = ''): string
    {
        $r = Database::row('SELECT valor FROM configuracion WHERE clave = ?', [$k]);
        return ($r && $r['valor'] !== null && $r['valor'] !== '') ? (string)$r['valor'] : $d;
    }
    public static function set(string $k, string $v): void
    {
        Database::exec('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$k, $v]);
    }
    public static function cfg(): array
    {
        $p = self::get('ia_proveedor', 'gemini'); if (!isset(self::PRESETS[$p])) $p = 'gemini';
        return ['proveedor' => $p, 'url' => self::get('ia_url', self::PRESETS[$p]['url']), 'modelo' => self::get('ia_modelo', self::PRESETS[$p]['modelo']),
                'clave' => self::descifrar(self::get('ia_clave')), 'resumen' => self::get('ia_resumen', '1') === '1'];
    }
    public static function listo(array $c): bool { return $c['url'] !== '' && $c['modelo'] !== '' && (!self::PRESETS[$c['proveedor']]['clave'] || $c['clave'] !== ''); }

    /* ---------- Llamada al modelo (API compatible con OpenAI) ---------- */
    public static function llamar(array $mensajes, int $maxTokens = 900): string
    {
        $c = self::cfg();
        if ($c['url'] === '' || $c['modelo'] === '') throw new RuntimeException('Configura el proveedor de IA (URL y modelo).');
        if (self::PRESETS[$c['proveedor']]['clave'] && $c['clave'] === '') throw new RuntimeException('Falta la clave (API key) del proveedor de IA. Configúrala con el botón «Configurar IA».');
        $url = rtrim($c['url'], '/') . '/chat/completions';
        if (!preg_match('#^https?://#i', $url)) throw new RuntimeException('La URL del proveedor debe empezar por http:// o https://');
        $payload = json_encode(['model' => $c['modelo'], 'messages' => $mensajes, 'temperature' => 0, 'max_tokens' => $maxTokens], JSON_UNESCAPED_UNICODE);
        $h = ['Content-Type: application/json', 'Accept: application/json']; if ($c['clave'] !== '') $h[] = 'Authorization: Bearer ' . $c['clave'];
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS]);
            $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        } else {
            $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $h), 'content' => $payload, 'timeout' => 60, 'ignore_errors' => true]]);
            $res = @file_get_contents($url, false, $ctx); $err = $res === false ? 'sin respuesta' : ''; $code = 0;
            foreach ($http_response_header ?? [] as $l) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $l, $m)) $code = (int)$m[1];
        }
        if ($res === false || $res === null) throw new RuntimeException('No se pudo conectar con el proveedor de IA (' . $err . '). Revisa tu internet y la URL.');
        $j = json_decode((string)$res, true);
        if ($code === 401 || $code === 403) throw new RuntimeException('El proveedor rechazó la clave (API key). Revisa que sea correcta.');
        if ($code === 429) throw new RuntimeException('Se alcanzó el límite del plan gratuito. Espera un minuto e inténtalo de nuevo.');
        if ($code === 404) throw new RuntimeException('Modelo o URL no encontrados (' . $c['modelo'] . '). Google renueva sus modelos: prueba con gemini-3.5-flash-lite o gemini-3.5-flash.');
        if ($code >= 400 || !is_array($j)) { $m = is_array($j) ? ($j['error']['message'] ?? ($j[0]['error']['message'] ?? '')) : ''; throw new RuntimeException('Error del proveedor de IA (' . $code . ')' . ($m ? ': ' . mb_substr($m, 0, 200) : '.')); }
        $txt = $j['choices'][0]['message']['content'] ?? '';
        if (is_array($txt)) $txt = implode('', array_map(fn($p) => $p['text'] ?? '', $txt));
        if (trim((string)$txt) === '') throw new RuntimeException('La IA no devolvió respuesta. Intenta reformular la pregunta.');
        return (string)$txt;
    }

    public static function extraerJson(string $t): ?array
    {
        $t = trim($t);
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $t, $m)) $t = trim($m[1]);
        $j = json_decode($t, true); if (is_array($j)) return $j;
        $a = strpos($t, '{'); $b = strrpos($t, '}');
        if ($a !== false && $b > $a) { $j = json_decode(substr($t, $a, $b - $a + 1), true); if (is_array($j)) return $j; }
        return null;
    }

    /* ---------- Validación del SQL (defensa en profundidad) ---------- */
    public static function validarSql(string $sql): string
    {
        $sql = rtrim(trim($sql), "; \t\r\n");
        if ($sql === '' || strlen($sql) > 4000) throw new InvalidArgumentException('La consulta está vacía o es demasiado larga.');
        $s = preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/s", ' 0 ', $sql);
        $s = preg_replace('/"(?:[^"\\\\]|\\\\.|"")*"/s', ' 0 ', $s);
        if (strpbrk($s, "'\"") !== false) throw new InvalidArgumentException('Comillas sin cerrar en la consulta.');
        if (strpos($s, ';') !== false) throw new InvalidArgumentException('Solo se permite una consulta.');
        if (preg_match('/--|\/\*|\*\/|#|@/', $s)) throw new InvalidArgumentException('No se permiten comentarios ni variables.');
        $l = strtolower(str_replace('`', ' ', $s));
        if (!preg_match('/^\s*(select|with)\b/', $l)) throw new InvalidArgumentException('Solo se permiten consultas SELECT.');
        $prohib = 'insert|update|delete|drop|alter|create|truncate|grant|revoke|call|execute|prepare|handler|load|outfile|dumpfile|into|lock|unlock|set|rename|use|show|describe|explain|optimize|analyze|sleep|benchmark|load_file|get_lock|release_lock|information_schema|performance_schema|mysql|sys|user|current_user|session_user|system_user|database|schema|version|connection_id|row_count|found_rows|last_insert_id|uuid|procedure|kill|shutdown|flush|reset|purge|install|uninstall|import|binlog|xa|savepoint|commit|rollback|begin|start|recursive';
        if (preg_match('/\b(' . $prohib . ')\b/', $l, $m)) throw new InvalidArgumentException('Palabra no permitida en la consulta: ' . strtoupper($m[1]));
        if (preg_match('/for\s+update|lock\s+in\s+share/', $l)) throw new InvalidArgumentException('Bloqueos no permitidos.');
        static $reales = null;
        if ($reales === null) {
            $reales = [];
            foreach (Database::conn()->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $r) if (strpos($r[0], 'v_ia_') !== 0) $reales[strtolower($r[0])] = true;
            $reales[strtolower(DB_NAME)] = true;
        }
        if (preg_match_all('/(?<![\w.])(?:(as)\s+)?([a-z_][a-z0-9_]*)\b/', $l, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $t) if (isset($reales[$t[2]]) && $t[1] === '') throw new InvalidArgumentException('Solo puedes consultar las vistas v_ia_* (no la tabla «' . $t[2] . '»). Si es un alias, usa otro nombre.');
        }
        if (preg_match('/\b[a-z_][a-z0-9_]*\s*\.\s*[a-z_][a-z0-9_]*\s*\./', $l)) throw new InvalidArgumentException('No se permiten nombres con base de datos.');
        return $sql;
    }

    /** Ejecuta en solo lectura con límite de tiempo y de filas. */
    public static function ejecutar(string $sql): array
    {
        $sql = self::validarSql($sql); $pdo = Database::conn(); $t0 = microtime(true);
        foreach (["SET SESSION MAX_EXECUTION_TIME=8000", "SET SESSION max_statement_time=8"] as $x) { try { $pdo->exec($x); } catch (Throwable $e) {} }
        $buf = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        try {
            $pdo->exec('START TRANSACTION READ ONLY');
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $st = $pdo->query($sql);
            $n = $st->columnCount(); $cols = $tipos = [];
            for ($i = 0; $i < $n; $i++) { $m = $st->getColumnMeta($i); $cols[] = $m['name'] ?? ('col' . $i); $tipos[] = strtoupper((string)($m['native_type'] ?? '')); }
            $filas = []; $trunc = false;
            while ($r = $st->fetch(PDO::FETCH_NUM)) {
                if (count($filas) >= self::MAX_FILAS) { $trunc = true; break; }
                foreach ($r as $i => $v) if ($v !== null && is_string($v) && in_array($tipos[$i], ['NEWDECIMAL', 'DECIMAL', 'LONG', 'LONGLONG', 'SHORT', 'TINY', 'INT24', 'FLOAT', 'DOUBLE'], true) && is_numeric($v)) $r[$i] = $v + 0;
                $filas[] = $r;
            }
            $st->closeCursor();
        } catch (PDOException $e) {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buf); try { $pdo->exec('ROLLBACK'); } catch (Throwable $x) {}
            throw new RuntimeException('Error SQL: ' . preg_replace('/^SQLSTATE\[[^\]]*\]:?\s*/', '', $e->getMessage()));
        }
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buf); try { $pdo->exec('ROLLBACK'); } catch (Throwable $x) {}
        return ['columnas' => $cols, 'filas' => $filas, 'truncado' => $trunc, 'ms' => (int)round((microtime(true) - $t0) * 1000), 'sql' => $sql];
    }

    /* ---------- Prompts ---------- */
    public static function reglas(): string
    {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        return "REGLAS:\n- Genera un único SELECT (o WITH ... SELECT) usando SOLO las vistas v_ia_*. Sin punto y coma, sin comentarios, sin variables.\n"
        . "- Hoy es " . $dias[(int)date('w')] . " " . date('Y-m-d') . " (usa CURDATE()/NOW() para fechas relativas). La semana empieza el lunes. «Este mes» = desde el día 1 del mes actual hasta hoy; «mes pasado» = mes calendario anterior.\n"
        . "- Socios activos = socios con estado='activo'. Socios con plan vigente = v_ia_socios.estado_membresia='vigente'. Membresías válidas: estado <> 'cancelada'.\n"
        . "- Ingresos por membresías = SUM(monto) de v_ia_pagos; ingresos de tienda = SUM(total) de v_ia_ventas. Ingresos totales = ambos. Utilidad = ingresos - SUM(monto) de v_ia_gastos.\n"
        . "- «Más vendidos» se mide por SUM(cantidad) salvo que pidan por monto. Productos activos: activo=1.\n"
        . "- Para buscar por nombre usa LOWER(columna) LIKE '%texto%'.\n"
        . "- Moneda: " . APP_CURRENCY . " (" . fmt_cfg()['code'] . "). Redondea importes con ROUND(x,2). Pon alias de columnas legibles en español (sin espacios: usa guion bajo), por ejemplo socio, total_cobrado, visitas.\n"
        . "- Evita alias que coincidan con nombres de tablas del sistema (usa total_ventas, no ventas).\n"
        . "- Si piden un ranking sin cantidad, usa LIMIT 10; en cualquier caso nunca más de " . self::MAX_FILAS . " filas. Ordena de forma útil.\n"
        . "- Si la pregunta no se puede responder con estas vistas, no inventes.";
    }

    public static function promptSistema(): string
    {
        return "Eres el analista de datos de un gimnasio (socios, membresías, asistencia, clases, tienda y caja). Conviertes preguntas en español en UNA consulta SQL (MySQL/MariaDB) de solo lectura.\n\n"
        . "VISTAS DISPONIBLES (no existe nada más):\n" . self::esquemaTexto() . "\n"
        . self::reglas() . " Si no se puede responder, devuelve sql null y explica en «mensaje».\n\n"
        . "Responde SOLO con un objeto JSON, sin texto adicional ni markdown:\n"
        . '{"titulo":"título corto del reporte","sql":"SELECT ...","grafico":{"tipo":"bar|line|pie|none","x":"alias_columna_etiquetas","y":"alias_columna_numerica"},"mensaje":null}' . "\n"
        . "grafico.tipo: line para series por fecha, bar para rankings/comparaciones, pie para participación (máx. 8 categorías), none si no tiene sentido graficar.";
    }

    public static function preguntar(string $pregunta, ?array $previo = null): array
    {
        $m = [['role' => 'system', 'content' => self::promptSistema()]];
        if ($previo && !empty($previo['pregunta']) && !empty($previo['sql'])) {
            $m[] = ['role' => 'user', 'content' => mb_substr((string)$previo['pregunta'], 0, 300)];
            $m[] = ['role' => 'assistant', 'content' => json_encode(['titulo' => '', 'sql' => mb_substr((string)$previo['sql'], 0, 1500), 'grafico' => ['tipo' => 'none'], 'mensaje' => null], JSON_UNESCAPED_UNICODE)];
        }
        $m[] = ['role' => 'user', 'content' => $pregunta];
        $ultimoError = '';
        for ($intento = 0; $intento < 2; $intento++) {
            $raw = self::llamar($m);
            $j = self::extraerJson($raw);
            if (!$j) { $m[] = ['role' => 'assistant', 'content' => mb_substr($raw, 0, 800)]; $m[] = ['role' => 'user', 'content' => 'Tu respuesta no era JSON válido. Responde SOLO el objeto JSON indicado.']; $ultimoError = 'La IA respondió en un formato inesperado.'; continue; }
            if (empty($j['sql'])) return ['mensaje' => (string)(($j['mensaje'] ?? '') ?: 'No puedo responder esa pregunta con los datos disponibles. Prueba con socios, membresías, pagos, asistencia, clases, ventas o gastos.')];
            try {
                $r = self::ejecutar((string)$j['sql']);
                $g = is_array($j['grafico'] ?? null) ? $j['grafico'] : [];
                return $r + ['titulo' => mb_substr(trim((string)($j['titulo'] ?? '')) ?: $pregunta, 0, 160), 'grafico' => ['tipo' => in_array($g['tipo'] ?? '', ['bar', 'line', 'pie', 'none'], true) ? $g['tipo'] : 'none', 'x' => (string)($g['x'] ?? ''), 'y' => (string)($g['y'] ?? '')]];
            } catch (Throwable $e) {
                $ultimoError = $e->getMessage();
                $m[] = ['role' => 'assistant', 'content' => $raw];
                $m[] = ['role' => 'user', 'content' => 'Esa consulta falló: ' . $ultimoError . '. Corrígela usando solo las vistas y columnas listadas y responde de nuevo SOLO el JSON.'];
            }
        }
        throw new RuntimeException('No pude generar una consulta válida para esa pregunta. Reformúlala con más detalle. (' . $ultimoError . ')');
    }

    public static function resumir(string $pregunta, array $cols, array $filas): string
    {
        $muestra = array_slice($filas, 0, 25);
        $txt = self::llamar([
            ['role' => 'system', 'content' => 'Eres un analista de un gimnasio. Responde en español, en 1 o 2 frases claras, mencionando las cifras más relevantes. No uses listas, ni markdown, ni inventes datos que no estén en la tabla. Moneda: ' . APP_CURRENCY . '.'],
            ['role' => 'user', 'content' => "Pregunta: $pregunta\nColumnas: " . implode(', ', $cols) . "\nFilas (" . count($filas) . " en total, se muestran " . count($muestra) . "):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE)],
        ], 250);
        return trim(strip_tags($txt));
    }

    /* ---------- Chat ---------- */
    public static function promptChat(): string
    {
        return "Eres el chatbot de «" . self::get('gimnasio_nombre', APP_NAME) . "», un sistema de gestión de gimnasio. Conversas en español con el administrador, con tono cercano y breve.\n"
        . "Puedes: (1) consultar datos del negocio (socios, membresías, pagos, asistencia, clases, ventas, productos, gastos, evaluaciones, cajas); (2) explicar cómo usar el sistema; (3) saludar o aclarar. NO puedes registrar, modificar ni borrar nada: si te lo piden, explica en qué módulo se hace.\n\n"
        . "VISTAS DISPONIBLES (no existe nada más):\n" . self::esquemaTexto() . "\n" . self::reglas() . "\n"
        . "- Para datos de un socio o producto concreto devuelve pocas columnas útiles y LIMIT 20.\n"
        . "- Si el mensaje es una continuación («y el mes pasado?», «solo los de plan mensual»), reutiliza y adapta la consulta anterior.\n\n"
        . "GUÍA DEL SISTEMA: " . self::GUIA . "\n\n"
        . "Responde SOLO con un objeto JSON, sin texto adicional ni markdown, en una de dos formas:\n"
        . '{"accion":"consulta","sql":"SELECT ..."}  → cuando necesites datos.' . "\n"
        . '{"accion":"respuesta","texto":"..."}  → cuando no necesites datos (saludo, ayuda de uso, aclaración, o si algo no es posible).';
    }

    public static function chat(string $msg, array $hist): array
    {
        $m = [['role' => 'system', 'content' => self::promptChat()]];
        foreach (array_slice($hist, -6) as $h) {
            $m[] = ['role' => 'user', 'content' => (string)$h['q']];
            $m[] = ['role' => 'assistant', 'content' => json_encode(!empty($h['sql']) ? ['accion' => 'consulta', 'sql' => $h['sql']] : ['accion' => 'respuesta', 'texto' => mb_substr((string)$h['a'], 0, 300)], JSON_UNESCAPED_UNICODE)];
        }
        $m[] = ['role' => 'user', 'content' => $msg];
        $err = '';
        for ($i = 0; $i < 2; $i++) {
            $raw = self::llamar($m, 700); $j = self::extraerJson($raw);
            if (!$j) { $t = trim(strip_tags($raw)); if ($t !== '' && $t[0] !== '{') return ['texto' => $t]; $m[] = ['role' => 'assistant', 'content' => mb_substr($raw, 0, 500)]; $m[] = ['role' => 'user', 'content' => 'Responde SOLO con el objeto JSON indicado.']; continue; }
            if (($j['accion'] ?? '') !== 'consulta' || empty($j['sql'])) return ['texto' => trim((string)($j['texto'] ?? $j['respuesta'] ?? '')) ?: 'No entendí bien. ¿Puedes darme más detalle?'];
            try { $r = self::ejecutar((string)$j['sql']); }
            catch (Throwable $e) { $err = $e->getMessage(); $m[] = ['role' => 'assistant', 'content' => $raw]; $m[] = ['role' => 'user', 'content' => 'Esa consulta falló: ' . $err . '. Corrígela usando solo las vistas y columnas listadas y responde SOLO el JSON.']; continue; }
            $muestra = array_slice($r['filas'], 0, 25);
            $texto = self::llamar([
                ['role' => 'system', 'content' => 'Eres el chatbot de un gimnasio. Responde en español con tono cercano y breve (máximo 4 frases o una lista corta con guiones). Usa las cifras EXACTAS de los datos, sin inventar nada. Importes con el símbolo ' . APP_CURRENCY . ' (ejemplo de formato: ' . money(1234.5) . '). Si no hay filas, dilo claramente. No menciones SQL, vistas ni columnas técnicas.'],
                ['role' => 'user', 'content' => "Pregunta: $msg\nColumnas: " . implode(', ', $r['columnas']) . "\nFilas (" . count($r['filas']) . " en total" . ($r['truncado'] ? ', hay más' : '') . ", se muestran " . count($muestra) . "):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE)],
            ], 450);
            return ['texto' => trim(strip_tags($texto)), 'sql' => $r['sql'], 'tabla' => ['columnas' => $r['columnas'], 'filas' => array_slice($r['filas'], 0, 10), 'total' => count($r['filas'])]];
        }
        throw new RuntimeException('No pude consultar eso con los datos disponibles. Prueba reformulando la pregunta. (' . $err . ')');
    }
}
