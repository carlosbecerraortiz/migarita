# MiGarita

Sistema web en PHP para gestionar los clientes de una garita: registro de clientes, compras fiadas, abonos, saldos, notificaciones por correo, informes en PDF y usuarios con roles.

## Tecnologías

- PHP 8.2 (MVC propio) + MySQL/MariaDB (XAMPP)
- Bootstrap 5, Dompdf (informes PDF), PHPMailer (correo)
- PHPUnit 11 + Xdebug y Playwright (pruebas)

## Instalación

1. Clonar el repositorio dentro de `htdocs` con el nombre de carpeta **`mi_garita`**. Las rutas de la app usan `/mi_garita/`.
2. Importar `migarita.sql` en phpMyAdmin (base `migarita`).
3. Ejecutar `composer install`. Si se queda colgado, usar `composer install --prefer-source`.
4. Copiar `app/config/email_config.example.php` como `app/config/email_config.php` y completar los datos SMTP.
5. Abrir `http://localhost/mi_garita/`.

## Estructura

```
app/validators/   reglas de validación (funciones puras)
app/services/     lógica de negocio (clientes, movimientos, usuarios, login, informes, correo)
model/            acceso a datos (PDO)
controller/       controladores HTTP
view/             vistas
tests/            pruebas: Unit, Integration, Functional, E2E
```

## Pruebas

```bash
composer test            # 192 pruebas
composer test:coverage   # cobertura (consola + HTML), falla si es menor al 60 %
composer coverage:ver    # abre el resumen de cobertura en español
```

Instrucciones completas, mapa de la suite, uso de IA y prueba de fuego en **[TESTING.md](TESTING.md)**.
