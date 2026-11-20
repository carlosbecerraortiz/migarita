// @ts-check
/**
 * Prueba E2E (navegador real con Playwright) del flujo crítico del negocio:
 *   iniciar sesión -> abrir la cuenta de un cliente -> registrar un FIADO
 *   -> ver el saldo actualizado en la lista -> intentar abonar MÁS de lo que debe
 *   -> la app lo rechaza.
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
  await expect(page).toHaveURL(/url=cliente\/index/);
}

function filaDe(page, nombre) {
  return page.locator('tr', { hasText: nombre });
}

test('login con contraseña incorrecta muestra el error', async ({ page }) => {
  await page.goto('/mi_garita/');
  await page.locator('#yourUsername').fill('admin');
  await page.locator('#yourPassword').fill('incorrecta');
  await page.getByRole('button', { name: 'Iniciar Sesión' }).click();

  await expect(page).toHaveURL(/error=contrasena/);
});

test('registrar un fiado actualiza el saldo del cliente', async ({ page }) => {
  // Arrange
  await iniciarSesion(page);
  await expect(filaDe(page, 'Laura')).toContainText('$ 0');

  // Act: abrir la cuenta del cliente y registrar un fiado de 15.000
  await page.goto('/mi_garita/index.php?url=cliente/deuda/1');
  await page.locator('#tipo').selectOption('fiado');
  await expect(page.locator('#metodo_pago')).toBeDisabled(); // un fiado no lleva método de pago
  await page.locator('#monto').fill('15000');
  await expect(page.locator('#monto')).toHaveValue('15.000'); // formato colombiano en vivo
  await page.locator('input[name="descripcion"]').fill('Almuerzo ejecutivo');
  await page.getByRole('button', { name: 'Registrar' }).click();

  // Assert
  await expect(page).toHaveURL(/ok=movimiento/);
  await expect(filaDe(page, 'Laura')).toContainText('$ 15.000');
});

test('un abono mayor que la deuda es rechazado', async ({ page }) => {
  // Arrange
  await iniciarSesion(page);
  await page.goto('/mi_garita/index.php?url=cliente/deuda/1');

  // Act: intentar abonar 20.000 cuando debe 15.000
  await page.locator('#tipo').selectOption('abono');
  await expect(page.locator('#metodo_pago')).toBeEnabled();
  await page.locator('#metodo_pago').selectOption({ label: 'efectivo' });
  await page.locator('#monto').fill('20000');
  await page.locator('input[name="descripcion"]').fill('Pago');
  await page.getByRole('button', { name: 'Registrar' }).click();

  // Assert: vuelve al formulario con el error y el saldo no cambió
  await expect(page).toHaveURL(/error=abono_mayor/);
  await page.goto('/mi_garita/index.php?url=cliente/index');
  await expect(filaDe(page, 'Laura')).toContainText('$ 15.000');
});
