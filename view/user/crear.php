<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>MiGarita - Registrar Usuario</title>

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
            <h1>Registrar Usuario</h1>
        </div>

        <section class="section">
            <div class="card p-4">

                <!-- Mensajes de error -->
                <?php require __DIR__ . "/alertas_usuario.php"; ?>

                <form id="formCrear" method="POST" action="/mi_garita/index.php?url=user/guardar">

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
                            <label>Correo</label>
                            <input type="text" 
                                   name="correo" 
                                   class="form-control"
                                   placeholder="ejemplo@correo.com"
                                   value="<?= $_GET['correo'] ?? '' ?>">
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
                            <label>Usuario</label>
                            <input type="text" 
                                   name="usuario" 
                                   class="form-control"
                                   placeholder="Nombre de usuario para login"
                                   value="<?= $_GET['usuario'] ?? '' ?>">
                        </div>

                        <div class="col-md-6">
                            <label>Contraseña</label>
                            <div class="input-group">
                                <input type="password" 
                                       name="password" 
                                       id="passwordCrear"
                                       class="form-control"
                                       placeholder="Mínimo 6 caracteres">
                                <span class="input-group-text" 
                                      id="togglePasswordCrear" 
                                      style="cursor: pointer;">
                                    <i class="bi bi-eye-slash" id="iconPasswordCrear"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Rol</label>
                            <select name="rol" class="form-select">
                                <option value="">Seleccione un rol</option>
                                <option value="usuario" <?= (isset($_GET['rol']) && $_GET['rol'] === 'usuario') ? 'selected' : '' ?>>
                                    Usuario
                                </option>
                                <option value="superusuario" <?= (isset($_GET['rol']) && $_GET['rol'] === 'superusuario') ? 'selected' : '' ?>>
                                    Superusuario
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success">Registrar</button>
                        <a href="/mi_garita/index.php?url=user/index" class="btn btn-secondary">Cancelar</a>
                    </div>

                </form>

            </div>
        </section>
</main>

<?php require __DIR__ . "/../layout/footer.php"; ?>
<script src="/mi_garita/assets/js/togglePasswordCrear.js"></script>

</body>
</html>