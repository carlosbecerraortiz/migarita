// @ts-check
/**
 * Prueba E2E (navegador real con Playwright) del flujo crítico del negocio:
 *   iniciar sesión -> panel de inicio -> abrir la cuenta de un cliente
 *   -> registrar un FIADO -> ver el saldo actualizado
 *   -> intentar abonar MÁS de lo que debe -> la app lo rechaza.
 *
 * Usa la BD de prueba que crea tests/E2E/servidor-e2e.php (cliente Laura Gómez, saldo 0).
 */
const { test, expect } = require('@playwright/test');

test.describe.configure({ mode: 'serial' });

async function iniciarSesion(page) {
  await page.goto('/mi_garita/');
  await page.locator('#yourUsername').fill('admin');
  await page.locator('#yourPassword').fill('admin123');
  await page.getByRole('button', { name: 'Iniciar Sesión' }).click();
  await expect(page).toHaveURL(/url=inicio\/index/);
}

function filaDe(page, nombre) {
  return page.locator('#tablaClientes tr', { hasText: nombre });
}

test('login con contraseña incorrecta muestra el error', async ({ page }) => {
  await page.goto('/mi_garita/');
  await page.locator('#yourUsername').fill('admin');
  await page.locator('#yourPassword').fill('incorrecta');
  await page.getByRole('button', { name: 'Iniciar Sesión' }).click();

  await expect(page.getByRole('alert')).toContainText('Usuario o contraseña incorrectos');
  // Seguridad: tras mostrar la alerta, el código de error no queda en la URL
  await expect(page).toHaveURL(/url=login\/index$/);
});

test('registrar un fiado actualiza el saldo del cliente', async ({ page }) => {
  // Arrange
  await iniciarSesion(page);
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Ana');
  await page.goto('/mi_garita/index.php?url=cliente/index');
  await expect(filaDe(page, 'Laura')).toContainText('$ 0');

  // Act: abrir la cuenta del cliente y registrar un fiado de 15.000
  await filaDe(page, 'Laura').getByRole('link', { name: 'Movimiento' }).click();
  await page.locator('label[for="tipoFiado"]').click();
  await expect(page.locator('#metodo_pago')).toBeDisabled(); // un fiado no lleva método de pago
  await page.locator('#monto').fill('15000');
  await expect(page.locator('#monto')).toHaveValue('15.000'); // formato colombiano en vivo
  await page.getByRole('button', { name: 'Almuerzo' }).click(); // descripción rápida
  await expect(page.locator('#descripcion')).toHaveValue('Almuerzo');
  await page.getByRole('button', { name: 'Registrar' }).click();

  // Assert: aviso de éxito y saldo actualizado en la lista
  await expect(page.getByRole('status')).toContainText('Movimiento registrado');
  await expect(filaDe(page, 'Laura')).toContainText('$ 15.000');
});

test('un abono mayor que la deuda es rechazado', async ({ page }) => {
  // Arrange
  await iniciarSesion(page);
  await page.goto('/mi_garita/index.php?url=cliente/deuda/1');
  await expect(page.locator('#saldoActual')).toHaveText('$ 15.000');

  // Act: intentar abonar 20.000 cuando debe 15.000
  await page.locator('label[for="tipoAbono"]').click();
  await expect(page.locator('#metodo_pago')).toBeEnabled();
  await page.locator('#metodo_pago').selectOption('1');
  await page.locator('#monto').fill('20000');
  await expect(page.locator('#avisoAbono')).toBeVisible(); // aviso inmediato en el formulario
  await page.locator('#descripcion').fill('Pago');
  await page.getByRole('button', { name: 'Registrar' }).click();

  // Assert: el servidor lo rechaza y el saldo no cambió
  await expect(page.getByRole('alert')).toContainText('no puede ser mayor');
  await expect(page).toHaveURL(/url=cliente\/deuda\/1$/); // URL limpia, sin "error=..."
  await expect(page.locator('#saldoActual')).toHaveText('$ 15.000');
});
