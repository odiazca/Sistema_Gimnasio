<?php
$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fecha = ucfirst($dias[(int)date('w')]) . ', ' . date('j') . ' de ' . $meses[(int)date('n') - 1] . ' de ' . date('Y');
$delta = function ($v) {
    if ($v === null) return '<span class="delta flat">—</span>';
    $cls = $v >= 0 ? 'up' : 'down'; $ic = $v >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
    return '<span class="delta ' . $cls . '"><i class="bi ' . $ic . '"></i>' . abs($v) . '%</span>';
};
$ocup = $k['aforo'] > 0 ? min(100, round($k['en_gym'] / $k['aforo'] * 100)) : 0;
$metodoIc = ['efectivo' => 'bi-cash', 'tarjeta' => 'bi-credit-card', 'yape' => 'bi-phone', 'plin' => 'bi-phone', 'transferencia' => 'bi-bank'];
$fmtFin = function ($f) { $d = (new DateTime('today'))->diff(new DateTime($f))->days; return $d === 0 ? ['Hoy', 'danger'] : ($d === 1 ? ['Mañana', 'danger'] : ["En $d días", $d <= 3 ? 'warning' : 'info']); };
?>
<!-- Hero -->
<section class="hero-card">
  <div class="hero-card-bg"></div>
  <div class="position-relative">
    <p class="mb-1 hero-date"><i class="bi bi-calendar3"></i> <?= $fecha ?></p>
    <h1><?= $saludo ?>, <?= e(explode(' ', Auth::user()['nombre'])[0]) ?> 👋</h1>
    <p class="hero-sub">
      Hoy han ingresado <b><?= $k['asis_hoy'] ?></b> socios
      <?php if ($k['por_vencer']): ?>y <b><?= $k['por_vencer'] ?></b> membresías vencen esta semana<?php endif; ?>.
    </p>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <a href="<?= url('socios/crear') ?>" class="btn btn-light btn-pill"><i class="bi bi-person-plus-fill"></i> Nuevo socio</a>
      <a href="<?= url('pagos/nuevo') ?>" class="btn btn-glass btn-pill"><i class="bi bi-cash-stack"></i> Registrar pago</a>
      <a href="<?= url('modulo/index/ventas') ?>" class="btn btn-glass btn-pill"><i class="bi bi-cart-plus"></i> Nueva venta</a>
    </div>
  </div>
  <div class="hero-occ">
    <div class="occ-ring" style="--p:<?= $ocup ?>"><div><b><?= $k['en_gym'] ?></b><small>en el gym</small></div></div>
    <small class="d-block text-center mt-2 opacity-75">Aforo <?= $ocup ?>% de <?= $k['aforo'] ?></small>
  </div>
</section>

