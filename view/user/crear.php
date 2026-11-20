<?php
$titulo = 'Nuevo usuario';
$seccion = 'usuarios';
$scripts = ['togglePasswordCrear.js', 'vista-previa.js'];
require __DIR__ . '/../layout/header.php';

// Si hubo un error, lo escrito vuelve desde la sesión (nunca por la URL ni la contraseña)
$datos = ($datosPrevios ?? []) + [
    'cedula' => '', 'nombre' => '', 'apellido' => '', 'correo' => '', 'usuario' => '', 'rol' => '',
];
$accion = url('user/guardar');
$esEdicion = false;
$textoBoton = 'Crear usuario';
?>

<div class="mg-migas"><a href="<?= url('user/index') ?>"><i class="bi bi-arrow-left"></i> Usuarios</a></div>
<div class="mg-encabezado">
    <div>
        <h1>Nuevo usuario</h1>
        <p>Dale acceso a MiGarita a una persona de la cafetería.</p>
    </div>
</div>

<div class="mg-form-layout">
    <section class="mg-tarjeta">
        <div class="mg-tarjeta-cuerpo">
            <?php require __DIR__ . '/_form.php'; ?>
        </div>
    </section>

    <?php require __DIR__ . '/_lateral.php'; ?>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
