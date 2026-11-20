<?php
$titulo = 'Editar usuario';
$seccion = 'usuarios';
$scripts = ['togglePasswordCrear.js', 'vista-previa.js'];
require __DIR__ . '/../layout/header.php';

$datos = $user;
$accion = url('user/actualizar/' . (int)$user['id']);
$esEdicion = true;
$textoBoton = 'Guardar cambios';
$esYo = (int)$_SESSION['id'] === (int)$user['id'];
?>

<div class="mg-migas"><a href="<?= url('user/index') ?>"><i class="bi bi-arrow-left"></i> Usuarios</a></div>
<div class="mg-encabezado">
    <div class="mg-persona">
        <span class="mg-avatar grande"><?= e(iniciales($user['nombre'], $user['apellido'])) ?></span>
        <div>
            <h1><?= $esYo ? 'Mi perfil' : 'Editar usuario' ?></h1>
            <p><?= e($user['nombre'] . ' ' . $user['apellido']) ?> · @<?= e($user['usuario']) ?></p>
        </div>
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
