<?php
class ConfiguracionController extends Controller
{
    public const MONEDAS = [
        'PEN' => ['Sol peruano', 'S/', 2, '.', 'coma'], 'USD' => ['Dólar estadounidense', '$', 2, '.', 'coma'], 'EUR' => ['Euro', '€', 2, ',', 'punto'],
        'MXN' => ['Peso mexicano', '$', 2, '.', 'coma'], 'COP' => ['Peso colombiano', '$', 0, ',', 'punto'], 'CLP' => ['Peso chileno', '$', 0, ',', 'punto'],
        'ARS' => ['Peso argentino', '$', 2, ',', 'punto'], 'BOB' => ['Boliviano', 'Bs', 2, ',', 'punto'], 'UYU' => ['Peso uruguayo', '$U', 2, ',', 'punto'],
        'PYG' => ['Guaraní', '₲', 0, ',', 'punto'], 'BRL' => ['Real brasileño', 'R$', 2, ',', 'punto'], 'CRC' => ['Colón costarricense', '₡', 2, ',', 'punto'],
        'DOP' => ['Peso dominicano', 'RD$', 2, '.', 'coma'], 'GTQ' => ['Quetzal', 'Q', 2, '.', 'coma'], 'HNL' => ['Lempira', 'L', 2, '.', 'coma'],
        'NIO' => ['Córdoba', 'C$', 2, '.', 'coma'], 'PAB' => ['Balboa', 'B/.', 2, '.', 'coma'], 'VES' => ['Bolívar', 'Bs.S', 2, ',', 'punto'],
    ];
    public const ZONAS = [
        'America/Lima' => 'Lima, Bogotá, Quito (UTC−5)', 'America/Bogota' => 'Bogotá (UTC−5)', 'America/Guayaquil' => 'Guayaquil (UTC−5)', 'America/Mexico_City' => 'Ciudad de México',
        'America/Panama' => 'Panamá', 'America/Costa_Rica' => 'Costa Rica', 'America/Guatemala' => 'Guatemala', 'America/El_Salvador' => 'El Salvador', 'America/Tegucigalpa' => 'Honduras',
        'America/Managua' => 'Nicaragua', 'America/Havana' => 'La Habana', 'America/Santo_Domingo' => 'Santo Domingo', 'America/Caracas' => 'Caracas', 'America/La_Paz' => 'La Paz',
        'America/Santiago' => 'Santiago', 'America/Asuncion' => 'Asunción', 'America/Argentina/Buenos_Aires' => 'Buenos Aires', 'America/Montevideo' => 'Montevideo', 'America/Sao_Paulo' => 'São Paulo',
        'America/New_York' => 'Nueva York', 'America/Los_Angeles' => 'Los Ángeles', 'Europe/Madrid' => 'Madrid', 'UTC' => 'UTC',
    ];

    public function index(): void
    {
        $this->requireAuth();
        $cfg = array_column(Database::all('SELECT clave, valor FROM configuracion'), 'valor', 'clave');
        $this->view('configuracion/index', ['title' => 'Configuración', 'active' => 'configuracion', 'cfg' => $cfg, 'fmt' => fmt_cfg(), 'monedas' => self::MONEDAS, 'zonas' => self::ZONAS]);
    }

    private function set(string $k, string $v): void
    {
        Database::exec('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$k, $v]);
    }

    private function t(string $k, int $max = 150): string { return mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max); }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('configuracion');
        $err = []; $d = [];
        $d['gimnasio_nombre'] = $this->t('gimnasio_nombre', 80); $d['razon_social'] = $this->t('razon_social', 120); $d['gimnasio_ruc'] = $this->t('gimnasio_ruc', 15);
        $d['gimnasio_direccion'] = $this->t('gimnasio_direccion'); $d['gimnasio_ciudad'] = $this->t('gimnasio_ciudad', 60); $d['gimnasio_telefono'] = $this->t('gimnasio_telefono', 20);
        $d['gimnasio_email'] = $this->t('gimnasio_email', 120); $d['gimnasio_web'] = $this->t('gimnasio_web', 120); $d['gimnasio_instagram'] = ltrim($this->t('gimnasio_instagram', 60), '@');
        $d['gimnasio_facebook'] = $this->t('gimnasio_facebook', 80); $d['gimnasio_whatsapp'] = preg_replace('/\D/', '', $this->t('gimnasio_whatsapp', 20)); $d['pie_recibo'] = $this->t('pie_recibo', 160);
        if ($d['gimnasio_nombre'] === '') $err[] = 'El nombre comercial es obligatorio.';
        if ($d['gimnasio_ruc'] !== '' && !preg_match('/^[0-9]{8,15}$/', $d['gimnasio_ruc'])) $err[] = 'El RUC / NIT debe contener solo números (8 a 15 dígitos).';
        if ($d['gimnasio_telefono'] !== '' && !preg_match('/^[0-9 +()-]{6,20}$/', $d['gimnasio_telefono'])) $err[] = 'El teléfono no es válido.';
        if ($d['gimnasio_email'] !== '' && !filter_var($d['gimnasio_email'], FILTER_VALIDATE_EMAIL)) $err[] = 'El correo de la empresa no es válido.';
        if ($d['gimnasio_web'] !== '' && !preg_match('#^(https?://)?[\w.-]+\.[a-z]{2,}([/?#].*)?$#i', $d['gimnasio_web'])) $err[] = 'La página web no es válida.';

