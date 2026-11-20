<?php

namespace Tests\Functional;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\BaseDatosPrueba;
use Tests\Support\RespuestaHttp;
use Tests\Support\ServidorPrueba;

/**
 * Pruebas FUNCIONALES (end-to-end a nivel HTTP): se levanta la app REAL con
 * `php -S` contra una BD SQLite de prueba y se hacen peticiones HTTP como lo
 * haría el navegador. Se validan códigos de estado, redirecciones, JSON y que
 * los datos queden realmente guardados en la base de datos.
 */
final class AppHttpTest extends TestCase
{
    private static ServidorPrueba $app;
    private const URL = '/mi_garita/index.php?url=';

    public static function setUpBeforeClass(): void
    {
        self::$app = new ServidorPrueba();
        self::$app->iniciar();
    }

    public static function tearDownAfterClass(): void
    {
        self::$app->detener();
    }

    protected function setUp(): void
    {
        // Cada prueba arranca sin sesión y sin clientes (independencia entre pruebas).
        self::$app->cerrarSesion();
        self::$app->bd->exec('DELETE FROM deudas; DELETE FROM clientes; DELETE FROM bitacora_login;');
    }

    private function bd(): PDO
    {
        return self::$app->bd;
    }

    private function entrarComoAdmin(): void
    {
        $r = self::$app->iniciarSesionComo('admin', BaseDatosPrueba::PASSWORD_ADMIN);
        $this->assertSame(302, $r->estado);
    }

    private function crearClienteHttp(array $cambios = []): \Tests\Support\RespuestaHttp
    {
        return self::$app->post(self::URL . 'cliente/guardar', array_merge([
            'cedula' => '1065777666', 'nombre' => 'Valentina', 'apellido' => 'Pérez',
            'telefono' => '3015556677', 'correo' => 'vale@correo.test', 'id_tipo_cliente' => '1',
        ], $cambios));
    }

    /** La redirección va a una URL LIMPIA: solo la ruta, sin códigos de aviso ni datos. */
    private function assertRedirigeLimpioA(string $ruta, RespuestaHttp $r): void
    {
        $this->assertSame(302, $r->estado);
        $this->assertSame('/mi_garita/index.php?url=' . $ruta, $r->redirigeA());
    }

    /** Sigue la redirección como lo haría el navegador y devuelve la página destino. */
    private function seguir(RespuestaHttp $r): RespuestaHttp
    {
        return self::$app->get($r->redirigeA());
    }

    // ---------------------------------------------------------------- Login

    #[Test]
    public function la_pagina_de_login_responde_200(): void
    {
        $r = self::$app->get('/mi_garita/');

        $this->assertSame(200, $r->estado);
        $this->assertStringContainsString('name="username"', $r->cuerpo);
    }

    #[Test]
    public function sin_sesion_las_paginas_privadas_redirigen_al_login(): void
    {
        $r = self::$app->get(self::URL . 'cliente/index');

        $this->assertSame(302, $r->estado);
        $this->assertStringContainsString('url=login/index', $r->redirigeA());
    }

    #[Test]
    public function login_con_contrasena_incorrecta_redirige_con_error_y_queda_en_bitacora(): void
    {
        // Act
        $r = self::$app->iniciarSesionComo('admin', 'clave-equivocada');

        // Assert HTTP: URL limpia y el mensaje se muestra en la página siguiente
        $this->assertRedirigeLimpioA('login/index', $r);
        $this->assertStringContainsString('La contraseña no es correcta', $this->seguir($r)->cuerpo);

        // Assert persistencia: intento fallido registrado
        $fila = $this->bd()->query('SELECT * FROM bitacora_login')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(0, $fila['exito']);
        $this->assertEquals(1, $fila['id_usuario']);
        $this->assertSame('Windows', $fila['sistema_operativo']);
    }

    #[Test]
    public function login_con_usuario_inexistente_redirige_con_error_usuario(): void
    {
        $r = self::$app->iniciarSesionComo('nadie', 'x');

        $this->assertRedirigeLimpioA('login/index', $r);
        $this->assertStringContainsString('No encontramos ese usuario', $this->seguir($r)->cuerpo);
    }

    #[Test]
    public function login_correcto_entra_al_panel_de_inicio(): void
    {
        // Act
        $login = self::$app->iniciarSesionComo('admin', BaseDatosPrueba::PASSWORD_ADMIN);
        $panel = self::$app->get(self::URL . 'inicio/index');

        // Assert
        $this->assertSame(302, $login->estado);
        $this->assertStringContainsString('url=inicio/index', $login->redirigeA());
        $this->assertSame(200, $panel->estado);
        $this->assertStringContainsString('Ana', $panel->cuerpo, 'Muestra el nombre del usuario en sesión.');
    }

