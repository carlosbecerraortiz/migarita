<?php
$titulo = 'Registrar movimiento';
$seccion = 'clientes';
$scripts = ['formatoMonto.js', 'movimiento.js'];
require __DIR__ . '/../layout/header.php';

$nombre = $cliente['nombre'] . ' ' . $cliente['apellido'];
$saldoNum = (float)$saldo;
$debe = $saldoNum > 0;

// Valores anteriores si hubo un error de validación
$anterior = $_SESSION['mov_data'] ?? [];
$tipoOld = $anterior['tipo'] ?? 'fiado';
$montoOld = $anterior['monto'] ?? '';
$descOld = $anterior['descripcion'] ?? '';
$metodoOld = $anterior['metodo_pago'] ?? '';
unset($_SESSION['mov_data']);

$errores = [
    'monto_invalido'    => 'Escribe un monto mayor a 0.',
    'tipo_invalido'     => 'Elige si es un fiado o un abono.',
    'abono_mayor'       => 'El abono no puede ser mayor que lo que debe el cliente (' . dinero($saldoNum) . ').',
    'descripcion_vacia' => 'Escribe una descripción del movimiento.',
    'metodo_vacio'      => 'Elige el método de pago del abono.',
];
$error = $_GET['error'] ?? '';
?>

<div class="mg-migas"><a href="<?= url('cliente/index') ?>"><i class="bi bi-arrow-left"></i> Clientes</a></div>
<div class="mg-encabezado">
    <div>
        <h1>Registrar movimiento</h1>
        <p>Anota una compra fiada o un pago de <?= e($nombre) ?>.</p>
    </div>
</div>

