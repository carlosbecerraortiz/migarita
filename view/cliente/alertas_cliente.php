<?php if (isset($_GET['error'])): ?>

    <?php
    $errores = [
        "cedula_vacia"       => "La cédula es obligatoria.",
        "cedula_invalida"    => "La cédula solo puede contener números.",
        "cedula_duplicada"   => "Esta cédula ya está registrada.",

        "nombre_vacio"       => "El nombre es obligatorio.",
        "nombre_invalido"    => "El nombre solo debe contener letras.",

        "apellido_vacio"     => "El apellido es obligatorio.",
        "apellido_invalido"  => "El apellido solo debe contener letras.",

        "telefono_vacio"     => "El teléfono es obligatorio.",
        "telefono_invalido"  => "El teléfono solo puede contener números.",

        "correo_vacio"       => "El correo es obligatorio.",
        "correo_invalido"    => "El correo ingresado no es válido.",

        "tipo_cliente_vacio" => "Debe seleccionar un tipo de cliente."
    ];
    ?>

    <div class="alert alert-danger text-center">
        <?= $errores[$_GET['error']] ?>
    </div>

<?php endif; ?>
