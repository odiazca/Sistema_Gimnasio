<?php
/**
 * FitCore Gym - Configuración general
 */
date_default_timezone_set('America/Lima');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // <- tu contraseña de MySQL (Laragon por defecto: vacío)
define('DB_NAME', 'sys_gimnasio');
define('DB_PORT', 3306);

define('APP_NAME', 'FitCore Gym');
define('APP_VERSION', '1.3.0');

// URL base autodetectada (funciona en /sys_gimnasio/ o en la raíz de un dominio)
$__base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $__base);
define('ASSETS_URL', BASE_URL . '/public/assets');
define('ROOT_PATH', dirname(__DIR__));
