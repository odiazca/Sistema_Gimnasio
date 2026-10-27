<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · <?= APP_NAME ?></title>
<style>
  *{box-sizing:border-box}body{margin:0;background:#eef1f6;font-family:'Segoe UI',system-ui,sans-serif;color:#111;padding:24px}
  .bar{max-width:380px;margin:0 auto 14px;display:flex;gap:8px}
  .bar a,.bar button{flex:1;text-align:center;padding:10px;border-radius:10px;border:0;font-weight:600;cursor:pointer;text-decoration:none;font-size:14px}
  .bar .p{background:linear-gradient(135deg,#ff5a1f,#ff8a3d);color:#fff}.bar .s{background:#fff;color:#333;border:1px solid #d7dce5}
  .tk{max-width:380px;margin:0 auto;background:#fff;border-radius:14px;padding:26px 24px;box-shadow:0 10px 30px -12px rgba(0,0,0,.25)}
  .c{text-align:center}.logo{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#ff5a1f,#ffb347);color:#fff;font-size:24px;display:inline-grid;place-items:center}
  h1{font-size:19px;margin:8px 0 2px}.m{color:#667;font-size:12px;line-height:1.5}
  hr{border:0;border-top:1px dashed #b8c0cf;margin:14px 0}
  .r{display:flex;justify-content:space-between;gap:10px;font-size:13.5px;padding:3px 0}.r span:first-child{color:#667}.r b{text-align:right}
  table{width:100%;font-size:13px;border-collapse:collapse}th{text-align:left;color:#667;font-weight:600;padding:3px 0}td{padding:4px 0;vertical-align:top}.n{text-align:right}
  .tot{display:flex;justify-content:space-between;align-items:center;background:#fff4ee;border-radius:10px;padding:12px 14px;margin-top:8px}
  .tot span{font-weight:600}.tot b{font-size:22px;color:#e2490f}
  .ok{display:inline-block;background:#e7f8f1;color:#087f5b;font-weight:700;font-size:12px;padding:4px 12px;border-radius:99px;margin-top:8px}
  .f{text-align:center;color:#8a93a6;font-size:11.5px;margin-top:6px}
  @media print{body{background:#fff;padding:0}.bar{display:none}.tk{box-shadow:none;border-radius:0;max-width:100%}}
</style></head>
<body>
<div class="bar"><button class="p" onclick="window.print()">🖨 Imprimir</button><a class="s" href="<?= url('ventas') ?>">Nueva venta</a><a class="s" href="<?= url('ventas/historial') ?>">Historial</a></div>
<div class="tk">
  <div class="c"><span class="logo">⚡</span><h1><?= e($cfg['gimnasio_nombre'] ?? APP_NAME) ?></h1>
    <div class="m"><?= e($cfg['gimnasio_direccion'] ?? '') ?><br>Tel. <?= e($cfg['gimnasio_telefono'] ?? '') ?><?= !empty($cfg['razon_social']) ? e($cfg['razon_social']) . '<br>' : '' ?><?= !empty($cfg['gimnasio_ruc']) ? '<br>RUC ' . e($cfg['gimnasio_ruc']) : '' ?></div>
    <div class="ok">TICKET DE VENTA N° <?= sprintf('%06d', $v['id']) ?></div></div>
  <hr>
  <div class="r"><span>Fecha</span><b><?= fdate($v['fecha'], 'd/m/Y H:i') ?></b></div>
  <div class="r"><span>Cliente</span><b><?= $v['nombres'] ? e($v['nombres'] . ' ' . $v['apellidos']) : 'Cliente libre' ?></b></div>
  <hr>
  <table><thead><tr><th>Cant.</th><th>Producto</th><th class="n">P.U.</th><th class="n">Importe</th></tr></thead><tbody>
  <?php foreach ($det as $d): ?><tr><td><?= (int)$d['cantidad'] ?></td><td><?= e($d['nombre']) ?></td><td class="n"><?= fmt_num($d['precio']) ?></td><td class="n"><?= fmt_num($d['precio'] * $d['cantidad']) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <div class="tot"><span>Total</span><b><?= money($v['total']) ?></b></div>
  <div class="r" style="margin-top:8px"><span>Método</span><b><?= e(ucfirst($v['metodo'])) ?></b></div>
  <hr>
  <div class="r"><span>Atendido por</span><b><?= e($v['cajero'] ?? '—') ?></b></div>
  <div class="f"><?= e($cfg['pie_recibo'] ?? '') !== '' ? e($cfg['pie_recibo']) : '¡Gracias por tu compra!' ?><br>Este ticket no es un comprobante de pago electrónico.</div>
</div>
</body></html>
