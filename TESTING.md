# Pruebas de MiGarita

Suite de pruebas automatizadas del Proyecto Integrador **MiGarita** (Ingeniería del Software II, Semana 15).

| Resumen | |
|---|---|
| Herramientas | PHPUnit 11.5 + Xdebug (cobertura) · Playwright (E2E) |
| Total | **186 pruebas PHPUnit** (132 unitarias · 31 integración · 23 funcionales) + **3 E2E** |
| Cobertura de líneas (capa de lógica) | **96,85 %** (492 de 508 líneas) — umbral mínimo exigido: 60 % |
| Prueba de fuego | 5 de 5 reglas de negocio saboteadas fueron detectadas (ver sección 6) |

---

## 1. Instalación

Requisitos: PHP 8.2+ con las extensiones `pdo_sqlite`, `curl` y **Xdebug** (XAMPP ya las trae), Composer y, solo para el E2E, Node.js 18+.

```bash
composer install
```

> **Si `composer install` se queda colgado** (archivos `tmp-*.zip~` de 0 bytes en `vendor/composer`), la red está bloqueando `codeload.github.com`. Detén Composer y usa:
>
> ```bash
> composer install --prefer-source
> ```

Las pruebas **no necesitan MySQL ni XAMPP encendido** y **nunca tocan la base real**: usan SQLite (en memoria o en un archivo temporal) con el mismo esquema de `migarita.sql`. Tampoco envían correos (`MIGARITA_MAIL_ENABLED=0`).

## 2. Ejecutar las pruebas

| Comando | Qué ejecuta |
|---|---|
| `composer test` | Toda la suite PHPUnit (unitarias + integración + funcionales) |
| `composer test:unit` | Solo unitarias (con mocks) |
| `composer test:integration` | Solo integración (BD SQLite en memoria) |
| `composer test:functional` | Solo funcionales HTTP (levanta la app con `php -S`) |
| `composer test:testdox` | Toda la suite, mostrando cada prueba como una frase legible |
| `composer test:coverage` | Suite + cobertura en consola y HTML + **falla si la cobertura es < 60 %** |

Equivalente sin Composer: `php vendor/bin/phpunit --testsuite Unitarias` (o `Integracion`, `Funcionales`).

### Reporte de cobertura

```bash
composer test:coverage
```

- **Consola:** tabla con el porcentaje de clases, métodos y líneas de cada clase.
- **HTML:** abrir `coverage/html/index.html` en el navegador.
- **Umbral:** PHPUnit no trae un `--fail-under` como `pytest-cov`, así que [tests/verificar-cobertura.php](tests/verificar-cobertura.php) lee `coverage/clover.xml` y termina con error si la cobertura es menor a 60 %.

### Prueba E2E en navegador (Playwright)

```bash
npm install
npx playwright install chromium
npm run test:e2e          # sin ventana
npm run test:e2e:ver      # viendo el navegador (ideal para la demo)
```

Playwright levanta la app solo ([tests/E2E/servidor-e2e.php](tests/E2E/servidor-e2e.php)) con una BD SQLite de prueba, y la apaga al terminar.

## 3. Mapa de la suite (Pirámide de pruebas)

```
                 ▲   E2E (Playwright)          3   flujo crítico en navegador real
                ▲▲▲  Funcionales (HTTP)       23   app real + BD de prueba + códigos HTTP
              ▲▲▲▲▲  Integración              31   modelos y casos de uso con SQLite
          ▲▲▲▲▲▲▲▲▲  Unitarias (+ mocks)     132   validadores y servicios aislados
```

