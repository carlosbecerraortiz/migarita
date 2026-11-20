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
</head>
<body>
  <?php require __DIR__ . "/../layout/header.php"; ?>
  <?php require __DIR__ . "/../layout/sidebar.php"; ?>
  <!-- ======= Main ======= -->
  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Clientes</h1>
    </div>

    <section class="section">
      <div class="card">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
            <h5 class="card-title">Lista de clientes</h5>
            <a href="/mi_garita/index.php?url=cliente/crear" class="btn btn-outline-success">Añadir Cliente</a>
          </div>

          <div class="mb-3">
              <div class="input-group" style="max-width: 350px;">
                  <input type="text" 
                         id="buscador" 
                         class="form-control" 
                         placeholder="Buscar por nombre, apellido o cédula...">

                  <span class="input-group-text bg-primary text-white">
                      <i class="bi bi-search"></i>
                  </span>
              </div>
          </div>
          <!-- CONTENEDOR CON SCROLL PARA LA TABLA -->
          <div class="tabla-scroll">
            <!-- TABLA DINÁMICA -->
              <table class="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Cédula</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Teléfono</th>
                    <th>Tipo</th>
                    <th>Saldo</th>
                    <th>Acciones</th>
                    <th>Movimientos</th>
                  </tr>
                </thead>

                <tbody id="tablaClientes">
                  <?php foreach ($clientes as $c): ?>
                    <tr>
                      <td><?= $c['id'] ?></td>
                        <td><?= $c['cedula'] ?></td>
                        <td><?= $c['nombre'] ?></td>
                        <td><?= $c['apellido'] ?></td>
                        <td><?= $c['telefono'] ?></td>
                        <td>
                            <?php
                            // Definir color según el tipo de cliente
                            $colorBadge = 'bg-secondary'; // Color por defecto
                            
                            switch(strtolower($c['tipo_cliente'])) {
                                case 'estudiante':
                                    $colorBadge = 'bg-primary'; // Azul
                                    break;
                                case 'administrativo':
                                    $colorBadge = 'bg-success'; // Verde
                                    break;
                                case 'profesor':
                                    $colorBadge = 'bg-warning'; // Amarillo
                                    break;
                                case 'egresado':
                                    $colorBadge = 'bg-info'; // Celeste
                                    break;
                                case 'externo':
                                    $colorBadge = 'bg-danger'; // Rojo
                                    break;
                            }
                            ?>
                            <span class="badge <?= $colorBadge ?>">
                                <?= ucfirst($c['tipo_cliente']) ?>
                            </span>
                        </td>
                        <td>$ <?= number_format($c['saldo'], 0, ',', '.') ?></td>

                        <td>
                        <!-- EDITAR -->
                          <a href="/mi_garita/index.php?url=cliente/editar/<?= $c['id'] ?>" 
                            class="btn btn-sm btn-primary" title="Editar">
                            <i class="bi bi-pencil-square"></i>
                          </a>

                        <!-- ELIMINAR -->
                        <button 
                          class="btn btn-sm btn-danger btnEliminar"
                          data-id="<?= $c['id'] ?>"
                          data-nombre="<?= $c['nombre'] . ' ' . $c['apellido'] ?>" title="Eliminar">
                          <i class="bi bi-trash"></i>
                        </button>

                        <!-- REPORTE -->
                          <a href="/mi_garita/index.php?url=cliente/seleccionarInforme/<?= $c['id'] ?>" 
                              class="btn btn-sm btn-success" title="Generar Informe">
                              <i class="bi bi-file-earmark-text"></i>
                          </a>
                                    
                        </td>
                        <td>
                          <a href="/mi_garita/index.php?url=cliente/deuda/<?= $c['id'] ?>" 
                            class="btn btn-outline-success btn-sm">
                            Registrar Movimiento
                          </a>
                        </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <!-- Fin tabla dinamica -->
          </div>
          <div class="mt-3 text-end">
            <h5>
                Total General Adeudado:
                <span class="badge bg-success" style="font-size: 1.1rem;">
                  $ <?= number_format($totalGeneral, 0, ',', '.') ?>
              </span>
            </h5>
          </div>
        </div>
      </div>
    </section>
  </main>
  <!-- End Main -->

  <!-- Modal de éxito -->
  <div class="modal fade" id="modalExito" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Éxito</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <p class="mb-0">Cliente registrado correctamente.</p>
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
        var myModal = new bootstrap.Modal(document.getElementById('modalExito'));
        myModal.show();
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
          <p class="mb-0">El cliente ha sido actualizado correctamente.</p>
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
      var myModal = new bootstrap.Modal(document.getElementById('modalActualizado'));
      myModal.show();
  });
  </script>
  <?php endif; ?>

  <!-- Modal Confirmacion Eliminar -->
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

  <!-- Script de confirmación -->
  <script>
  let idEliminar = null;

  // Abrir modal al dar clic en botón rojo
  document.querySelectorAll(".btnEliminar").forEach(boton => {
      boton.addEventListener("click", () => {

          idEliminar = boton.dataset.id;
          let nombre = boton.dataset.nombre;

          document.getElementById("textoConfirmacion").innerHTML =
              `¿Estás seguro que deseas eliminar al cliente <b>${nombre}</b>?`;

          new bootstrap.Modal(document.getElementById('modalConfirmarEliminar')).show();
      });
  });

  // Al confirmar eliminación
  document.getElementById("btnEliminarConfirmado").addEventListener("click", () => {
      if (idEliminar) {
          window.location.href = "/mi_garita/index.php?url=cliente/eliminar/" + idEliminar;
      }
  });
  </script>

  <!-- Modal Exito Eliminar -->
  <div class="modal fade" id="modalExitoEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Cliente eliminado</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <p>El cliente ha sido eliminado correctamente.</p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>
        </div>

      </div>
    </div>
  </div>

  <!-- Mostrar modal si viene "ok=eliminado" -->
  <?php if (isset($_GET['ok']) && $_GET['ok'] === "eliminado"): ?>
  <script>
  document.addEventListener("DOMContentLoaded", function() {
      new bootstrap.Modal(document.getElementById('modalExitoEliminar')).show();
  });
  </script>
  <?php endif; ?>

  <!-- Modal Error Eliminar -->
  <div class="modal fade" id="modalErrorEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">No se puede eliminar</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <p id="textoError"></p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Aceptar</button>
        </div>

      </div>
    </div>
  </div>

  <!-- Mostrar error si viene "error=saldo" -->
  <?php if (isset($_GET['error']) && $_GET['error'] === "saldo"): ?>
  <script>
  document.addEventListener("DOMContentLoaded", function() {

      const saldo = "<?= $_GET['monto'] ?? '0' ?>";

      document.getElementById("textoError").innerHTML =
          `Este cliente no se puede eliminar porque tiene un saldo pendiente de <b>$ ${saldo}</b>.`;

      new bootstrap.Modal(document.getElementById('modalErrorEliminar')).show();
  });
  </script>
  <?php endif; ?>


  <?php require __DIR__ . "/../layout/footer.php"; ?>
  <script src="/mi_garita/assets/js/buscadorClientes.js?v=<?= time() ?>"></script>
</body>
</html>