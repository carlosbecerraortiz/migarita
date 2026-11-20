<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Inicio de Sesión - MiGarita</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="/mi_garita/assets/img/favicon1.png" rel="icon">
  <link href="/mi_garita/assets/img/apple-touch-icon1.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="/mi_garita/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="/mi_garita/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="/mi_garita/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="/mi_garita/assets/css/style.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 7 2025 with Bootstrap v5.3.5
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>

  <main>
    <div class="container">

      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

              <div class="d-flex justify-content-center py-4">
                <a href="/mi_garita/index.php?url=login/index" class="logo d-flex align-items-center w-auto">
                  <img src="/mi_garita/assets/img/logo1.png" alt="">
                  <span class="d-none d-lg-block">MiGarita</span>
                </a>
              </div><!-- End Logo -->

              <div class="card mb-3">

                <div class="card-body">

                  <div class="pt-4 pb-2">
                    <h5 class="card-title text-center pb-0 fs-4">Inicio de Sesión</h5>
                    <p class="text-center small">Por favor, ingresa tu usuario y contraseña</p>
                  </div>

                  <?php if (isset($_GET['error'])): ?>
                      <div class="alert alert-danger text-center">
                          <?php if ($_GET['error'] == 'usuario'): ?>
                              Usuario no existe.
                          <?php elseif ($_GET['error'] == 'contrasena'): ?>
                              Contraseña incorrecta.
                          <?php endif; ?>
                      </div>
                  <?php endif; ?>

                  <form class="row g-3 needs-validation" method="POST" action="/mi_garita/index.php?url=login/autenticar" novalidate>
                    <div class="col-12">
                      <label for="yourUsername" class="form-label">Usuario</label>
                      <div class="input-group has-validation">
                        <span class="input-group-text" id="inputGroupPrepend">@</span>
                        <input type="text" name="username" class="form-control" id="yourUsername" required>
                        <div class="invalid-feedback">Por favor ingresa tu usuario</div>
                      </div>
                    </div>

                    <div class="col-12">
                        <label for="yourPassword" class="form-label">Contraseña</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                <i class="bi bi-eye-slash" id="iconoPassword"></i>
                            </span>
                            <input type="password" name="password" class="form-control" id="yourPassword" required>
                            <div class="invalid-feedback">Por favor ingresa tu contraseña</div>
                        </div>
                    </div>

                    <div class="col-12">
                      <button class="btn btn-primary w-100" type="submit">Iniciar Sesión</button>
                    </div>
                  </form>
                </div>
              </div>

            </div>
          </div>
        </div>

      </section>

    </div>
  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="/mi_garita/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Template Main JS File -->
  <script src="/mi_garita/assets/js/main.js"></script>
  <script src="/mi_garita/assets/js/login.js"></script>

</body>

</html>