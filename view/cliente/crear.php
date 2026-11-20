<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>MiGarita - Registrar Cliente</title>

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

    <!-- ======= Main ======= -->
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Registrar Cliente</h1>
        </div>

        <section class="section">
            <div class="card p-4">

                <!-- Mensajes de error -->
                <?php require __DIR__ . "/alertas_cliente.php"; ?>
                <?php $tipoOld = $_GET["tipo_cliente"] ?? ""; ?>

                <form id="formCrear" method="POST" action="/mi_garita/index.php?url=cliente/guardar">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Cédula</label>
                            <input type="text" 
                                   name="cedula" 
                                   class="form-control"
                                   placeholder="Ingrese la cédula"
                                   value="<?= $_GET['cedula'] ?? '' ?>">
                        </div>

                        <div class="col-md-6">
                            <label>Teléfono</label>
                            <input type="text" 
                                   name="telefono" 
                                   class="form-control"
                                   placeholder="Ingrese número de teléfono"
                                   value="<?= $_GET['telefono'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Nombre</label>
                            <input type="text" 
                                   name="nombre" 
                                   class="form-control"
                                   placeholder="Ingrese el nombre"
                                   value="<?= $_GET['nombre'] ?? '' ?>">
                        </div>

                        <div class="col-md-6">
                            <label>Apellido</label>
                            <input type="text" 
                                   name="apellido" 
                                   class="form-control"
                                   placeholder="Ingrese el apellido"
                                   value="<?= $_GET['apellido'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                           <label>Correo</label>
                           <input type="text" name="correo" class="form-control"
                               placeholder="ejemplo@correo.com"
                               value="<?= $_GET['correo'] ?? '' ?>">
                        </div>

                        <div class="col-md-6">
                            <label>Tipo de Cliente</label>
                            <select name="id_tipo_cliente" class="form-control">
                                <option value="">Seleccione un tipo...</option>
                                <?php 
                                $tipoOld = $_GET["id_tipo_cliente"] ?? ""; 
                                foreach ($tipos as $t): 
                                ?>
                                    <option 
                                        value="<?= $t['id'] ?>" 
                                        <?= ($tipoOld == $t['id']) ? "selected" : "" ?>
                                    >
                                        <?= $t['nombre'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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

</body>
</html>
