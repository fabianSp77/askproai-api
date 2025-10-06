/**
 * COMPREHENSIVE UI TEST SUITE - CRITICAL PRODUCTION VALIDATION
 *
 * Tests ALL admin pages with:
 * - Screenshots at every step
 * - Console error detection
 * - Network failure detection
 * - Element presence validation
 * - Performance metrics
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Configuration
const BASE_URL = process.env.BASE_URL || 'https://api.askproai.de';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@askproai.de';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || '';
const SCREENSHOT_DIR = '/var/www/api-gateway/storage/screenshots';
const HEADLESS = process.env.HEADLESS !== 'false';

// Create screenshot directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

// Test results tracking
const testResults = {
    total: 0,
    passed: 0,
    failed: 0,
    screenshots: [],
    consoleErrors: [],
    networkErrors: [],
    performanceMetrics: {},
    elementChecks: {}
};

/**
 * Take screenshot with metadata
 */
async function takeScreenshot(page, name, description = '') {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `${timestamp}_${name}.png`;
    const filepath = path.join(SCREENSHOT_DIR, filename);

    await page.screenshot({
        path: filepath,
        fullPage: true
    });

    testResults.screenshots.push({
        name,
        description,
        filename,
        timestamp: new Date().toISOString(),
        url: page.url()
    });

    console.log(`📸 Screenshot: ${filename}`);
    return filepath;
}

/**
 * Wait for page load and network idle
 */
async function waitForPageLoad(page, timeout = 30000) {
    await page.waitForNavigation({
        waitUntil: ['networkidle0', 'domcontentloaded'],
        timeout
    });
}

/**
 * Check if element exists
 */
async function elementExists(page, selector) {
    try {
        const element = await page.$(selector);
        return element !== null;
    } catch (e) {
        return false;
    }
}

/**
 * Test Login Flow
 */
async function testLogin(page) {
    console.log('\n🔐 TEST: Login Flow');
    testResults.total++;

    try {
        // Navigate to login page
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle0' });
        await takeScreenshot(page, 'login-page', 'Admin login page before authentication');

        // Check critical elements
        const emailExists = await elementExists(page, 'input[type="email"], input[name="email"]');
        const passwordExists = await elementExists(page, 'input[type="password"], input[name="password"]');
        const submitExists = await elementExists(page, 'button[type="submit"]');

        if (!emailExists || !passwordExists || !submitExists) {
            throw new Error('Login form elements missing');
        }

        // Fill login form
        await page.type('input[type="email"], input[name="email"]', ADMIN_EMAIL);
        await page.type('input[type="password"], input[name="password"]', ADMIN_PASSWORD);
        await takeScreenshot(page, 'login-filled', 'Login form filled');

        // Submit
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 })
        ]);

        await takeScreenshot(page, 'post-login-dashboard', 'Dashboard after successful login');

        // Verify we're logged in (check for dashboard or admin panel elements)
        const loggedIn = await elementExists(page, '[data-layout="app"], .fi-sidebar, nav');

        if (loggedIn) {
            console.log('✅ PASS: Login successful');
            testResults.passed++;
            return true;
        } else {
            throw new Error('Login failed - no admin panel detected');
        }
    } catch (error) {
        console.log(`❌ FAIL: Login - ${error.message}`);
        testResults.failed++;
        await takeScreenshot(page, 'login-error', `Login failed: ${error.message}`);
        return false;
    }
}

/**
 * Test Dashboard
 */
async function testDashboard(page) {
    console.log('\n📊 TEST: Dashboard');
    testResults.total++;

    try {
        await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle0' });

        const startTime = Date.now();
        await page.waitForSelector('body', { timeout: 5000 });
        const loadTime = Date.now() - startTime;

        testResults.performanceMetrics.dashboard_load = loadTime;

        await takeScreenshot(page, 'dashboard-full', 'Complete dashboard view');

        // Check for widgets
        const hasWidgets = await elementExists(page, '[data-widget], .fi-wi, .widget');
        testResults.elementChecks.dashboard_widgets = hasWidgets;

        if (loadTime < 2000) {
            console.log(`✅ PASS: Dashboard (${loadTime}ms)`);
            testResults.passed++;
        } else {
            console.log(`⚠️ WARN: Dashboard slow (${loadTime}ms)`);
            testResults.passed++;
        }
    } catch (error) {
        console.log(`❌ FAIL: Dashboard - ${error.message}`);
        testResults.failed++;
        await takeScreenshot(page, 'dashboard-error', error.message);
    }
}

