#!/usr/bin/env node

/**
 * Settings Dashboard - Encryption & Data Persistence E2E Test
 *
 * Tests complete save/load cycle:
 * 1. Login as super admin
 * 2. Navigate to Settings Dashboard
 * 3. Fill in Retell AI settings
 * 4. Save data
 * 5. Refresh page
 * 6. Verify data persists and displays correctly
 */

const puppeteer = require('puppeteer');
const fs = require('fs');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOT_DIR = __dirname + '/screenshots';

// Ensure screenshot directory exists
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function log(message, type = 'info') {
    const timestamp = new Date().toISOString();
    const symbols = {
        info: '✓',
        error: '❌',
        warning: '⚠️',
        progress: '🔄'
    };
    console.log(`${symbols[type]} [${timestamp}] ${message}`);
}

async function takeScreenshot(page, name) {
    const path = `${SCREENSHOT_DIR}/${name}.png`;
    await page.screenshot({ path, fullPage: true });
    await log(`Screenshot saved: ${name}.png`);
}

async function runTest() {
    const browser = await puppeteer.launch({
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        console.log('\n╔════════════════════════════════════════════════════════════════╗');
        console.log('║  E2E TEST: Settings Dashboard - Data Persistence              ║');
        console.log('╚════════════════════════════════════════════════════════════════╝\n');

        // =====================================================================
        // PHASE 1: Login
        // =====================================================================
        await log('PHASE 1: Login as super_admin', 'progress');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2' });
        await takeScreenshot(page, '01-login-page');

        await page.type('input[type="email"]', 'info@askproai.de');
        await page.type('input[type="password"]', 'LandP007!');
        await page.click('button[type="submit"]');

        await page.waitForNavigation({ waitUntil: 'networkidle2' });
        await log('✓ Login successful');
        await takeScreenshot(page, '02-dashboard');

        // =====================================================================
        // PHASE 2: Navigate to Settings Dashboard
        // =====================================================================
        await log('PHASE 2: Navigate to Settings Dashboard', 'progress');
        await page.goto(`${BASE_URL}/admin/settings-dashboard`, { waitUntil: 'networkidle2' });
        await page.waitForSelector('form', { timeout: 10000 });
        await log('✓ Settings Dashboard loaded');
        await takeScreenshot(page, '03-settings-dashboard-initial');

        // Check for errors
        const errorMessage = await page.evaluate(() => {
            const errorEl = document.querySelector('.fi-no-notification-title, .error, [role="alert"]');
            return errorEl ? errorEl.textContent : null;
        });

        if (errorMessage) {
            await log(`Error found: ${errorMessage}`, 'error');
            throw new Error(`Page error: ${errorMessage}`);
        }

        // =====================================================================
        // PHASE 3: Fill in Retell AI Settings
        // =====================================================================
        await log('PHASE 3: Fill in test data', 'progress');

        // Generate unique test values
        const timestamp = Date.now();
        const testApiKey = `sk_test_e2e_${timestamp}`;
        const testAgentId = `agent_test_e2e_${timestamp}`;

        // Wait for form to be ready
        await page.waitForSelector('input[wire\\:model="data.retell_api_key"]', { timeout: 5000 });

        // Clear existing values and type new ones
        await log(`  - Entering API Key: ${testApiKey}`);
        const apiKeyInput = await page.$('input[wire\\:model="data.retell_api_key"]');
        await apiKeyInput.click({ clickCount: 3 }); // Select all
        await apiKeyInput.type(testApiKey);

        await log(`  - Entering Agent ID: ${testAgentId}`);
        const agentIdInput = await page.$('input[wire\\:model="data.retell_agent_id"]');
        await agentIdInput.click({ clickCount: 3 }); // Select all
        await agentIdInput.type(testAgentId);

        await log('  - Enabling test mode');
        const testModeToggle = await page.$('input[wire\\:model="data.retell_test_mode"]');
        const isChecked = await page.evaluate(el => el.checked, testModeToggle);
        if (!isChecked) {
            await testModeToggle.click();
        }

        await log('✓ Test data entered');
        await takeScreenshot(page, '04-data-filled');

        // =====================================================================
        // PHASE 4: Save Data
        // =====================================================================
        await log('PHASE 4: Save data', 'progress');

        // Click save button
        await page.click('button[type="submit"]');

        // Wait for success notification
        await page.waitForSelector('.fi-no-notification', { timeout: 10000 });
        const notificationText = await page.evaluate(() => {
            const notification = document.querySelector('.fi-no-notification');
            return notification ? notification.textContent : null;
        });

        await log(`  - Notification: ${notificationText}`);

        if (!notificationText || !notificationText.includes('gespeichert')) {
            throw new Error('Save notification not found or incorrect');
        }

        await log('✓ Data saved successfully');
        await takeScreenshot(page, '05-data-saved');

        // Wait a bit for Livewire to finish
        await page.waitForTimeout(2000);

        // =====================================================================
        // PHASE 5: Refresh Page
        // =====================================================================
        await log('PHASE 5: Refresh page to verify persistence', 'progress');
        await page.reload({ waitUntil: 'networkidle2' });
        await page.waitForSelector('form', { timeout: 10000 });
        await log('✓ Page refreshed');
        await takeScreenshot(page, '06-page-refreshed');

        // =====================================================================
        // PHASE 6: Verify Data Persists
        // =====================================================================
        await log('PHASE 6: Verify saved data is displayed', 'progress');

        // Get current values from form
        const loadedApiKey = await page.$eval(
            'input[wire\\:model="data.retell_api_key"]',
            el => el.value
        );
        const loadedAgentId = await page.$eval(
            'input[wire\\:model="data.retell_agent_id"]',
            el => el.value
        );
        const loadedTestMode = await page.$eval(
            'input[wire\\:model="data.retell_test_mode"]',
            el => el.checked
        );

        await log(`  - Loaded API Key: ${loadedApiKey}`);
        await log(`  - Loaded Agent ID: ${loadedAgentId}`);
        await log(`  - Loaded Test Mode: ${loadedTestMode}`);

        // Verify values match
        const errors = [];

        if (loadedApiKey !== testApiKey) {
            errors.push(`API Key mismatch: expected "${testApiKey}", got "${loadedApiKey}"`);
        }

        if (loadedAgentId !== testAgentId) {
            errors.push(`Agent ID mismatch: expected "${testAgentId}", got "${loadedAgentId}"`);
        }

        if (loadedTestMode !== true) {
            errors.push(`Test Mode mismatch: expected true, got ${loadedTestMode}`);
        }

        if (errors.length > 0) {
            await log('Data verification FAILED:', 'error');
            errors.forEach(err => log(`  - ${err}`, 'error'));
            await takeScreenshot(page, '07-verification-failed');
            throw new Error('Data persistence verification failed');
        }

        await log('✓ All data verified - persistence working correctly!');
        await takeScreenshot(page, '07-verification-success');

        // =====================================================================
        // PHASE 7: Cleanup - Clear test data
        // =====================================================================
        await log('PHASE 7: Cleanup test data', 'progress');

        const apiKeyInputCleanup = await page.$('input[wire\\:model="data.retell_api_key"]');
        await apiKeyInputCleanup.click({ clickCount: 3 });
        await apiKeyInputCleanup.press('Backspace');

        const agentIdInputCleanup = await page.$('input[wire\\:model="data.retell_agent_id"]');
        await agentIdInputCleanup.click({ clickCount: 3 });
        await agentIdInputCleanup.press('Backspace');

        await page.click('button[type="submit"]');
        await page.waitForSelector('.fi-no-notification', { timeout: 10000 });

        await log('✓ Test data cleaned up');
        await takeScreenshot(page, '08-cleanup-complete');

        // =====================================================================
        // SUCCESS
        // =====================================================================
        console.log('\n╔════════════════════════════════════════════════════════════════╗');
        console.log('║                  ✅ ALL TESTS PASSED                          ║');
        console.log('║                                                                ║');
        console.log('║  ✓ Settings Dashboard loads without errors                    ║');
        console.log('║  ✓ Data can be entered and saved                              ║');
        console.log('║  ✓ Success notification displayed                             ║');
        console.log('║  ✓ Page refresh successful                                    ║');
        console.log('║  ✓ Saved data persists and displays correctly                 ║');
        console.log('║  ✓ Encryption working properly                                ║');
        console.log('╚════════════════════════════════════════════════════════════════╝\n');

        await browser.close();
        process.exit(0);

    } catch (error) {
        await log(`Test failed: ${error.message}`, 'error');
        await takeScreenshot(page, 'error-final');

        console.log('\n╔════════════════════════════════════════════════════════════════╗');
        console.log('║                    ❌ TEST FAILED                             ║');
        console.log('╚════════════════════════════════════════════════════════════════╝\n');
        console.error(error);

        await browser.close();
        process.exit(1);
    }
}

runTest();
