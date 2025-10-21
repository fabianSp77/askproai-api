import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright Configuration for AskPro AI Gateway E2E Tests
 *
 * Covers:
 * - User journey testing (booking flow)
 * - Admin panel interaction
 * - Error scenario validation
 * - Race condition handling (RCA-driven)
 */
export default defineConfig({
  testDir: './tests/E2E/playwright',

  // Test execution settings
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,

  // Reporter configuration
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/junit.xml' }],
    ['list']
  ],

  // Global test settings
  use: {
    // Base URL for tests
    baseURL: process.env.APP_URL || 'http://localhost:8000',

    // Trace and screenshot settings
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',

    // Browser settings
    actionTimeout: 10000,
    navigationTimeout: 30000,

    // Locale
    locale: 'de-DE',
    timezoneId: 'Europe/Berlin',
  },

  // Browser projects
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },

    // Mobile testing
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },

    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],

  // Web server
  webServer: {
    command: 'php artisan serve',
    url: 'http://localhost:8000',
    reuseExistingServer: !process.env.CI,
    timeout: 120000,
  },

  // Test timeouts
  timeout: 60000,
  expect: {
    timeout: 5000,
  },
});
