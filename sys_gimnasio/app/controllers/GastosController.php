<?php
require_once ROOT_PATH . '/app/models/Caja.php';

class GastosController extends Controller
{
    private static function cats(): array
    {
        $base = ['Fijos', 'Personal', 'Servicios', 'Operativos', 'Marketing', 'Mantenimiento', 'Compras', 'Impuestos', 'Otros'];
        $db = array_column(Database::all("SELECT DISTINCT categoria FROM gastos WHERE categoria IS NOT NULL AND categoria <> ''"), 'categoria');
        $all = array_values(array_unique(array_merge($base, $db))); sort($all); return $all;
    }

    public function index(): void
    {
        $this->requireAuth();
        $f = ['desde' => $this->in('desde', date('Y-m-01')), 'hasta' => $this->in('hasta', date('Y-m-d')), 'cat' => $this->in('cat'), 'q' => $this->in('q')];
        $w = 'fecha BETWEEN ? AND ?'; $p = [$f['desde'], $f['hasta']];
        if ($f['cat'] !== '') { $w .= ' AND categoria = ?'; $p[] = $f['cat']; }
        if ($f['q'] !== '') { $w .= ' AND concepto LIKE ?'; $p[] = '%' . $f['q'] . '%'; }
        $tot = Database::row("SELECT COUNT(*) n, COALESCE(SUM(monto),0) t FROM gastos WHERE $w", $p);
        $pg = paginate((int)$tot['n'], 15, $this->pageNum());
        $rows = Database::all("SELECT g.*, u.nombre usuario FROM gastos g LEFT JOIN usuarios u ON u.id = g.usuario_id WHERE " . str_replace(['fecha', 'categoria', 'concepto'], ['g.fecha', 'g.categoria', 'g.concepto'], $w) . " ORDER BY g.fecha DESC, g.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
        $porCat = Database::all("SELECT COALESCE(categoria,'Sin categoría') cat, SUM(monto) t FROM gastos WHERE $w GROUP BY cat ORDER BY t DESC", $p);
        $ing = (float)Database::val('SELECT COALESCE(SUM(monto),0) FROM pagos WHERE DATE(fecha_pago) BETWEEN ? AND ?', [$f['desde'], $f['hasta']])
             + (float)Database::val('SELECT COALESCE(SUM(total),0) FROM ventas WHERE DATE(fecha) BETWEEN ? AND ?', [$f['desde'], $f['hasta']]);
        $this->view('gastos/index', [
            'title' => 'Gastos', 'active' => 'gastos', 'f' => $f, 'tot' => $tot, 'pg' => $pg, 'rows' => $rows, 'porCat' => $porCat,
            'ing' => $ing, 'cats' => self::cats(), 'cajaAbierta' => (bool)Caja::abierta(),
        ]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('gastos');
        Caja::ensure();
        $id = (int)($_POST['id'] ?? 0); $conc = trim((string)($_POST['concepto'] ?? '')); $cat = trim((string)($_POST['categoria'] ?? ''));
        $monto = round((float)str_replace(',', '.', (string)($_POST['monto'] ?? 0)), 2); $fecha = (string)($_POST['fecha'] ?? '');
        $d = DateTime::createFromFormat('Y-m-d', $fecha); $err = [];
        if ($conc === '') $err[] = 'El concepto es obligatorio.';
        if ($monto <= 0 || $monto > 10000000) $err[] = 'El monto debe ser mayor a 0.';
        if (!$d || $d->format('Y-m-d') !== $fecha) $err[] = 'La fecha no es válida.';
        elseif ($fecha > date('Y-m-d')) $err[] = 'La fecha no puede ser futura.';
        $caja = (!$id && !empty($_POST['de_caja'])) ? Caja::abierta() : null;
        if ($caja && !$err) { $r = Caja::resumen($caja); if ($monto > $r['esperado'] + 0.001) $err[] = 'El monto supera el efectivo disponible en caja (' . money($r['esperado']) . ').'; }
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect('gastos'); }
        if ($id) {
            Database::exec('UPDATE gastos SET concepto=?, categoria=?, monto=?, fecha=? WHERE id=?', [$conc, $cat ?: null, $monto, $fecha, $id]);
            $this->flash('success', 'Gasto actualizado.');
        } else {
            $pdo = Database::conn(); $pdo->beginTransaction();
            try {
                Database::exec('INSERT INTO gastos (concepto,categoria,monto,fecha,usuario_id) VALUES (?,?,?,?,?)', [$conc, $cat ?: null, $monto, $fecha, Auth::user()['id'] ?? null]);
                if ($caja) Database::exec("INSERT INTO caja_movimientos (caja_id,tipo,concepto,monto,fecha,usuario_id) VALUES (?, 'egreso', ?, ?, NOW(), ?)", [$caja['id'], 'Gasto: ' . mb_substr($conc, 0, 135), $monto, Auth::user()['id'] ?? null]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
            $this->flash('success', 'Gasto registrado' . ($caja ? ' y descontado de la caja abierta.' : '.'));
        }
        $this->redirect('gastos');
    }

    public function eliminar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('gastos');
        Database::exec('DELETE FROM gastos WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Gasto eliminado.');
        $this->redirect('gastos');
    }
}
