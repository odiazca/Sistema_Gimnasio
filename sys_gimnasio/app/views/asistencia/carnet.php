<?php [$lbl] = $m ? estado_membresia($m['fecha_inicio'], $m['fecha_fin'], $m['estado']) : ['Sin membresía']; ?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · <?= APP_NAME ?></title>
<style>
  *{box-sizing:border-box}body{margin:0;background:#eef1f6;font-family:'Segoe UI',system-ui,sans-serif;padding:24px;color:#fff}
  .bar{max-width:640px;margin:0 auto 18px;display:flex;gap:8px;flex-wrap:wrap}
  .bar a,.bar button{padding:10px 18px;border-radius:10px;border:0;font-weight:600;cursor:pointer;text-decoration:none;font-size:14px}
  .bar .p{background:linear-gradient(135deg,#ff5a1f,#ff8a3d);color:#fff}.bar .s{background:#fff;color:#333;border:1px solid #d7dce5}
  .wrap{display:flex;gap:20px;flex-wrap:wrap;justify-content:center}
  .card{width:340px;height:215px;border-radius:16px;position:relative;overflow:hidden;box-shadow:0 14px 34px -14px rgba(0,0,0,.45);-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .front{background:linear-gradient(135deg,#0f172a,#1b1030 60%,#3a1305)}
  .front::before{content:"";position:absolute;right:-60px;top:-60px;width:210px;height:210px;border-radius:50%;background:radial-gradient(circle,rgba(255,90,31,.7),transparent 70%)}
  .brand{position:absolute;left:18px;top:16px;display:flex;align-items:center;gap:9px;font-weight:800;font-size:16px}
  .brand i{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#ff5a1f,#ffb347);display:grid;place-items:center;font-style:normal;font-size:16px}
  .brand b{color:#ff7a3d}
  .ph{position:absolute;left:18px;bottom:18px;width:84px;height:84px;border-radius:18px;object-fit:cover;border:2px solid rgba(255,255,255,.3);background:linear-gradient(135deg,#ff5a1f,#ffb347);display:grid;place-items:center;font-weight:800;font-size:28px}
  .info{position:absolute;left:116px;bottom:20px;right:14px}
  .nm{font-size:17px;font-weight:800;line-height:1.15;margin-bottom:5px}.cd{font-size:12px;color:#ffb089;letter-spacing:.08em;font-weight:700}
  .pl{font-size:11px;opacity:.75;margin-top:6px}
  .back{background:#fff;color:#111;display:flex;align-items:center;gap:14px;padding:18px}
  .back::before{content:"";position:absolute;left:0;right:0;top:0;height:8px;background:linear-gradient(90deg,#ff5a1f,#ffb347)}
  .qr{width:140px;height:140px;flex:none;margin-top:6px}.qr svg{width:100%;height:100%}
  .bt{font-size:10.5px;line-height:1.5;color:#445}.bt b{display:block;font-size:12px;color:#111;margin-bottom:4px}
  @media print{body{background:#fff;padding:0}.bar{display:none}.card{box-shadow:none;border:1px solid #ccc}}
</style></head><body>
<div class="bar"><button class="p" onclick="window.print()">🖨 Imprimir carnet</button><a class="s" href="<?= url('socios/ver/' . $s['id']) ?>">Volver a la ficha</a></div>
<div class="wrap">
  <div class="card front">
    <div class="brand"><i>⚡</i><span>Fit<b>Core</b> Gym</span></div>
    <?php if ($s['foto']): ?><img class="ph" src="<?= e(foto_url($s['foto'])) ?>" alt="">
    <?php else: ?><div class="ph"><?= e(iniciales($s['nombres'], $s['apellidos'])) ?></div><?php endif; ?>
    <div class="info"><div class="nm"><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></div><div class="cd"><?= e($s['codigo']) ?></div>
      <div class="pl"><?= $m ? e($m['plan']) . ' · vence ' . fdate($m['fecha_fin']) : 'Sin membresía' ?></div></div>
  </div>
  <div class="card back">
    <div class="qr" id="qr"></div>
    <div class="bt"><b><?= e($cfg['gimnasio_nombre'] ?? APP_NAME) ?></b>Presenta este código QR en recepción para registrar tu ingreso.<br><br>Carnet personal e intransferible.<br><?= e($cfg['gimnasio_telefono'] ?? '') ?></div>
  </div>
</div>
<script src="<?= ASSETS_URL ?>/vendor/qrcode.min.js"></script>
<script>
  var q = qrcode(0, 'M'); q.addData(<?= json_encode($s['codigo']) ?>); q.make();
  document.getElementById('qr').innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
</script>
</body></html>
