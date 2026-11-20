<?php
$titulo = 'Generar informe';
$seccion = 'clientes';
$scripts = ['informes.js'];
require __DIR__ . '/../layout/header.php';

$nombre = $cliente['nombre'] . ' ' . $cliente['apellido'];
$hoy = date('Y-m-d');
?>

<div class="mg-migas"><a href="<?= url('cliente/index') ?>"><i class="bi bi-arrow-left"></i> Clientes</a></div>
<div class="mg-encabezado">
    <div class="mg-persona">
        <span class="mg-avatar grande"><?= e(iniciales($cliente['nombre'], $cliente['apellido'])) ?></span>
        <div>
            <h1>Generar informe</h1>
            <p>Historial de fiados y abonos de <?= e($nombre) ?> en PDF.</p>
        </div>
    </div>
</div>

<section class="mg-tarjeta" style="max-width: 820px">
    <div class="mg-tarjeta-cuerpo">
        <form method="GET" action="/mi_garita/index.php" id="formInforme">
            <input type="hidden" name="url" value="cliente/generarInforme/<?= (int)$cliente['id'] ?>">

            <span class="form-label d-block mb-2">¿Qué periodo quieres incluir?</span>
            <div class="mg-opciones" role="radiogroup">
                <div class="mg-opcion">
                    <input type="radio" name="tipo" id="tipoMes" value="mes" checked>
                    <label for="tipoMes">
                        <i class="bi bi-calendar2-week"></i>
                        <b>Último mes</b>
                        <span>Los movimientos de los últimos 30 días.</span>
                    </label>
                </div>
                <div class="mg-opcion">
                    <input type="radio" name="tipo" id="tipoFechas" value="fechas">
                    <label for="tipoFechas">
                        <i class="bi bi-calendar-range"></i>
                        <b>Entre fechas</b>
                        <span>Elige el día de inicio y de fin.</span>
                    </label>
                </div>
                <div class="mg-opcion">
                    <input type="radio" name="tipo" id="tipoCompleto" value="completo">
                    <label for="tipoCompleto">
                        <i class="bi bi-collection"></i>
                        <b>Completo</b>
                        <span>Todo el historial del cliente.</span>
                    </label>
                </div>
            </div>

            <div class="row g-3 mt-1" id="bloqueFechas" hidden>
                <div class="col-sm-6">
                    <label for="desde" class="form-label">Desde</label>
                    <input type="date" name="desde" id="desde" class="form-control" max="<?= $hoy ?>" disabled required>
                </div>
                <div class="col-sm-6">
                    <label for="hasta" class="form-label">Hasta</label>
                    <input type="date" name="hasta" id="hasta" class="form-control" value="<?= $hoy ?>" max="<?= $hoy ?>" disabled required>
                </div>
            </div>

            <div class="mg-form-acciones">
                <a href="<?= url('cliente/index') ?>" class="btn btn-claro">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-arrow-down"></i> Descargar PDF</button>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
