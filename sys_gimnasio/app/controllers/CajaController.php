<?php
require_once ROOT_PATH . '/app/models/Pago.php';
require_once ROOT_PATH . '/app/models/Caja.php';

class CajaController extends Controller
{
    private function dinero(string $k): float { return round((float)str_replace(',', '.', (string)($_POST[$k] ?? 0)), 2); }

    public function index(): void
    {
        $this->requireAuth();
        $c = Caja::abierta();
        $hist = Database::all("SELECT c.*, u.nombre usuario FROM cajas c LEFT JOIN usuarios u ON u.id = c.usuario_id WHERE c.estado = 'cerrada' ORDER BY c.id DESC LIMIT 10");
        $this->view('caja/index', [
            'title' => 'Caja', 'active' => 'caja', 'c' => $c, 'hist' => $hist,
            'r' => $c ? Caja::resumen($c) : null, 'mov' => $c ? Caja::movimientos((int)$c['id']) : [],
        ]);
    }

    public function abrir(): void
    {
        $this->requireAuth();
        $this->requirePost('caja');
        Caja::ensure();
        if (Caja::abierta()) { $this->flash('warning', 'Ya hay una caja abierta.'); $this->redirect('caja'); }
        $m = $this->dinero('monto_inicial');
        if ($m < 0 || $m > 1000000) { $this->flash('danger', 'El monto inicial no es válido.'); $this->redirect('caja'); }
        Database::exec("INSERT INTO cajas (usuario_id, apertura, monto_inicial, estado) VALUES (?, NOW(), ?, 'abierta')", [Auth::user()['id'] ?? null, $m]);
        $this->flash('success', 'Caja abierta con ' . money($m) . '. ¡Buen turno!');
        $this->redirect('caja');
    }

    public function movimiento(): void
    {
        $this->requireAuth();
        $this->requirePost('caja');
        $c = Caja::abierta();
        if (!$c) { $this->flash('danger', 'Primero abre la caja.'); $this->redirect('caja'); }
        $tipo = (string)($_POST['tipo'] ?? ''); $conc = trim((string)($_POST['concepto'] ?? '')); $monto = $this->dinero('monto');
        if (!in_array($tipo, ['ingreso', 'egreso'], true) || $conc === '' || $monto <= 0 || $monto > 1000000) { $this->flash('danger', 'Completa tipo, concepto y un monto mayor a 0.'); $this->redirect('caja'); }
        if ($tipo === 'egreso') {
            $r = Caja::resumen($c);
            if ($monto > $r['esperado'] + 0.001) { $this->flash('danger', 'El egreso (' . money($monto) . ') supera el efectivo disponible en caja (' . money($r['esperado']) . ').'); $this->redirect('caja'); }
        }
        Database::exec('INSERT INTO caja_movimientos (caja_id,tipo,concepto,monto,fecha,usuario_id) VALUES (?,?,?,?,NOW(),?)', [$c['id'], $tipo, mb_substr($conc, 0, 150), $monto, Auth::user()['id'] ?? null]);
        $this->flash('success', ucfirst($tipo) . ' registrado.');
        $this->redirect('caja');
    }

    public function cerrar(): void
    {
        $this->requireAuth();
        $this->requirePost('caja');
        $c = Caja::abierta();
        if (!$c) $this->redirect('caja');
        if (!isset($_POST['contado']) || trim((string)$_POST['contado']) === '' || $this->dinero('contado') < 0) { $this->flash('danger', 'Ingresa el efectivo contado en caja.'); $this->redirect('caja'); }
        $cont = $this->dinero('contado'); $obs = trim((string)($_POST['observacion'] ?? ''));
        $r = Caja::resumen($c); $dif = round($cont - $r['esperado'], 2);
        if (abs($dif) > 0.009 && $obs === '') { $this->flash('danger', 'Hay una diferencia de ' . money($dif) . '. Indica una observación para cerrar.'); $this->redirect('caja'); }
        Database::exec("UPDATE cajas SET cierre = NOW(), esperado = ?, monto_contado = ?, diferencia = ?, observacion = ?, estado = 'cerrada' WHERE id = ?", [$r['esperado'], $cont, $dif, $obs ?: null, $c['id']]);
        $this->flash(abs($dif) > 0.009 ? 'warning' : 'success', 'Caja cerrada. ' . (abs($dif) > 0.009 ? 'Diferencia: ' . money($dif) . '.' : 'Cuadre perfecto.'));
        $this->redirect('caja/ver/' . $c['id']);
    }

    public function ver($id = 0): void
    {
        $this->requireAuth();
        $c = Caja::find((int)$id);
        if (!$c) { $this->flash('danger', 'La caja no existe.'); $this->redirect('caja'); }
        $this->view('caja/ver', ['title' => 'Caja #' . $c['id'], 'active' => 'caja', 'c' => $c, 'r' => Caja::resumen($c), 'mov' => Caja::movimientos((int)$c['id'])]);
    }

    public function historial(): void
    {
        $this->requireAuth();
        Caja::ensure();
        $tot = (int)Database::val("SELECT COUNT(*) FROM cajas WHERE estado = 'cerrada'");
        $pg = paginate($tot, 15, $this->pageNum());
        $rows = Database::all("SELECT c.*, u.nombre usuario FROM cajas c LEFT JOIN usuarios u ON u.id = c.usuario_id WHERE c.estado = 'cerrada' ORDER BY c.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}");
        $this->view('caja/historial', ['title' => 'Historial de cajas', 'active' => 'caja', 'rows' => $rows, 'pg' => $pg]);
    }
}
