<?php
$menu = require ROOT_PATH . '/app/menu.php';
$user = Auth::user();
$iniciales = mb_strtoupper(mb_substr($user['nombre'], 0, 1) . (mb_substr(strstr($user['nombre'], ' ') ?: ' ', 1, 1)));
?><!doctype html>
<html lang="es" data-theme="light" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Panel') ?> · <?= APP_NAME ?></title>
<script>try{var t=localStorage.getItem('fc-theme');if(!t){var d=<?= json_encode(cfg('tema_defecto', 'claro')) ?>;t=d==='oscuro'?'dark':(d==='sistema'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):'');}if(t){document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t)}}catch(e){}
try{if(localStorage.getItem('fc-collapsed')==='1'&&innerWidth>=992)document.documentElement.classList.add('sb-collapsed')}catch(e){}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="<?= ASSETS_URL ?>/vendor/bootstrap.min.css" rel="stylesheet">
<link href="<?= ASSETS_URL ?>/vendor/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= ASSETS_URL ?>/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
<script>
window.FMT = <?= json_encode(fmt_cfg()) ?>;
window.fmtNum = function (n, d) { var f = window.FMT, k = d == null ? f.dec : d, v = Math.abs(Number(n) || 0).toFixed(k), p = v.split('.'), i = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, f.ts);
  return (Number(n) < 0 && Number(v) !== 0 ? '-' : '') + i + (k > 0 ? f.ds + p[1] : ''); };
window.fmtMoney = function (n, d) { var f = window.FMT, x = window.fmtNum(n, d), sp = f.sp ? ' ' : ''; return f.pos === 'despues' ? x + sp + f.sym : f.sym + sp + x; };
</script>
</head>
<body>
<div class="sb-backdrop" id="sbBackdrop"></div>

<aside class="sidebar" id="sidebar">
  <a href="<?= url('dashboard') ?>" class="brand">
    <?php if (cfg('logo')): ?><span class="brand-logo" style="background:#fff;padding:3px"><img src="<?= e(foto_url(cfg('logo'))) ?>" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:10px"></span>
    <?php else: ?><span class="brand-logo"><i class="bi bi-lightning-charge-fill"></i></span><?php endif; ?>
    <span class="brand-text"><span>Fit<b>Core</b></span><small>Gym System</small></span>
  </a>
  <nav class="sb-nav">
    <?php foreach ($menu as $group => $items):
      $items = array_filter($items, fn($k) => Permisos::can($k), ARRAY_FILTER_USE_KEY); if (!$items) continue; ?>
      <div class="sb-group"><?= e($group) ?></div>
      <?php foreach ($items as $slug => $it):
        $href = isset($it['url']) ? url($it['url']) : url('modulo/index/' . $slug);
        $isActive = ($active ?? '') === $slug; ?>
        <a href="<?= $href ?>" class="sb-link <?= $isActive ? 'active' : '' ?>" title="<?= e($it[0]) ?>">
          <i class="bi <?= e($it[1]) ?>"></i><span><?= e($it[0]) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="sb-foot">
    <div class="sb-promo">
      <i class="bi bi-trophy-fill"></i>
      <div><b>¡Meta del mes!</b><small>Sigue creciendo tu gimnasio</small></div>
    </div>
  </div>
</aside>