<div class="mg-movimiento">
    <!-- Ficha del cliente -->
    <aside class="d-flex flex-column gap-3">
        <div class="mg-tarjeta mg-ficha">
            <span class="mg-avatar grande"><?= e(iniciales($cliente['nombre'], $cliente['apellido'])) ?></span>
            <h2><?= e($nombre) ?></h2>
            <div class="sub">CC <?= e($cliente['cedula']) ?></div>

            <div class="saldo-caja <?= $debe ? '' : 'al-dia' ?>">
                <div class="etiqueta"><?= $debe ? 'Saldo pendiente' : 'Al día' ?></div>
                <div class="valor" id="saldoActual" data-saldo="<?= $saldoNum ?>"><?= dinero($saldoNum) ?></div>
            </div>

            <ul class="contacto">
                <?php if ($cliente['telefono']): ?>
                    <li><i class="bi bi-telephone"></i> <?= e($cliente['telefono']) ?></li>
                <?php endif; ?>
                <?php if ($cliente['correo']): ?>
                    <li><i class="bi bi-envelope"></i> <?= e($cliente['correo']) ?></li>
                <?php endif; ?>
            </ul>

            <div class="botones">
                <a href="<?= url('cliente/editar/' . (int)$cliente['id']) ?>" class="btn btn-sm btn-claro"><i class="bi bi-pencil"></i> Editar</a>
                <a href="<?= url('cliente/seleccionarInforme/' . (int)$cliente['id']) ?>" class="btn btn-sm btn-claro"><i class="bi bi-file-earmark-pdf"></i> Informe</a>
            </div>
        </div>

        <div class="mg-tarjeta mg-historial">
            <div class="mg-tarjeta-cabecera"><h2>Últimos movimientos</h2></div>
            <?php if (!$historial): ?>
                <div class="mg-vacio"><i class="bi bi-inbox"></i>Sin movimientos todavía.</div>
            <?php else: ?>
                <ul class="mg-movimientos">
                    <?php foreach ($historial as $m):
                        $esAbono = (float)$m['monto'] < 0;
                        $tipo = $esAbono ? 'abono' : 'fiado';
                    ?>
                        <li>
                            <span class="mg-mov-icono <?= $tipo ?>"><i class="bi <?= $esAbono ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i></span>
                            <div style="min-width:0">
                                <div class="titulo"><?= e($m['descripcion']) ?></div>
                                <div class="sub"><?= e(fecha_corta($m['fecha'])) ?> · <?= e($m['usuario_nombre']) ?></div>
                            </div>
                            <div class="monto <?= $tipo ?>"><?= $esAbono ? '−' : '+' ?><?= dinero(abs((float)$m['monto'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Formulario -->
    <section class="mg-tarjeta mg-form-mov">
        <div class="mg-tarjeta-cuerpo">
            <?php if ($error !== '' && isset($errores[$error])): ?>
                <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle"></i><span><?= e($errores[$error]) ?></span></div>
            <?php endif; ?>

            <form method="POST" action="<?= url('cliente/guardarMovimiento/' . (int)$cliente['id']) ?>" id="formMovimiento" novalidate>

                <div class="mb-4">
                    <span class="form-label d-block">Tipo de movimiento</span>
                    <div class="mg-segmentado" role="radiogroup" aria-label="Tipo de movimiento">
                        <input type="radio" name="tipo" id="tipoFiado" value="fiado" class="fiado" <?= $tipoOld !== 'abono' ? 'checked' : '' ?>>
                        <label for="tipoFiado"><i class="bi bi-arrow-up-right"></i> Fiado <small>suma a la deuda</small></label>

                        <input type="radio" name="tipo" id="tipoAbono" value="abono" class="abono" <?= $tipoOld === 'abono' ? 'checked' : '' ?>>
                        <label for="tipoAbono"><i class="bi bi-arrow-down-left"></i> Abono <small>resta a la deuda</small></label>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="monto" class="form-label">Monto</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">$</span>
                        <input type="text" name="monto" id="monto" inputmode="numeric" autocomplete="off"
                               class="form-control num<?= error_en('monto') ?>" placeholder="0" value="<?= e($montoOld) ?>">
                    </div>
                    <div class="mg-chips" aria-label="Montos rápidos">
                        <button type="button" class="mg-chip" data-monto="1000">$ 1.000</button>
                        <button type="button" class="mg-chip" data-monto="2000">$ 2.000</button>
                        <button type="button" class="mg-chip" data-monto="5000">$ 5.000</button>
                        <button type="button" class="mg-chip" data-monto="10000">$ 10.000</button>
                        <button type="button" class="mg-chip" id="pagarTodo" data-monto="<?= (int)round(max($saldoNum, 0)) ?>" hidden>
                            Pagar todo (<?= dinero(max($saldoNum, 0)) ?>)
                        </button>
                    </div>
                    <div class="form-text text-danger" id="avisoAbono" hidden>
                        El abono no puede superar lo que debe: <?= dinero(max($saldoNum, 0)) ?>.
                    </div>
                </div>

                <div class="mb-4" id="bloqueMetodo">
                    <label for="metodo_pago" class="form-label">Método de pago</label>
                    <select name="metodo_pago" id="metodo_pago" class="form-select<?= error_en('metodo') ?>">
                        <option value="">Elige cómo pagó</option>
                        <?php foreach ($metodos as $m): ?>
                            <option value="<?= (int)$m['id'] ?>" <?= ($metodoOld == $m['id']) ? 'selected' : '' ?>><?= e(ucfirst($m['nombre'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <input type="text" name="descripcion" id="descripcion" maxlength="255"
                           class="form-control<?= error_en('descripcion') ?>"
                           placeholder="Ej: almuerzo ejecutivo, empanada con gaseosa…" value="<?= e($descOld) ?>">
                    <div class="mg-chips" id="sugerencias" aria-label="Descripciones frecuentes">
                        <button type="button" class="mg-chip" data-texto="Desayuno" data-tipo="fiado">Desayuno</button>
                        <button type="button" class="mg-chip" data-texto="Almuerzo" data-tipo="fiado">Almuerzo</button>
                        <button type="button" class="mg-chip" data-texto="Empanada con gaseosa" data-tipo="fiado">Empanada con gaseosa</button>
                        <button type="button" class="mg-chip" data-texto="Café" data-tipo="fiado">Café</button>
                        <button type="button" class="mg-chip" data-texto="Abono a la deuda" data-tipo="abono">Abono a la deuda</button>
                        <button type="button" class="mg-chip" data-texto="Pago total" data-tipo="abono">Pago total</button>
                    </div>
                </div>

                <div class="mg-form-acciones">
                    <a href="<?= url('cliente/index') ?>" class="btn btn-claro">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-lg" id="btnRegistrar"><i class="bi bi-check2"></i> Registrar</button>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
