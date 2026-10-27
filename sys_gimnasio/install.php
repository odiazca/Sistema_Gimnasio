<?php
/**
 * Instalador FitCore Gym: crea la BD sys_gimnasio, tablas y datos demo.
 * Se bloquea solo tras instalar (database/install.lock).
 */
require __DIR__ . '/config/config.php';
$lock = __DIR__ . '/database/install.lock';
$msg = ''; $ok = false;

if (is_file($lock)) { header('Location: ' . BASE_URL . '/login'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        set_time_limit(300);
        $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        try { // Hosting compartido: la BD ya existe (creada en el panel); local: se crea sola
            $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
        } catch (PDOException $e) {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . DB_NAME . '`');
        }
        $pdo->exec("SET time_zone = '-05:00'");

        // Ejecutar esquema
        $sql = file_get_contents(__DIR__ . '/database/sys_gimnasio.sql');
        $sql = preg_replace('/^--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
            $pdo->exec($stmt);
        }

        // Usuarios
        $ins = $pdo->prepare('INSERT INTO usuarios (rol_id,nombre,usuario,email,password) VALUES (?,?,?,?,?)');
        $ins->execute([1, 'Administrador General', 'admin', 'admin@fitcore.pe', password_hash('admin123', PASSWORD_DEFAULT)]);
        $ins->execute([2, 'María Recepción', 'recepcion', 'recepcion@fitcore.pe', password_hash('recep123', PASSWORD_DEFAULT)]);

        if (!empty($_POST['demo'])) { require __DIR__ . '/database/seed.php'; seed_demo($pdo); }

        file_put_contents($lock, date('c'));
        $ok = true;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
    }
}
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalación · <?= APP_NAME ?></title>
<link href="<?= ASSETS_URL ?>/vendor/bootstrap.min.css" rel="stylesheet">
<style>body{background:#0b0f19;color:#e8ecf4;min-height:100vh;display:grid;place-items:center;font-family:system-ui}
.box{max-width:520px;width:100%;background:#131a2a;border:1px solid #232d45;border-radius:20px;padding:36px}
.btn-o{background:linear-gradient(135deg,#ff5a1f,#ff8a3d);border:0;color:#fff;font-weight:700;padding:12px}</style></head>
<body><div class="box">
<h3 class="fw-bold mb-1">🏋️ <?= APP_NAME ?></h3><p class="text-secondary">Instalador de la base de datos <code><?= DB_NAME ?></code></p>
<?php if ($ok): ?>
  <div class="alert alert-success">Instalación completada.</div>
  <p>Usuario: <b>admin</b> · Clave: <b>admin123</b></p>
  <a class="btn btn-o w-100" href="<?= BASE_URL ?>/login">Ir al login</a>
<?php else: ?>
  <?php if ($msg): ?><div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post">
    <p class="small text-secondary mb-3">Servidor: <?= DB_HOST ?>:<?= DB_PORT ?> · Usuario: <?= DB_USER ?> (edita <code>config/config.php</code> si es distinto)</p>
    <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" name="demo" id="d" checked>
      <label class="form-check-label" for="d">Cargar datos de demostración (socios, pagos, asistencias, ventas)</label></div>
    <button class="btn btn-o w-100">Instalar sistema</button>
  </form>
<?php endif; ?>
</div></body></html>
