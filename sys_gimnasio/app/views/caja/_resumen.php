<?php $metIc = ['efectivo' => 'bi-cash', 'tarjeta' => 'bi-credit-card', 'yape' => 'bi-phone', 'plin' => 'bi-phone', 'transferencia' => 'bi-bank']; ?>
<div class="row g-3 mb-3 kpi-grid">
  <?php foreach ([
    ['Efectivo esperado', money($r['esperado']), 'bi-cash-coin', '#10b981', '#059669', 'inicial ' . money($r['inicial'])],
    ['Total cobrado', money($r['total_cobrado']), 'bi-wallet2', '#6366f1', '#4f46e5', 'todos los métodos'],
    ['Membresías', money($r['membresias']), 'bi-card-checklist', '#f59e0b', '#d97706', 'cobros del turno'],
    ['Tienda', money($r['tienda']), 'bi-bag-fill', '#ec4899', '#db2777', 'ventas del turno'],
  ] as $k): ?>
  <div class="col-6 col-xl-3"><div class="stat-card" style="--c1:<?= $k[3] ?>;--c2:<?= $k[4] ?>"><div class="stat-body">
    <div class="stat-val"><?= $k[1] ?></div><div class="stat-lbl"><?= $k[0] ?></div><div class="stat-delta"><span class="delta"><?= $k[5] ?></span></div><i class="bi <?= $k[2] ?> stat-ghost"></i></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card-x mb-3 py-3"><div class="small fw-bold mb-2">Cobrado por método</div><div class="row g-2">
  <?php foreach (Pago::metodos() as $k => $l): $t = $r['metodos'][$k]; $pc = $r['total_cobrado'] > 0 ? round($t / $r['total_cobrado'] * 100) : 0; ?>
    <div class="col-6 col-md"><div class="met-box"><i class="bi <?= $metIc[$k] ?>"></i><b><?= money($t) ?></b><span><?= $l ?> · <?= $pc ?>%</span><div class="progress-x"><span style="width:<?= $pc ?>%"></span></div></div></div>
  <?php endforeach; ?></div>
  <div class="small text-muted mt-3">Efectivo esperado = inicial <?= money($r['inicial']) ?> + cobros en efectivo <?= money($r['metodos']['efectivo']) ?> + ingresos <?= money($r['ingresos']) ?> − egresos <?= money($r['egresos']) ?>.</div>
</div>
