<?php
$titulo = 'Nuevo cliente';
$seccion = 'clientes';
$scripts = ['vista-previa.js'];
require __DIR__ . '/../layout/header.php';

// Si hubo un error, lo escrito vuelve desde la sesión (nunca por la URL)
$datos = ($datosPrevios ?? []) + [
    'cedula' => '', 'nombre' => '', 'apellido' => '', 'telefono' => '', 'correo' => '', 'id_tipo_cliente' => '',
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

<div class="mg-form-layout">
    <section class="mg-tarjeta">
        <div class="mg-tarjeta-cuerpo">
            <?php require __DIR__ . '/_form.php'; ?>
        </div>
    </section>

    <aside class="mg-lateral" aria-label="Vista previa y ayuda">
        <div class="mg-tarjeta mg-previa mg-solo-pc" aria-hidden="true">
            <span class="mg-avatar grande" data-previa="iniciales">?</span>
            <div class="nombre" data-previa="nombre"><span class="vacio">Nombre del cliente</span></div>
            <div class="sub" data-previa="detalle">CC · teléfono</div>
            <span class="mg-etiqueta" data-previa="tipo" hidden></span>
            <div class="mg-saldo-lateral al-dia mt-3">
                <span class="etiqueta">Saldo inicial</span>
                <span class="valor">$ 0</span>
            </div>
        </div>

        <div class="mg-tarjeta">
            <div class="mg-tarjeta-cuerpo">
                <h2>Después de registrarlo</h2>
                <ul class="mg-ayuda">
                    <li><i class="bi bi-plus-lg"></i><div><b>Fíale o recibe abonos</b>Desde la lista de clientes, con el botón “Movimiento”.</div></li>
                    <li><i class="bi bi-envelope-check"></i><div><b>Aviso por correo</b>El cliente recibe un correo cada vez que fía o abona.</div></li>
                    <li><i class="bi bi-file-earmark-pdf"></i><div><b>Informe en PDF</b>Puedes descargar su historial cuando lo necesites.</div></li>
                </ul>
            </div>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
