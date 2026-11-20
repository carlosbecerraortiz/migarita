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

        "correo_vacio"       => "El correo es obligatorio.",
        "correo_invalido"    => "El correo ingresado no es válido.",
        "correo_duplicado"   => "Este correo ya está registrado.",

        "usuario_vacio"      => "El usuario es obligatorio.",
        "usuario_duplicado"  => "Este usuario ya está registrado.",

        "password_vacio"     => "La contraseña es obligatoria.",
        "password_corto"     => "La contraseña debe tener al menos 6 caracteres.",

        "rol_vacio"          => "Debe seleccionar un rol para el usuario.",
        "rol_invalido"       => "El rol seleccionado no es válido.",
        "no_auto_cambiar_rol" => "No puedes cambiar tu propio rol de superusuario."
    ];
    ?>

    <div class="alert alert-danger text-center">
        <?= $errores[$_GET['error']] ?? "Error desconocido" ?>
    </div>

<?php endif; ?>