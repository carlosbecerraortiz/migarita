<?php
/**
 * Formulario de usuario (crear y editar).
 * Espera: $accion, $datos, $esEdicion (bool), $textoBoton.
 */
$mensajes = [
    'cedula_vacia'        => 'La cédula es obligatoria.',
    'cedula_invalida'     => 'La cédula solo puede contener números.',
    'cedula_duplicada'    => 'Ya hay un usuario con esta cédula.',
    'nombre_vacio'        => 'El nombre es obligatorio.',
    'nombre_invalido'     => 'El nombre solo puede contener letras.',
    'apellido_vacio'      => 'El apellido es obligatorio.',
    'apellido_invalido'   => 'El apellido solo puede contener letras.',
    'correo_vacio'        => 'El correo es obligatorio.',
    'correo_invalido'     => 'El correo no es válido.',
    'correo_duplicado'    => 'Este correo ya está registrado.',
    'usuario_vacio'       => 'El nombre de usuario es obligatorio.',
    'usuario_duplicado'   => 'Este nombre de usuario ya existe.',
    'password_vacio'      => 'La contraseña es obligatoria.',
    'password_corto'      => 'La contraseña debe tener al menos 6 caracteres.',
    'rol_vacio'           => 'Elige un rol.',
    'rol_invalido'        => 'El rol elegido no es válido.',
    'no_auto_cambiar_rol' => 'No puedes quitarte tu propio rol de superusuario.',
];
$error = $_GET['error'] ?? '';
$mensaje = $mensajes[$error] ?? ($error !== '' && $error !== 'sin_permisos' ? 'Revisa los datos del formulario.' : null);

$ayuda = function (string $campo) use ($mensaje) {
    return error_en($campo) && $mensaje ? '<div class="invalid-feedback">' . e($mensaje) . '</div>' : '';
};

$esSuper = ($_SESSION['rol'] ?? '') === 'superusuario';
$editandoseASiMismo = $esEdicion && (int)$_SESSION['id'] === (int)($datos['id'] ?? 0);
$rolActual = $datos['rol'] ?? '';
?>
<?php if ($mensaje): ?>
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle"></i><span><?= e($mensaje) ?></span></div>
<?php endif; ?>

<form method="POST" action="<?= e($accion) ?>" novalidate>
    <div class="mg-form-seccion">
        <h3>Datos personales</h3>
        <p>Nombre de la persona que usará la cuenta.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control<?= error_en('nombre') ?>"
                       value="<?= e($datos['nombre'] ?? '') ?>" placeholder="Ej: Sofía" autocomplete="off">
                <?= $ayuda('nombre') ?>
            </div>
            <div class="col-md-6">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" id="apellido" name="apellido" class="form-control<?= error_en('apellido') ?>"
                       value="<?= e($datos['apellido'] ?? '') ?>" placeholder="Ej: Ríos" autocomplete="off">
                <?= $ayuda('apellido') ?>
            </div>
            <div class="col-md-6">
                <label for="cedula" class="form-label">Cédula</label>
                <div class="mg-campo-icono">
                    <i class="bi bi-person-vcard"></i>
                    <input type="text" id="cedula" name="cedula" inputmode="numeric" class="form-control<?= error_en('cedula') ?>"
                           value="<?= e($datos['cedula'] ?? '') ?>" placeholder="Solo números" autocomplete="off">
                    <?= $ayuda('cedula') ?>
                </div>
            </div>
            <div class="col-md-6">
                <label for="correo" class="form-label">Correo</label>
                <div class="mg-campo-icono">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="correo" name="correo" class="form-control<?= error_en('correo') ?>"
                           value="<?= e($datos['correo'] ?? '') ?>" placeholder="ejemplo@correo.com" autocomplete="off">
                    <?= $ayuda('correo') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mg-form-seccion">
        <h3>Acceso</h3>
        <p><?= $esEdicion ? 'Deja la contraseña vacía si no quieres cambiarla.' : 'Con estos datos la persona iniciará sesión.' ?></p>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="usuario" class="form-label">Nombre de usuario</label>
                <div class="mg-campo-icono">
                    <i class="bi bi-at"></i>
                    <input type="text" id="usuario" name="usuario" class="form-control<?= error_en('usuario') ?>"
                           value="<?= e($datos['usuario'] ?? '') ?>" placeholder="Ej: sofia" autocomplete="off" autocapitalize="none">
                    <?= $ayuda('usuario') ?>
                </div>
            </div>
            <div class="col-md-6">
                <label for="passwordCrear" class="form-label"><?= $esEdicion ? 'Nueva contraseña' : 'Contraseña' ?></label>
                <div class="input-group has-validation">
                    <input type="password" id="passwordCrear" name="password" class="form-control<?= error_en('password') ?>"
                           placeholder="<?= $esEdicion ? 'Opcional' : 'Mínimo 6 caracteres' ?>" autocomplete="new-password">
                    <button type="button" class="input-group-text" id="togglePasswordCrear" aria-label="Mostrar u ocultar contraseña">
                        <i class="bi bi-eye-slash" id="iconPasswordCrear"></i>
                    </button>
                    <?= $ayuda('password') ?>
                </div>
            </div>

            <?php if (!$esEdicion || $esSuper): ?>
                <div class="col-md-6">
                    <label for="rol" class="form-label">Rol</label>
                    <?php if ($editandoseASiMismo && $rolActual === 'superusuario'): ?>
                        <select id="rol" class="form-select" disabled>
                            <option selected>Superusuario</option>
                        </select>
                        <input type="hidden" name="rol" value="superusuario">
                        <div class="form-text">No puedes cambiar tu propio rol.</div>
                    <?php else: ?>
                        <select id="rol" name="rol" class="form-select<?= error_en('rol') ?>">
                            <?php if (!$esEdicion): ?><option value="">Elige un rol…</option><?php endif; ?>
                            <option value="usuario" <?= $rolActual === 'usuario' ? 'selected' : '' ?>>Usuario · registra fiados y abonos</option>
                            <option value="superusuario" <?= $rolActual === 'superusuario' ? 'selected' : '' ?>>Superusuario · además administra usuarios</option>
                        </select>
                        <?= $ayuda('rol') ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mg-form-acciones">
        <a href="<?= url('user/index') ?>" class="btn btn-claro">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> <?= e($textoBoton) ?></button>
    </div>
</form>