<!-- KPIs -->
<div class="row g-3 g-xl-4 kpi-grid">
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="--c1:#ff5a1f;--c2:#ff8a3d">
      <div class="stat-body">
        <div class="stat-val" data-count="<?= $k['activos'] ?>">0</div>
        <div class="stat-lbl">Socios activos</div>
        <div class="stat-delta"><?= $delta($k['activos_delta']) ?></div>
        <i class="bi bi-people-fill stat-ghost"></i>
      </div>
      <a href="<?= url('socios') ?>" class="stat-foot"><span>Ver socios</span><i class="bi bi-arrow-right-circle"></i></a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="--c1:#10b981;--c2:#059669">
      <div class="stat-body">
        <div class="stat-val" data-count="<?= round($k['ingresos']) ?>" data-prefix="<?= APP_CURRENCY ?> ">0</div>
        <div class="stat-lbl">Ingresos del mes</div>
        <div class="stat-delta"><?= $delta($k['ingresos_delta']) ?></div>
        <i class="bi bi-wallet2 stat-ghost"></i>
      </div>
      <a href="<?= url('pagos') ?>" class="stat-foot"><span>Ver pagos</span><i class="bi bi-arrow-right-circle"></i></a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="--c1:#6366f1;--c2:#4f46e5">
      <div class="stat-body">
        <div class="stat-val" data-count="<?= $k['asis_hoy'] ?>">0</div>
        <div class="stat-lbl">Asistencias de hoy</div>
        <div class="stat-delta"><?= $delta($k['asis_delta']) ?></div>
        <i class="bi bi-door-open-fill stat-ghost"></i>
      </div>
      <a href="<?= url('asistencia') ?>" class="stat-foot"><span>Control de asistencia</span><i class="bi bi-arrow-right-circle"></i></a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="--c1:#f59e0b;--c2:#d97706">
      <div class="stat-body">
        <div class="stat-val" data-count="<?= $k['por_vencer'] ?>">0</div>
        <div class="stat-lbl">Membresías por vencer (7 días)</div>
        <div class="stat-delta"><span class="delta"><?= $k['vencidos'] ?> vencidas</span></div>
        <i class="bi bi-hourglass-split stat-ghost"></i>
      </div>
      <a href="<?= url('membresias?vista=porvencer') ?>" class="stat-foot"><span>Ver membresías</span><i class="bi bi-arrow-right-circle"></i></a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="--c1:#06b6d4;--c2:#0891b2">
      <div class="stat-body">
        <div class="stat-val" data-count="<?= $k['nuevos'] ?>">0</div>
        <div class="stat-lbl">Altas del mes</div>
        <div class="stat-delta"><?= $delta($k['nuevos_delta']) ?></div>
        <i class="bi bi-person-plus-fill stat-ghost"></i>
      </div>
      <a href="<?= url('socios') ?>" class="stat-foot"><span>Ver socios</span><i class="bi bi-arrow-right-circle"></i></a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="--c1:#14b8a6;--c2:#0d9488">
      <div class="stat-body">
        <div class="stat-val" data-count="<?= round($k['utilidad']) ?>" data-prefix="<?= APP_CURRENCY ?> ">0</div>
        <div class="stat-lbl">Utilidad del mes</div>
        <div class="stat-delta"></div>
        <i class="bi bi-graph-up-arrow stat-ghost"></i>
      </div>
      <a href="<?= url('modulo/index/reportes') ?>" class="stat-foot"><span>Ver reportes</span><i class="bi bi-arrow-right-circle"></i></a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="--c1:#ef4444;--c2:#dc2626">
      <div class="stat-body">
        <div class="stat-val" data-count="<?= round($k['gastos']) ?>" data-prefix="<?= APP_CURRENCY ?> ">0</div>
        <div class="stat-lbl">Gastos del mes</div>
        <div class="stat-delta"></div>
        <i class="bi bi-receipt-cutoff stat-ghost"></i>
      </div>
      <a href="<?= url('modulo/index/gastos') ?>" class="stat-foot"><span>Ver gastos</span><i class="bi bi-arrow-right-circle"></i></a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="--c1:#ec4899;--c2:#db2777">
      <div class="stat-body">
        <div class="stat-val" data-count="<?= $k['stock_bajo'] ?>">0</div>
        <div class="stat-lbl">Productos con stock bajo</div>
        <div class="stat-delta"></div>
        <i class="bi bi-box-seam-fill stat-ghost"></i>
      </div>
      <a href="<?= url('modulo/index/inventario') ?>" class="stat-foot"><span>Ver inventario</span><i class="bi bi-arrow-right-circle"></i></a>
    </div>
  </div>
</div>

<!-- Gráficos fila 1 -->
<div class="row g-3 g-xl-4 mt-0">
  <div class="col-xl-8">
    <div class="card-x">
      <div class="card-h">
        <div><h6>Ingresos vs gastos</h6><small>Últimos 12 meses · <?= APP_CURRENCY ?></small></div>
        <div class="legend"><span><i style="background:var(--brand)"></i>Membresías</span><span><i style="background:#6366f1"></i>Tienda</span><span><i style="background:#ef4444"></i>Gastos</span></div>
      </div>
      <div class="chart-box" style="height:320px"><canvas id="chFinanzas"></canvas></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>Socios por plan</h6><small>Membresías vigentes</small></div></div>
      <div class="chart-box donut-box"><canvas id="chPlanes"></canvas><div class="donut-center"><b id="donutTotal">0</b><small>vigentes</small></div></div>
      <div class="plan-legend" id="planLegend"></div>
    </div>
  </div>