<div class="main-wrap">
  <header class="topbar">
    <button class="icon-btn" id="sbToggle" aria-label="Menú"><i class="bi bi-list"></i></button>
    <form class="search-box d-none d-md-flex" method="get" action="<?= url('socios') ?>">
      <i class="bi bi-search"></i>
      <input type="search" name="q" placeholder="Buscar socio por nombre, DNI o código…" aria-label="Buscar">
      <kbd>/</kbd>
    </form>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a href="<?= url('asistencia') ?>" class="btn btn-brand btn-sm d-none d-sm-inline-flex"><i class="bi bi-qr-code-scan"></i> Check-in</a>
      <button class="icon-btn" id="themeToggle" aria-label="Cambiar tema"><i class="bi bi-moon-stars-fill"></i></button>
      <div class="dropdown">
        <?php
        require_once ROOT_PATH . '/app/models/Notif.php';
        try { $bell = Notif::conteos(); $bell['stock'] = (int)Database::val('SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock <= stock_minimo'); } catch (Throwable $e) { $bell = ['vencer' => 0, 'deudas' => 0, 'cumple' => 0, 'inactivos' => 0, 'vencidos' => 0, 'stock' => 0]; }
        $bellItems = [
            ['vencer', 'bi-exclamation-triangle-fill text-warning', 'Membresías por vencer (7 días)', url('notificaciones?seg=vencer'), 'notificaciones'],
            ['deudas', 'bi-cash-coin text-danger', 'Socios con deuda pendiente', url('notificaciones?seg=deudas'), 'pagos'],
            ['stock', 'bi-box-seam-fill text-danger', 'Productos con stock bajo', url('inventario?stock=bajo'), 'inventario'],
            ['cumple', 'bi-gift-fill text-success', 'Cumpleaños próximos (7 días)', url('notificaciones?seg=cumple'), 'notificaciones'],
        ];
        $bellTotal = 0; foreach ($bellItems as $bi) if (Permisos::can($bi[4])) $bellTotal += $bell[$bi[0]];
        ?>
        <button class="icon-btn position-relative" data-bs-toggle="dropdown" aria-label="Notificaciones">
          <i class="bi bi-bell-fill"></i><?php if ($bellTotal): ?><span class="dot"></span><?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end drop-card p-0">
          <div class="p-3 border-bottom fw-bold">Notificaciones</div>
          <?php foreach ($bellItems as $bi): if (!$bell[$bi[0]] || !Permisos::can($bi[4])) continue; ?>
          <a class="notif" href="<?= $bi[3] ?>"><i class="bi <?= $bi[1] ?>"></i><div><?= $bi[2] ?><small><?= (int)$bell[$bi[0]] ?> pendiente<?= $bell[$bi[0]] == 1 ? '' : 's' ?></small></div></a>
          <?php endforeach; ?>
          <?php if (!$bellTotal): ?><div class="p-3 small text-muted text-center">Todo al día ✓</div><?php endif; ?>
          <?php if (Permisos::can('notificaciones')): ?><a class="notif justify-content-center fw-semibold" href="<?= url('notificaciones') ?>">Ver todas</a><?php endif; ?>
        </div>
      </div>
      <div class="dropdown">
        <button class="user-chip" data-bs-toggle="dropdown">
          <span class="avatar"><?= e($iniciales) ?></span>
          <span class="d-none d-md-block text-start lh-1"><b><?= e(explode(' ', $user['nombre'])[0]) ?></b><small><?= e($user['rol']) ?></small></span>
          <i class="bi bi-chevron-down d-none d-md-block small"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end drop-card">
          <li class="px-3 py-2 small text-muted"><?= e($user['nombre']) ?><br><?= e($user['rol']) ?></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= url('perfil') ?>"><i class="bi bi-person-gear me-2"></i>Mi perfil y contraseña</a></li>
          <?php if (Permisos::can('configuracion')): ?><li><a class="dropdown-item" href="<?= url('configuracion') ?>"><i class="bi bi-gear me-2"></i>Configuración</a></li><?php endif; ?>
          <li><a class="dropdown-item text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="content">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="flash-stack">
      <?php foreach ($_SESSION['flash'] as $fl): $ic = ['success' => 'check-circle-fill', 'danger' => 'exclamation-octagon-fill', 'warning' => 'exclamation-triangle-fill', 'info' => 'info-circle-fill'][$fl['t']] ?? 'info-circle-fill'; ?>
        <div class="alert alert-<?= e($fl['t']) ?> alert-dismissible fade show flash" role="alert"><i class="bi bi-<?= $ic ?>"></i><span><?= e($fl['m']) ?></span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endforeach; unset($_SESSION['flash']); ?>
      </div>
    <?php endif; ?>
    <?= $content ?>
    <footer class="app-foot">© <?= date('Y') ?> <?= APP_NAME ?> · v<?= APP_VERSION ?></footer>
  </main>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content modal-x text-center p-3">
  <div class="p-2"><div class="confirm-ico"><i class="bi bi-question-lg"></i></div><h6 class="fw-bold mt-3 mb-1">Confirmar acción</h6><p class="text-muted small mb-3" id="confirmText"></p>
  <div class="d-flex gap-2"><button class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand flex-fill" id="confirmOk">Confirmar</button></div></div></div></div></div>
<script src="<?= ASSETS_URL ?>/vendor/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js?v=<?= APP_VERSION ?>"></script>
<?php if (!empty($charts)): ?>
<script src="<?= ASSETS_URL ?>/vendor/chart.umd.min.js"></script>
<script>window.DASH = <?= json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>; window.CUR = <?= json_encode(APP_CURRENCY) ?>;</script>
<script src="<?= ASSETS_URL ?>/js/dashboard.js?v=<?= APP_VERSION ?>"></script>
<?php endif; ?>
<?php if (Permisos::esAdmin()): ?>
<link href="<?= ASSETS_URL ?>/css/ia.css?v=<?= APP_VERSION ?>" rel="stylesheet">
<script>window.IA = {csrf: <?= json_encode(Auth::csrf()) ?>, base: <?= json_encode(rtrim(url(''), '/')) ?>, moneda: <?= json_encode(APP_CURRENCY) ?>};</script>
<script src="<?= ASSETS_URL ?>/js/ia.js?v=<?= APP_VERSION ?>"></script>
<script src="<?= ASSETS_URL ?>/js/chat.js?v=<?= APP_VERSION ?>"></script>
<?php endif; ?>
</body>
</html>
