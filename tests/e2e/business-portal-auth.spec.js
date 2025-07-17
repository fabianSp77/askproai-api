/**
 * Business Portal Authentication E2E Tests
 * 
 * These tests verify the authentication flow including login, logout,
 * session management, and permission checks.
 */

const { test, expect } = require('@playwright/test');

// Test configuration
const BASE_URL = process.env.APP_URL || 'http://localhost:8000';
const TEST_USER = {
  admin: {
    email: 'admin@test-gmbh.de',
    password: 'Test123!'
  },
  staff: {
    email: 'staff@test-gmbh.de', 
    password: 'Test123!'
  },
  invalid: {
    email: 'invalid@example.com',
    password: 'wrongpassword'
  }
};

test.describe('Business Portal Authentication', () => {
  
  test.beforeEach(async ({ page }) => {
    await page.goto(`${BASE_URL}/business/login`);
  });

  test('should display login page correctly', async ({ page }) => {
    // Check page elements
    await expect(page.locator('h1')).toContainText('Anmelden');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    await expect(page.locator('a[href*="password/reset"]')).toBeVisible();
    
    // Check for CSRF token
    const csrfToken = await page.locator('input[name="_token"]').getAttribute('value');
    expect(csrfToken).toBeTruthy();
  });

  test('should login successfully with valid credentials', async ({ page }) => {
    // Fill login form
    await page.fill('input[name="email"]', TEST_USER.admin.email);
    await page.fill('input[name="password"]', TEST_USER.admin.password);
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Wait for navigation
    await page.waitForURL('**/business/dashboard');
    
    // Verify successful login
    await expect(page.locator('[data-test="user-menu"]')).toBeVisible();
    await expect(page.locator('h1')).toContainText('Dashboard');
    
    // Check for success message
    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('should show error with invalid credentials', async ({ page }) => {
    // Fill login form with invalid credentials
    await page.fill('input[name="email"]', TEST_USER.invalid.email);
    await page.fill('input[name="password"]', TEST_USER.invalid.password);
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Should stay on login page
    await expect(page).toHaveURL(/.*\/business\/login/);
    
    // Check for error message
    await expect(page.locator('.alert-danger')).toBeVisible();
    await expect(page.locator('.alert-danger')).toContainText('Diese Kombination');
  });

  test('should validate required fields', async ({ page }) => {
    // Try to submit empty form
    await page.click('button[type="submit"]');
    
    // Check for validation messages
    await expect(page.locator('input[name="email"]:invalid')).toBeVisible();
    await expect(page.locator('input[name="password"]:invalid')).toBeVisible();
  });

  test('should handle remember me functionality', async ({ page, context }) => {
    // Check remember me checkbox
    await page.check('input[name="remember"]');
    
    // Login
    await page.fill('input[name="email"]', TEST_USER.admin.email);
    await page.fill('input[name="password"]', TEST_USER.admin.password);
    await page.click('button[type="submit"]');
    
    // Wait for dashboard
    await page.waitForURL('**/business/dashboard');
    
    // Get cookies
    const cookies = await context.cookies();
    const rememberCookie = cookies.find(c => c.name.includes('remember'));
    
    // Verify remember cookie is set
    expect(rememberCookie).toBeTruthy();
    expect(rememberCookie.expires).toBeGreaterThan(Date.now() / 1000 + 86400); // More than 1 day
  });

  test('should logout successfully', async ({ page }) => {
    // First login
    await page.fill('input[name="email"]', TEST_USER.admin.email);
    await page.fill('input[name="password"]', TEST_USER.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/business/dashboard');
    
    // Open user menu
    await page.click('[data-test="user-menu"]');
    
    // Click logout
    await page.click('[data-test="logout-button"]');
    
    // Should redirect to login
    await page.waitForURL('**/business/login');
    
    // Verify logged out
    await expect(page.locator('h1')).toContainText('Anmelden');
    
    // Try to access protected page
    await page.goto(`${BASE_URL}/business/dashboard`);
    await page.waitForURL('**/business/login');
  });

  test('should handle session timeout', async ({ page }) => {
    // Login
    await page.fill('input[name="email"]', TEST_USER.admin.email);
    await page.fill('input[name="password"]', TEST_USER.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/business/dashboard');
    
    // Simulate session expiry by deleting session cookie
    await page.context().clearCookies();
    
    // Try to navigate to protected page
    await page.goto(`${BASE_URL}/business/calls`);
    
    // Should redirect to login
    await page.waitForURL('**/business/login');
    
    // Check for session expired message
    await expect(page.locator('.alert')).toContainText('Sitzung abgelaufen');
  });

  test('should redirect to intended URL after login', async ({ page }) => {
    // Try to access protected page
    await page.goto(`${BASE_URL}/business/calls`);
    
    // Should redirect to login
    await page.waitForURL('**/business/login');
    
    // Login
    await page.fill('input[name="email"]', TEST_USER.admin.email);
    await page.fill('input[name="password"]', TEST_USER.admin.password);
    await page.click('button[type="submit"]');
    
    // Should redirect to originally intended page
    await page.waitForURL('**/business/calls');
    await expect(page.locator('h1')).toContainText('Anrufe');
  });

  test('should handle password reset link', async ({ page }) => {
    // Click password reset link
    await page.click('a[href*="password/reset"]');
    
    // Should navigate to password reset page
    await page.waitForURL('**/password/reset');
    
    // Check page content
    await expect(page.locator('h1')).toContainText('Passwort zurücksetzen');
    await expect(page.locator('input[name="email"]')).toBeVisible();
  });

  test('should enforce role-based access', async ({ page }) => {
    // Login as staff user
    await page.fill('input[name="email"]', TEST_USER.staff.email);
    await page.fill('input[name="password"]', TEST_USER.staff.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/business/dashboard');
    
    // Try to access admin-only page
    await page.goto(`${BASE_URL}/business/billing`);
    
    // Should show 403 or redirect
    const responseCode = page.locator('h1');
    await expect(responseCode).toContainText(/403|Keine Berechtigung/);
  });

  test('should handle concurrent login attempts', async ({ browser }) => {
    // Create two browser contexts
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();
    
    const page1 = await context1.newPage();
    const page2 = await context2.newPage();
    
    // Navigate both to login
    await page1.goto(`${BASE_URL}/business/login`);
    await page2.goto(`${BASE_URL}/business/login`);
    
    // Login with same user on both
    await page1.fill('input[name="email"]', TEST_USER.admin.email);
    await page1.fill('input[name="password"]', TEST_USER.admin.password);
    
    await page2.fill('input[name="email"]', TEST_USER.admin.email);
    await page2.fill('input[name="password"]', TEST_USER.admin.password);
    
    // Submit both
    await Promise.all([
      page1.click('button[type="submit"]'),
      page2.click('button[type="submit"]')
    ]);
    
    // Both should be logged in
    await page1.waitForURL('**/business/dashboard');
    await page2.waitForURL('**/business/dashboard');
    
    // Clean up
    await context1.close();
    await context2.close();
  });

  test('should handle admin viewing as customer', async ({ page }) => {
    // Login as admin
    await page.fill('input[name="email"]', TEST_USER.admin.email);
    await page.fill('input[name="password"]', TEST_USER.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/business/dashboard');
    
    // Navigate to admin panel
    await page.goto(`${BASE_URL}/admin/users`);
    
    // Find view as user button (assuming user ID 2 exists)
    await page.click('[data-test="view-as-user-2"]');
    
    // Should show admin banner
    await expect(page.locator('[data-test="admin-viewing-banner"]')).toBeVisible();
    await expect(page.locator('[data-test="admin-viewing-banner"]')).toContainText('Admin-Ansicht');
    
    // Exit admin view
    await page.click('[data-test="exit-admin-view"]');
    
    // Should return to admin panel
    await page.waitForURL('**/admin/users');
  });

  test('should protect against CSRF attacks', async ({ page }) => {
    // Get initial CSRF token
    const initialToken = await page.locator('input[name="_token"]').getAttribute('value');
    
    // Modify CSRF token
    await page.evaluate(() => {
      document.querySelector('input[name="_token"]').value = 'invalid-token';
    });
    
    // Try to login
    await page.fill('input[name="email"]', TEST_USER.admin.email);
    await page.fill('input[name="password"]', TEST_USER.admin.password);
    await page.click('button[type="submit"]');
    
    // Should show CSRF error
    await expect(page.locator('.alert-danger')).toContainText('419');
  });

  test('should handle network errors gracefully', async ({ page, context }) => {
    // Login first
    await page.fill('input[name="email"]', TEST_USER.admin.email);
    await page.fill('input[name="password"]', TEST_USER.admin.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/business/dashboard');
    
    // Simulate network offline
    await context.setOffline(true);
    
    // Try to navigate
    await page.click('a[href*="/business/calls"]').catch(() => {});
    
    // Should show offline message
    await expect(page.locator('body')).toContainText(/offline|Keine Verbindung/i);
    
    // Restore connection
    await context.setOffline(false);
  });

});

// Test helper functions
async function loginAsAdmin(page) {
  await page.goto(`${BASE_URL}/business/login`);
  await page.fill('input[name="email"]', TEST_USER.admin.email);
  await page.fill('input[name="password"]', TEST_USER.admin.password);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/business/dashboard');
}

async function loginAsStaff(page) {
  await page.goto(`${BASE_URL}/business/login`);
  await page.fill('input[name="email"]', TEST_USER.staff.email);
  await page.fill('input[name="password"]', TEST_USER.staff.password);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/business/dashboard');
}

module.exports = { loginAsAdmin, loginAsStaff };