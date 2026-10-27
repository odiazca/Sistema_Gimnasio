<?php
require_once ROOT_PATH . '/app/models/Dashboard.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        require_once ROOT_PATH . '/app/models/Backup.php';
        if (Permisos::esAdmin()) Backup::auto();
        $charts = [
            'finanzas'   => Dashboard::finanzas(),
            'asistencia' => Dashboard::asistenciaDiaria(),
            'horas'      => Dashboard::afluenciaHoras(),
            'planes'     => Dashboard::distribucionPlanes(),
            'altas'      => Dashboard::altas(),
            'spark'      => Dashboard::spark(),
        ];
        $this->view('dashboard/index', [
            'title'   => 'Dashboard',
            'active'  => 'dashboard',
            'k'       => Dashboard::kpis(),
            'charts'  => $charts,
            'venc'    => Dashboard::proximosVencimientos(),
            'pagos'   => Dashboard::ultimosPagos(),
            'ingresos'=> Dashboard::ultimosIngresos(),
            'clases'  => Dashboard::clasesHoy(),
            'cumples' => Dashboard::cumpleanos(),
            'stock'   => Dashboard::stockBajo(),
        ]);
    }
}
