<?php
$titulo = 'Usuarios';
$seccion = 'usuarios';
$scripts = ['usuarios.js'];
$avisos = [
    'creado'    => 'Usuario registrado correctamente.',
    'editado'   => 'Usuario actualizado correctamente.',
    'eliminado' => 'Usuario eliminado.',
];
require __DIR__ . '/../layout/header.php';

$esSuper = ($_SESSION['rol'] ?? '') === 'superusuario';
$idSesion = (int)$_SESSION['id'];
?>

<div class="mg-encabezado">
    <div>
        <h1>Usuarios</h1>
        <p>Personas que pueden entrar a MiGarita y registrar movimientos.</p>
    </div>
    <?php if ($esSuper): ?>
        <div class="acciones">
            <a href="<?= url('user/crear') ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nuevo usuario</a>
        </div>
    <?php endif; ?>
</div>

<?php if (($_GET['error'] ?? '') === 'sin_permisos'): ?>
    <div class="alert alert-warning" role="alert"><i class="bi bi-shield-lock"></i><span>Solo un superusuario puede hacer esa acción.</span></div>
<?php endif; ?>

<section class="mg-tarjeta">
    <div class="mg-barra-filtros">
        <label class="mg-buscador mb-0">
            <span class="visually-hidden">Buscar usuario</span>
            <i class="bi bi-search"></i>
            <input type="search" id="buscadorUsuarios" class="form-control" autocomplete="off"
                   placeholder="Buscar por nombre, usuario, cédula o correo">
        </label>
    </div>

    <table class="mg-tabla">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Rol</th>
                <th class="derecha"><span class="visually-hidden">Acciones</span></th>
            </tr>
        </thead>
        <tbody id="tablaUsuarios">
            <?php foreach ($usuarios as $u):
                $id = (int)$u['id'];
                $esYo = $id === $idSesion;
                $puedeEditar = $esYo || $esSuper;
                $puedeEliminar = $esSuper && !$esYo;
                $nombre = $u['nombre'] . ' ' . $u['apellido'];
            ?>
                <tr data-id="<?= $id ?>">
                    <td class="col-persona">
                        <div class="mg-persona">
                            <span class="mg-avatar"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></span>
                            <div class="datos">
                                <div class="nombre"><?= e($nombre) ?><?= $esYo ? ' <span class="mg-etiqueta ms-1">Tú</span>' : '' ?></div>
                                <div class="sub">@<?= e($u['usuario']) ?> · CC <?= e($u['cedula']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="ocultar-movil"><?= e($u['correo']) ?></td>
                    <td class="col-saldo">
                        <span class="mg-etiqueta <?= clase_tipo($u['rol']) ?>"><?= $u['rol'] === 'superusuario' ? 'Superusuario' : 'Usuario' ?></span>
                    </td>
                    <td class="col-acciones">
                        <div class="acciones">
                            <?php if ($puedeEditar): ?>
                                <a href="<?= url('user/editar/' . $id) ?>" class="btn btn-sm btn-claro"><i class="bi bi-pencil"></i> Editar</a>
                            <?php endif; ?>
                            <?php if ($puedeEliminar): ?>
                                <button type="button" class="btn btn-sm btn-icono text-danger" data-eliminar-usuario="<?= $id ?>"
                                        data-nombre="<?= e($nombre) ?>" aria-label="Eliminar a <?= e($nombre) ?>" title="Eliminar">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="vacio" <?= $usuarios ? 'hidden' : '' ?>>
                <td colspan="4"><i class="bi bi-search d-block fs-3 mb-2"></i>No se encontraron usuarios.</td>
            </tr>
        </tbody>
    </table>

    <div class="mg-pie-tabla">
        <span id="resumenUsuarios"><?= count($usuarios) ?> usuarios</span>
        <?php if (!$esSuper): ?>
            <span><i class="bi bi-info-circle"></i> Solo puedes editar tu propio perfil.</span>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
