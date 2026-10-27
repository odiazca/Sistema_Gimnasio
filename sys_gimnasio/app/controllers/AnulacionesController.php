<?php
require_once ROOT_PATH . '/app/models/Anulacion.php';

/** Registro de anulaciones (solo administrador). Las acciones de anular/congelar viven en Pagos, Ventas y Membresías. */
class AnulacionesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth(); Anulacion::ensure();
        $tipo = in_array($this->in('tipo'), ['pago', 'venta'], true) ? $this->in('tipo') : '';
        $rows = Anulacion::listar($tipo);
        $this->view('anulaciones/index', ['title' => 'Anulaciones', 'active' => 'anulaciones', 'tipo' => $tipo, 'rows' => $rows,
            'total' => array_sum(array_column($rows, 'monto'))]);
    }
}
