<?php
require_once ROOT_PATH . '/app/models/Producto.php';

class InventarioController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Producto::ensure();
        $f = ['q' => $this->in('q'), 'cat' => $this->in('cat'), 'stock' => $this->in('stock')];
        $pg = paginate(Producto::contar($f), 15, $this->pageNum());
        $this->view('inventario/index', [
            'title' => 'Inventario', 'active' => 'inventario', 'f' => $f, 'pg' => $pg,
            'rows' => Producto::listar($f, $pg['per'], $pg['offset']), 'res' => Producto::resumen(),
            'cats' => Producto::categorias(), 'nextCode' => Producto::siguienteCodigo(),
        ]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePost('inventario');
        Producto::ensure();
        $id = (int)($_POST['id'] ?? 0);
        $cod = strtoupper(trim((string)($_POST['codigo'] ?? ''))); $nom = trim((string)($_POST['nombre'] ?? ''));
        $cat = trim((string)($_POST['categoria'] ?? '')); $pc = (float)str_replace(',', '.', (string)($_POST['precio_compra'] ?? 0));
        $pv = (float)str_replace(',', '.', (string)($_POST['precio_venta'] ?? 0)); $min = (int)($_POST['stock_minimo'] ?? 0); $stock = (int)($_POST['stock'] ?? 0);
        $err = [];
        if ($nom === '') $err[] = 'El nombre es obligatorio.';
        if (!preg_match('/^[A-Z0-9-]{1,20}$/', $cod)) $err[] = 'El código solo admite letras, números y guion (máx. 20).';
        if ($pv <= 0) $err[] = 'El precio de venta debe ser mayor que 0.';
        if ($pc < 0) $err[] = 'El precio de compra no es válido.';
        if ($min < 0) $err[] = 'El stock mínimo no es válido.';
        if (!$id && $stock < 0) $err[] = 'El stock inicial no puede ser negativo.';
        if (!$err && Database::val('SELECT COUNT(*) FROM productos WHERE codigo = ? AND id <> ?', [$cod, $id])) $err[] = 'Ya existe un producto con ese código.';
        if ($err) { $this->flash('danger', implode(' ', $err)); $this->redirect('inventario'); }
        if ($id) {
            Database::exec('UPDATE productos SET codigo=?, nombre=?, categoria=?, precio_compra=?, precio_venta=?, stock_minimo=? WHERE id=?', [$cod, $nom, $cat ?: null, $pc, $pv, $min, $id]);
            $this->flash('success', 'Producto actualizado.');
        } else {
            $pdo = Database::conn(); $pdo->beginTransaction();
            try {
                Database::exec('INSERT INTO productos (codigo,nombre,categoria,precio_compra,precio_venta,stock,stock_minimo) VALUES (?,?,?,?,?,0,?)', [$cod, $nom, $cat ?: null, $pc, $pv, $min]);
                $pid = (int)$pdo->lastInsertId();
                if ($stock > 0) Producto::mover($pid, 'inicial', $stock, 'Stock inicial');
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
            $this->flash('success', 'Producto registrado.');
        }
        $this->redirect('inventario');
    }

    public function estado($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('inventario');
        Database::exec('UPDATE productos SET estado = 1 - estado WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Estado del producto actualizado.');
        $this->redirect('inventario');
    }

    public function eliminar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('inventario');
        $id = (int)$id;
        if (Database::val('SELECT COUNT(*) FROM venta_detalle WHERE producto_id = ?', [$id])) $this->flash('warning', 'El producto tiene ventas registradas. Puedes desactivarlo en lugar de eliminarlo.');
        else { Database::exec('DELETE FROM productos WHERE id = ?', [$id]); $this->flash('success', 'Producto eliminado.'); }
        $this->redirect('inventario');
    }

    public function ajustar($id = 0): void
    {
        $this->requireAuth();
        $this->requirePost('inventario');
        Producto::ensure();
        $p = Producto::find((int)$id);
        if (!$p) $this->redirect('inventario');
        $tipo = (string)($_POST['tipo'] ?? ''); $cant = (int)($_POST['cantidad'] ?? 0); $motivo = trim((string)($_POST['motivo'] ?? ''));
        $back = $_POST['back'] ?? '';
        $dest = $back === 'kardex' ? 'inventario/kardex/' . $p['id'] : 'inventario';
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) { $this->flash('danger', 'Tipo de movimiento no válido.'); $this->redirect($dest); }
        if ($cant < 0 || ($tipo !== 'ajuste' && $cant < 1) || $cant > 100000) { $this->flash('danger', 'La cantidad no es válida.'); $this->redirect($dest); }
        if ($tipo === 'salida' && $cant > (int)$p['stock']) { $this->flash('danger', 'No puedes sacar más unidades (' . $cant . ') que el stock actual (' . $p['stock'] . ').'); $this->redirect($dest); }
        if ($tipo === 'ajuste' && $motivo === '') { $this->flash('danger', 'Indica el motivo del ajuste.'); $this->redirect($dest); }
        $pdo = Database::conn(); $pdo->beginTransaction();
        try { $n = Producto::mover((int)$p['id'], $tipo, $cant, $motivo ?: ($tipo === 'entrada' ? 'Compra / reposición' : 'Salida manual')); $pdo->commit(); }
        catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        $this->flash('success', 'Movimiento registrado. Stock de «' . $p['nombre'] . '»: ' . $n . '.');
        $this->redirect($dest);
    }

    public function kardex($id = 0): void
    {
        $this->requireAuth();
        $p = Producto::find((int)$id);
        if (!$p) { $this->flash('danger', 'El producto no existe.'); $this->redirect('inventario'); }
        $this->view('inventario/kardex', ['title' => 'Kardex · ' . $p['nombre'], 'active' => 'inventario', 'p' => $p, 'mov' => Producto::kardex((int)$p['id'])]);
    }
}
