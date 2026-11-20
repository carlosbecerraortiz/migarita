<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>MiGarita - Clientes</title>

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

  <style>
      .oculto { display: none; }
  </style>
</head>
<body>

<?php require __DIR__ . "/../layout/header.php"; ?>
<?php require __DIR__ . "/../layout/sidebar.php"; ?>

<main id="main" class="main">

    <div class="pagetitle mb-3">
        <h1>Generar Informe</h1>
        <h6 class="text-muted">
            Cliente: <b><?= $cliente["nombre"] . " " . $cliente["apellido"] ?></b>
        </h6>
    </div>

    <section class="section">
        <div class="card shadow-sm p-4">

            <h5 class="fw-bold mb-4">Seleccione el tipo de informe</h5>

            <!-- SELECTOR -->
            <select id="tipoInforme" class="form-control mb-2" required>
                <option value="">Seleccione tipo de informe</option>
                <option value="mes">Movimientos del Último Mes</option>
                <option value="fechas">Movimientos Entre Dos Fechas</option>
                <option value="completo">Informe Completo</option>
            </select>

            <!-- FORMULARIO ÚLTIMO MES -->
            <div id="bloqueMes" class="oculto">
                <a href="/mi_garita/index.php?url=cliente/generarInforme/<?= $cliente['id'] ?>&tipo=mes"
                   class="btn btn-success w-100 py-2">
                   <i class="bi bi-calendar2-week me-2"></i> Generar Informe del Último Mes
                </a>
            </div>

            <!-- FORMULARIO ENTRE FECHAS -->
            <div id="bloqueFechas" class="oculto mt-2">
                <form method="GET" action="/mi_garita/index.php">
                    <input type="hidden" name="url" value="cliente/generarInforme/<?= $cliente['id'] ?>">
                    <input type="hidden" name="tipo" value="fechas">

                    <label class="form-label mt-2">Desde:</label>
                    <input type="date" name="desde" class="form-control mb-3" required>

                    <label class="form-label">Hasta:</label>
                    <input type="date" name="hasta" class="form-control mb-3" required>

                    <button class="btn btn-success w-100 py-2">
                        <i class="bi bi-calendar-range me-2"></i> Generar Informe por Fechas
                    </button>
                </form>
            </div>

            <!-- FORMULARIO COMPLETO -->
            <div id="bloqueCompleto" class="oculto mt-4">
                <a href="/mi_garita/index.php?url=cliente/generarInforme/<?= $cliente['id'] ?>&tipo=completo"
                   class="btn btn-success w-100 py-2">
                   <i class="bi bi-collection me-2"></i> Generar Informe Completo
                </a>
            </div>

        </div>
    </section>

</main>

<?php require __DIR__ . "/../layout/footer.php"; ?>

<script src="/mi_garita/assets/js/informes.js"></script>

</body>
</html>