export async function loginAsUser(page, email, password) {
    await page.goto('/login');
    await page.fill('[data-testid="email-input"]', email);
    await page.fill('[data-testid="password-input"]', password);
    await page.click('button:has-text("Sign In")');
    await page.waitForURL('/dashboard');
}

export async function switchUser(page, email, password) {
    // Logout current user
    await page.click('.user-menu-toggle');
    await page.click('button:has-text("Sign Out")');
    await page.waitForURL('/login');
    
    // Login as new user
    await loginAsUser(page, email, password);
}

export async function waitForLoadingToFinish(page) {
    // Wait for any loading indicators to disappear
    await page.waitForSelector('.loading-spinner', { state: 'hidden' });
    await page.waitForSelector('[data-loading="true"]', { state: 'hidden' });
    
    // Wait for network to be idle
    await page.waitForLoadState('networkidle');
}

export async function ensureAuthenticated(page) {
    const cookies = await page.context().cookies();
    const sessionCookie = cookies.find(c => c.name === 'askproai_session');
    
    if (!sessionCookie) {
        throw new Error('Not authenticated');
    }
    
    return sessionCookie;
}

export async function setupTwoFactorAuth(page, user) {
    // Navigate to security settings
    await page.goto('/settings/security');
    
    // Enable 2FA
    await page.click('button:has-text("Enable Two-Factor Authentication")');
    
    // Get QR code or secret
    const secret = await page.locator('[data-testid="2fa-secret"]').textContent();
    
    // Enter verification code
    const code = generateTOTP(secret);
    await page.fill('[data-testid="2fa-verify-code"]', code);
    await page.click('button:has-text("Verify and Enable")');
    
    return secret;
}

export function generateTOTP(secret, timeStep = 30) {
    // Simplified TOTP generation for testing
    const time = Math.floor(Date.now() / 1000 / timeStep);
    return String(time).slice(-6).padStart(6, '0');
}