<?php
$titulo = 'Inicio';
$seccion = 'inicio';
require __DIR__ . '/../layout/header.php';

$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$primerNombre = explode(' ', trim($_SESSION['nombre'] ?? ''))[0];
$hoy = $panel['hoy'];
?>

<div class="mg-encabezado">
    <div>
        <h1><?= e($saludo) ?>, <?= e($primerNombre) ?></h1>
        <p>Así va la cartera de la cafetería hoy.</p>
    </div>
    <div class="acciones d-flex gap-2">
        <a href="<?= url('cliente/index') ?>" class="btn btn-claro"><i class="bi bi-search"></i> Buscar cliente</a>
        <a href="<?= url('cliente/crear') ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nuevo cliente</a>
    </div>
</div>

<section class="mg-kpis" aria-label="Indicadores">
    <div class="mg-tarjeta mg-kpi destacado">
        <div class="etiqueta"><i class="bi bi-wallet2"></i> Total adeudado</div>
        <div class="valor"><?= dinero($panel['totalAdeudado']) ?></div>
        <div class="detalle"><?= $panel['clientesConDeuda'] ?> de <?= $panel['clientes'] ?> clientes con saldo pendiente</div>
    </div>
    <div class="mg-tarjeta mg-kpi">
        <div class="etiqueta"><i class="bi bi-arrow-up-right deuda"></i> Fiado hoy</div>
        <div class="valor"><?= dinero($hoy['fiado']) ?></div>
        <div class="detalle">Compras a crédito del día</div>
    </div>
    <div class="mg-tarjeta mg-kpi">
        <div class="etiqueta"><i class="bi bi-arrow-down-left"></i> Abonado hoy</div>
        <div class="valor"><?= dinero($hoy['abonado']) ?></div>
        <div class="detalle">Pagos recibidos del día</div>
    </div>
    <div class="mg-tarjeta mg-kpi">
        <div class="etiqueta"><i class="bi bi-receipt info"></i> Movimientos hoy</div>
        <div class="valor"><?= $hoy['cantidad'] ?></div>
        <div class="detalle">Fiados y abonos registrados</div>
    </div>
</section>

<div class="mg-panel">
    <section class="mg-tarjeta">
        <div class="mg-tarjeta-cabecera">
            <h2>Últimos movimientos</h2>
            <a href="<?= url('cliente/index') ?>">Ver clientes <i class="bi bi-arrow-right"></i></a>
        </div>
        <?php if (!$panel['ultimos']): ?>
            <div class="mg-vacio"><i class="bi bi-inbox"></i>Aún no hay movimientos registrados.</div>
        <?php else: ?>
            <ul class="mg-movimientos">
                <?php foreach ($panel['ultimos'] as $m):
                    $esAbono = (float)$m['monto'] < 0;
                    $tipo = $esAbono ? 'abono' : 'fiado';
                ?>
                    <li>
                        <span class="mg-mov-icono <?= $tipo ?>">
                            <i class="bi <?= $esAbono ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i>
                        </span>
                        <div style="min-width:0">
                            <a class="titulo d-block text-reset" href="<?= url('cliente/deuda/' . (int)$m['id_cliente']) ?>">
                                <?= e($m['cliente_nombre'] . ' ' . $m['cliente_apellido']) ?>
                            </a>
                            <div class="sub">
                                <?= e($m['descripcion']) ?> · <?= e(fecha_corta($m['fecha'])) ?>
                                <?= $esAbono && $m['metodo_pago'] ? ' · ' . e(ucfirst($m['metodo_pago'])) : '' ?>
                            </div>
                        </div>
                        <div class="monto <?= $tipo ?>"><?= $esAbono ? '−' : '+' ?><?= dinero(abs((float)$m['monto'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="mg-tarjeta">
        <div class="mg-tarjeta-cabecera">
            <h2>Clientes que más deben</h2>
        </div>
        <?php if (!$panel['deudores']): ?>
            <div class="mg-vacio"><i class="bi bi-emoji-smile"></i>Nadie tiene deudas pendientes.</div>
        <?php else: ?>
            <ol class="mg-ranking">
                <?php foreach ($panel['deudores'] as $d): ?>
                    <li>
                        <div class="fila">
                            <a href="<?= url('cliente/deuda/' . (int)$d['id']) ?>" class="mg-persona text-reset">
                                <span class="mg-avatar"><?= e(iniciales($d['nombre'], $d['apellido'])) ?></span>
                                <span class="datos">
                                    <span class="nombre d-block"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></span>
                                    <span class="sub"><?= e(ucfirst($d['tipo_cliente'] ?? '')) ?></span>
                                </span>
                            </a>
                            <span class="mg-saldo debe"><?= dinero($d['saldo']) ?></span>
                        </div>
                        <div class="barra" aria-hidden="true"><span style="width: <?= (float)$d['porcentaje'] ?>%"></span></div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
