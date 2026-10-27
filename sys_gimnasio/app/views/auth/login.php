<!doctype html>
<html lang="es" data-theme="dark" data-bs-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="<?= ASSETS_URL ?>/vendor/bootstrap.min.css" rel="stylesheet">
<link href="<?= ASSETS_URL ?>/vendor/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= ASSETS_URL ?>/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body class="login-body">
<div class="login-wrap">
  <section class="login-hero">
    <div class="hero-bg"></div>
    <div class="hero-grid"></div>
    <div class="hero-inner">
      <div class="brand brand-lg">
        <span class="brand-logo"><i class="bi bi-lightning-charge-fill"></i></span>
        <span class="brand-text"><span>Fit<b>Core</b></span><small>Gym System</small></span>
      </div>
      <div class="hero-copy">
        <span class="pill"><i class="bi bi-stars"></i> Gestión integral de gimnasios</span>
        <h1>Controla tu gimnasio.<br><span class="text-gradient">Haz crecer tu negocio.</span></h1>
        <p>Socios, membresías, asistencia, clases, ventas y reportes en un solo lugar, desde cualquier dispositivo.</p>
        <div class="hero-stats">
          <div><b>∞</b><span>Socios y planes</span></div>
          <div><b>24/7</b><span>Control de acceso</span></div>
          <div><b>100%</b><span>Responsivo</span></div>
        </div>
      </div>
      <div class="hero-float f1"><i class="bi bi-person-check-fill"></i><div><b>Check-in exitoso</b><small>Carlos M. · Plan Mensual</small></div></div>
      <div class="hero-float f2"><i class="bi bi-graph-up-arrow"></i><div><b>+18.4%</b><small>Ingresos este mes</small></div></div>
    </div>
  </section>

  <section class="login-form-side">
    <div class="login-card">
      <div class="brand d-lg-none mb-4 justify-content-center">
        <span class="brand-logo"><i class="bi bi-lightning-charge-fill"></i></span>
        <span class="brand-text"><span>Fit<b>Core</b></span><small>Gym System</small></span>
      </div>
      <h2>Bienvenido de nuevo 👋</h2>
      <p class="text-muted mb-4">Ingresa tus credenciales para acceder al panel.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
          <i class="bi bi-exclamation-octagon-fill"></i><span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= url('login') ?>" id="loginForm" novalidate>
        <input type="hidden" name="csrf" value="<?= e(Auth::csrf()) ?>">
        <label class="form-lbl" for="usuario">Usuario o correo</label>
        <div class="input-ico mb-3">
          <i class="bi bi-person"></i>
          <input type="text" id="usuario" name="usuario" class="form-control" placeholder="admin" value="<?= e($old) ?>" autocomplete="username" required autofocus>
        </div>
        <label class="form-lbl" for="password">Contraseña</label>
        <div class="input-ico mb-3">
          <i class="bi bi-lock"></i>
          <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="eye" id="togglePass" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="form-check m-0">
            <input class="form-check-input" type="checkbox" id="remember">
            <label class="form-check-label small" for="remember">Recordarme</label>
          </div>
          <a href="#" class="small link-brand">¿Olvidaste tu contraseña?</a>
        </div>
        <button type="submit" class="btn btn-brand btn-lg w-100" id="btnLogin">
          <span class="lbl">Ingresar al sistema <i class="bi bi-arrow-right"></i></span>
          <span class="spin spinner-border spinner-border-sm d-none"></span>
        </button>
      </form>
      <div class="demo-box mt-4">
        <i class="bi bi-info-circle"></i>
        <span>Demo: <b>admin</b> / <b>admin123</b></span>
      </div>
      <p class="text-center text-muted small mt-4 mb-0">© <?= date('Y') ?> <?= APP_NAME ?> · v<?= APP_VERSION ?></p>
    </div>
  </section>
</div>
<script>
document.getElementById('togglePass').addEventListener('click',function(){
  var i=document.getElementById('password'),s=i.type==='password';
  i.type=s?'text':'password';this.innerHTML='<i class="bi bi-eye'+(s?'-slash':'')+'"></i>';
});
document.getElementById('loginForm').addEventListener('submit',function(e){
  var u=this.usuario.value.trim(),p=this.password.value;
  if(!u||!p){e.preventDefault();(u?this.password:this.usuario).focus();(u?this.password:this.usuario).classList.add('is-invalid');return;}
  var b=document.getElementById('btnLogin');b.disabled=true;b.querySelector('.lbl').classList.add('d-none');b.querySelector('.spin').classList.remove('d-none');
});
</script>
</body>
</html>
