<?php
// Copia este archivo como email_config.php y completa tus datos SMTP reales.
// email_config.php está en .gitignore para no subir credenciales a GitHub.

return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'tu_correo@gmail.com',
    'password' => 'tu_contraseña_de_aplicacion',
    'from_email' => 'tu_correo@gmail.com',
    'from_name' => 'MiGarita',
    'smtp_secure' => 'tls',
];