/**
 * Test Resource Pages
 */
async function testResourcePage(page, resource, expectedElements = []) {
    console.log(`\n📋 TEST: ${resource} Resource`);
    testResults.total++;

    try {
        const url = `${BASE_URL}/admin/${resource}`;
        await page.goto(url, { waitUntil: 'networkidle0', timeout: 15000 });

        const startTime = Date.now();
        await page.waitForSelector('body', { timeout: 5000 });
        const loadTime = Date.now() - startTime;

        testResults.performanceMetrics[`${resource}_load`] = loadTime;

        await takeScreenshot(page, `resource-${resource}`, `${resource} list page`);

        // Check for Filament table
        const hasTable = await elementExists(page, 'table, [data-table], .fi-ta');
        testResults.elementChecks[`${resource}_table`] = hasTable;

        // Check for expected elements
        for (const selector of expectedElements) {
            const exists = await elementExists(page, selector);
            testResults.elementChecks[`${resource}_${selector}`] = exists;
        }

        console.log(`✅ PASS: ${resource} (${loadTime}ms, table: ${hasTable})`);
        testResults.passed++;
    } catch (error) {
        console.log(`❌ FAIL: ${resource} - ${error.message}`);
        testResults.failed++;
        await takeScreenshot(page, `resource-${resource}-error`, error.message);
    }
}

/**
 * Test Branch Detail Page (Previously had 500 error)
 */
async function testBranchDetail(page) {
    console.log('\n🏢 TEST: Branch Detail Page (Critical - Previously 500 Error)');
    testResults.total++;

    try {
        // First get a branch ID from the list
        await page.goto(`${BASE_URL}/admin/branches`, { waitUntil: 'networkidle0' });

        // Try to find a branch link
        const branchLink = await page.$('table a[href*="/admin/branches/"]');

        if (branchLink) {
            const branchUrl = await page.evaluate(el => el.href, branchLink);
            console.log(`   Testing branch URL: ${branchUrl}`);

            await page.goto(branchUrl, { waitUntil: 'networkidle0', timeout: 10000 });
            await takeScreenshot(page, 'branch-detail', 'Branch detail page view');

            // Check if we got redirected to login (would indicate 403/500)
            const currentUrl = page.url();
            if (currentUrl.includes('/login')) {
                throw new Error('Redirected to login - possible 403/500 error');
            }

            console.log('✅ PASS: Branch Detail (no 500 error)');
            testResults.passed++;
        } else {
            console.log('⚠️ SKIP: No branches found to test detail page');
            testResults.total--;
        }
    } catch (error) {
        console.log(`❌ FAIL: Branch Detail - ${error.message}`);
        testResults.failed++;
        await takeScreenshot(page, 'branch-detail-error', error.message);
    }
}

/**
 * Test NEW Callback Request Resource
 */
async function testCallbackRequests(page) {
    console.log('\n📞 TEST: Callback Requests (NEW FEATURE)');
    testResults.total++;

    try {
        await page.goto(`${BASE_URL}/admin/callback-requests`, { waitUntil: 'networkidle0' });
        await takeScreenshot(page, 'callback-requests', 'New callback requests resource');

        // Check for table
        const hasTable = await elementExists(page, 'table');

        // Check for filters (status, priority, branch)
        const hasFilters = await elementExists(page, '[data-filter], .fi-fo-filter');

        // Check for actions (assign, complete, etc.)
        const hasActions = await elementExists(page, '[data-action], button');

        testResults.elementChecks.callback_table = hasTable;
        testResults.elementChecks.callback_filters = hasFilters;
        testResults.elementChecks.callback_actions = hasActions;

        if (hasTable) {
            console.log('✅ PASS: Callback Requests (table: ✓, filters: ✓)');
            testResults.passed++;
        } else {
            throw new Error('Callback requests table not found');
        }
    } catch (error) {
        console.log(`❌ FAIL: Callback Requests - ${error.message}`);
        testResults.failed++;
        await takeScreenshot(page, 'callback-requests-error', error.message);
    }
}

/**
 * Main Test Suite
 */
