<?php /* var: $acts */ ?>
<?php if (!$acts): ?><div class="crm-empty"><i class="bi bi-clock-history"></i><p>Aún no hay actividad registrada.</p></div><?php endif; ?>
<div class="tl"><?php foreach ($acts as $a): $T = Crm::TIPOS_ACT[$a['tipo']] ?? Crm::TIPOS_ACT['nota']; ?>
  <div class="tl-i"><span class="tl-dot" style="--c:<?= $T[2] ?>"><i class="bi <?= $T[1] ?>"></i></span><div class="tl-b">
    <div class="d-flex justify-content-between gap-2"><b><?= $T[0] ?><?= !$a['completada'] ? ' <span class="badge-soft warning">Pendiente · ' . date('d/m H:i', strtotime($a['fecha_programada'])) . '</span>' : '' ?></b>
      <small class="text-muted text-nowrap"><?= date('d/m/Y H:i', strtotime($a['completada_en'] ?: $a['created_at'])) ?></small></div>
    <div><?= e($a['detalle']) ?></div><?php if (!empty($a['usuario'])): ?><small class="text-muted"><i class="bi bi-person"></i> <?= e($a['usuario']) ?></small><?php endif; ?>
    <?php if (!$a['completada']): ?><div><button class="btn btn-soft btn-sm mt-1" data-done="<?= $a['id'] ?>"><i class="bi bi-check2"></i> Marcar hecha</button></div><?php endif; ?></div></div><?php endforeach; ?></div>
