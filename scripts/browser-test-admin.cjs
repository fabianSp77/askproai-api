#!/usr/bin/env node

/**
 * Browser Testing Script - Admin Panel Validation
 * Using Puppeteer (ARM64 compatible)
 *
 * Admin credentials: admin@test.com / password
 */

const puppeteer = require('puppeteer');
const fs = require('fs').promises;
const path = require('path');

const ADMIN_URL = process.env.ADMIN_URL || 'https://api.askproai.de/admin';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@test.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'password';
const SCREENSHOT_DIR = path.join(__dirname, '../storage/browser-test-screenshots');

async function ensureScreenshotDir() {
    try {
        await fs.mkdir(SCREENSHOT_DIR, { recursive: true });
        console.log(`✅ Screenshot directory: ${SCREENSHOT_DIR}`);
    } catch (err) {
        console.error(`❌ Failed to create screenshot dir: ${err.message}`);
    }
}

async function login(page) {
    console.log(`\n🔐 Logging in as ${ADMIN_EMAIL}...`);

    await page.goto(`${ADMIN_URL}/login`, { waitUntil: 'networkidle2' });
    await page.screenshot({ path: path.join(SCREENSHOT_DIR, '01-login-page.png') });

    // Fill login form
    await page.type('input[type="email"]', ADMIN_EMAIL);
    await page.type('input[type="password"]', ADMIN_PASSWORD);

    // Click login button
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle2' });

    await page.screenshot({ path: path.join(SCREENSHOT_DIR, '02-after-login.png') });

    const url = page.url();
    if (url.includes('/admin') && !url.includes('/login')) {
        console.log('✅ Login successful');
        return true;
    } else {
        console.log('❌ Login failed - still on login page');
        return false;
    }
}

async function testDashboard(page) {
    console.log('\n📊 Testing Dashboard...');

    await page.goto(`${ADMIN_URL}`, { waitUntil: 'networkidle2' });
    await page.screenshot({ path: path.join(SCREENSHOT_DIR, '03-dashboard.png') });

    // Check for widgets
    const widgetCount = await page.$$eval('[class*="widget"]', widgets => widgets.length);
    console.log(`   Widgets found: ${widgetCount}`);

    return { url: page.url(), widgetCount };
}

async function testResourceNavigation(page) {
    console.log('\n🗂️  Testing Resource Navigation...');

    const resources = [];

    // Find all navigation items
    const navItems = await page.$$eval('nav a, [role="navigation"] a', links =>
        links.map(link => ({
            text: link.textContent.trim(),
            href: link.href
        })).filter(item => item.text && item.href)
    );

    console.log(`   Found ${navItems.length} navigation items`);

    // Test first few resources
    for (let i = 0; i < Math.min(5, navItems.length); i++) {
        const item = navItems[i];
        if (!item.href.includes('/admin/')) continue;

        try {
            console.log(`   Testing: ${item.text} (${item.href})`);
            await page.goto(item.href, { waitUntil: 'networkidle2', timeout: 10000 });

            const screenshotName = `resource-${i+1}-${item.text.toLowerCase().replace(/[^a-z0-9]/g, '-')}.png`;
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, screenshotName) });

            resources.push({
                name: item.text,
                url: item.href,
                status: 'ok'
            });
        } catch (err) {
            console.log(`   ❌ Failed: ${err.message}`);
            resources.push({
                name: item.text,
                url: item.href,
                status: 'error',
                error: err.message
            });
        }
    }

    return resources;
}

async function testPolicyConfiguration(page) {
    console.log('\n⚙️  Testing PolicyConfiguration Resource...');

    const policyUrl = `${ADMIN_URL}/policy-configurations`;

    try {
        await page.goto(policyUrl, { waitUntil: 'networkidle2', timeout: 10000 });
        await page.screenshot({ path: path.join(SCREENSHOT_DIR, '10-policy-config.png') });

        const pageTitle = await page.title();
        const hasTable = await page.$('table') !== null;
        const hasCreateButton = await page.$('a[href*="create"], button[contains(text(), "Create")]') !== null;

        console.log(`   Title: ${pageTitle}`);
        console.log(`   Has table: ${hasTable}`);
        console.log(`   Has create button: ${hasCreateButton}`);

        return { exists: true, hasTable, hasCreateButton };
    } catch (err) {
        console.log(`   ❌ Resource not found: ${err.message}`);
        return { exists: false, error: err.message };
    }
}

