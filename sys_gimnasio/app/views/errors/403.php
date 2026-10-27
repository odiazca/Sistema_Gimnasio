<?php $m = Permisos::primerModulo(); ?>
<div class="text-center py-5">
  <div class="display-1 fw-bold text-gradient">403</div>
  <h4 class="mb-2">Acceso restringido</h4>
  <p class="text-muted">Tu rol no tiene permiso para entrar a este módulo. Si lo necesitas, pide al administrador que lo habilite.</p>
  <?php if ($m): ?><a href="<?= url($m) ?>" class="btn btn-brand"><i class="bi bi-house"></i> Ir a un módulo permitido</a><?php endif; ?>
</div>
