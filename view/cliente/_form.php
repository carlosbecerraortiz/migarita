<?php
/**
 * Formulario de cliente (crear y editar).
 * Espera: $accion (URL del POST), $datos (valores), $tipos (tipos de cliente), $textoBoton.
 */
$mensajes = [
    'cedula_vacia'       => 'La cédula es obligatoria.',
    'cedula_invalida'    => 'La cédula solo puede contener números.',
    'cedula_duplicada'   => 'Ya hay un cliente con esta cédula.',
    'nombre_vacio'       => 'El nombre es obligatorio.',
    'nombre_invalido'    => 'El nombre solo puede contener letras.',
    'apellido_vacio'     => 'El apellido es obligatorio.',
    'apellido_invalido'  => 'El apellido solo puede contener letras.',
    'telefono_vacio'     => 'El teléfono es obligatorio.',
    'telefono_invalido'  => 'El teléfono solo puede contener números.',
    'correo_vacio'       => 'El correo es obligatorio.',
    'correo_invalido'    => 'El correo no es válido.',
    'tipo_cliente_vacio' => 'Elige el tipo de cliente.',
];
$error = $_GET['error'] ?? '';
$mensaje = $mensajes[$error] ?? null;

/** Mensaje bajo el campo con error. */
$ayuda = function (string $campo) use ($mensaje) {
    return error_en($campo) && $mensaje ? '<div class="invalid-feedback">' . e($mensaje) . '</div>' : '';
};
?>
<?php if ($mensaje): ?>
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle"></i><span><?= e($mensaje) ?></span></div>
<?php endif; ?>

<form method="POST" action="<?= e($accion) ?>" novalidate>
    <div class="mg-form-seccion">
        <h3>Datos personales</h3>
        <p>Así aparecerá el cliente en la lista y en los informes.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control<?= error_en('nombre') ?>"
                       placeholder="Ej: Laura" value="<?= e($datos['nombre'] ?? '') ?>" autocomplete="off">
                <?= $ayuda('nombre') ?>
            </div>
            <div class="col-md-6">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" id="apellido" name="apellido" class="form-control<?= error_en('apellido') ?>"
                       placeholder="Ej: Gómez" value="<?= e($datos['apellido'] ?? '') ?>" autocomplete="off">
                <?= $ayuda('apellido') ?>
            </div>
            <div class="col-md-6">
                <label for="cedula" class="form-label">Cédula</label>
                <div class="mg-campo-icono">
                    <i class="bi bi-person-vcard"></i>
                    <input type="text" id="cedula" name="cedula" inputmode="numeric" class="form-control<?= error_en('cedula') ?>"
                           placeholder="Solo números" value="<?= e($datos['cedula'] ?? '') ?>" autocomplete="off">
                    <?= $ayuda('cedula') ?>
                </div>
            </div>
            <div class="col-md-6">
                <label for="id_tipo_cliente" class="form-label">Tipo de cliente</label>
                <select id="id_tipo_cliente" name="id_tipo_cliente" class="form-select<?= error_en('id_tipo_cliente') ?>">
                    <option value="">Elige un tipo…</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (($datos['id_tipo_cliente'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                            <?= e(ucfirst($t['nombre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $ayuda('id_tipo_cliente') ?>
            </div>
        </div>
    </div>

    <div class="mg-form-seccion">
        <h3>Contacto</h3>
        <p>El correo se usa para avisarle al cliente cada vez que fía o abona.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="telefono" class="form-label">Teléfono</label>
                <div class="mg-campo-icono">
                    <i class="bi bi-telephone"></i>
                    <input type="tel" id="telefono" name="telefono" inputmode="numeric" class="form-control<?= error_en('telefono') ?>"
                           placeholder="Ej: 3001234567" value="<?= e($datos['telefono'] ?? '') ?>" autocomplete="off">
                    <?= $ayuda('telefono') ?>
                </div>
            </div>
            <div class="col-md-6">
                <label for="correo" class="form-label">Correo</label>
                <div class="mg-campo-icono">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="correo" name="correo" class="form-control<?= error_en('correo') ?>"
                           placeholder="ejemplo@correo.com" value="<?= e($datos['correo'] ?? '') ?>" autocomplete="off">
                    <?= $ayuda('correo') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mg-form-acciones">
        <a href="<?= url('cliente/index') ?>" class="btn btn-claro">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> <?= e($textoBoton) ?></button>
    </div>
</form>
