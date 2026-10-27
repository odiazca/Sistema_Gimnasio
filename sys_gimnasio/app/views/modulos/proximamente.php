<div class="page-head">
  <div>
    <h1 class="page-title"><?= e($mod[0]) ?></h1>
    <p class="page-sub"><?= e($mod['desc']) ?></p>
  </div>
</div>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-x p-4 p-lg-5 text-center">
      <div class="empty-ico"><i class="bi <?= e($mod[1]) ?>"></i></div>
      <h3 class="fw-bold mt-3">Módulo en preparación</h3>
      <p class="text-muted mx-auto" style="max-width:420px">La estructura de base de datos de este módulo ya está creada. Se desarrollará en la siguiente etapa.</p>
      <a href="<?= url('dashboard') ?>" class="btn btn-brand mt-2"><i class="bi bi-arrow-left"></i> Volver al dashboard</a>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-x p-4 h-100">
      <h6 class="fw-bold mb-3"><i class="bi bi-stars text-brand"></i> Funcionalidades previstas</h6>
      <ul class="feat-list">
        <?php foreach ($mod['features'] as $f): ?><li><i class="bi bi-check2-circle"></i> <?= e($f) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