</div>

<!-- Gráficos fila 2 -->
<div class="row g-3 g-xl-4 mt-0">
  <div class="col-lg-6 col-xl-4">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>Asistencia diaria</h6><small>Últimos 14 días</small></div></div>
      <div class="chart-box" style="height:250px"><canvas id="chAsistencia"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6 col-xl-4">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>Horas pico</h6><small>Promedio de ingresos por hora</small></div></div>
      <div class="chart-box" style="height:250px"><canvas id="chHoras"></canvas></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>Altas de socios</h6><small>Nuevas inscripciones por mes</small></div></div>
      <div class="chart-box" style="height:250px"><canvas id="chAltas"></canvas></div>
    </div>
  </div>
</div>

<!-- Tablas -->
<div class="row g-3 g-xl-4 mt-0">
  <div class="col-xl-8">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>Próximos vencimientos</h6><small>Socios que deben renovar en 7 días</small></div>
        <a href="<?= url('membresias?vista=porvencer') ?>" class="btn btn-soft btn-sm">Ver todo</a></div>
      <div class="table-responsive">
        <table class="table tbl align-middle mb-0">
          <thead><tr><th>Socio</th><th>Plan</th><th>Vence</th><th class="text-end">Acción</th></tr></thead>
          <tbody>
          <?php if (!$venc): ?><tr><td colspan="4" class="text-center text-muted py-4">Sin vencimientos próximos 🎉</td></tr><?php endif; ?>
          <?php foreach ($venc as $v): [$txt, $cl] = $fmtFin($v['fecha_fin']);
            $ini = mb_strtoupper(mb_substr($v['nombres'], 0, 1) . mb_substr($v['apellidos'], 0, 1)); ?>
            <tr>
              <td><div class="d-flex align-items-center gap-2"><span class="avatar sm" style="--c:<?= e($v['color']) ?>"><?= e($ini) ?></span>
                <div class="lh-sm"><b><?= e($v['nombres'] . ' ' . $v['apellidos']) ?></b><br><small class="text-muted"><?= e($v['codigo']) ?></small></div></div></td>
              <td><span class="tag" style="--c:<?= e($v['color']) ?>"><?= e($v['plan']) ?></span></td>
              <td><span class="badge-soft <?= $cl ?>"><?= $txt ?></span> <small class="text-muted d-none d-md-inline ms-1"><?= date('d/m', strtotime($v['fecha_fin'])) ?></small></td>
              <td class="text-end text-nowrap">
                <a class="icon-sm wa" title="WhatsApp" target="_blank" rel="noopener" href="https://wa.me/51<?= preg_replace('/\D/', '', $v['telefono']) ?>?text=<?= rawurlencode('Hola ' . $v['nombres'] . ', tu membresía en ' . APP_NAME . ' vence pronto. ¡Renueva y sigue entrenando!') ?>"><i class="bi bi-whatsapp"></i></a>
                <a class="icon-sm" title="Renovar" href="<?= url('membresias/nueva?socio=' . $v['id']) ?>"><i class="bi bi-arrow-repeat"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>Clases de hoy</h6><small>Reservas y cupos</small></div></div>
      <div class="class-list">
        <?php if (!$clases): ?><p class="text-muted text-center py-4 mb-0">No hay clases programadas hoy.</p><?php endif; ?>
        <?php foreach ($clases as $c): $pct = $c['cupo'] ? min(100, round($c['reservas'] / $c['cupo'] * 100)) : 0; ?>
          <div class="class-item" style="--c:<?= e($c['color']) ?>">
            <div class="class-time"><b><?= substr($c['hora_inicio'], 0, 5) ?></b><small><?= substr($c['hora_fin'], 0, 5) ?></small></div>
            <div class="flex-fill">
              <div class="d-flex justify-content-between"><b><?= e($c['nombre']) ?></b><small class="text-muted"><?= (int)$c['reservas'] ?>/<?= (int)$c['cupo'] ?></small></div>
              <small class="text-muted"><?= e($c['entrenador']) ?> · <?= e($c['sala']) ?></small>
              <div class="progress-x"><span style="width:<?= $pct ?>%"></span></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 g-xl-4 mt-0">
  <div class="col-xl-5">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>Últimos ingresos</h6><small>Control de acceso en vivo</small></div></div>
      <ul class="feed">
        <?php foreach ($ingresos as $a): $ini = mb_strtoupper(mb_substr($a['nombres'], 0, 1) . mb_substr($a['apellidos'], 0, 1)); ?>
          <li><span class="avatar sm"><?= e($ini) ?></span>
            <div class="flex-fill lh-sm"><b><?= e($a['nombres'] . ' ' . $a['apellidos']) ?></b><br><small class="text-muted"><?= e($a['codigo']) ?> · vía <?= e($a['metodo']) ?></small></div>
            <div class="text-end lh-sm"><b><?= date('H:i', strtotime($a['ingreso'])) ?></b><br><small class="<?= $a['salida'] ? 'text-muted' : 'text-success' ?>"><?= $a['salida'] ? 'salió' : '● dentro' ?></small></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-xl-7">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>Últimos pagos</h6><small>Cobros recientes de membresías</small></div>
        <a href="<?= url('pagos') ?>" class="btn btn-soft btn-sm">Ver todo</a></div>
      <ul class="feed">
        <?php foreach ($pagos as $p): ?>
          <li><span class="pay-ico"><i class="bi <?= $metodoIc[$p['metodo']] ?? 'bi-cash' ?>"></i></span>
            <div class="flex-fill lh-sm"><b><?= e($p['nombres'] . ' ' . $p['apellidos']) ?></b><br><small class="text-muted"><?= e($p['concepto']) ?> · <?= ucfirst(e($p['metodo'])) ?></small></div>
            <div class="text-end lh-sm"><b class="text-success">+ <?= money($p['monto']) ?></b><br><small class="text-muted"><?= date('d/m H:i', strtotime($p['fecha_pago'])) ?></small></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<div class="row g-3 g-xl-4 mt-0 mb-2">
  <div class="col-md-6">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>🎂 Cumpleaños de la semana</h6><small>Envía un saludo y fideliza</small></div></div>
      <ul class="feed">
        <?php if (!$cumples): ?><li class="text-muted justify-content-center py-3">Sin cumpleaños próximos</li><?php endif; ?>
        <?php foreach ($cumples as $c): ?>
          <li><span class="avatar sm" style="--c:#ec4899"><i class="bi bi-gift-fill"></i></span>
            <div class="flex-fill lh-sm"><b><?= e($c['nombres'] . ' ' . $c['apellidos']) ?></b><br><small class="text-muted"><?= date('d/m', strtotime($c['fecha_nacimiento'])) ?></small></div>
            <span class="badge-soft <?= $c['dias'] == 0 ? 'success' : 'info' ?>"><?= $c['dias'] == 0 ? '¡Hoy!' : 'En ' . $c['dias'] . ' d' ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card-x h-100">
      <div class="card-h"><div><h6>📦 Stock bajo</h6><small>Productos por reponer</small></div>
        <a href="<?= url('modulo/index/inventario') ?>" class="btn btn-soft btn-sm">Inventario</a></div>
      <ul class="feed">
        <?php if (!$stock): ?><li class="text-muted justify-content-center py-3">Stock en orden ✔</li><?php endif; ?>
        <?php foreach ($stock as $s): $pc = $s['stock_minimo'] ? min(100, round($s['stock'] / $s['stock_minimo'] * 100)) : 0; ?>
          <li><span class="pay-ico" style="--c:#ef4444"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="flex-fill lh-sm"><b><?= e($s['nombre']) ?></b><div class="progress-x danger"><span style="width:<?= $pc ?>%"></span></div></div>
            <div class="text-end lh-sm"><b><?= (int)$s['stock'] ?></b><br><small class="text-muted">mín. <?= (int)$s['stock_minimo'] ?></small></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
