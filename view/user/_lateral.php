<?php
/**
 * Columna lateral de los formularios de usuario: vista previa y permisos por rol.
 * Espera: $datos.
 */
$rolPrevio = $datos['rol'] ?? '';
?>
<aside class="mg-lateral" aria-label="Vista previa y roles">
    <div class="mg-tarjeta mg-previa mg-solo-pc" aria-hidden="true">
        <span class="mg-avatar grande" data-previa="iniciales">?</span>
        <div class="nombre" data-previa="nombre"><span class="vacio">Nombre del usuario</span></div>
        <div class="sub" data-previa="detalle">@usuario · CC</div>
        <span class="mg-etiqueta <?= clase_tipo($rolPrevio) ?>" data-previa="tipo" <?= $rolPrevio ? '' : 'hidden' ?>>
            <?= $rolPrevio === 'superusuario' ? 'Superusuario' : ($rolPrevio ? 'Usuario' : '') ?>
        </span>
    </div>

    <div class="mg-tarjeta">
        <div class="mg-tarjeta-cuerpo">
            <h2>Qué puede hacer cada rol</h2>
            <ul class="mg-ayuda">
                <li>
                    <i class="bi bi-person"></i>
                    <div><b>Usuario</b>Registra clientes, fiados y abonos, genera informes y edita su propio perfil.</div>
                </li>
                <li>
                    <i class="bi bi-shield-check"></i>
                    <div><b>Superusuario</b>Todo lo anterior y además crea, edita y elimina usuarios y cambia sus roles.</div>
                </li>
                <li>
                    <i class="bi bi-lock"></i>
                    <div><b>Seguridad</b>Nadie puede eliminarse a sí mismo ni quitarse su propio rol de superusuario.</div>
                </li>
            </ul>
        </div>
    </div>
</aside>
