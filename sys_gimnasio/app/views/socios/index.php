<div class="page-head">
  <div><h1 class="page-title">Socios</h1><p class="page-sub">Gestiona los miembros de tu gimnasio y su estado de membresía.</p></div>
  <a href="<?= url('socios/crear') ?>" class="btn btn-brand"><i class="bi bi-person-plus-fill"></i> Nuevo socio</a>
</div>

<div class="row g-3 mb-3 kpi-grid">
  <?php foreach ([
    ['Total de socios', (int)($res['total'] ?? 0), 'bi-people-fill', '#6366f1', ''],
    ['Con membresía vigente', (int)($res['vigentes'] ?? 0), 'bi-patch-check-fill', '#10b981', 'vigente'],
    ['Membresía vencida', (int)($res['vencidos'] ?? 0), 'bi-exclamation-octagon-fill', '#ef4444', 'vencida'],
    ['Sin membresía', (int)($res['sin_mem'] ?? 0), 'bi-person-dash-fill', '#f59e0b', 'sin'],
  ] as [$l, $n, $ic, $c, $mem]): ?>
  <div class="col-6 col-xl-3">
    <a href="<?= url('socios') . ($mem ? '?mem=' . $mem : '') ?>" class="mini mini-link">
      <i class="bi <?= $ic ?>" style="--c:<?= $c ?>"></i><div><b><?= $n ?></b><span><?= $l ?></span></div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<div class="card-x p-0">
  <form class="filter-bar" method="get" action="<?= url('socios') ?>">
    <div class="input-ico flex-fill" style="min-width:220px">
      <i class="bi bi-search"></i>
      <input type="search" name="q" class="form-control" placeholder="Buscar por nombre, DNI, código o teléfono…" value="<?= e($f['q']) ?>">
    </div>
    <select name="estado" class="form-select w-auto" onchange="this.form.submit()">
      <option value="">Todos los estados</option>
      <?php foreach (['activo' => 'Activos', 'inactivo' => 'Inactivos', 'suspendido' => 'Suspendidos'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= $f['estado'] === $k ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <select name="mem" class="form-select w-auto" onchange="this.form.submit()">
      <option value="">Toda membresía</option>
      <?php foreach (['vigente' => 'Vigente', 'porvencer' => 'Por vencer (7 días)', 'vencida' => 'Vencida', 'sin' => 'Sin membresía'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= $f['mem'] === $k ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-soft"><i class="bi bi-funnel"></i> Filtrar</button>
    <?php if ($f['q'] || $f['estado'] || $f['mem']): ?><a href="<?= url('socios') ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a><?php endif; ?>
  </form>

  <div class="table-responsive">
    <table class="table tbl align-middle mb-0 tbl-pad">
      <thead><tr><th>Socio</th><th class="d-none d-lg-table-cell">Documento</th><th class="d-none d-md-table-cell">Contacto</th><th>Membresía</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php if (!$socios): ?>
        <tr><td colspan="6"><div class="empty-state"><i class="bi bi-search"></i><b>No se encontraron socios</b><span>Prueba con otros filtros o registra un nuevo socio.</span></div></td></tr>
      <?php endif; ?>
      <?php foreach ($socios as $s):
        [$lbl, $cls] = estado_membresia($s['fecha_inicio'], $s['fecha_fin']);
        $dr = dias_restantes($s['fecha_fin']); ?>
        <tr>
          <td>
            <a href="<?= url('socios/ver/' . $s['id']) ?>" class="d-flex align-items-center gap-3 text-reset">
              <?php if ($s['foto']): ?><img src="<?= e(foto_url($s['foto'])) ?>" class="avatar-img" alt="">
              <?php else: ?><span class="avatar"><?= e(iniciales($s['nombres'], $s['apellidos'])) ?></span><?php endif; ?>
              <div class="lh-sm"><b><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></b><br><small class="text-muted"><?= e($s['codigo']) ?></small></div>
            </a>
          </td>
          <td class="d-none d-lg-table-cell"><?= e($s['dni'] ?: '—') ?></td>
          <td class="d-none d-md-table-cell"><?= e($s['telefono'] ?: '—') ?><br><small class="text-muted"><?= e($s['email'] ?: '') ?></small></td>
          <td>
            <?php if ($s['plan']): ?>
              <span class="tag" style="--c:<?= e($s['plan_color']) ?>"><?= e($s['plan']) ?></span>
              <div class="mt-1"><span class="badge-soft <?= $cls ?>"><?= $lbl ?></span>
                <small class="text-muted ms-1"><?= $dr >= 0 ? 'vence ' . fdate($s['fecha_fin'], 'd/m') : 'venció ' . fdate($s['fecha_fin'], 'd/m/y') ?></small></div>
            <?php else: ?><span class="badge-soft secondary">Sin membresía</span><?php endif; ?>
          </td>
          <td><span class="badge-soft <?= ['activo' => 'success', 'inactivo' => 'secondary', 'suspendido' => 'warning'][$s['estado']] ?>"><?= ucfirst($s['estado']) ?></span></td>
          <td class="text-end text-nowrap">
            <a class="icon-sm" title="Ver ficha" href="<?= url('socios/ver/' . $s['id']) ?>"><i class="bi bi-eye"></i></a>
            <a class="icon-sm" title="Renovar / inscribir" href="<?= url('membresias/nueva?socio=' . $s['id']) ?>"><i class="bi bi-arrow-repeat"></i></a>
            <a class="icon-sm" title="Editar" href="<?= url('socios/editar/' . $s['id']) ?>"><i class="bi bi-pencil"></i></a>
            <?php if ($s['telefono']): ?><a class="icon-sm wa" title="WhatsApp" target="_blank" rel="noopener" href="<?= e(wa_link($s['telefono'])) ?>"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-foot">
    <small class="text-muted">Mostrando <?= $socios ? $pg['offset'] + 1 : 0 ?>–<?= $pg['offset'] + count($socios) ?> de <?= $pg['total'] ?> socios</small>
    <?= pager($pg) ?>
  </div>
</div>
