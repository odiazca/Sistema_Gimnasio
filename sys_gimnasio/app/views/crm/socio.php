<?php include __DIR__ . '/_nav.php';
$nom = $s['nombres'] . ' ' . $s['apellidos']; $nivel = $riesgo['nivel'] ?? 'bajo'; $pt = $riesgo['puntaje'] ?? 0;
$act = Crm::actividadesDe('socio', (int)$s['id'], 30); ?>
<div class="page-head"><div class="d-flex align-items-center gap-2 text-muted"><a href="<?= url('crm/retencion') ?>" class="link-brand"><i class="bi bi-arrow-left"></i> Retención</a> / <span>Ficha 360° · <?= e($s['codigo']) ?></span></div>
  <a href="<?= url('socios/ver/' . $s['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-vcard"></i> Ficha completa del socio</a></div>
<section class="lead-hero" style="--c:#ff5a1f"><div class="d-flex flex-wrap align-items-center gap-3 position-relative">
  <?php if ($s['foto']): ?><img src="<?= e(foto_url($s['foto'])) ?>" class="profile-photo" style="width:72px;height:72px" alt=""><?php else: ?><span class="avatar lg"><?= e(iniciales($s['nombres'], $s['apellidos'])) ?></span><?php endif; ?>
  <div class="flex-grow-1"><h2 class="m-0"><?= e($nom) ?></h2><div class="opacity-75 small mt-1"><?= e($s['codigo']) ?> · Socio desde <?= fdate($s['fecha_registro']) ?><?= $s['objetivo'] ? ' · ' . e($s['objetivo']) : '' ?></div></div>
  <div class="d-flex flex-wrap gap-2"><?php if ($s['telefono']): ?><a class="btn btn-light btn-pill" target="_blank" rel="noopener" href="<?= e(wa_link($s['telefono'], 'Hola ' . $s['nombres'] . ' 👋')) ?>"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?>
    <a class="btn btn-glass btn-pill" href="<?= url('membresias/nueva?socio=' . $s['id']) ?>"><i class="bi bi-arrow-repeat"></i> Renovar</a><a class="btn btn-glass btn-pill" href="<?= url('pagos/nuevo?socio=' . $s['id']) ?>"><i class="bi bi-cash-stack"></i> Cobrar</a></div></div></section>
<div class="row g-3 g-xl-4 mt-0">
  <div class="col-lg-4">
    <div class="card-x mb-3 text-center"><h6 class="sec-title text-start"><i class="bi bi-heart-pulse-fill"></i> Riesgo de abandono</h6>
      <div class="risk-gauge <?= $nivel ?>" style="--p:<?= $pt ?>"><div><b><?= $pt ?></b><small><?= $riesgo ? ucfirst($nivel) : 'Sin plan reciente' ?></small></div></div>
      <?php if ($riesgo && $riesgo['motivos']): ?><div class="mt-3 text-start"><?php foreach ($riesgo['motivos'] as $m): ?><div class="small mb-1"><i class="bi bi-exclamation-circle text-warning"></i> <?= e($m) ?></div><?php endforeach; ?></div>
      <?php else: ?><p class="small text-muted mt-3 mb-0">Sin señales de alerta. Mantén el contacto.</p><?php endif; ?></div>
    <div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-graph-up"></i> Resumen del socio</h6><dl class="dl-crm">
      <dt>Visitas totales</dt><dd><?= (int)($rfm['visitas'] ?? 0) ?></dd><dt>Últimos 30 días</dt><dd><?= (int)($rfm['v30'] ?? 0) ?> visitas</dd><dt>Membresías</dt><dd><?= (int)($rfm['n_mem'] ?? 0) ?></dd>
      <dt>Pagado en membresías</dt><dd><?= money($rfm['pagos'] ?? 0) ?></dd><dt>Compras en tienda</dt><dd><?= money($rfm['compras'] ?? 0) ?></dd><dt>Valor total</dt><dd><b><?= money(($rfm['pagos'] ?? 0) + ($rfm['compras'] ?? 0)) ?></b></dd></dl></div>
    <?php if ($pend): ?><div class="card-x"><h6 class="sec-title"><i class="bi bi-alarm"></i> Seguimientos pendientes</h6><?php foreach ($pend as $a): ?><div class="task-mini"><div class="flex-grow-1 small"><b><?= e($a['detalle']) ?></b></div><span class="badge-soft info"><?= date('d/m H:i', strtotime($a['fecha_programada'])) ?></span><button class="icon-sm" data-done="<?= $a['id'] ?>" title="Hecha"><i class="bi bi-check2"></i></button></div><?php endforeach; ?></div><?php endif; ?>
  </div>
  <div class="col-lg-8">
    <div class="card-x mb-3"><h6 class="sec-title"><i class="bi bi-plus-circle-fill"></i> Registrar contacto o programar seguimiento</h6><?php $af_lead = 0; $af_socio = $s['id']; include __DIR__ . '/_actividad_form.php'; ?></div>
    <div class="card-x"><h6 class="sec-title"><i class="bi bi-clock-history"></i> Línea de tiempo 360°</h6>
      <?php if (!$linea): ?><div class="crm-empty"><i class="bi bi-clock-history"></i><p>Sin movimientos todavía.</p></div><?php endif; ?>
      <div class="tl"><?php foreach ($linea as $ev): ?><div class="tl-i"><span class="tl-dot" style="--c:<?= $ev['c'] ?>"><i class="bi <?= $ev['ico'] ?>"></i></span><div class="tl-b">
        <div class="d-flex justify-content-between gap-2"><b><?= e($ev['t']) ?></b><small class="text-muted text-nowrap"><?= date('d/m/Y' . (strlen((string)$ev['f']) > 10 ? ' H:i' : ''), strtotime($ev['f'])) ?></small></div>
        <?php if ($ev['d']): ?><div class="small"><?= e($ev['d']) ?></div><?php endif; ?><?php if (!empty($ev['u'])): ?><small class="text-muted"><i class="bi bi-person"></i> <?= e($ev['u']) ?></small><?php endif; ?></div></div><?php endforeach; ?></div></div>
  </div>
</div>
<script>window.CRM = { base: <?= json_encode(rtrim(url(''), '/')) ?> };</script>
<script src="<?= ASSETS_URL ?>/js/crm.js?v=<?= APP_VERSION ?>"></script>