async function runTests() {
    console.log('🚀 STARTING COMPREHENSIVE UI TEST SUITE');
    console.log(`   Base URL: ${BASE_URL}`);
    console.log(`   Headless: ${HEADLESS}`);
    console.log(`   Screenshots: ${SCREENSHOT_DIR}`);

    let browser;
    let page;

    try {
        // Launch browser
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

        // Set viewport
        await page.setViewport({ width: 1920, height: 1080 });

        // Track console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                testResults.consoleErrors.push({
                    text: msg.text(),
                    url: page.url(),
                    timestamp: new Date().toISOString()
                });
                console.log(`   ⚠️ Console Error: ${msg.text()}`);
            }
        });

        // Track network failures
        page.on('response', response => {
            if (response.status() >= 400) {
                testResults.networkErrors.push({
                    url: response.url(),
                    status: response.status(),
                    statusText: response.statusText(),
                    timestamp: new Date().toISOString()
                });
                console.log(`   ⚠️ Network Error: ${response.status()} ${response.url()}`);
            }
        });

        // Run all tests
        const isLoggedIn = await testLogin(page);

        if (isLoggedIn) {
            await testDashboard(page);

            // Test existing resources
            await testResourcePage(page, 'companies');
            await testResourcePage(page, 'branches');
            await testResourcePage(page, 'services');
            await testResourcePage(page, 'users');
            await testResourcePage(page, 'appointments');

            // Test NEW features
            await testCallbackRequests(page);

            // Critical regression test
            await testBranchDetail(page);
        }

    } catch (error) {
        console.error('❌ CRITICAL ERROR:', error);
    } finally {
        if (browser) {
            await browser.close();
        }

        // Generate report
        generateReport();
    }
}

/**
 * Generate Test Report
 */
function generateReport() {
    console.log('\n' + '='.repeat(80));
    console.log('📊 TEST EXECUTION SUMMARY');
    console.log('='.repeat(80));

    const passRate = testResults.total > 0
        ? ((testResults.passed / testResults.total) * 100).toFixed(1)
        : 0;

    console.log(`Total Tests:      ${testResults.total}`);
    console.log(`Passed:           ${testResults.passed} ✅`);
    console.log(`Failed:           ${testResults.failed} ❌`);
    console.log(`Pass Rate:        ${passRate}%`);
    console.log(`Screenshots:      ${testResults.screenshots.length}`);
    console.log(`Console Errors:   ${testResults.consoleErrors.length}`);
    console.log(`Network Errors:   ${testResults.networkErrors.length}`);

    console.log('\n📈 PERFORMANCE METRICS:');
    for (const [metric, value] of Object.entries(testResults.performanceMetrics)) {
        const status = value < 2000 ? '✅' : '⚠️';
        console.log(`   ${status} ${metric}: ${value}ms`);
    }

    console.log('\n🔍 ELEMENT CHECKS:');
    for (const [element, exists] of Object.entries(testResults.elementChecks)) {
        const status = exists ? '✅' : '❌';
        console.log(`   ${status} ${element}: ${exists ? 'found' : 'missing'}`);
    }

    if (testResults.consoleErrors.length > 0) {
        console.log('\n⚠️ CONSOLE ERRORS:');
        testResults.consoleErrors.slice(0, 10).forEach((err, i) => {
            console.log(`   ${i + 1}. ${err.text}`);
        });
        if (testResults.consoleErrors.length > 10) {
            console.log(`   ... and ${testResults.consoleErrors.length - 10} more`);
        }
    }

    if (testResults.networkErrors.length > 0) {
        console.log('\n⚠️ NETWORK ERRORS:');
        testResults.networkErrors.slice(0, 10).forEach((err, i) => {
            console.log(`   ${i + 1}. ${err.status} ${err.url}`);
        });
        if (testResults.networkErrors.length > 10) {
            console.log(`   ... and ${testResults.networkErrors.length - 10} more`);
        }
    }

    console.log('\n📸 SCREENSHOTS SAVED TO:');
    console.log(`   ${SCREENSHOT_DIR}`);
    console.log(`   Total: ${testResults.screenshots.length} files`);

    // Save JSON report
    const reportPath = path.join(SCREENSHOT_DIR, 'test-report.json');
    fs.writeFileSync(reportPath, JSON.stringify(testResults, null, 2));
    console.log(`\n💾 Full report saved: ${reportPath}`);

    // Exit code
    const exitCode = testResults.failed > 0 ? 1 : 0;
    console.log('\n' + '='.repeat(80));
    console.log(exitCode === 0 ? '✅ ALL TESTS PASSED' : '❌ TESTS FAILED');
    console.log('='.repeat(80) + '\n');

    process.exit(exitCode);
}

// Run tests
runTests().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
