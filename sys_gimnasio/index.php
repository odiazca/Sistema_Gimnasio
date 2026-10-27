<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/core/Database.php';
require __DIR__ . '/core/Auth.php';
require __DIR__ . '/core/Controller.php';
require __DIR__ . '/core/Permisos.php';
require __DIR__ . '/core/App.php';
require __DIR__ . '/core/helpers.php';

if (!is_file(__DIR__ . '/database/install.lock') && !isset($_GET['skip_install'])) {
    header('Location: ' . BASE_URL . '/install.php');
    exit;
}


Auth::start();
try {
    (new App())->run();
} catch (PDOException $ex) {
    http_response_code(500);
    echo '<h2>Error de base de datos</h2><p>' . e($ex->getMessage()) . '</p><p>Verifica <code>config/config.php</code> o ejecuta <a href="' . BASE_URL . '/install.php">install.php</a>.</p>';
}
