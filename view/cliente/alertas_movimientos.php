<?php if (isset($_GET['error'])): ?>

    <?php
    $errores = [
        "monto_invalido"    => "El monto es obligatorio y debe ser mayor a 0.",
        "tipo_invalido"     => "Debe seleccionar Fiado o Abono.",
        "abono_mayor"       => "El abono no puede ser mayor al saldo actual.",
        "descripcion_vacia" => "La descripción no puede estar vacía.",
        "metodo_vacio"      => "Debe seleccionar un método de pago."
    ];
    ?>

    <div class="alert alert-danger text-center">
        <?= $errores[$_GET['error']] ?>
    </div>

<?php endif; ?>
