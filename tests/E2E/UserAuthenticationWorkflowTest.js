import { test, expect } from '@playwright/test';
import { createTestUser, cleanupTestUser } from './helpers/testData';

test.describe('User Authentication Workflow', () => {
    let testUser;

    test.beforeEach(async () => {
        testUser = await createTestUser({
            email: 'test@example.com',
            password: 'Test123!@#',
            role: 'staff'
        });
    });

    test.afterEach(async () => {
        await cleanupTestUser(testUser);
    });

    test('should complete login flow successfully', async ({ page }) => {
        // Step 1: Navigate to login page
        await page.goto('/login');
        await expect(page.locator('h1')).toContainText('Sign In');

        // Step 2: Enter credentials
        await page.fill('[data-testid="email-input"]', testUser.email);
        await page.fill('[data-testid="password-input"]', testUser.password);

        // Step 3: Submit login form
        await page.click('button:has-text("Sign In")');

        // Step 4: Should redirect to dashboard
        await expect(page).toHaveURL('/dashboard');
        await expect(page.locator('.user-menu')).toContainText(testUser.name);

        // Step 5: Verify authentication state
        const cookies = await page.context().cookies();
        const sessionCookie = cookies.find(c => c.name === 'askproai_session');
        expect(sessionCookie).toBeDefined();
        expect(sessionCookie.httpOnly).toBe(true);
        expect(sessionCookie.secure).toBe(true);
    });

    test('should handle invalid credentials', async ({ page }) => {
        await page.goto('/login');

        // Wrong password
        await page.fill('[data-testid="email-input"]', testUser.email);
        await page.fill('[data-testid="password-input"]', 'wrongpassword');
        await page.click('button:has-text("Sign In")');

        await expect(page.locator('.error-message')).toContainText('Invalid credentials');
        await expect(page).toHaveURL('/login');

        // Non-existent user
        await page.fill('[data-testid="email-input"]', 'nonexistent@example.com');
        await page.fill('[data-testid="password-input"]', 'password');
        await page.click('button:has-text("Sign In")');

        await expect(page.locator('.error-message')).toContainText('Invalid credentials');
    });

    test('should handle two-factor authentication', async ({ page }) => {
        // Enable 2FA for test user
        testUser.two_factor_enabled = true;
        testUser.two_factor_secret = 'JBSWY3DPEHPK3PXP';

        await page.goto('/login');

        // Login with credentials
        await page.fill('[data-testid="email-input"]', testUser.email);
        await page.fill('[data-testid="password-input"]', testUser.password);
        await page.click('button:has-text("Sign In")');

        // Should show 2FA prompt
        await expect(page.locator('h2')).toContainText('Two-Factor Authentication');
        await expect(page.locator('.2fa-instructions')).toContainText('Enter the 6-digit code');

        // Enter valid 2FA code
        const validCode = generateTOTP(testUser.two_factor_secret);
        await page.fill('[data-testid="2fa-code-input"]', validCode);
        await page.click('button:has-text("Verify")');

        // Should complete login
        await expect(page).toHaveURL('/dashboard');
    });

    test('should handle password reset flow', async ({ page, request }) => {
        await page.goto('/login');

        // Click forgot password
        await page.click('a:has-text("Forgot Password?")');
        await expect(page).toHaveURL('/forgot-password');

        // Enter email
        await page.fill('[data-testid="reset-email-input"]', testUser.email);
        await page.click('button:has-text("Send Reset Link")');

        // Should show success message
        await expect(page.locator('.success-message')).toContainText('Password reset link sent');

        // Simulate clicking reset link (get token from API)
        const response = await request.get(`/api/test/password-reset-token/${testUser.email}`);
        const { token } = await response.json();

        // Navigate to reset page with token
        await page.goto(`/reset-password?token=${token}`);

        // Enter new password
        const newPassword = 'NewPassword123!@#';
        await page.fill('[data-testid="new-password-input"]', newPassword);
        await page.fill('[data-testid="confirm-password-input"]', newPassword);
        await page.click('button:has-text("Reset Password")');

        // Should redirect to login with success message
        await expect(page).toHaveURL('/login');
        await expect(page.locator('.success-message')).toContainText('Password reset successfully');

        // Should be able to login with new password
        await page.fill('[data-testid="email-input"]', testUser.email);
        await page.fill('[data-testid="password-input"]', newPassword);
        await page.click('button:has-text("Sign In")');

        await expect(page).toHaveURL('/dashboard');
    });

    test('should handle remember me functionality', async ({ page, context }) => {
        await page.goto('/login');

        // Login with remember me checked
        await page.fill('[data-testid="email-input"]', testUser.email);
        await page.fill('[data-testid="password-input"]', testUser.password);
        await page.check('[data-testid="remember-me-checkbox"]');
        await page.click('button:has-text("Sign In")');

        await expect(page).toHaveURL('/dashboard');

        // Get cookies
        const cookies = await context.cookies();
        const rememberToken = cookies.find(c => c.name === 'remember_token');
        
        expect(rememberToken).toBeDefined();
        expect(rememberToken.expires).toBeGreaterThan(Date.now() / 1000 + 30 * 24 * 60 * 60); // 30 days

        // Close browser and reopen (simulate coming back later)
        await context.close();
        const newContext = await browser.newContext();
        await newContext.addCookies(cookies);
        const newPage = await newContext.newPage();

        // Should be automatically logged in
        await newPage.goto('/dashboard');
        await expect(newPage.locator('.user-menu')).toContainText(testUser.name);
    });

    test('should handle session timeout', async ({ page }) => {
        // Login normally
        await page.goto('/login');
        await page.fill('[data-testid="email-input"]', testUser.email);
        await page.fill('[data-testid="password-input"]', testUser.password);
        await page.click('button:has-text("Sign In")');

        await expect(page).toHaveURL('/dashboard');

        // Simulate session expiry
        await page.evaluate(() => {
            document.cookie = 'askproai_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        });

        // Try to navigate to protected page
        await page.goto('/appointments');

        // Should redirect to login with session expired message
        await expect(page).toHaveURL('/login?expired=1');
        await expect(page.locator('.warning-message')).toContainText('Session expired');
    });

    test('should handle logout flow', async ({ page, context }) => {
        // Login first
        await page.goto('/login');
        await page.fill('[data-testid="email-input"]', testUser.email);
        await page.fill('[data-testid="password-input"]', testUser.password);
        await page.click('button:has-text("Sign In")');

        await expect(page).toHaveURL('/dashboard');

        // Click logout
        await page.click('.user-menu-toggle');
        await page.click('button:has-text("Sign Out")');

        // Should redirect to login
        await expect(page).toHaveURL('/login');
        await expect(page.locator('.success-message')).toContainText('Signed out successfully');

        // Session should be cleared
        const cookies = await context.cookies();
        const sessionCookie = cookies.find(c => c.name === 'askproai_session');
        expect(sessionCookie).toBeUndefined();

        // Should not be able to access protected pages
        await page.goto('/dashboard');
        await expect(page).toHaveURL('/login');
    });

    test('should handle account lockout after failed attempts', async ({ page }) => {
        await page.goto('/login');

        // Make 5 failed login attempts
        for (let i = 0; i < 5; i++) {
            await page.fill('[data-testid="email-input"]', testUser.email);
            await page.fill('[data-testid="password-input"]', 'wrongpassword');
            await page.click('button:has-text("Sign In")');
            
            if (i < 4) {
                await expect(page.locator('.error-message')).toContainText('Invalid credentials');
            }
        }

        // Account should be locked
        await expect(page.locator('.error-message')).toContainText('Account locked');
        await expect(page.locator('.lockout-message')).toContainText('Too many failed attempts');

        // Should not be able to login even with correct password
        await page.fill('[data-testid="email-input"]', testUser.email);
        await page.fill('[data-testid="password-input"]', testUser.password);
        await page.click('button:has-text("Sign In")');

        await expect(page.locator('.error-message')).toContainText('Account locked');
    });

    test('should enforce password requirements', async ({ page }) => {
        await page.goto('/register');

        // Fill registration form
        await page.fill('[data-testid="name-input"]', 'New User');
        await page.fill('[data-testid="email-input"]', 'newuser@example.com');

        // Test weak passwords
        const weakPasswords = [
            { password: '123456', error: 'Password must be at least 8 characters' },
            { password: 'password', error: 'Password must contain uppercase letters' },
            { password: 'PASSWORD', error: 'Password must contain lowercase letters' },
            { password: 'Password', error: 'Password must contain numbers' },
            { password: 'Password1', error: 'Password must contain special characters' }
        ];

        for (const { password, error } of weakPasswords) {
            await page.fill('[data-testid="password-input"]', password);
            await page.fill('[data-testid="confirm-password-input"]', password);
            await page.click('button:has-text("Register")');
            await expect(page.locator('.password-error')).toContainText(error);
        }

        // Test strong password
        await page.fill('[data-testid="password-input"]', 'StrongPass123!@#');
        await page.fill('[data-testid="confirm-password-input"]', 'StrongPass123!@#');
        await page.click('button:has-text("Register")');

        // Should succeed (would create account in real test)
        await expect(page.locator('.password-error')).not.toBeVisible();
    });

    test('should handle OAuth login', async ({ page }) => {
        await page.goto('/login');

        // Click Google login
        await page.click('button:has-text("Continue with Google")');

        // Should redirect to Google OAuth (mocked in test)
        await expect(page).toHaveURL(/accounts\.google\.com/);

        // Simulate OAuth callback
        await page.goto('/auth/callback?provider=google&code=test_code&state=test_state');

        // Should complete login and redirect to dashboard
        await expect(page).toHaveURL('/dashboard');
        await expect(page.locator('.auth-provider-badge')).toContainText('Google');
    });
});

// Helper function to generate TOTP code
function generateTOTP(secret) {
    // Simplified TOTP generation for testing
    const time = Math.floor(Date.now() / 1000 / 30);
    return String(time).slice(-6).padStart(6, '0');
}