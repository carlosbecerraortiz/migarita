<?php
/**
 * Cierre del layout de la app.
 *
 * Variables opcionales:
 *   $scripts  Lista de archivos JS de assets/js a cargar en esta página.
 *   $avisos   Mensajes de éxito según ?ok=... (código => texto).
 */
$scripts = $scripts ?? [];
$avisos = $avisos ?? [];
$codigoOk = $_GET['ok'] ?? '';
?>
    </main>

    <!-- Navegación inferior (celular) -->
    <nav class="mg-nav-movil" aria-label="Navegación">
        <?php foreach ($menu as $clave => [$texto, $icono, $ruta]): ?>
            <a href="<?= url($ruta) ?>" class="<?= $seccion === $clave ? 'activo' : '' ?>">
                <i class="bi <?= $icono ?>"></i><?= $texto ?>
            </a>
        <?php endforeach; ?>
        <form method="POST" action="/mi_garita/logout.php" class="m-0 d-contents">
            <?= Csrf::campo() ?>
            <button type="submit"><i class="bi bi-box-arrow-right"></i>Salir</button>
        </form>
    </nav>
</div>

<!-- Avisos de éxito -->
<div class="mg-avisos" aria-live="polite">
    <?php if ($codigoOk !== '' && isset($avisos[$codigoOk])): ?>
        <div class="mg-aviso" role="status">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= e($avisos[$codigoOk]) ?></span>
            <button type="button" class="btn-close btn-close-sm" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
</div>

<!-- Confirmación genérica (eliminar) -->
<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body">
                <div class="mg-modal-icono peligro"><i class="bi bi-trash3"></i></div>
                <h5 id="confirmarTitulo">¿Eliminar?</h5>
                <p class="text-secondary mb-0" id="confirmarTexto"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-claro" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarAceptar">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- Mensaje de error / aviso -->
<div class="modal fade" id="modalAviso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body">
                <div class="mg-modal-icono alerta"><i class="bi bi-exclamation-lg"></i></div>
                <h5 id="avisoTitulo"></h5>
                <p class="text-secondary mb-0" id="avisoTexto"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>?v=10"></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= asset('js/' . $script) ?>?v=10"></script>
<?php endforeach; ?>
</body>
</html>