        $sym = $this->t('moneda', 6); $cod = strtoupper($this->t('moneda_codigo', 3)); $dec = (int)($_POST['moneda_decimales'] ?? 2);
        $ds = ($_POST['sep_decimal'] ?? '.') === ',' ? ',' : '.'; $ts = in_array($_POST['sep_miles'] ?? '', ['coma', 'punto', 'espacio', 'ninguno'], true) ? $_POST['sep_miles'] : 'coma';
        if ($sym === '') $err[] = 'Indica el símbolo de la moneda.';
        if ($cod !== '' && !preg_match('/^[A-Z]{3}$/', $cod)) $err[] = 'El código de moneda debe tener 3 letras (ej. PEN).';
        if ($dec < 0 || $dec > 4) $err[] = 'Los decimales deben estar entre 0 y 4.';
        if ($ts === ($ds === '.' ? 'punto' : 'coma')) $err[] = 'El separador decimal y el de miles no pueden ser el mismo.';
        $d += ['moneda' => $sym, 'moneda_codigo' => $cod ?: 'PEN', 'moneda_decimales' => (string)$dec, 'sep_decimal' => $ds, 'sep_miles' => $ts,
               'moneda_posicion' => ($_POST['moneda_posicion'] ?? '') === 'despues' ? 'despues' : 'antes', 'moneda_espacio' => !empty($_POST['moneda_espacio']) ? '1' : '0'];

        $zona = (string)($_POST['zona_horaria'] ?? 'America/Lima'); if (!isset(self::ZONAS[$zona])) $err[] = 'La zona horaria no es válida.';
        $ff = (string)($_POST['formato_fecha'] ?? 'd/m/Y'); if (!in_array($ff, ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'], true)) $err[] = 'El formato de fecha no es válido.';
        $tema = in_array($_POST['tema_defecto'] ?? '', ['claro', 'oscuro', 'sistema'], true) ? $_POST['tema_defecto'] : 'claro';
        $aforo = (int)($_POST['aforo_maximo'] ?? 0); $dias = (int)($_POST['dias_aviso_vencimiento'] ?? 0);
        if ($aforo < 1 || $aforo > 5000) $err[] = 'El aforo máximo debe estar entre 1 y 5000.';
        if ($dias < 1 || $dias > 60) $err[] = 'Los días de aviso deben estar entre 1 y 60.';
        $d += ['zona_horaria' => $zona, 'formato_fecha' => $ff, 'tema_defecto' => $tema, 'aforo_maximo' => (string)$aforo, 'dias_aviso_vencimiento' => (string)$dias];

        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect('configuracion'); }
        foreach ($d as $k => $v) $this->set($k, (string)$v);
        Bitacora::log('configuracion', 'Datos de empresa y formato (' . $d['moneda_codigo'] . ')');
        $this->flash('success', 'Configuración guardada. Los cambios de formato ya se aplican en todo el sistema.');
        $this->redirect('configuracion');
    }

    public function logo(): void
    {
        $this->requireAuth();
        $this->requirePost('configuracion');
        if (!empty($_POST['quitar'])) {
            $old = Database::val("SELECT valor FROM configuracion WHERE clave = 'logo'");
            if ($old && is_file(ROOT_PATH . '/public/' . $old)) @unlink(ROOT_PATH . '/public/' . $old);
            $this->set('logo', ''); Bitacora::log('configuracion', 'Logo eliminado'); $this->flash('success', 'Logo eliminado.'); $this->redirect('configuracion');
        }
        $f = $_FILES['logo'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) { $this->flash('danger', 'Selecciona una imagen para el logo.'); $this->redirect('configuracion'); }
        if ($f['size'] > 1024 * 1024) { $this->flash('danger', 'El logo no debe superar 1 MB.'); $this->redirect('configuracion'); }
        $info = @getimagesize($f['tmp_name']);
        $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
        if (!$ext) { $this->flash('danger', 'El logo debe ser JPG, PNG o WEBP.'); $this->redirect('configuracion'); }
        $dir = ROOT_PATH . '/public/uploads/logo'; if (!is_dir($dir)) mkdir($dir, 0775, true);
        $name = 'logo_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) { $this->flash('danger', 'No se pudo guardar el logo.'); $this->redirect('configuracion'); }
        $old = Database::val("SELECT valor FROM configuracion WHERE clave = 'logo'");
        if ($old && is_file(ROOT_PATH . '/public/' . $old)) @unlink(ROOT_PATH . '/public/' . $old);
        $this->set('logo', 'uploads/logo/' . $name);
        Bitacora::log('configuracion', 'Logo actualizado');
        $this->flash('success', 'Logo actualizado.');
        $this->redirect('configuracion');
    }

    /** El respaldo ahora vive en el módulo «Respaldo y sistema». */
    public function respaldo(): void { $this->requireAuth(); $this->redirect('sistema'); }
}
