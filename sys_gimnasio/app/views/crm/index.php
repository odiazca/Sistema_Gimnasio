<?php include __DIR__ . '/_nav.php';
$kp = [
  ['Prospectos abiertos', $r['abiertos'], '#3b82f6', '#60a5fa', 'bi-bullseye', 'Ver embudo', 'crm/embudo', $r['nuevos_mes'] . ' nuevos este mes'],
  ['Valor en el embudo', money($r['valor']), '#ff5a1f', '#ff8a3d', 'bi-cash-coin', 'Ver negociaciones', 'crm/embudo', 'Potencial de ventas'],
  ['Conversión (90 días)', $r['conversion'] . '%', '#10b981', '#34d399', 'bi-trophy-fill', 'Ver ganados', 'crm/embudo', $r['ganados_mes'] . ' ganados este mes'],
  ['Socios en riesgo alto', $n_riesgo, '#ef4444', '#f87171', 'bi-heart-pulse-fill', 'Actuar ahora', 'crm/retencion?nivel=alto', 'Puntaje de abandono ≥ 60'],
]; ?>
<div class="page-head">
  <div><h1 class="page-title">CRM</h1><p class="page-sub">Capta prospectos, da seguimiento y retén a tus socios con datos reales del gimnasio.</p></div>
  <div class="d-flex gap-2 flex-wrap"><button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#leadModal"><i class="bi bi-person-plus-fill"></i> Nuevo prospecto</button>
    <a href="<?= url('crm/campanas') ?>" class="btn btn-outline-secondary"><i class="bi bi-megaphone-fill"></i> Nueva campaña</a></div>
</div>
<div class="row g-3 g-xl-4 kpi-grid">
  <?php foreach ($kp as $k): ?><div class="col-6 col-xl-3"><div class="stat-card" style="--c1:<?= $k[2] ?>;--c2:<?= $k[3] ?>"><div class="stat-body">
    <div class="stat-val" style="font-size:<?= strlen((string)$k[1]) > 8 ? '1.6rem' : '2.1rem' ?>"><?= e((string)$k[1]) ?></div><div class="stat-lbl"><?= $k[0] ?></div><div class="stat-delta"><span class="small opacity-75"><?= $k[7] ?></span></div><i class="bi <?= $k[4] ?> stat-ghost"></i></div>
    <a href="<?= url($k[6]) ?>" class="stat-foot"><span><?= $k[5] ?></span><i class="bi bi-arrow-right-circle"></i></a></div></div><?php endforeach; ?>
</div>

<div class="row g-3 g-xl-4 mt-0">
  <div class="col-lg-7"><div class="card-x h-100"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="sec-title mb-0"><i class="bi bi-funnel-fill"></i> Embudo de ventas</h6><a href="<?= url('crm/embudo') ?>" class="link-brand small">Abrir tablero</a></div>
    <?php $maxN = max(1, max(array_column($embudo, 'n'))); foreach (Crm::ETAPAS as $k => $e): if ($k === 'perdido') continue; $n = $embudo[$k]['n']; ?>
      <div class="fun-row"><div class="fun-lbl"><i class="bi <?= $e[1] ?>" style="color:<?= $e[2] ?>"></i> <?= $e[0] ?></div>
        <div class="fun-bar"><span style="width:<?= max(4, round($n / $maxN * 100)) ?>%;background:<?= $e[2] ?>"><?= $n ?></span></div>
        <div class="fun-val"><?= $embudo[$k]['valor'] > 0 ? money($embudo[$k]['valor']) : '—' ?></div></div>
    <?php endforeach; ?>
    <div class="small text-muted mt-2"><i class="bi bi-x-octagon"></i> Perdidos: <b><?= $embudo['perdido']['n'] ?></b><?php if ($motivos): ?> · principal motivo: <b><?= e($motivos[0]['motivo']) ?></b><?php endif; ?></div>
  </div></div>
  <div class="col-lg-5"><div class="card-x h-100"><h6 class="sec-title"><i class="bi bi-pie-chart-fill"></i> Origen de prospectos</h6>
    <?php if ($fuentes): ?><div style="height:230px"><canvas id="chFuentes"></canvas></div><?php else: ?><div class="crm-empty"><i class="bi bi-bullseye"></i><p>Aún no hay prospectos. Registra el primero para ver de dónde vienen.</p></div><?php endif; ?></div></div>
