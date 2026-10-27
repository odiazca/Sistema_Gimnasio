<?php include __DIR__ . '/_nav.php'; ?>
<div class="page-head"><div><h1 class="page-title">Tareas y seguimientos</h1><p class="page-sub">Llamadas, mensajes y visitas pendientes con prospectos y socios.</p></div>
  <div class="seg"><a href="<?= url('crm/tareas') ?>" class="<?= $est === 'pendientes' ? 'on' : '' ?>">Pendientes <?php if ($cnt['pendientes']): ?><span class="cnt"><?= $cnt['pendientes'] ?></span><?php endif; ?></a><a href="<?= url('crm/tareas?est=hechas') ?>" class="<?= $est === 'hechas' ? 'on' : '' ?>">Completadas</a></div></div>
<?php if ($est === 'pendientes'): ?><div class="row g-3 mb-3">
  <div class="col-4"><div class="mini-kpi bad"><b><?= $cnt['vencidas'] ?></b><span>Vencidas</span></div></div><div class="col-4"><div class="mini-kpi warn"><b><?= $cnt['hoy'] ?></b><span>Para hoy</span></div></div><div class="col-4"><div class="mini-kpi"><b><?= $cnt['pendientes'] ?></b><span>Pendientes</span></div></div></div><?php endif; ?>
<div class="card-x p-0"><?php if (!$rows): ?><div class="crm-empty py-5"><i class="bi bi-check2-circle"></i><p><?= $est === 'pendientes' ? 'No tienes tareas pendientes. ¡Buen trabajo!' : 'Aún no hay tareas completadas.' ?></p></div><?php endif; ?>
  <?php foreach ($rows as $t): $T = Crm::TIPOS_ACT[$t['tipo']] ?? Crm::TIPOS_ACT['tarea'];
    $esLead = (bool)$t['l_nom']; $nom = trim($esLead ? $t['l_nom'] . ' ' . $t['l_ape'] : $t['s_nom'] . ' ' . $t['s_ape']); $tel = $esLead ? $t['l_tel'] : $t['s_tel'];
    $link = $esLead ? url('crm/lead/' . $t['lead_id']) : url('crm/socio/' . $t['socio_id']); $venc = !$t['completada'] && strtotime($t['fecha_programada']) < time(); ?>
    <div class="task-row <?= $t['completada'] ? 'done' : '' ?>" id="task<?= $t['id'] ?>">
      <?php if (!$t['completada']): ?><button class="task-check" data-done="<?= $t['id'] ?>" title="Marcar hecha"><i class="bi bi-check-lg"></i></button><?php else: ?><span class="task-check ok"><i class="bi bi-check-lg"></i></span><?php endif; ?>
      <span class="task-ic" style="--c:<?= $T[2] ?>"><i class="bi <?= $T[1] ?>"></i></span>
      <div class="flex-grow-1 min-w-0"><div class="fw-semibold"><?= e($t['detalle']) ?></div><a href="<?= $link ?>" class="small text-muted text-decoration-none"><i class="bi <?= $esLead ? 'bi-bullseye' : 'bi-person' ?>"></i> <?= e($nom) ?> <span class="badge-soft secondary"><?= $esLead ? 'Prospecto' : 'Socio' ?></span></a></div>
      <span class="badge-soft <?= $t['completada'] ? 'success' : ($venc ? 'danger' : 'info') ?> text-nowrap"><?= $t['completada'] ? 'Hecha ' . date('d/m H:i', strtotime($t['completada_en'])) : ($venc ? 'Vencida · ' : '') . date('d/m H:i', strtotime($t['fecha_programada'])) ?></span>
      <?php if ($tel && !$t['completada']): ?><a class="icon-sm wa" target="_blank" rel="noopener" href="<?= e(wa_link($tel)) ?>" title="WhatsApp"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
    </div><?php endforeach; ?></div>
<script>window.CRM = { base: <?= json_encode(rtrim(url(''), '/')) ?> };</script>
<script src="<?= ASSETS_URL ?>/js/crm.js?v=<?= APP_VERSION ?>"></script>
