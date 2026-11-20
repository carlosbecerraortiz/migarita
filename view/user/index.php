<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>MiGarita - Administradores</title>

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
<body data-session-id="<?= $_SESSION['id'] ?>" data-session-rol="<?= $_SESSION['rol'] ?>">
  <?php require __DIR__ . "/../layout/header.php"; ?>
  <?php require __DIR__ . "/../layout/sidebar.php"; ?>
  
  <!-- ======= Main ======= -->
  <main id="main" class="main">

    <div class="pagetitle">
        <h1>Usuarios</h1>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                    <h5 class="card-title">Lista de usuarios</h5>

                    <?php if ($_SESSION['rol'] === 'superusuario'): ?>
                        <a href="/mi_garita/index.php?url=user/crear"
                           class="btn btn-outline-success">
                            Añadir Usuario
                        </a>
                    <?php endif; ?>
                </div>

                <!-- BUSCADOR -->
                <div class="input-group mb-3" style="max-width: 350px;">
                    <input type="text"
                           id="buscadorUsuarios"
                           class="form-control"
                           placeholder="Buscar por nombre, usuario, cédula...">

                    <span class="input-group-text bg-primary text-white">
                        <i class="bi bi-search"></i>
                    </span>
                </div>

                <!-- TABLA -->
                <div class="tabla-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="tablaUsuarios">
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= $u["id"] ?></td>
                                <td><?= $u["cedula"] ?></td>
                                <td><?= $u["nombre"] ?></td>
                                <td><?= $u["apellido"] ?></td>
                                <td><?= $u["usuario"] ?></td>
                                <td><?= $u["correo"] ?></td>
                                <td>
                                    <?php if ($u["rol"] === "superusuario"): ?>
                                        <span class="badge bg-danger">Superusuario</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Usuario</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php
                                    // Usuario puede editar solo su propio perfil
                                    // Superusuario puede editar a todos
                                    $puedeEditar = ($_SESSION['id'] == $u['id']) || ($_SESSION['rol'] === 'superusuario');
                                    
                                    // Solo superusuario puede eliminar
                                    // No puede eliminarse a sí mismo
                                    $puedeEliminar = ($_SESSION['rol'] === 'superusuario') && ($_SESSION['id'] != $u['id']);
                                    ?>

                                    <?php if ($puedeEditar): ?>
                                        <a href="/mi_garita/index.php?url=user/editar/<?= $u['id'] ?>"
                                            class="btn btn-sm btn-primary"
                                            title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="Sin permisos">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($puedeEliminar): ?>
                                        <button class="btn btn-sm btn-danger btnEliminarUsuario"
                                            data-id="<?= $u['id'] ?>"
                                            data-nombre="<?= $u['nombre'] . ' ' . $u['apellido'] ?>"
                                            title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled 
                                            title="<?= ($_SESSION['id'] == $u['id']) ? 'No puedes eliminarte a ti mismo' : 'Sin permisos' ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </section>
  </main>
  <!-- End Main -->

  <!-- Modal de éxito al crear -->
  <div class="modal fade" id="modalExito" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Éxito</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p class="mb-0">Usuario registrado correctamente.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <?php if (isset($_GET['ok']) && $_GET['ok'] === "creado"): ?>
  <script>
  document.addEventListener("DOMContentLoaded", function() {
      new bootstrap.Modal(document.getElementById('modalExito')).show();
  });
  </script>
  <?php endif; ?>

  <!-- Modal de éxito al actualizar -->
  <div class="modal fade" id="modalActualizado" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Éxito</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p class="mb-0">El usuario ha sido actualizado correctamente.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <?php if (isset($_GET['ok']) && $_GET['ok'] === "editado"): ?>
  <script>
  document.addEventListener("DOMContentLoaded", function() {
      new bootstrap.Modal(document.getElementById('modalActualizado')).show();
  });
  </script>
  <?php endif; ?>

  <!-- Modal Confirmación Eliminar -->
  <div class="modal fade" id="modalConfirmarEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Confirmar eliminación</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p id="textoConfirmacion"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="btnEliminarConfirmado">Eliminar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Éxito Eliminar -->
  <div class="modal fade" id="modalExitoEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Usuario eliminado</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p>El usuario ha sido eliminado correctamente.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <?php if (isset($_GET['ok']) && $_GET['ok'] === "eliminado"): ?>
  <script>
  document.addEventListener("DOMContentLoaded", function() {
      new bootstrap.Modal(document.getElementById('modalExitoEliminar')).show();
  });
  </script>
  <?php endif; ?>


  <!-- Modal Error Permisos -->
  <div class="modal fade" id="modalErrorPermisos" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Sin permisos</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p id="textoErrorPermisos"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <script>
  let idEliminar = null;

  // Activar en carga inicial
  activarEliminarUsuario();

  function activarEliminarUsuario() {

      // Asignar evento a cada botón eliminar
      document.querySelectorAll(".btnEliminarUsuario").forEach(boton => {

          boton.addEventListener("click", () => {

              idEliminar = boton.dataset.id;
              let nombre = boton.dataset.nombre;

              document.getElementById("textoConfirmacion").innerHTML =
                  `¿Estás seguro que deseas eliminar al usuario <b>${nombre}</b>?`;

              new bootstrap.Modal(document.getElementById('modalConfirmarEliminar')).show();
          });
      });

      // Asegurar que el botón confirmar tenga UN SOLO evento
      const btn = document.getElementById("btnEliminarConfirmado");
      const clon = btn.cloneNode(true);
      btn.replaceWith(clon);

      clon.addEventListener("click", () => {
          if (idEliminar) {
              window.location.href = "/mi_garita/index.php?url=user/eliminar/" + idEliminar;
          }
      });
  }
  </script>

  <?php require __DIR__ . "/../layout/footer.php"; ?>
  <script src="/mi_garita/assets/js/usuariosBuscador.js"></script>
</body>
</html>