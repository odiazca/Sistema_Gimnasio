<?php $rows = []; foreach ($det as $dia => $ex) foreach ($ex as $x) $rows[] = ['ejercicio_id' => (int)$x['ejercicio_id'], 'dia' => (int)$dia, 'series' => (int)$x['series'], 'reps' => $x['repeticiones'], 'descanso' => (int)$x['descanso_seg']]; ?>
<div class="page-head"><div><a href="<?= url('rutinas') ?>" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Volver a rutinas</a><h1 class="page-title mt-1"><?= e($title) ?></h1></div></div>
<form method="post" action="<?= url('rutinas/guardar') ?>" id="rForm"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
<div class="card-x mb-3"><div class="row g-3">
  <div class="col-md-6"><label class="form-lbl">Nombre *</label><input name="nombre" class="form-control" required maxlength="100" value="<?= v($r ?? [], 'nombre') ?>"></div>
  <div class="col-6 col-md-3"><label class="form-lbl">Objetivo</label><select name="objetivo" class="form-select"><option value="">—</option><?php foreach (Rutina::OBJETIVOS as $o): ?><option <?= ($r['objetivo'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
  <div class="col-6 col-md-3"><label class="form-lbl">Nivel</label><select name="nivel" class="form-select"><option value="">—</option><?php foreach (Rutina::NIVELES as $o): ?><option <?= ($r['nivel'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-lbl">Descripción</label><input name="descripcion" class="form-control" maxlength="255" value="<?= v($r ?? [], 'descripcion') ?>"></div>
</div></div>
<div class="card-x"><div class="d-flex justify-content-between align-items-center mb-3"><h6 class="sec-title mb-0"><i class="bi bi-list-check"></i> Ejercicios</h6><button type="button" class="btn btn-soft btn-sm" id="addEj"><i class="bi bi-plus-lg"></i> Agregar ejercicio</button></div>
  <div class="table-responsive"><table class="table tbl align-middle mb-0"><thead><tr><th style="width:90px">Día</th><th>Ejercicio</th><th style="width:90px">Series</th><th style="width:110px">Repet.</th><th style="width:110px">Desc. (s)</th><th style="width:44px"></th></tr></thead><tbody id="exBody"></tbody></table></div>
  <div class="small text-muted mt-2">Los días agrupan los ejercicios de cada sesión (Día 1, Día 2…). Los ejercicios sin elegir se ignoran.</div>
</div>
<div class="mt-3 d-flex gap-2"><button class="btn btn-brand btn-lg"><i class="bi bi-check2"></i> Guardar rutina</button><a href="<?= url('rutinas') ?>" class="btn btn-outline-secondary btn-lg">Cancelar</a></div>
</form>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var EJ = <?= json_encode($ejs, JSON_UNESCAPED_UNICODE) ?>, DATA = <?= json_encode($rows) ?>, body = document.getElementById('exBody'), n = 0;
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
  function opts(sel) {
    var h = '<option value="">— elegir —</option>', g = '';
    EJ.forEach(function (x) { if (x.grupo !== g) { if (g) h += '</optgroup>'; h += '<optgroup label="' + esc(x.grupo) + '">'; g = x.grupo; } h += '<option value="' + x.id + '"' + (x.id == sel ? ' selected' : '') + '>' + esc(x.nombre) + '</option>'; });
    return h + '</optgroup>';
  }
  function row(d) {
    d = d || { dia: 1, series: 3, reps: '12', descanso: 60 }; var i = n++, tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="number" min="1" max="7" class="form-control form-control-sm" name="ej[' + i + '][dia]" value="' + d.dia + '"></td>' +
      '<td><select class="form-select form-select-sm" name="ej[' + i + '][ejercicio_id]">' + opts(d.ejercicio_id) + '</select></td>' +
      '<td><input type="number" min="1" max="20" class="form-control form-control-sm" name="ej[' + i + '][series]" value="' + d.series + '"></td>' +
      '<td><input class="form-control form-control-sm" maxlength="20" name="ej[' + i + '][reps]" value="' + esc(String(d.reps)) + '"></td>' +
      '<td><input type="number" min="0" max="900" class="form-control form-control-sm" name="ej[' + i + '][descanso]" value="' + (d.descanso || '') + '"></td>' +
      '<td><button type="button" class="btn btn-outline-danger btn-sm" title="Quitar"><i class="bi bi-x-lg"></i></button></td>';
    tr.querySelector('button').onclick = function () { tr.remove(); }; body.appendChild(tr);
  }
  DATA.forEach(row); if (!DATA.length) row();
  document.getElementById('addEj').onclick = function () { var l = body.lastElementChild, d = { dia: 1, series: 3, reps: '12', descanso: 60 }; if (l) d.dia = l.querySelector('input').value || 1; row(d); };
});
</script>
