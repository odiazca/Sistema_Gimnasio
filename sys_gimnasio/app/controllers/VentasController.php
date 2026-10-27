<?php
require_once ROOT_PATH . '/app/models/Producto.php';
require_once ROOT_PATH . '/app/models/Pago.php';

class VentasController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $prods = Database::all('SELECT id, codigo, nombre, categoria, precio_venta precio, stock FROM productos WHERE estado = 1 ORDER BY nombre');
        foreach ($prods as &$p) { $p['id'] = (int)$p['id']; $p['precio'] = (float)$p['precio']; $p['stock'] = (int)$p['stock']; }
        $this->view('ventas/index', [
            'title' => 'Punto de venta', 'active' => 'ventas', 'prods' => $prods, 'cats' => Producto::categorias(),
            'hoy' => Database::row('SELECT COUNT(*) n, COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha) = CURDATE()'),
        ]);
    }

    public function cobrar(): void
    {
        $this->requireAuth();
        $this->requirePost('ventas');
        $items = json_decode((string)($_POST['items'] ?? '[]'), true);
        $metodo = (string)($_POST['metodo'] ?? 'efectivo');
        $socio = (int)($_POST['socio_id'] ?? 0) ?: null;
        if (!is_array($items) || !$items) { $this->flash('danger', 'El carrito está vacío.'); $this->redirect('ventas'); }
        if (!isset(Pago::metodos()[$metodo])) { $this->flash('danger', 'Método de pago no válido.'); $this->redirect('ventas'); }
        if ($socio && !Database::val('SELECT COUNT(*) FROM socios WHERE id = ?', [$socio])) $socio = null;
        // consolidar por producto
        $qty = [];
        foreach ($items as $it) {
            $pid = (int)($it['id'] ?? 0); $q = (int)($it['q'] ?? 0);
            if ($pid < 1 || $q < 1 || $q > 1000) { $this->flash('danger', 'Hay productos con cantidad no válida.'); $this->redirect('ventas'); }
            $qty[$pid] = ($qty[$pid] ?? 0) + $q;
        }
        Producto::ensure();
        $pdo = Database::conn(); $pdo->beginTransaction();
        try {
            $lines = []; $total = 0;
            foreach ($qty as $pid => $q) {
                $p = Database::row('SELECT * FROM productos WHERE id = ? FOR UPDATE', [$pid]);
                if (!$p || !$p['estado']) throw new RuntimeException('Un producto del carrito ya no está disponible.');
                if ((int)$p['stock'] < $q) throw new RuntimeException('Stock insuficiente de «' . $p['nombre'] . '» (disponible: ' . $p['stock'] . ').');
                $lines[] = [$p, $q]; $total += $p['precio_venta'] * $q;
            }
            Database::exec('INSERT INTO ventas (socio_id,total,metodo,fecha,usuario_id) VALUES (?,?,?,NOW(),?)', [$socio, $total, $metodo, Auth::user()['id'] ?? null]);
            $vid = (int)$pdo->lastInsertId();
            foreach ($lines as [$p, $q]) {
                Database::exec('INSERT INTO venta_detalle (venta_id,producto_id,cantidad,precio) VALUES (?,?,?,?)', [$vid, $p['id'], $q, $p['precio_venta']]);
                Producto::mover((int)$p['id'], 'venta', $q, 'Venta N° ' . sprintf('%06d', $vid));
            }
            $pdo->commit();
        } catch (RuntimeException $e) {
            $pdo->rollBack(); $this->flash('danger', $e->getMessage()); $this->redirect('ventas');
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        $this->redirect('ventas/ticket/' . $vid);
    }

    public function anular($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('ventas/historial');
        if (!Permisos::esAdmin()) { $this->flash('danger', 'Solo el administrador puede anular ventas.'); $this->redirect('ventas/historial'); }
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        if (mb_strlen($motivo) < 3) { $this->flash('danger', 'Indica el motivo de la anulación.'); $this->redirect('ventas/historial'); }
        require_once ROOT_PATH . '/app/models/Anulacion.php'; Anulacion::ensure();
        try { $msg = Anulacion::anularVenta((int)$id, mb_substr($motivo, 0, 200), (int)Auth::user()['id']); Bitacora::log('anular_venta', 'Venta N° ' . (int)$id . ': ' . mb_substr($motivo, 0, 120)); $this->flash('success', $msg); }
        catch (RuntimeException $e) { $this->flash('danger', $e->getMessage()); }
        $this->redirect('ventas/historial');
    }

    public function ticket($id = 0): void
    {
        $this->requireAuth();
        $v = Database::row("SELECT v.*, s.nombres, s.apellidos, s.codigo, u.nombre cajero FROM ventas v LEFT JOIN socios s ON s.id = v.socio_id LEFT JOIN usuarios u ON u.id = v.usuario_id WHERE v.id = ?", [(int)$id]);
        if (!$v) { $this->flash('danger', 'La venta no existe.'); $this->redirect('ventas/historial'); }
        $det = Database::all('SELECT d.*, p.nombre FROM venta_detalle d JOIN productos p ON p.id = d.producto_id WHERE d.venta_id = ?', [$v['id']]);
        $cfg = array_column(Database::all('SELECT clave, valor FROM configuracion'), 'valor', 'clave');
        $this->view('ventas/ticket', ['title' => 'Ticket ' . sprintf('%06d', $v['id']), 'v' => $v, 'det' => $det, 'cfg' => $cfg], 'none');
    }

    public function historial(): void
    {
        $this->requireAuth();
        $f = ['desde' => $this->in('desde', date('Y-m-01')), 'hasta' => $this->in('hasta', date('Y-m-d')), 'metodo' => $this->in('metodo')];
        $w = 'DATE(v.fecha) BETWEEN ? AND ?'; $p = [$f['desde'], $f['hasta']];
        if ($f['metodo'] !== '') { $w .= ' AND v.metodo = ?'; $p[] = $f['metodo']; }
        $tot = Database::row("SELECT COUNT(*) n, COALESCE(SUM(v.total),0) t FROM ventas v WHERE $w", $p);
        $pg = paginate((int)$tot['n'], 15, $this->pageNum());
        $rows = Database::all("SELECT v.*, s.nombres, s.apellidos, u.nombre cajero,
                (SELECT COALESCE(SUM(d.cantidad),0) FROM venta_detalle d WHERE d.venta_id = v.id) unidades
             FROM ventas v LEFT JOIN socios s ON s.id = v.socio_id LEFT JOIN usuarios u ON u.id = v.usuario_id
             WHERE $w ORDER BY v.fecha DESC, v.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
        $top = Database::all("SELECT p.nombre, SUM(d.cantidad) u, SUM(d.cantidad * d.precio) t FROM venta_detalle d JOIN ventas v ON v.id = d.venta_id JOIN productos p ON p.id = d.producto_id
             WHERE $w GROUP BY p.id, p.nombre ORDER BY u DESC LIMIT 5", $p);
        $this->view('ventas/historial', ['title' => 'Historial de ventas', 'active' => 'ventas', 'f' => $f, 'tot' => $tot, 'pg' => $pg, 'rows' => $rows, 'top' => $top]);
    }
}
