<?php include __DIR__ . '/_nav.php'; $S = Crm::SEGS[$c['segmento']] ?? ['—', 'bi-megaphone', '#64748b']; $env_n = count(array_filter($env, fn($e) => $e['enviado_en'])); $pct = $c['total'] ? round($env_n / $c['total'] * 100) : 0; ?>
<div class="page-head"><div class="d-flex align-items-center gap-2 text-muted"><a href="<?= url('crm/campanas') ?>" class="link-brand"><i class="bi bi-arrow-left"></i> Campañas</a> / <span><?= e($c['nombre']) ?></span></div>
  <form method="post" action="<?= url('crm/eliminarCampana/' . $c['id']) ?>" onsubmit="return confirm('¿Eliminar la campaña y su lista de envíos?')"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Eliminar</button></form></div>
<div class="camp-hero" style="--c:<?= $S[2] ?>"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative">
  <div><span class="badge-soft on-dark"><i class="bi <?= $S[1] ?>"></i> <?= e($S[0]) ?></span><h3 class="mt-2 mb-1"><?= e($c['nombre']) ?></h3><div class="opacity-75 small"><?= fdate($c['created_at'], 'd/m/Y H:i') ?></div></div>
  <div class="text-end"><div class="display-6 fw-bold" id="campCount"><?= $env_n ?>/<?= (int)$c['total'] ?></div><div class="small opacity-75">mensajes enviados</div></div></div>
  <div class="camp-prog"><span id="campBar" style="width:<?= $pct ?>%"></span></div></div>
<div class="card-x mt-3 mb-3"><div class="small fw-bold mb-1">Mensaje base</div><div class="small text-muted" style="white-space:pre-line"><?= e($c['mensaje']) ?></div></div>
<div class="card-x p-0"><div class="table-responsive"><table class="table tbl align-middle mb-0 tbl-pad"><thead><tr><th>Destinatario</th><th>Estado</th><th class="text-end">Enviar</th></tr></thead><tbody>
  <?php foreach ($env as $e): ?><tr><td><b><?= e($e['nombre']) ?></b><div class="small text-muted"><?= e($e['telefono']) ?></div></td>
    <td class="env-st"><?= $e['enviado_en'] ? '<span class="badge-soft success"><i class="bi bi-check2"></i> Enviado ' . fdate($e['enviado_en'], 'd/m H:i') . '</span>' : '<span class="badge-soft secondary">Pendiente</span>' ?></td>
    <td class="text-end"><a href="<?= e(wa_link($e['telefono'], $e['mensaje'])) ?>" target="_blank" rel="noopener" class="btn btn-sm <?= $e['enviado_en'] ? 'btn-outline-secondary' : 'btn-brand' ?> camp-send" data-env="<?= $e['id'] ?>"><i class="bi bi-whatsapp"></i> <?= $e['enviado_en'] ? 'Reenviar' : 'WhatsApp' ?></a></td></tr><?php endforeach; ?></tbody></table></div></div>
<p class="small text-muted mt-2">WhatsApp se abre con el mensaje ya personalizado; tú confirmas el envío. Al hacer clic el destinatario queda marcado y se anota en su historial del CRM.</p>
<script>window.CRM = { base: <?= json_encode(rtrim(url(''), '/')) ?>, total: <?= (int)$c['total'] ?>, enviados: <?= $env_n ?> };</script>
<script src="<?= ASSETS_URL ?>/js/crm.js?v=<?= APP_VERSION ?>"></script>
