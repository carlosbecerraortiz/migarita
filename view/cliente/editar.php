<?php
$titulo = 'Editar cliente';
$seccion = 'clientes';
require __DIR__ . '/../layout/header.php';

// Si hubo un error al guardar, se muestra lo que se había escrito (viene de la sesión)
$datos = ($datosPrevios ?? []) + $cliente;
$accion = url('cliente/actualizar/' . (int)$cliente['id']);
$textoBoton = 'Guardar cambios';
?>

<div class="mg-migas"><a href="<?= url('cliente/index') ?>"><i class="bi bi-arrow-left"></i> Clientes</a></div>
<div class="mg-encabezado">
    <div class="mg-persona">
        <span class="mg-avatar grande"><?= e(iniciales($cliente['nombre'], $cliente['apellido'])) ?></span>
        <div>
            <h1>Editar cliente</h1>
            <p><?= e($cliente['nombre'] . ' ' . $cliente['apellido']) ?></p>
        </div>
    </div>
</div>

<div class="mg-form-layout">
    <section class="mg-tarjeta">
        <div class="mg-tarjeta-cuerpo">
            <?php require __DIR__ . '/_form.php'; ?>
        </div>
    </section>

    <aside class="mg-lateral" aria-label="Estado de cuenta">
        <?php require __DIR__ . '/_resumen.php'; ?>
    </aside>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
