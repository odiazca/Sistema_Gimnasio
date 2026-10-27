<link href="<?= ASSETS_URL ?>/css/crm.css?v=<?= APP_VERSION ?>" rel="stylesheet">
<form id="csrfBox" class="d-none"><?= csrf_field() ?></form>
<div class="crm-nav mb-3"><div class="seg">
  <a href="<?= url('crm') ?>" class="<?= $crm_tab === 'panel' ? 'on' : '' ?>"><i class="bi bi-speedometer2"></i> Panel</a>
  <a href="<?= url('crm/embudo') ?>" class="<?= $crm_tab === 'embudo' ? 'on' : '' ?>"><i class="bi bi-kanban-fill"></i> Embudo</a>
  <a href="<?= url('crm/tareas') ?>" class="<?= $crm_tab === 'tareas' ? 'on' : '' ?>"><i class="bi bi-check2-square"></i> Tareas<?php if ($crm_tareas): ?> <span class="cnt bad"><?= (int)$crm_tareas ?></span><?php endif; ?></a>
  <a href="<?= url('crm/retencion') ?>" class="<?= $crm_tab === 'retencion' ? 'on' : '' ?>"><i class="bi bi-heart-pulse-fill"></i> Retención</a>
  <a href="<?= url('crm/campanas') ?>" class="<?= $crm_tab === 'campanas' ? 'on' : '' ?>"><i class="bi bi-megaphone-fill"></i> Campañas</a>
</div></div>