| Nivel | Archivo | Pruebas | Qué protege |
|---|---|---:|---|
| Unitaria | `tests/Unit/Validators/ClienteValidatorTest.php` | 27 | Cédula, nombre, teléfono, correo y tipo de cliente; valores límite y orden de errores |
| Unitaria | `tests/Unit/Validators/MovimientoValidatorTest.php` | 22 | Monto > 0, tipos válidos, método de pago en abonos, **abono ≤ saldo**, signo del monto |
| Unitaria | `tests/Unit/Validators/UsuarioValidatorTest.php` | 25 | Datos del usuario, contraseña ≥ 6 caracteres, roles válidos, duplicados |
| Unitaria + **mocks** | `tests/Unit/Services/MovimientoServiceTest.php` | 7 | Registro de fiados/abonos con BD y correo simulados (stub + spy) |
| Unitaria + **mocks** | `tests/Unit/Services/ClienteServiceTest.php` | 11 | Crear/editar cliente, **no eliminar con saldo**, total general |
| Unitaria + **mocks** | `tests/Unit/Services/UsuarioServiceTest.php` | 14 | Permisos por rol, no quitarse el rol, no auto-eliminarse |
| Unitaria + **mocks** | `tests/Unit/Services/AuthServiceTest.php` | 13 | Login, registro en bitácora (spy), detección del sistema operativo |
| Unitaria + **mocks** | `tests/Unit/Services/EmailServiceTest.php` | 5 | Correo al cliente con PHPMailer simulado, fallo de SMTP |
| Unitaria | `tests/Unit/Services/InformeServiceTest.php` | 8 | Rangos de fechas de los informes PDF (fecha "hoy" inyectada) |
| Integración | `tests/Integration/ClienteModelTest.php` | 15 | SQL real: saldo = Σ montos, vista `vista_saldos`, filtros por fecha, cascada |
| Integración | `tests/Integration/UserModelTest.php` | 9 | Usuarios, restricciones UNIQUE/CHECK, bitácora |
| Integración | `tests/Integration/CasosDeUsoTest.php` | 7 | Flujo completo: crear cliente → fiar → abonar → saldar → eliminar |
| Funcional | `tests/Functional/AppHttpTest.php` | 23 | Login, rutas, clientes, movimientos, permisos; HTTP 200/302/400/403/404/422 + persistencia |
| E2E | `tests/E2E/flujo-fiado.spec.js` | 3 | Login, fiado con formato de monto en vivo, abono mayor rechazado |

## 4. Design for Testability (refactorización)

Antes, toda la lógica vivía dentro de los controladores, mezclada con `$_POST`, `header()`, `exit` y `new Modelo()`. Así no se podía probar sin un navegador y una base MySQL. Los cambios fueron:

| Problema | Refactorización |
|---|---|
| Validaciones repetidas dentro de `guardar()` y `actualizar()` | Extraídas a **funciones puras** en `app/validators/` (`ClienteValidator`, `UsuarioValidator`, `MovimientoValidator`) |
| Reglas de negocio en los controladores (saldo, permisos, abono ≤ deuda) | Extraídas a **servicios** en `app/services/` (`ClienteService`, `MovimientoService`, `UsuarioService`, `AuthService`, `InformeService`) |
| `Database` creaba siempre una conexión MySQL fija | **Inyección de dependencias:** el constructor acepta un `PDO` (SQLite en las pruebas). También lee `MIGARITA_DB_DSN` para las pruebas funcionales |
| Los servicios hacían `new ClienteModel()` por dentro | Reciben sus modelos por el **constructor**, así se reemplazan por mocks |
| `EmailService` creaba `PHPMailer` y leía un archivo de configuración | Recibe la configuración y una **fábrica** de PHPMailer, así se inyecta un mock y no se conecta a ningún SMTP |
| `detectarSO()` e informes usaban la fecha del sistema | `InformeService` recibe la fecha de "hoy", así las pruebas son **repetibles** |
| Controladores | Quedaron delgados: leen el request → llaman al servicio → redirigen a las **mismas URL de antes** |

La cobertura se mide sobre `app/` y `model/`, la capa de lógica. Los controladores y las vistas no entran en el porcentaje porque se ejecutan en otro proceso (`php -S`), pero sí los recorren de punta a punta las 23 pruebas funcionales y las 3 E2E.

## 5. Uso de IA (Claude Code)

La suite se construyó con **Claude Code** (agente de IA en VS Code). El equipo definió las reglas de negocio y revisó cada aserción para que verifique lo que **el negocio exige**, no lo que el código hacía.

### Prompts reales usados

1. Se le entregó al agente la guía del taller en PDF con el prompt:
   > *"Tengo que añadir esto a mi proyecto […]"*
2. Decisión de alcance:
   > *"Listo entonces usemos el código actual funcional de PHP para implementar todo lo de las pruebas"*
3. Arranque:
   > *"Sí, empieza con las pruebas"*

### Reglas que se le dieron al agente

