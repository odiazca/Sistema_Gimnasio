<?php
require_once ROOT_PATH . '/app/models/Notif.php';

class NotificacionesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth(); Notif::ensure();
        $seg = $this->in('seg', 'vencer'); if (!isset(Notif::SEGS[$seg])) $seg = 'vencer';
        $base = (int)cfg('dias_aviso_vencimiento', '7') ?: 7; $opts = array_values(array_unique([3, 7, 15, 30, $base])); sort($opts);
        $dias = (int)$this->in('dias', (string)$base); if (!in_array($dias, $opts, true)) $dias = $base;
        $rows = Notif::lista($seg, $dias); $tpl = Notif::plantilla($seg);
        foreach ($rows as &$r) { $r['msg'] = Notif::mensaje($tpl, $r); $r['wa'] = $r['telefono'] ? wa_link($r['telefono'], $r['msg']) : ''; } unset($r);
        $cont = []; foreach (array_keys(Notif::SEGS) as $k) $cont[$k] = $k === $seg ? count($rows) : count(Notif::lista($k));
        $this->view('notificaciones/index', [
            'title' => 'Notificaciones', 'active' => 'notificaciones', 'seg' => $seg, 'dias' => $dias, 'rows' => $rows,
            'tpl' => $tpl, 'opts' => $opts, 'cont' => $cont, 'env' => Notif::enviados($seg),
        ]);
    }

    public function plantilla(): void
    {
        $this->requireAuth();
        $seg = (string)($_POST['seg'] ?? ''); $back = 'notificaciones?seg=' . urlencode($seg);
        $this->requirePost('notificaciones');
        if (!isset(Notif::SEGS[$seg])) $this->redirect('notificaciones');
        $t = trim((string)($_POST['texto'] ?? ''));
        if (!empty($_POST['restaurar'])) { Database::exec('DELETE FROM configuracion WHERE clave = ?', ['tpl_' . $seg]); $this->flash('success', 'Plantilla restaurada.'); $this->redirect($back); }
        if ($t === '' || mb_strlen($t) > 600) { $this->flash('danger', 'La plantilla debe tener entre 1 y 600 caracteres.'); $this->redirect($back); }
        Database::exec('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', ['tpl_' . $seg, $t]);
        $this->flash('success', 'Plantilla guardada.');
        $this->redirect($back);
    }

    /** Registra (vía fetch) que se abrió el WhatsApp de un socio */
    public function registrar(): void
    {
        $this->requireAuth(); Notif::ensure();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !Auth::verifyCsrf($_POST['csrf'] ?? null)) $this->json(['ok' => false], 403);
        $seg = (string)($_POST['seg'] ?? ''); $sid = (int)($_POST['socio_id'] ?? 0);
        if (!isset(Notif::SEGS[$seg]) || !Database::val('SELECT COUNT(*) FROM socios WHERE id = ?', [$sid])) $this->json(['ok' => false], 422);
        Database::exec('INSERT INTO notificaciones_log (socio_id,tipo,fecha,usuario_id) VALUES (?,?,NOW(),?)', [$sid, $seg, Auth::user()['id'] ?? null]);
        $this->json(['ok' => true]);
    }
}
