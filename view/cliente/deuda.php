<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>MiGarita - Registrar Movimiento</title>

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
<body>

  <?php require __DIR__ . "/../layout/header.php"; ?>
  <?php require __DIR__ . "/../layout/sidebar.php"; ?>

  <main id="main" class="main">

      <div class="pagetitle">
          <h1>Registrar Movimiento</h1>
          
      </div>

      <section class="section">
          <div class="card p-4">
              <h3 class="text-muted">
                <b>Cliente: <?= $cliente['nombre'] . " " . $cliente['apellido'] ?></b>
              </h3>

              <?php require __DIR__ . "/alertas_movimientos.php"; ?>

              <?php 
                  // Variables persistentes después de errores
                  $tipoOld = $_SESSION["mov_data"]["tipo"] ?? "";
                  $montoOld = $_SESSION["mov_data"]["monto"] ?? "";
                  $descOld = $_SESSION["mov_data"]["descripcion"] ?? "";
                  $metodoOld = $_SESSION["mov_data"]["metodo_pago"] ?? "";
              ?>

              <form method="POST" action="/mi_garita/index.php?url=cliente/guardarMovimiento/<?= $cliente['id'] ?>">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Tipo de movimiento</label>
                        <select name="tipo" id="tipo" class="form-control">
                            <option value="">Seleccione</option>
                            <option value="fiado" <?= $tipoOld == "fiado" ? "selected" : "" ?>>Fiado</option>
                            <option value="abono" <?= $tipoOld == "abono" ? "selected" : "" ?>>Abono / Pago</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Monto</label>
                        <input type="text" 
                               name="monto" 
                               id="monto"
                               class="form-control"
                               placeholder="Ingrese el monto"
                               value="<?= $montoOld ?>">
                    </div>

                    <div class="col-md-4">
                        <label>Método de pago</label>
                        <select name="metodo_pago" class="form-control" id="metodo_pago">
                            <option value="">Seleccione</option>

                            <?php foreach ($metodos as $m): ?>
                                <option value="<?= $m['id'] ?>"
                                    <?= ($metodoOld == $m['id']) ? "selected" : "" ?>>
                                    <?= $m['nombre'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Descripción</label>
                    <input type="text" 
                           name="descripcion" 
                           class="form-control"
                           placeholder="Ej: empanada con gaseosa, abono de dinero..."
                           value="<?= $descOld ?>">
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success">Registrar</button>
                    <a href="/mi_garita/index.php?url=cliente/index" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
          </div>
      </section>

  </main>

  <?php require __DIR__ . "/../layout/footer.php"; ?>
  <script src="/mi_garita/assets/js/formatoMonto.js"></script>
  <script src="/mi_garita/assets/js/movimiento.js"></script>
  <?php unset($_SESSION["mov_data"]); ?>
</body>
</html>
