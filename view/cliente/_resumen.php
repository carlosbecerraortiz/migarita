<?php
/**
 * Resumen del cliente para la columna lateral: saldo, datos y últimos movimientos.
 * Espera: $cliente, $saldo, $historial.
 */
$saldoNum = (float)$saldo;
$debe = $saldoNum > 0;
?>
<div class="mg-tarjeta">
    <div class="mg-tarjeta-cuerpo">
        <h2>Estado de cuenta</h2>
        <div class="mg-saldo-lateral <?= $debe ? '' : 'al-dia' ?>">
            <span class="etiqueta"><?= $debe ? 'Debe' : 'Al día' ?></span>
            <span class="valor"><?= dinero($saldoNum) ?></span>
        </div>
        <ul class="mg-datos">
            <li><span>Cédula</span><b><?= e($cliente['cedula']) ?></b></li>
            <li><span>Cliente desde</span><b><?= $cliente['fecha_registro'] ? e(date('d/m/Y', strtotime($cliente['fecha_registro']))) : '—' ?></b></li>
        </ul>
    </div>
</div>

<div class="mg-tarjeta">
    <div class="mg-tarjeta-cuerpo">
        <h2>Últimos movimientos</h2>
        <?php if (!$historial): ?>
            <p class="text-secondary mb-0" style="font-size:14px">Sin movimientos todavía.</p>
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
                            <div class="sub"><?= e(fecha_corta($m['fecha'])) ?></div>
                        </div>
                        <div class="monto <?= $tipo ?>"><?= $esAbono ? '−' : '+' ?><?= dinero(abs((float)$m['monto'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
