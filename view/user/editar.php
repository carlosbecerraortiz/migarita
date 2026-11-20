<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>MiGarita - Editar Usuario</title>

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
        <h1>Editar Usuario</h1>
    </div>

    <section class="section">
        <div class="card p-4">

            <!-- ALERTAS -->
            <?php require __DIR__ . "/alertas_usuario.php"; ?>

            <form id="formEditar" 
                  method="POST" 
                  action="/mi_garita/index.php?url=user/actualizar/<?= $user['id'] ?>">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Cédula</label>
                        <input type="text" name="cedula" class="form-control" placeholder="Ingrese la cédula" value="<?= $user['cedula'] ?>">
                    </div>

                    <div class="col-md-6">
                        <label>Correo</label>
                        <input type="text" name="correo" class="form-control" placeholder="ejemplo@correo.com" value="<?= $user['correo'] ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ingrese el nombre" value="<?= $user['nombre'] ?>">
                    </div>

                    <div class="col-md-6">
                        <label>Apellido</label>
                        <input type="text" name="apellido" class="form-control" placeholder="Ingrese el apellido" value="<?= $user['apellido'] ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Usuario</label>
                        <input type="text" name="usuario" class="form-control" placeholder="Nombre de usuario para login" value="<?= $user['usuario'] ?>">
                    </div>

                    <div class="col-md-6">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Dejar vacío para mantener la actual">
                        <small class="text-muted">Solo completa si deseas cambiar la contraseña</small>
                    </div>
                </div>

                <?php if ($_SESSION['rol'] === 'superusuario'): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Rol</label>
                        <?php if ($_SESSION['id'] == $user['id'] && $_SESSION['rol'] === 'superusuario'): ?>
                            <!-- Si el superusuario se está editando a sí mismo, deshabilitar el campo -->
                            <select name="rol" class="form-select" disabled>
                                <option value="superusuario" selected>Superusuario</option>
                            </select>
                            <input type="hidden" name="rol" value="superusuario">
                            <small class="text-muted text-warning">No puedes cambiar tu propio rol</small>
                        <?php else: ?>
                            <select name="rol" class="form-select">
                                <option value="usuario" <?= $user['rol'] === 'usuario' ? 'selected' : '' ?>>
                                    Usuario
                                </option>
                                <option value="superusuario" <?= $user['rol'] === 'superusuario' ? 'selected' : '' ?>>
                                    Superusuario
                                </option>
                            </select>
                            <small class="text-muted">Solo superusuarios pueden cambiar roles</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="text-center">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <a href="/mi_garita/index.php?url=user/index" 
                       class="btn btn-secondary">Cancelar</a>
                </div>

            </form>

        </div>
    </section>

</main>

<?php require __DIR__ . "/../layout/footer.php"; ?>

</body>
</html>