- **No cambiar el comportamiento de la app:** mismas URL, mismos códigos de error (`?error=...`) y mismo orden de validación.
- **Patrón AAA** en cada prueba, con comentarios `// Arrange`, `// Act`, `// Assert`, y principios **F.I.R.S.T.**: BD nueva en memoria por prueba y fecha inyectada.
- **Mocks obligatorios** para la BD y el correo en las pruebas unitarias.
- Siempre incluir **valores límite**: abono **igual** al saldo (permitido) vs. abono **un peso mayor** (rechazado), contraseña de 5 vs. 6 caracteres, monto 0 vs. 0,01, fechas en el borde del rango.
- Las aserciones deben salir de las **reglas de negocio**:
  1. El saldo es la suma de los fiados (positivos) menos los abonos (negativos).
  2. Un abono no puede superar la deuda.
  3. Un cliente con saldo distinto de 0 no se elimina.
  4. Solo el superusuario crea usuarios, cambia roles o elimina.
  5. Nadie se elimina a sí mismo ni se quita el rol de superusuario.
  6. Si el correo falla, el movimiento igual queda guardado.

### Casos reales donde hubo que corregir a la IA o el código

1. **Test complaciente evitado: sistema operativo de la bitácora.** El código original revisaba primero "Linux" y "Mac". Un celular Android tiene "Linux" en su User-Agent y un iPhone tiene "Mac OS X", así que la bitácora los registraba mal. Una prueba que copiara el comportamiento actual habría aprobado el error. Se escribió la aserción según lo que el negocio espera (Android → `Android`, iPhone → `iOS`) y se corrigió el orden en `AuthService::detectarSO()`.
2. **Dato de prueba mal escrito por la IA.** En `ClienteModelTest`, la IA buscó la cédula parcial `"206"` esperando encontrar al cliente con cédula `2077`. La prueba falló en la primera corrida y se corrigió a `"207"`. La lección: una prueba en rojo no siempre significa que el código esté mal; hay que revisar si el error está en la prueba.
3. **Defecto encontrado al escribir el mock de correo.** La prueba "sin configuración SMTP no intenta enviar" descubrió que con una configuración vacía (`[]`) el servicio sí intentaba enviar. Se corrigió `EmailService::estaHabilitado()` para exigir el servidor (`host`).
4. **Enrutador sin códigos HTTP correctos.** Al revisar `index.php` para las pruebas funcionales se vio que:
   - una ruta inexistente respondía **200** con un mensaje de error;
   - pedir un método privado del controlador (por ejemplo `cliente/volverGuardar`) pasaba el chequeo `method_exists` y terminaba en un **error fatal 500**;
   - el nombre del controlador no se validaba antes de armar la ruta del archivo.

   Ahora valida los nombres, usa `is_callable` y responde **404**. Hay pruebas funcionales que lo vigilan.

## 6. Prueba de fuego (detectar defectos)

Cada regla se rompió a propósito en el código de producción y se volvió a correr `composer test`:

| Regla saboteada | Cambio introducido | Resultado |
|---|---|---|
| Un abono igual al saldo es válido | `>` por `>=` en `MovimientoValidator::abonoSuperaSaldo` | 🔴 3 pruebas fallan |
| Los abonos restan deuda | Quitar el signo negativo en `MovimientoValidator::montoConSigno` | 🔴 6 pruebas fallan |
| No eliminar clientes con saldo a favor | `$saldo != 0` por `$saldo > 0` en `ClienteService::eliminar` | 🔴 1 prueba falla |
| Contraseña mínima de 6 caracteres | `PASSWORD_MIN = 6` por `5` en `UsuarioValidator` | 🔴 1 prueba falla |
| Código HTTP al auto-eliminarse | `422` por `200` en `UserController::eliminarAjax` | 🔴 1 prueba falla |

Al revertir cada cambio, la suite vuelve a **verde** (186/186).

**Guion sugerido para la demo:**

1. Ejecutar `composer test:coverage`: 186 pruebas en verde y cobertura de 96,85 %.
2. Abrir [app/validators/MovimientoValidator.php](app/validators/MovimientoValidator.php) y en `abonoSuperaSaldo` cambiar `>` por `>=`.
3. Ejecutar `composer test`: la suite queda en 🔴 y falla, entre otras, *"un abono IGUAL al saldo (límite)"*.
4. Revertir con `Ctrl+Z` y volver a ejecutar `composer test`: todo en ✅ verde.
