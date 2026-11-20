<?php
if (!isset($_SESSION['id'])) {
    header("Location: /mi_garita/index.php?url=login/index");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>MiGarita</title>

  <!-- Favicons -->
  <link href="/mi_garita/assets/img/favicon1.png" rel="icon">
  <link href="/mi_garita/assets/img/apple-touch-icon1.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Poppins:300,400,500,600,700" rel="stylesheet">

  <!-- Vendor CSS -->
  <link href="/mi_garita/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="/mi_garita/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="/mi_garita/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">

  <!-- Main CSS -->
  <link href="/mi_garita/assets/css/style.css" rel="stylesheet">
</head>

<!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="#" class="logo d-flex align-items-center">
        <img src="/mi_garita/assets/img/logo1.png" alt="">
        <span class="d-none d-lg-block">MiGarita</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

        <!-- Perfil reducido -->
        <li class="nav-item dropdown pe-3">
          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
              <span class="d-none d-md-block dropdown-toggle ps-2">
                  <?php echo $_SESSION['nombre']; ?>
              </span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
              <li class="dropdown-header">
                  <h6><?php echo $_SESSION['nombre']; ?></h6>
                  <span>Administrador</span>
              </li>

              <li><hr class="dropdown-divider"></li>

              <li>
                  <a class="dropdown-item d-flex align-items-center" href="/mi_garita/logout.php">
                      <i class="bi bi-box-arrow-right"></i>
                      <span>Cerrar Sesión</span>
                  </a>
              </li>
          </ul>
        </li>

      </ul>
    </nav>
  </header>
  <!-- End Header -->