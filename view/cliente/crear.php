<?php
$titulo = 'Nuevo cliente';
$seccion = 'clientes';
require __DIR__ . '/../layout/header.php';

// Si hubo un error, el controlador devuelve lo escrito en la URL para no perderlo
$datos = [
    'cedula'          => $_GET['cedula'] ?? '',
    'nombre'          => $_GET['nombre'] ?? '',
    'apellido'        => $_GET['apellido'] ?? '',
    'telefono'        => $_GET['telefono'] ?? '',
    'correo'          => $_GET['correo'] ?? '',
    'id_tipo_cliente' => $_GET['id_tipo_cliente'] ?? '',
];
$accion = url('cliente/guardar');
$textoBoton = 'Registrar cliente';
?>

<div class="mg-migas"><a href="<?= url('cliente/index') ?>"><i class="bi bi-arrow-left"></i> Clientes</a></div>
<div class="mg-encabezado">
    <div>
        <h1>Nuevo cliente</h1>
        <p>Registra a una persona para poder fiarle en la cafetería.</p>
    </div>
</div>

<section class="mg-tarjeta" style="max-width: 820px">
    <div class="mg-tarjeta-cuerpo">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
