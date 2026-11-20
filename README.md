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
composer test            # 246 pruebas
composer test:coverage   # cobertura (consola + HTML), falla si es menor al 60 %
composer coverage:ver    # abre el resumen de cobertura en español
```

Instrucciones completas, mapa de la suite, uso de IA y prueba de fuego en **[TESTING.md](TESTING.md)**. Diapositivas de la sustentación: **[docs/presentacion.html](docs/presentacion.html)**.

## Seguridad

- **Solo se publica lo necesario.** El [.htaccess](.htaccess) deja acceder únicamente a `index.php`, `logout.php` y `assets/`. Todo lo demás responde 404: `.git`, `migarita.sql`, el código PHP, `tests/`, `vendor/`, `node_modules/`, `coverage/`, `docs/`, `composer.json`, etc. También desactiva el listado de carpetas.
- **Ningún dato viaja en la URL.** Los avisos de éxito o error se guardan en la sesión (`app/services/Flash.php`) y se muestran una sola vez. Las redirecciones van siempre a URLs limpias (`?url=cliente/index`). Un `?ok=` o `?error=` escrito a mano se ignora.
- **Formularios con error.** Lo que se escribió vuelve desde la sesión, nunca por la URL. La contraseña no se guarda nunca.
- **Acciones por POST.** Buscar, eliminar y generar informes se hacen por POST. Abrir una URL nunca borra datos, y lo que se busca no queda en el registro del servidor.
- **Cabeceras.** `Referrer-Policy: same-origin` (la URL no se envía a otros sitios), `X-Frame-Options` y `X-Content-Type-Options`.
- **Salida escapada.** Todos los datos que se muestran pasan por `e()` (`view/layout/helpers.php`), incluido el PDF y el correo al cliente.
- **Contraseñas con bcrypt.** Se usa `password_hash()` con sal aleatoria. Las cuentas con el hash antiguo (SHA-256 sin sal) se migran solas la próxima vez que inician sesión.
- **Protección CSRF.** Todo POST (formularios y AJAX) lleva un token secreto por sesión (`app/services/Csrf.php`). Sin él, el servidor responde 403. Cerrar sesión también es POST.
- **Login protegido.**
  - Tras 5 intentos fallidos desde una IP en 15 minutos, se bloquea esa IP.
  - El mensaje es el mismo exista o no el usuario, y el tiempo de respuesta también.
- **Sesión endurecida** (`app/arranque.php`).
  - Cookie `HttpOnly` y `SameSite=Lax`, solo para `/mi_garita/`, y `Secure` con HTTPS.
  - Modo estricto y cierre por 60 minutos de inactividad.
  - Errores ocultos (se registran en el log) y sin `X-Powered-By`.
- **PDF seguro.** Dompdf no descarga recursos externos ni ejecuta PHP, y solo lee archivos del proyecto.
- **Política de contenido (CSP).** Solo se ejecutan scripts propios; no hay scripts en línea. Las páginas con datos se envían con `Cache-Control: no-store`.

> Para producción conviene además servir la app por **HTTPS**. Con HTTPS, la cookie de sesión se marca `Secure` automáticamente.

> Si la app se instala en otro servidor, Apache debe tener `AllowOverride All` y `mod_rewrite` activos para que el `.htaccess` funcione. XAMPP los trae activos.
