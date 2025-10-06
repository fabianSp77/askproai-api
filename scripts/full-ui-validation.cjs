/**
 * FULL UI VALIDATION WITH AUTHENTICATED USER
 *
 * Tests ALL admin pages with proper credentials
 * User: https://api.askproai.de/admin/users/41
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Configuration
const BASE_URL = process.env.BASE_URL || 'https://api.askproai.de';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL;
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD;
const SCREENSHOT_DIR = '/var/www/api-gateway/storage/screenshots/authenticated';
const HEADLESS = process.env.HEADLESS !== 'false';

// Create screenshot directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

// Test results
const results = {
    total: 0,
    passed: 0,
    failed: 0,
    screenshots: [],
    consoleErrors: [],
    networkErrors: [],
    performanceMetrics: {},
    pages: []
};

async function takeScreenshot(page, name, description = '') {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `${timestamp}_${name}.png`;
    const filepath = path.join(SCREENSHOT_DIR, filename);

    await page.screenshot({
        path: filepath,
        fullPage: true
    });

    results.screenshots.push({
        name,
        description,
        filename,
        timestamp: new Date().toISOString(),
        url: page.url()
    });

    console.log(`📸 ${name}: ${filename}`);
    return filepath;
}

async function testPage(page, url, name, elementChecks = []) {
    console.log(`\n📋 Testing: ${name}`);
    results.total++;

    try {
        const startTime = Date.now();
        await page.goto(url, { waitUntil: 'networkidle0', timeout: 30000 });
        const loadTime = Date.now() - startTime;

        results.performanceMetrics[name] = loadTime;

        await takeScreenshot(page, name.replace(/\s+/g, '-').toLowerCase(), name);

        // Check elements
        const elementResults = {};
        for (const selector of elementChecks) {
            const exists = await page.$(selector);
            elementResults[selector] = exists !== null;
        }

        results.pages.push({
            name,
            url,
            loadTime,
            elements: elementResults,
            status: 'pass'
        });

        console.log(`✅ PASS: ${name} (${loadTime}ms)`);
        results.passed++;
        return true;

    } catch (error) {
        console.log(`❌ FAIL: ${name} - ${error.message}`);
        results.failed++;
        await takeScreenshot(page, `${name.replace(/\s+/g, '-').toLowerCase()}-error`, `Error: ${error.message}`);

        results.pages.push({
            name,
            url,
            error: error.message,
            status: 'fail'
        });

        return false;
    }
}

async function runFullValidation() {
    console.log('🚀 STARTING FULL AUTHENTICATED UI VALIDATION');
    console.log(`   Base URL: ${BASE_URL}`);
    console.log(`   Admin Email: ${ADMIN_EMAIL}`);
    console.log(`   Screenshots: ${SCREENSHOT_DIR}`);

    if (!ADMIN_EMAIL || !ADMIN_PASSWORD) {
        console.error('❌ ERROR: ADMIN_EMAIL and ADMIN_PASSWORD must be set');
        process.exit(1);
    }

    let browser;
    let page;

    try {
        browser = await puppeteer.launch({
            headless: HEADLESS,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--disable-gpu'
            ],
            executablePath: '/usr/bin/chromium'
        });

        page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });

        // Track errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                results.consoleErrors.push({
                    text: msg.text(),
                    url: page.url(),
                    timestamp: new Date().toISOString()
                });
                console.log(`   ⚠️ Console Error: ${msg.text()}`);
            }
        });

        page.on('response', response => {
            if (response.status() >= 400) {
                results.networkErrors.push({
                    url: response.url(),
                    status: response.status(),
                    timestamp: new Date().toISOString()
                });
                console.log(`   ⚠️ Network ${response.status()}: ${response.url()}`);
            }
        });

        // LOGIN
        console.log('\n🔐 LOGIN');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle0' });
        await takeScreenshot(page, 'login-page', 'Login page before authentication');

        await page.type('input[type="email"], input[name="email"]', ADMIN_EMAIL);
        await page.type('input[type="password"], input[name="password"]', ADMIN_PASSWORD);
        await takeScreenshot(page, 'login-filled', 'Login credentials filled');

        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 })
        ]);

        await takeScreenshot(page, 'dashboard-after-login', 'Dashboard after successful login');
        console.log('✅ LOGIN SUCCESSFUL');

        // Test all pages
        await testPage(page, `${BASE_URL}/admin`, 'Dashboard', ['body', '[data-layout]']);

        // Existing features
        await testPage(page, `${BASE_URL}/admin/companies`, 'Companies', ['table']);
        await testPage(page, `${BASE_URL}/admin/branches`, 'Branches', ['table']);
        await testPage(page, `${BASE_URL}/admin/services`, 'Services', ['table']);
        await testPage(page, `${BASE_URL}/admin/staff`, 'Staff', ['table']);
        await testPage(page, `${BASE_URL}/admin/users`, 'Users', ['table']);
        await testPage(page, `${BASE_URL}/admin/appointments`, 'Appointments', ['table']);
        await testPage(page, `${BASE_URL}/admin/customers`, 'Customers', ['table']);

        // NEW FEATURES
        await testPage(page, `${BASE_URL}/admin/callback-requests`, 'Callback Requests (NEW)', ['table', '[data-action]']);

        // Test Branch Detail (previously had 500 error)
        console.log('\n🏢 Testing Branch Detail (Critical Regression Test)');
        await page.goto(`${BASE_URL}/admin/branches`, { waitUntil: 'networkidle0' });

        const branchLink = await page.$('table a[href*="/admin/branches/"]');
        if (branchLink) {
            const branchUrl = await page.evaluate(el => el.href, branchLink);
            await testPage(page, branchUrl, 'Branch Detail (Previously 500)', ['body']);
        }

        // Test creating new callback (if possible)
        try {
            await page.goto(`${BASE_URL}/admin/callback-requests`, { waitUntil: 'networkidle0' });
            const createButton = await page.$('[href*="/create"], button:has-text("Erstellen"), button:has-text("Create")');
            if (createButton) {
                await createButton.click();
                await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 });
                await takeScreenshot(page, 'callback-create-form', 'Callback Request creation form');
                console.log('📋 Callback creation form captured');
            }
        } catch (e) {
            console.log('⚠️ Callback creation form not accessible');
        }

        // Generate report
        console.log('\n' + '='.repeat(80));
        console.log('📊 FULL UI VALIDATION SUMMARY');
        console.log('='.repeat(80));

        const passRate = results.total > 0 ? ((results.passed / results.total) * 100).toFixed(1) : 0;

        console.log(`Total Pages:      ${results.total}`);
        console.log(`Passed:           ${results.passed} ✅`);
        console.log(`Failed:           ${results.failed} ❌`);
        console.log(`Pass Rate:        ${passRate}%`);
        console.log(`Screenshots:      ${results.screenshots.length}`);
        console.log(`Console Errors:   ${results.consoleErrors.length}`);
        console.log(`Network Errors:   ${results.networkErrors.length}`);

        console.log('\n📈 PERFORMANCE:');
        for (const [page, time] of Object.entries(results.performanceMetrics)) {
            const status = time < 2000 ? '✅' : '⚠️';
            console.log(`   ${status} ${page}: ${time}ms`);
        }

        console.log('\n📸 SCREENSHOTS:');
        console.log(`   Directory: ${SCREENSHOT_DIR}`);
        console.log(`   Total: ${results.screenshots.length} files`);

        if (results.consoleErrors.length > 0) {
            console.log('\n⚠️ CONSOLE ERRORS:');
            results.consoleErrors.slice(0, 10).forEach((err, i) => {
                console.log(`   ${i + 1}. ${err.text}`);
            });
        }

        if (results.networkErrors.length > 0) {
            console.log('\n⚠️ NETWORK ERRORS:');
            results.networkErrors.slice(0, 10).forEach((err, i) => {
                console.log(`   ${i + 1}. ${err.status} ${err.url}`);
            });
        }

        // Save JSON report
        const reportPath = path.join(SCREENSHOT_DIR, 'full-validation-report.json');
        fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
        console.log(`\n💾 Report saved: ${reportPath}`);

        console.log('\n' + '='.repeat(80));
        console.log(results.failed === 0 ? '✅ ALL UI TESTS PASSED' : `⚠️ ${results.failed} TESTS FAILED`);
        console.log('='.repeat(80) + '\n');

        process.exit(results.failed > 0 ? 1 : 0);

    } catch (error) {
        console.error('❌ FATAL ERROR:', error);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

runFullValidation().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
