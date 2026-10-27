<?php include __DIR__ . '/_nav.php'; $porEtapa = []; foreach ($leads as $l) $porEtapa[$l['etapa']][] = $l; $keys = array_keys(Crm::ETAPAS); ?>
<div class="page-head">
  <div><h1 class="page-title">Embudo de prospectos</h1><p class="page-sub">Arrastra las tarjetas entre etapas (o usa las flechas en el celular).</p></div>
  <div class="d-flex gap-2 flex-wrap align-items-center">
    <form class="d-flex gap-2 flex-wrap" method="get" action="<?= url('crm/embudo') ?>">
      <input class="form-control" style="width:190px" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar prospecto…">
      <select class="form-select" style="width:170px" name="fuente" onchange="this.form.submit()"><option value="">Todas las fuentes</option><?php foreach (Crm::FUENTES as $k => $x): ?><option value="<?= $k ?>" <?= $f['fuente'] === $k ? 'selected' : '' ?>><?= $x[0] ?></option><?php endforeach; ?></select>
      <select class="form-select" style="width:170px" name="resp" onchange="this.form.submit()"><option value="">Todos los responsables</option><?php foreach ($usuarios as $u): ?><option value="<?= $u['id'] ?>" <?= (int)$f['resp'] === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nombre']) ?></option><?php endforeach; ?></select>
    </form>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#leadModal"><i class="bi bi-person-plus-fill"></i> Nuevo</button>
  </div>
</div>
<div class="kanban" id="kanban">
<?php foreach (Crm::ETAPAS as $k => $e): $items = $porEtapa[$k] ?? []; ?>
  <section class="kan-col" data-etapa="<?= $k ?>" style="--c:<?= $e[2] ?>">
    <header><span class="kan-t"><i class="bi <?= $e[1] ?>"></i> <?= $e[0] ?></span><span class="kan-n" data-n><?= count($items) ?></span></header>
    <div class="kan-sum small text-muted" data-sum><?= array_sum(array_column($items, 'valor')) > 0 ? money(array_sum(array_column($items, 'valor'))) : '&nbsp;' ?></div>
    <div class="kan-body" data-drop>
      <?php foreach ($items as $l): $nom = trim($l['nombres'] . ' ' . $l['apellidos']); $F = Crm::FUENTES[$l['fuente']] ?? Crm::FUENTES['otro'];
        $venc = $l['proxima'] && strtotime($l['proxima']) < time(); $idx = array_search($k, $keys); ?>
        <article class="kan-card" draggable="true" data-id="<?= $l['id'] ?>" data-nombre="<?= e($nom) ?>" data-valor="<?= (float)$l['valor'] ?>">
          <div class="d-flex gap-2 align-items-start"><span class="avatar sm" style="--c:<?= $e[2] ?>"><?= e(iniciales($l['nombres'], $l['apellidos'])) ?></span>
            <div class="min-w-0 flex-grow-1"><a href="<?= url('crm/lead/' . $l['id']) ?>" class="kan-name"><?= e($nom) ?></a>
              <div class="small text-muted text-truncate"><i class="bi <?= $F[1] ?>"></i> <?= e($F[0]) ?><?= $l['interes'] ? ' · ' . e($l['interes']) : '' ?></div></div></div>
          <div class="kan-meta">
            <?php if ($l['valor'] > 0): ?><span class="badge-soft success"><?= money($l['valor']) ?></span><?php endif; ?>
            <?php if ($l['proxima']): ?><span class="badge-soft <?= $venc ? 'danger' : 'info' ?>" title="Próxima acción"><i class="bi bi-alarm"></i> <?= date('d/m H:i', strtotime($l['proxima'])) ?></span>
            <?php elseif (!in_array($k, ['ganado', 'perdido'], true) && $l['dias_sin_contacto'] >= 3): ?><span class="badge-soft warning" title="Sin contacto"><i class="bi bi-hourglass-split"></i> <?= (int)$l['dias_sin_contacto'] ?> d sin contacto</span><?php endif; ?>
            <?php if ($k === 'perdido' && $l['motivo_perdida']): ?><span class="badge-soft secondary"><?= e($l['motivo_perdida']) ?></span><?php endif; ?>
          </div>
          <div class="kan-foot">
            <?php if ($l['telefono']): ?><a class="icon-sm wa" target="_blank" rel="noopener" title="WhatsApp" href="<?= e(wa_link($l['telefono'], 'Hola ' . $l['nombres'] . ', te escribimos de ' . cfg('gimnasio_nombre', APP_NAME) . ' 👋')) ?>"><i class="bi bi-whatsapp"></i></a><a class="icon-sm" title="Llamar" href="tel:<?= e($l['telefono']) ?>"><i class="bi bi-telephone"></i></a><?php endif; ?>
            <span class="ms-auto d-flex gap-1"><?php if ($idx > 0 && $k !== 'perdido'): ?><button class="icon-sm" data-mv="<?= $keys[$idx - 1] ?>" title="Etapa anterior"><i class="bi bi-chevron-left"></i></button><?php endif; ?>
              <?php if ($idx < 4): ?><button class="icon-sm" data-mv="<?= $keys[$idx + 1] ?>" title="Siguiente etapa"><i class="bi bi-chevron-right"></i></button><?php endif; ?>
              <?php if ($k === 'negociacion'): ?><button class="icon-sm" data-mv="ganado" title="Ganado: registrar socio"><i class="bi bi-trophy"></i></button><?php endif; ?></span>
          </div></article>
      <?php endforeach; ?>
      <?php if (!$items): ?><div class="kan-empty">Arrastra aquí</div><?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>
</div>
<p class="small text-muted mt-2">Los ganados y perdidos se muestran de los últimos 30 días. Al soltar una tarjeta en «Ganado» se abre el registro del socio con sus datos ya cargados.</p>

<div class="modal fade" id="lostModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content modal-x p-3">
  <h6 class="fw-bold mb-2"><i class="bi bi-x-octagon text-secondary"></i> ¿Por qué se perdió?</h6><p class="small text-muted" id="lostName"></p>
  <select id="lostMotivo" class="form-select mb-3"><?php foreach (Crm::MOTIVOS as $m): ?><option><?= e($m) ?></option><?php endforeach; ?></select>
  <div class="d-flex gap-2"><button class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal" id="lostCancel">Cancelar</button><button class="btn btn-brand flex-fill" id="lostOk">Marcar perdido</button></div></div></div></div>
<?php include __DIR__ . '/_lead_modal.php'; ?>
<script>window.CRM = { base: <?= json_encode(rtrim(url(''), '/')) ?>, moneda: <?= json_encode(APP_CURRENCY) ?> };</script>
<script src="<?= ASSETS_URL ?>/js/crm.js?v=<?= APP_VERSION ?>"></script>
