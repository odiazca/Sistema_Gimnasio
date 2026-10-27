<?php /* Modales compartidos: anular (pago/venta) y congelar membresía. Botones: [data-anular] y [data-congelar] */ ?>
<div class="modal fade" id="anularModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" id="anularForm"><?= csrf_field() ?>
  <input type="hidden" name="volver" id="anularVolver">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-danger"><i class="bi bi-slash-circle-fill"></i> <span id="anularTitulo">Anular</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="small text-muted" id="anularInfo"></p><label class="form-lbl">Motivo de la anulación *</label>
    <textarea name="motivo" class="form-control" rows="3" maxlength="200" required placeholder="Ej: monto mal digitado, cobro duplicado, devolución del cliente…"></textarea>
    <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Esta acción no se puede deshacer. Quedará registrada en <b>Anulaciones</b> con tu nombre.</div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger">Anular</button></div></form></div></div>

<div class="modal fade" id="congelarModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content modal-x" method="post" id="congelarForm"><?= csrf_field() ?>
  <input type="hidden" name="volver" id="congelarVolver">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold"><i class="bi bi-snow2 text-info"></i> Congelar membresía</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="small text-muted" id="congelarInfo"></p><label class="form-lbl">Motivo *</label>
    <input name="motivo" class="form-control" maxlength="200" required placeholder="Viaje, lesión, enfermedad…">
    <div class="alert alert-info small mt-3 mb-0"><i class="bi bi-info-circle-fill"></i> Mientras esté congelada <b>no podrá ingresar</b> al gimnasio. Al reactivarla se suman los días congelados a su fecha de fin.</div></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Congelar</button></div></form></div></div>
<script>
document.addEventListener('click', function (e) {
  var a = e.target.closest('[data-anular]'), c = e.target.closest('[data-congelar]');
  if (a) { e.preventDefault(); var f = document.getElementById('anularForm'); f.action = a.dataset.anular; document.getElementById('anularVolver').value = a.dataset.volver || '';
    document.getElementById('anularTitulo').textContent = a.dataset.titulo || 'Anular'; document.getElementById('anularInfo').textContent = a.dataset.info || ''; f.querySelector('textarea').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('anularModal')).show(); }
  if (c) { e.preventDefault(); var g = document.getElementById('congelarForm'); g.action = c.dataset.congelar; document.getElementById('congelarVolver').value = c.dataset.volver || '';
    document.getElementById('congelarInfo').textContent = c.dataset.info || ''; g.querySelector('input[name=motivo]').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('congelarModal')).show(); }
});
</script>