async function testNotificationConfiguration(page) {
    console.log('\n📧 Testing NotificationConfiguration Resource...');

    const notifUrl = `${ADMIN_URL}/notification-configurations`;

    try {
        await page.goto(notifUrl, { waitUntil: 'networkidle2', timeout: 10000 });
        await page.screenshot({ path: path.join(SCREENSHOT_DIR, '11-notification-config.png') });

        const pageTitle = await page.title();
        return { exists: true, title: pageTitle };
    } catch (err) {
        console.log(`   ❌ Resource not found: ${err.message}`);
        return { exists: false, error: err.message };
    }
}

async function testAppointmentModification(page) {
    console.log('\n📝 Testing AppointmentModification Resource...');

    const modUrl = `${ADMIN_URL}/appointment-modifications`;

    try {
        await page.goto(modUrl, { waitUntil: 'networkidle2', timeout: 10000 });
        await page.screenshot({ path: path.join(SCREENSHOT_DIR, '12-appointment-mod.png') });

        const pageTitle = await page.title();
        return { exists: true, title: pageTitle };
    } catch (err) {
        console.log(`   ❌ Resource not found: ${err.message}`);
        return { exists: false, error: err.message };
    }
}

async function main() {
    console.log('🚀 Starting Browser Tests with Puppeteer...');
    console.log(`   Admin URL: ${ADMIN_URL}`);
    console.log(`   Chromium: /usr/bin/chromium`);

    await ensureScreenshotDir();

    const browser = await puppeteer.launch({
        executablePath: '/usr/bin/chromium',
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

    const results = {
        timestamp: new Date().toISOString(),
        adminUrl: ADMIN_URL,
        tests: {}
    };

    try {
        // Test 1: Login
        results.tests.login = await login(page);

        if (!results.tests.login) {
            throw new Error('Login failed - cannot continue tests');
        }

        // Test 2: Dashboard
        results.tests.dashboard = await testDashboard(page);

        // Test 3: Resource Navigation
        results.tests.resources = await testResourceNavigation(page);

        // Test 4: Critical Resources
        results.tests.policyConfig = await testPolicyConfiguration(page);
        results.tests.notificationConfig = await testNotificationConfiguration(page);
        results.tests.appointmentMod = await testAppointmentModification(page);

    } catch (err) {
        console.error(`\n❌ Test failed: ${err.message}`);
        results.error = err.message;
    } finally {
        await browser.close();
    }

    // Save results
    const resultPath = path.join(SCREENSHOT_DIR, 'test-results.json');
    await fs.writeFile(resultPath, JSON.stringify(results, null, 2));

    console.log(`\n📋 Test Results saved to: ${resultPath}`);
    console.log(`📸 Screenshots saved to: ${SCREENSHOT_DIR}`);

    // Print summary
    console.log('\n📊 Test Summary:');
    console.log(`   Login: ${results.tests.login ? '✅' : '❌'}`);
    console.log(`   Dashboard: ${results.tests.dashboard ? '✅' : '❌'}`);
    console.log(`   Resources tested: ${results.tests.resources?.length || 0}`);
    console.log(`   PolicyConfig exists: ${results.tests.policyConfig?.exists ? '✅' : '❌'}`);
    console.log(`   NotificationConfig exists: ${results.tests.notificationConfig?.exists ? '✅' : '❌'}`);
    console.log(`   AppointmentMod exists: ${results.tests.appointmentMod?.exists ? '✅' : '❌'}`);

    process.exit(results.error ? 1 : 0);
}

main().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
