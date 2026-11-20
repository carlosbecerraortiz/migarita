// @ts-check
const { defineConfig } = require('@playwright/test');

const PUERTO = 8090;

module.exports = defineConfig({
  testDir: './tests/E2E',
  timeout: 30_000,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
  use: {
    baseURL: `http://127.0.0.1:${PUERTO}`,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  // Playwright levanta la app con una BD SQLite de prueba y la apaga al terminar.
  webServer: {
    command: `php tests/E2E/servidor-e2e.php ${PUERTO}`,
    url: `http://127.0.0.1:${PUERTO}/mi_garita/`,
    reuseExistingServer: false,
    timeout: 30_000,
    // El log de acceso de `php -S` es muy ruidoso para la demo en clase.
    stdout: 'ignore',
    stderr: 'ignore',
  },
});