</div>

<div class="row g-3 g-xl-4 mt-0">
  <div class="col-lg-7"><div class="card-x h-100"><h6 class="sec-title"><i class="bi bi-graph-up-arrow"></i> Prospectos vs. ganados (6 meses)</h6><div style="height:240px"><canvas id="chTend"></canvas></div></div></div>
  <div class="col-lg-5"><div class="card-x h-100"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="sec-title mb-0"><i class="bi bi-check2-square"></i> Próximas tareas</h6><a href="<?= url('crm/tareas') ?>" class="link-brand small">Ver todas</a></div>
    <?php if (!$tareas): ?><div class="crm-empty"><i class="bi bi-check2-circle"></i><p>Sin tareas pendientes. ¡Todo al día!</p></div><?php endif; ?>
    <?php foreach ($tareas as $t): $T = Crm::TIPOS_ACT[$t['tipo']] ?? Crm::TIPOS_ACT['tarea']; $venc = strtotime($t['fecha_programada']) < time(); $nom = $t['l_nom'] ? $t['l_nom'] . ' ' . $t['l_ape'] : $t['s_nom'] . ' ' . $t['s_ape']; ?>
      <div class="task-mini"><span class="task-ic" style="--c:<?= $T[2] ?>"><i class="bi <?= $T[1] ?>"></i></span><div class="flex-grow-1 min-w-0"><div class="fw-semibold text-truncate"><?= e($t['detalle']) ?></div><div class="small text-muted text-truncate"><?= e(trim($nom)) ?></div></div>
        <span class="badge-soft <?= $venc ? 'danger' : 'info' ?>"><?= date('d/m H:i', strtotime($t['fecha_programada'])) ?></span></div>
    <?php endforeach; ?></div></div>
</div>

<div class="card-x mt-3 mt-xl-4"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="sec-title mb-0"><i class="bi bi-heart-pulse-fill text-danger"></i> Socios que necesitan atención</h6><a href="<?= url('crm/retencion') ?>" class="link-brand small">Ver todos</a></div>
  <?php if (!$riesgo): ?><div class="crm-empty"><i class="bi bi-emoji-smile"></i><p>Nadie en riesgo por ahora. ¡Buena retención!</p></div><?php endif; ?>
  <div class="row g-3"><?php foreach ($riesgo as $x): ?><div class="col-md-6 col-xl-4"><a class="risk-card" href="<?= url('crm/socio/' . $x['id']) ?>">
    <div class="risk-score <?= $x['nivel'] ?>"><b><?= $x['puntaje'] ?></b><small>riesgo</small></div>
    <div class="min-w-0"><div class="fw-bold text-truncate"><?= e($x['nombres'] . ' ' . $x['apellidos']) ?></div><div class="small text-muted text-truncate"><?= e($x['plan']) ?></div><div class="small mt-1"><?= e(implode(' · ', array_slice($x['motivos'], 0, 2))) ?></div></div></a></div><?php endforeach; ?></div></div>

<?php include __DIR__ . '/_lead_modal.php'; ?>
<script src="<?= ASSETS_URL ?>/vendor/chart.umd.min.js"></script>
<script>window.CRM = <?= json_encode(['fuentes' => array_map(fn($f) => ['l' => Crm::FUENTES[$f['fuente']][0] ?? $f['fuente'], 'n' => (int)$f['n']], $fuentes), 'tend' => $tend], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="<?= ASSETS_URL ?>/js/crm.js?v=<?= APP_VERSION ?>"></script>
