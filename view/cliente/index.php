<?php
$titulo = 'Clientes';
$seccion = 'clientes';
$scripts = ['clientes.js'];
$avisos = [
    'creado'     => 'Cliente registrado correctamente.',
    'editado'    => 'Cliente actualizado correctamente.',
    'eliminado'  => 'Cliente eliminado.',
    'movimiento' => 'Movimiento registrado correctamente.',
];
require __DIR__ . '/../layout/header.php';

$conDeuda = count(array_filter($clientes, fn($c) => (float)$c['saldo'] > 0));
$alDia = count($clientes) - $conDeuda;
?>

<div class="mg-encabezado">
    <div>
        <h1>Clientes</h1>
        <p>Busca un cliente para registrar un fiado o un abono.</p>
    </div>
    <div class="acciones">
        <a href="<?= url('cliente/crear') ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nuevo cliente</a>
    </div>
</div>

<section class="mg-kpis tres" aria-label="Resumen">
    <div class="mg-tarjeta mg-kpi destacado">
        <div class="etiqueta"><i class="bi bi-wallet2"></i> Total adeudado</div>
        <div class="valor"><?= dinero($totalGeneral) ?></div>
    </div>
    <div class="mg-tarjeta mg-kpi">
        <div class="etiqueta"><i class="bi bi-exclamation-circle deuda"></i> Con deuda</div>
        <div class="valor"><?= $conDeuda ?></div>
    </div>
    <div class="mg-tarjeta mg-kpi">
        <div class="etiqueta"><i class="bi bi-check2-circle"></i> Al día</div>
        <div class="valor"><?= $alDia ?></div>
    </div>
</section>

<section class="mg-tarjeta">
    <div class="mg-barra-filtros">
        <label class="mg-buscador mb-0">
            <span class="visually-hidden">Buscar cliente</span>
            <i class="bi bi-search"></i>
            <input type="search" id="buscador" class="form-control" autocomplete="off"
                   placeholder="Buscar por nombre, apellido o cédula" value="<?= e($_GET['buscar'] ?? '') ?>">
        </label>
        <div class="mg-filtros" role="group" aria-label="Filtrar por estado">
            <button type="button" class="mg-filtro activo" data-filtro="todos">Todos <span class="cuenta" data-cuenta="todos"><?= count($clientes) ?></span></button>
            <button type="button" class="mg-filtro" data-filtro="deuda">Con deuda <span class="cuenta" data-cuenta="deuda"><?= $conDeuda ?></span></button>
            <button type="button" class="mg-filtro" data-filtro="aldia">Al día <span class="cuenta" data-cuenta="aldia"><?= $alDia ?></span></button>
        </div>
    </div>

    <table class="mg-tabla">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Tipo</th>
                <th class="derecha">Saldo</th>
                <th class="derecha"><span class="visually-hidden">Acciones</span></th>
            </tr>
        </thead>
        <tbody id="tablaClientes">
            <?php foreach ($clientes as $c):
                $saldo = (float)$c['saldo'];
                $debe = $saldo > 0;
                $nombre = $c['nombre'] . ' ' . $c['apellido'];
            ?>
                <tr data-saldo="<?= $saldo ?>">
                    <td class="col-persona">
                        <div class="mg-persona">
                            <span class="mg-avatar"><?= e(iniciales($c['nombre'], $c['apellido'])) ?></span>
                            <div class="datos">
                                <a class="nombre d-block text-reset" href="<?= url('cliente/deuda/' . (int)$c['id']) ?>"><?= e($nombre) ?></a>
                                <div class="sub">CC <?= e($c['cedula']) ?><?= $c['telefono'] ? ' · ' . e($c['telefono']) : '' ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="col-tipo">
                        <span class="mg-etiqueta <?= clase_tipo($c['tipo_cliente']) ?>"><?= e(ucfirst($c['tipo_cliente'] ?? '')) ?></span>
                    </td>
                    <td class="col-saldo derecha">
                        <span class="mg-saldo <?= $debe ? 'debe' : 'al-dia' ?>"><?= dinero($saldo) ?></span>
                        <span class="mg-estado"><?= $debe ? 'Debe' : 'Al día' ?></span>
                    </td>
                    <td class="col-acciones">
                        <div class="acciones">
                            <a href="<?= url('cliente/deuda/' . (int)$c['id']) ?>" class="btn btn-sm btn-suave">
                                <i class="bi bi-plus-lg"></i> Movimiento
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-icono" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Más acciones para <?= e($nombre) ?>">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= url('cliente/editar/' . (int)$c['id']) ?>"><i class="bi bi-pencil"></i> Editar datos</a></li>
                                    <li><a class="dropdown-item" href="<?= url('cliente/seleccionarInforme/' . (int)$c['id']) ?>"><i class="bi bi-file-earmark-pdf"></i> Generar informe</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item peligro" href="<?= url('cliente/eliminar/' . (int)$c['id']) ?>"
                                           data-titulo="¿Eliminar cliente?"
                                           data-confirmar="Se eliminará a <?= e($nombre) ?> y todo su historial. Esta acción no se puede deshacer.">
                                            <i class="bi bi-trash3"></i> Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="vacio" <?= $clientes ? 'hidden' : '' ?>>
                <td colspan="4"><i class="bi bi-search d-block fs-3 mb-2"></i>No se encontraron clientes.</td>
            </tr>
        </tbody>
    </table>

    <div class="mg-pie-tabla">
        <span id="resumenLista"><?= count($clientes) ?> clientes</span>
        <span>Saldo total: <b class="text-body num"><?= dinero($totalGeneral) ?></b></span>
    </div>
</section>

<?php if (($_GET['error'] ?? '') === 'saldo'): ?>
    <div hidden id="errorEliminar"
         data-titulo="No se puede eliminar"
         data-texto="Este cliente tiene un saldo pendiente de <?= e(dinero($_GET['monto'] ?? 0)) ?>. Registra el abono completo antes de eliminarlo."></div>
<?php elseif (($_GET['error'] ?? '') === 'notfound'): ?>
    <div hidden id="errorEliminar" data-titulo="Cliente no encontrado" data-texto="El cliente que buscas no existe o ya fue eliminado."></div>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