    #[Test]
    public function con_sesion_iniciada_la_pagina_de_login_redirige_al_panel(): void
    {
        $this->entrarComoAdmin();

        $r = self::$app->get('/mi_garita/');

        $this->assertSame(302, $r->estado);
        $this->assertStringContainsString('url=inicio/index', $r->redirigeA());
    }

    #[Test]
    public function sin_sesion_el_panel_redirige_al_login(): void
    {
        $r = self::$app->get(self::URL . 'inicio/index');

        $this->assertSame(302, $r->estado);
        $this->assertStringContainsString('url=login/index', $r->redirigeA());
    }

    #[Test]
    public function el_panel_muestra_el_total_adeudado_y_los_movimientos_de_hoy(): void
    {
        // Arrange: Laura debe 9.000 (fió 12.000 y abonó 3.000 hoy)
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd(), ['nombre' => 'Laura']);
        BaseDatosPrueba::crearMovimiento($this->bd(), $id, 12000);
        BaseDatosPrueba::crearMovimiento($this->bd(), $id, -3000, null, 1);

        // Act
        $r = self::$app->get(self::URL . 'inicio/index');

        // Assert
        $this->assertSame(200, $r->estado);
        $this->assertStringContainsString('$ 9.000', $r->cuerpo, 'Total adeudado');
        $this->assertStringContainsString('$ 12.000', $r->cuerpo, 'Fiado hoy');
        $this->assertStringContainsString('$ 3.000', $r->cuerpo, 'Abonado hoy');
        $this->assertStringContainsString('Laura', $r->cuerpo, 'Aparece en últimos movimientos y mayores deudores');
    }

    #[Test]
    public function la_pagina_de_movimiento_muestra_el_saldo_actual_del_cliente(): void
    {
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd(), ['nombre' => 'Laura']);
        BaseDatosPrueba::crearMovimiento($this->bd(), $id, 4500);

        $r = self::$app->get(self::URL . "cliente/deuda/$id");

        $this->assertSame(200, $r->estado);
        $this->assertStringContainsString('data-saldo="4500"', $r->cuerpo);
        $this->assertStringContainsString('$ 4.500', $r->cuerpo);
    }

    #[Test]
    public function editar_e_informe_de_un_cliente_inexistente_redirigen_con_notfound(): void
    {
        $this->entrarComoAdmin();

        // Cada aviso se usa una sola vez: se sigue cada redirección enseguida, como el navegador
        $editar = self::$app->get(self::URL . 'cliente/editar/99999');
        $paginaEditar = $this->seguir($editar);
        $informe = self::$app->get(self::URL . 'cliente/seleccionarInforme/99999');
        $paginaInforme = $this->seguir($informe);

        $this->assertRedirigeLimpioA('cliente/index', $editar);
        $this->assertStringContainsString('Cliente no encontrado', $paginaEditar->cuerpo);
        $this->assertRedirigeLimpioA('cliente/index', $informe);
        $this->assertStringContainsString('Cliente no encontrado', $paginaInforme->cuerpo);
    }

    #[Test]
    public function editar_e_informe_muestran_el_estado_de_cuenta_del_cliente(): void
    {
        // Arrange: Laura debe 6.000
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd(), ['nombre' => 'Laura']);
        BaseDatosPrueba::crearMovimiento($this->bd(), $id, 6000);

        // Act
        $editar = self::$app->get(self::URL . "cliente/editar/$id");
        $informe = self::$app->get(self::URL . "cliente/seleccionarInforme/$id");

        // Assert
        foreach ([$editar, $informe] as $r) {
            $this->assertSame(200, $r->estado);
            $this->assertStringContainsString('Estado de cuenta', $r->cuerpo);
            $this->assertStringContainsString('$ 6.000', $r->cuerpo);
        }
    }

    #[Test]
    public function los_nombres_se_escapan_para_evitar_inyeccion_de_html(): void
    {
        // Un nombre con HTML guardado directo en la BD no debe ejecutarse en la página
        $this->entrarComoAdmin();
        BaseDatosPrueba::crearCliente($this->bd(), ['nombre' => '<script>alert(1)</script>']);

        $r = self::$app->get(self::URL . 'cliente/index');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $r->cuerpo);
        $this->assertStringContainsString('&lt;script&gt;', $r->cuerpo);
    }

    // ---------------------------------------------------------------- Rutas

    #[Test]
    public function un_controlador_inexistente_responde_404(): void
    {
        $this->assertSame(404, self::$app->get(self::URL . 'noexiste/index')->estado);
    }

    #[Test]
    public function un_metodo_inexistente_responde_404(): void
    {
        $this->entrarComoAdmin();

        $this->assertSame(404, self::$app->get(self::URL . 'cliente/metodoFalso')->estado);
    }

    #[Test]
    public function no_se_pueden_invocar_metodos_privados_desde_la_url(): void
    {
        $this->entrarComoAdmin();

        $this->assertSame(404, self::$app->get(self::URL . 'cliente/servicio')->estado);
    }

    #[Test]
    public function una_ruta_con_caracteres_peligrosos_responde_404(): void
    {
        $this->assertSame(404, self::$app->get(self::URL . '..%2F..%2Fx/index')->estado);
    }

    // ---------------------------------------------------------------- Clientes

    #[Test]
    public function crear_un_cliente_valido_lo_guarda_y_redirige_con_ok(): void
    {
        $this->entrarComoAdmin();

        $r = $this->crearClienteHttp();

        $this->assertRedirigeLimpioA('cliente/index', $r);
        $this->assertStringContainsString('Cliente registrado correctamente.', $this->seguir($r)->cuerpo);
        $cliente = $this->bd()->query("SELECT * FROM clientes WHERE cedula = '1065777666'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Valentina', $cliente['nombre']);
    }

    #[Test]
    public function crear_un_cliente_invalido_no_lo_guarda_y_devuelve_el_error_y_los_datos(): void
    {
        // Arrange
        $this->entrarComoAdmin();

        // Act
        $r = $this->crearClienteHttp(['telefono' => '300-ABC']);
        $formulario = $this->seguir($r);
        $recarga = self::$app->get(self::URL . 'cliente/crear');

        // Assert: ni el error ni los datos personales viajan en la URL (seguridad)
        $this->assertRedirigeLimpioA('cliente/crear', $r);
        $this->assertStringContainsString('El teléfono solo puede contener números.', $formulario->cuerpo);
        $this->assertStringNotContainsString('solo puede contener números', $recarga->cuerpo, 'El aviso no reaparece al recargar.');

        // El formulario recupera lo escrito (desde la sesión) una sola vez
        $this->assertStringContainsString('value="Valentina"', $formulario->cuerpo);
        $this->assertStringNotContainsString('value="Valentina"', $recarga->cuerpo);
        $this->assertSame(0, (int)$this->bd()->query('SELECT COUNT(*) FROM clientes')->fetchColumn());
    }

    #[Test]
    public function crear_un_usuario_invalido_no_expone_los_datos_ni_la_contrasena(): void
    {
        $this->entrarComoAdmin();

        $r = self::$app->post(self::URL . 'user/guardar', [
            'cedula' => '5555', 'nombre' => 'Sofía', 'apellido' => 'Ríos', 'correo' => 'no-es-correo',
            'usuario' => 'sofia', 'password' => 'Secreta123', 'rol' => 'usuario',
        ]);
        $formulario = $this->seguir($r);

        $this->assertRedirigeLimpioA('user/crear', $r);
        $this->assertStringContainsString('El correo no es válido.', $formulario->cuerpo);
        $this->assertStringContainsString('value="sofia"', $formulario->cuerpo, 'Recupera lo escrito.');
        $this->assertStringNotContainsString('Secreta123', $formulario->cuerpo, 'Nunca devuelve la contraseña.');
    }

    #[Test]
    public function el_buscador_ajax_devuelve_json_con_el_saldo(): void
    {
        // Arrange
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd(), ['nombre' => 'Laura']);
        BaseDatosPrueba::crearMovimiento($this->bd(), $id, 6500);

        // Act
        $r = self::$app->post(self::URL . 'cliente/buscarAjax', ['q' => 'Laur']);

        // Assert
        $this->assertSame(200, $r->estado);
        $this->assertStringContainsString('application/json', $r->cabecera('content-type'));
        $this->assertCount(1, $r->json());
        $this->assertEquals(6500, $r->json()[0]['saldo']);
    }

    #[Test]
    public function la_pagina_de_deuda_de_un_cliente_inexistente_redirige_con_notfound(): void
    {
        $this->entrarComoAdmin();

        $r = self::$app->get(self::URL . 'cliente/deuda/99999');

        $this->assertRedirigeLimpioA('cliente/index', $r);
        $this->assertStringContainsString('Cliente no encontrado', $this->seguir($r)->cuerpo);
    }

    // ---------------------------------------------------------------- Movimientos

    #[Test]
    public function registrar_fiado_y_abono_actualiza_el_saldo_en_la_bd(): void
    {
        // Arrange
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd());

        // Act
        $fiado = self::$app->post(self::URL . "cliente/guardarMovimiento/$id", [
            'tipo' => 'fiado', 'monto' => '9000', 'descripcion' => 'Almuerzo',
        ]);
        $paginaFiado = $this->seguir($fiado);
        $abono = self::$app->post(self::URL . "cliente/guardarMovimiento/$id", [
            'tipo' => 'abono', 'monto' => '4000', 'descripcion' => 'Pago', 'metodo_pago' => '1',
        ]);

        // Assert
        $this->assertRedirigeLimpioA('cliente/index', $fiado);
        $this->assertStringContainsString('Movimiento registrado correctamente.', $paginaFiado->cuerpo);
        $this->assertRedirigeLimpioA('cliente/index', $abono);
        $saldo = $this->bd()->query("SELECT saldo FROM vista_saldos WHERE id = $id")->fetchColumn();
        $this->assertEquals(5000, $saldo);
    }

    #[Test]
    public function un_abono_mayor_que_la_deuda_se_rechaza(): void
    {
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd());
        BaseDatosPrueba::crearMovimiento($this->bd(), $id, 2000);

        $r = self::$app->post(self::URL . "cliente/guardarMovimiento/$id", [
            'tipo' => 'abono', 'monto' => '2001', 'descripcion' => 'Pago', 'metodo_pago' => '1',
        ]);

        $this->assertRedirigeLimpioA("cliente/deuda/$id", $r);
        $this->assertStringContainsString('no puede ser mayor que lo que debe', $this->seguir($r)->cuerpo);
        $this->assertSame(1, (int)$this->bd()->query('SELECT COUNT(*) FROM deudas')->fetchColumn());
    }

    // ---------------------------------------------------------------- Eliminar

    #[Test]
    public function eliminar_ajax_un_cliente_con_deuda_responde_422(): void
    {
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd());
        BaseDatosPrueba::crearMovimiento($this->bd(), $id, 3500);

        $r = self::$app->post(self::URL . 'cliente/eliminarAjax', ['id' => $id]);

        $this->assertSame(422, $r->estado);
        $this->assertFalse($r->json()['ok']);
        $this->assertStringContainsString('3500', $r->json()['msg']);
        $this->assertNotFalse($this->bd()->query("SELECT id FROM clientes WHERE id = $id")->fetchColumn());
    }

    #[Test]
    public function eliminar_ajax_un_cliente_inexistente_responde_404(): void
    {
        $this->entrarComoAdmin();

        $r = self::$app->post(self::URL . 'cliente/eliminarAjax', ['id' => 99999]);

        $this->assertSame(404, $r->estado);
    }

    #[Test]
    public function eliminar_ajax_un_cliente_al_dia_responde_200_y_lo_borra(): void
    {
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd());

        $r = self::$app->post(self::URL . 'cliente/eliminarAjax', ['id' => $id]);

        $this->assertSame(200, $r->estado);
        $this->assertSame(['ok' => true], $r->json());
        $this->assertFalse($this->bd()->query("SELECT id FROM clientes WHERE id = $id")->fetchColumn());
    }

    // ---------------------------------------------------------------- Permisos

    #[Test]
    public function un_usuario_normal_no_puede_entrar_a_crear_usuarios(): void
    {
        self::$app->iniciarSesionComo('cajero', BaseDatosPrueba::PASSWORD_CAJERO);

        $r = self::$app->get(self::URL . 'user/crear');

        $this->assertRedirigeLimpioA('user/index', $r);
        $this->assertStringContainsString('Solo un superusuario puede hacer esa acción.', $this->seguir($r)->cuerpo);
    }

    #[Test]
    public function un_usuario_normal_no_puede_eliminar_usuarios_responde_403(): void
    {
        self::$app->iniciarSesionComo('cajero', BaseDatosPrueba::PASSWORD_CAJERO);

        $r = self::$app->post(self::URL . 'user/eliminarAjax', ['id' => '1']);

        $this->assertSame(403, $r->estado);
        $this->assertSame(['error' => 'sin_permisos'], $r->json());
        $this->assertNotFalse($this->bd()->query('SELECT id FROM usuarios WHERE id = 1')->fetchColumn());
    }

    #[Test]
    public function el_superusuario_no_puede_eliminarse_a_si_mismo_responde_422(): void
    {
        $this->entrarComoAdmin();

        $r = self::$app->post(self::URL . 'user/eliminarAjax', ['id' => '1']);

        $this->assertSame(422, $r->estado);
        $this->assertSame(['error' => 'auto_eliminar'], $r->json());
    }

    #[Test]
    public function eliminar_usuario_sin_id_responde_400(): void
    {
        $this->entrarComoAdmin();

        $this->assertSame(400, self::$app->post(self::URL . 'user/eliminarAjax')->estado);
    }

    // ---------------------------------------------------------------- URLs sin datos

    #[Test]
    public function no_se_pueden_falsificar_avisos_escribiendolos_en_la_url(): void
    {
        // Un enlace malicioso con "?ok=..." o "?error=saldo&monto=..." no debe mostrar nada
        $this->entrarComoAdmin();

        $r = self::$app->get(self::URL . 'cliente/index&ok=creado&error=saldo&monto=999999');

        $this->assertSame(200, $r->estado);
        $this->assertStringNotContainsString('Cliente registrado correctamente.', $r->cuerpo);
        $this->assertStringNotContainsString('999.999', $r->cuerpo);
    }

    #[Test]
    public function eliminar_un_cliente_con_un_enlace_get_no_hace_nada(): void
    {
        // Abrir una URL nunca debe borrar datos
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd());

        $r = self::$app->get(self::URL . "cliente/eliminar/$id");

        $this->assertRedirigeLimpioA('cliente/index', $r);
        $this->assertNotFalse($this->bd()->query("SELECT id FROM clientes WHERE id = $id")->fetchColumn());
    }

    #[Test]
    public function eliminar_un_cliente_al_dia_por_post_lo_borra_y_avisa_sin_datos_en_la_url(): void
    {
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd());

        $r = self::$app->post(self::URL . "cliente/eliminar/$id");

        $this->assertRedirigeLimpioA('cliente/index', $r);
        $this->assertStringContainsString('Cliente eliminado.', $this->seguir($r)->cuerpo);
        $this->assertFalse($this->bd()->query("SELECT id FROM clientes WHERE id = $id")->fetchColumn());
    }

    #[Test]
    public function eliminar_un_cliente_que_debe_avisa_el_saldo_sin_ponerlo_en_la_url(): void
    {
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd());
        BaseDatosPrueba::crearMovimiento($this->bd(), $id, 4500);

        $r = self::$app->post(self::URL . "cliente/eliminar/$id");
        $pagina = $this->seguir($r);
        $recarga = self::$app->get(self::URL . 'cliente/index');

        $this->assertRedirigeLimpioA('cliente/index', $r);
        $this->assertStringContainsString('saldo pendiente de $ 4.500', $pagina->cuerpo);
        $this->assertStringNotContainsString('saldo pendiente', $recarga->cuerpo, 'El aviso no reaparece al recargar.');
    }

    #[Test]
    public function eliminar_ajax_por_get_responde_405(): void
    {
        $this->entrarComoAdmin();

        $this->assertSame(405, self::$app->get(self::URL . 'cliente/eliminarAjax&id=1')->estado);
    }

    #[Test]
    public function el_informe_no_acepta_parametros_en_la_url(): void
    {
        $this->entrarComoAdmin();
        $id = BaseDatosPrueba::crearCliente($this->bd());

        $r = self::$app->get(self::URL . "cliente/generarInforme/$id&tipo=fechas&desde=2026-01-01&hasta=2026-12-31");

        $this->assertRedirigeLimpioA("cliente/seleccionarInforme/$id", $r);
    }

    #[Test]
    public function el_buscador_ignora_lo_que_venga_en_la_url(): void
    {
        // La búsqueda solo se lee del cuerpo POST; un ?q= en la URL no filtra nada
        $this->entrarComoAdmin();
        BaseDatosPrueba::crearCliente($this->bd(), ['cedula' => '1', 'nombre' => 'Laura']);
        BaseDatosPrueba::crearCliente($this->bd(), ['cedula' => '2', 'nombre' => 'Pedro']);

        $r = self::$app->post(self::URL . 'cliente/buscarAjax&q=Laura', []);

        $this->assertCount(2, $r->json());
    }

    #[Test]
    public function logout_cierra_la_sesion(): void
    {
        // Arrange
        $this->entrarComoAdmin();

        // Act
        $salir = self::$app->get('/mi_garita/logout.php');
        $despues = self::$app->get(self::URL . 'cliente/index');

        // Assert
        $this->assertSame(302, $salir->estado);
        $this->assertStringContainsString('url=login/index', $despues->redirigeA());
    }
}
