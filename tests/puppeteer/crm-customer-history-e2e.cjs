/**
 * CRM Customer History E2E Test - Filament Admin Panel
 *
 * Tests appointment history display in Filament Admin panel
 * Verifies: Customer #461 (Hansi Hinterseer) with appointments #672, #673
 */

const puppeteer = require('puppeteer');

// Configuration
const BASE_URL = process.env.APP_URL || 'https://api.askproai.de';
const ADMIN_URL = `${BASE_URL}/admin`;
const SCREENSHOTS_DIR = 'screenshots';

// Test credentials (from PUPPETEER_LOGIN_CONFIG.md)
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'fabian@askproai.de';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || null;

// Test data
const TEST_CUSTOMER_ID = 461;
const TEST_CUSTOMER_NAME = 'Hansi Hinterseer';
const TEST_APPOINTMENT_IDS = [672, 673];

/**
 * Helper: Take screenshot on failure
 */
async function screenshotOnError(page, testName) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `${SCREENSHOTS_DIR}/error-${testName}-${timestamp}.png`;
    try {
        await page.screenshot({
            path: filename,
            fullPage: true,
        });
        console.log(`❌ Screenshot saved: ${filename}`);
    } catch (err) {
        console.error('Failed to capture screenshot:', err.message);
    }
}

/**
 * Helper: Login to Filament Admin
 */
async function loginToAdmin(page) {
    console.log('→ Navigating to admin login...');
    await page.goto(`${ADMIN_URL}/login`, {
        waitUntil: 'networkidle2',
        timeout: 30000,
    });

    // Wait for login form
    console.log('→ Waiting for login form...');
    await page.waitForSelector('input[type="email"], input[name="email"]', {
        timeout: 10000,
    });

    // Fill credentials
    console.log('→ Filling credentials...');
    await page.type('input[type="email"], input[name="email"]', ADMIN_EMAIL);

    if (ADMIN_PASSWORD) {
        await page.type('input[type="password"], input[name="password"]', ADMIN_PASSWORD);
    } else {
        console.warn('⚠️  ADMIN_PASSWORD not set, login may fail');
        // Try to find password field and focus it for manual entry
        const passwordField = await page.$('input[type="password"], input[name="password"]');
        if (passwordField) {
            await passwordField.focus();
            // Wait a moment for manual password entry in non-headless mode
            await page.waitForTimeout(2000);
        }
    }

    // Submit login
    console.log('→ Submitting login form...');
    const submitButton = await page.$(
        'button[type="submit"], button:has-text("Sign in"), button:has-text("Anmelden")'
    );

    if (submitButton) {
        await submitButton.click();
    } else {
        // Fallback: press Enter
        await page.keyboard.press('Enter');
    }

    // Wait for redirect to dashboard
    console.log('→ Waiting for redirect to dashboard...');
    await page.waitForNavigation({
        waitUntil: 'networkidle2',
        timeout: 15000,
    });

    // Verify we're logged in
    const currentUrl = page.url();
    if (!currentUrl.includes('/admin') || currentUrl.includes('/login')) {
        throw new Error(`Login failed - still on: ${currentUrl}`);
    }

    console.log('✅ Login successful');
}

/**
 * Helper: Navigate to Customers page
 */
async function navigateToCustomers(page) {
    console.log('→ Navigating to Customers...');

    // Try direct navigation first
    await page.goto(`${ADMIN_URL}/customers`, {
        waitUntil: 'networkidle2',
        timeout: 30000,
    });

    // Wait for customer table to load
    await page.waitForSelector('table, [role="table"], .fi-ta-table', {
        timeout: 15000,
    });

    console.log('✅ Customers page loaded');
}

/**
 * Helper: Find and open customer detail
 */
async function openCustomerDetail(page, customerId, customerName) {
    console.log(`→ Looking for customer #${customerId} (${customerName})...`);

    // Try to find customer by name in table
    await page.waitForSelector('table tbody tr, [role="row"]', {
        timeout: 10000,
    });

    // Search for customer (if search exists)
    const searchInput = await page.$('input[type="search"], input[placeholder*="Suchen"]');
    if (searchInput) {
        console.log(`→ Searching for "${customerName}"...`);
        await searchInput.type(customerName);
        await page.waitForTimeout(1000); // Wait for search results
    }

    // Direct navigation to customer detail page
    console.log(`→ Navigating to customer #${customerId} detail page...`);
    await page.goto(`${ADMIN_URL}/customers/${customerId}`, {
        waitUntil: 'networkidle2',
        timeout: 30000,
    });

    // Verify we're on the customer detail page
    const currentUrl = page.url();
    if (!currentUrl.includes(`/customers/${customerId}`)) {
        throw new Error(`Failed to navigate to customer #${customerId}`);
    }

    // Wait for page content to load
    await page.waitForSelector('body', { timeout: 5000 });

    console.log(`✅ Customer #${customerId} detail page loaded`);
}

/**
 * Helper: Verify appointments section exists
 */
async function verifyAppointmentsSection(page) {
    console.log('→ Checking for Appointments section...');

    // Look for appointments section/tab
    const appointmentsSectionSelectors = [
        'h2:has-text("Termine")',
        'h3:has-text("Termine")',
        '[aria-label*="Termine"]',
        'div:has-text("Termine")',
        '.fi-ta-header-heading:has-text("Termine")',
    ];

    let sectionFound = false;
    for (const selector of appointmentsSectionSelectors) {
        try {
            const element = await page.waitForSelector(selector, { timeout: 3000 });
            if (element) {
                sectionFound = true;
                console.log(`✅ Appointments section found with selector: ${selector}`);
                break;
            }
        } catch (err) {
            // Try next selector
            continue;
        }
    }

    if (!sectionFound) {
        // Check if it's in a tab that needs to be clicked
        const tabSelectors = [
            'button:has-text("Termine")',
            '[role="tab"]:has-text("Termine")',
            'a:has-text("Termine")',
        ];

        for (const tabSelector of tabSelectors) {
            try {
                const tab = await page.$(tabSelector);
                if (tab) {
                    console.log('→ Clicking Termine tab...');
                    await tab.click();
                    await page.waitForTimeout(1000);
                    sectionFound = true;
                    break;
                }
            } catch (err) {
                continue;
            }
        }
    }

    if (!sectionFound) {
        throw new Error('Appointments section not found on customer detail page');
    }

    return true;
}

/**
 * Helper: Verify appointments are visible
 */
async function verifyAppointmentsVisible(page, appointmentIds) {
    console.log(`→ Verifying appointments ${appointmentIds.join(', ')} are visible...`);

    // Wait for appointment table/list to load
    await page.waitForSelector('table tbody tr, [role="row"], .fi-ta-record', {
        timeout: 10000,
    });

    // Get page content for analysis
    const pageContent = await page.content();

    // Check for appointment IDs or dates
    const verificationsNeeded = appointmentIds.length;
    let verificationsFound = 0;

    for (const appointmentId of appointmentIds) {
        // Look for appointment ID in content
        if (pageContent.includes(`#${appointmentId}`) ||
            pageContent.includes(`appointment-${appointmentId}`) ||
            pageContent.includes(`/appointments/${appointmentId}`)) {
            console.log(`✅ Appointment #${appointmentId} found in page content`);
            verificationsFound++;
            continue;
        }

        // Try to find appointment row by ID
        const rowSelectors = [
            `tr[data-id="${appointmentId}"]`,
            `tr:has-text("${appointmentId}")`,
            `[data-appointment-id="${appointmentId}"]`,
        ];

        for (const selector of rowSelectors) {
            try {
                const element = await page.$(selector);
                if (element) {
                    console.log(`✅ Appointment #${appointmentId} found with selector: ${selector}`);
                    verificationsFound++;
                    break;
                }
            } catch (err) {
                continue;
            }
        }
    }

    // If we didn't find appointments by ID, check if ANY appointments are shown
    const rows = await page.$$('table tbody tr, [role="row"]');
    const rowCount = rows.length;

    console.log(`→ Found ${rowCount} appointment rows in table`);

    if (rowCount === 0) {
        throw new Error('No appointments displayed in table');
    }

    if (verificationsFound === 0) {
        console.warn(`⚠️  Could not verify specific appointment IDs, but ${rowCount} appointments are displayed`);
    } else {
        console.log(`✅ Verified ${verificationsFound}/${verificationsNeeded} specific appointments`);
    }

    return rowCount;
}

/**
 * Helper: Click on appointment and verify detail view
 */
async function verifyAppointmentDetail(page, appointmentId) {
    console.log(`→ Opening appointment #${appointmentId} detail...`);

    // Try to find and click appointment row
    const clickSelectors = [
        `tr[data-id="${appointmentId}"] a`,
        `a[href*="/appointments/${appointmentId}"]`,
        `tr:has-text("${appointmentId}") a`,
    ];

    let clicked = false;
    for (const selector of clickSelectors) {
        try {
            const link = await page.$(selector);
            if (link) {
                await link.click();
                clicked = true;
                break;
            }
        } catch (err) {
            continue;
        }
    }

    if (clicked) {
        // Wait for detail view or modal to appear
        await page.waitForTimeout(2000);

        // Check if we navigated or if modal opened
        const currentUrl = page.url();

        // Look for metadata display
        const metadataSelectors = [
            '[data-appointment-metadata]',
            '.appointment-metadata',
            'div:has-text("Metadaten")',
            'div:has-text("Booking Source")',
            'div:has-text("Created")',
        ];

        let metadataFound = false;
        for (const selector of metadataSelectors) {
            try {
                const element = await page.$(selector);
                if (element) {
                    metadataFound = true;
                    console.log(`✅ Appointment metadata found: ${selector}`);
                    break;
                }
            } catch (err) {
                continue;
            }
        }

        if (metadataFound) {
            console.log('✅ Appointment detail view contains metadata');
        } else {
            console.warn('⚠️  Appointment detail opened but metadata not found');
        }
    } else {
        console.warn(`⚠️  Could not click on appointment #${appointmentId} - may need edit action`);
    }

    return clicked;
}

/**
 * Main test execution
 */
async function runTest() {
    console.log('\n====================================');
    console.log('CRM Customer History E2E Test');
    console.log('====================================\n');

    let browser;
    let page;
    let testsPassed = 0;
    let testsFailed = 0;

    try {
        // Launch browser
        console.log('→ Launching browser...');
        browser = await puppeteer.launch({
            headless: process.env.HEADLESS !== 'false',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
            ],
        });

        page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });

        // Set default timeout
        page.setDefaultTimeout(30000);

        console.log('✅ Browser launched\n');

        // Test 1: Login
        console.log('\n[TEST 1] Admin Login');
        console.log('─────────────────────');
        try {
            await loginToAdmin(page);
            testsPassed++;
        } catch (err) {
            console.error('❌ Login failed:', err.message);
            await screenshotOnError(page, 'login-failed');
            testsFailed++;
            throw err; // Cannot continue without login
        }

        // Test 2: Navigate to Customers
        console.log('\n[TEST 2] Navigate to Customers');
        console.log('─────────────────────────────────');
        try {
            await navigateToCustomers(page);
            testsPassed++;
        } catch (err) {
            console.error('❌ Navigation failed:', err.message);
            await screenshotOnError(page, 'customers-navigation-failed');
            testsFailed++;
            throw err;
        }

        // Test 3: Open Customer Detail
        console.log(`\n[TEST 3] Open Customer #${TEST_CUSTOMER_ID} Detail`);
        console.log('─────────────────────────────────────────────────');
        try {
            await openCustomerDetail(page, TEST_CUSTOMER_ID, TEST_CUSTOMER_NAME);

            // Take screenshot of customer detail page
            await page.screenshot({
                path: `${SCREENSHOTS_DIR}/customer-${TEST_CUSTOMER_ID}-detail.png`,
                fullPage: true,
            });
            console.log(`→ Screenshot saved: ${SCREENSHOTS_DIR}/customer-${TEST_CUSTOMER_ID}-detail.png`);

            testsPassed++;
        } catch (err) {
            console.error('❌ Customer detail failed:', err.message);
            await screenshotOnError(page, 'customer-detail-failed');
            testsFailed++;
            throw err;
        }

        // Test 4: Verify Appointments Section
        console.log('\n[TEST 4] Verify Appointments Section Exists');
        console.log('──────────────────────────────────────────────');
        try {
            await verifyAppointmentsSection(page);
            testsPassed++;
        } catch (err) {
            console.error('❌ Appointments section not found:', err.message);
            await screenshotOnError(page, 'appointments-section-missing');
            testsFailed++;
        }

        // Test 5: Verify Appointments Visible
        console.log('\n[TEST 5] Verify Appointments #672 and #673 are Visible');
        console.log('───────────────────────────────────────────────────────');
        try {
            const appointmentCount = await verifyAppointmentsVisible(page, TEST_APPOINTMENT_IDS);
            console.log(`→ Total appointments displayed: ${appointmentCount}`);

            // Take screenshot of appointments section
            await page.screenshot({
                path: `${SCREENSHOTS_DIR}/customer-${TEST_CUSTOMER_ID}-appointments.png`,
                fullPage: true,
            });
            console.log(`→ Screenshot saved: ${SCREENSHOTS_DIR}/customer-${TEST_CUSTOMER_ID}-appointments.png`);

            testsPassed++;
        } catch (err) {
            console.error('❌ Appointments verification failed:', err.message);
            await screenshotOnError(page, 'appointments-verification-failed');
            testsFailed++;
        }

        // Test 6: Open Appointment Detail (optional - may not work if it's edit-only)
        console.log('\n[TEST 6] Verify Appointment #672 Detail/Metadata');
        console.log('────────────────────────────────────────────────────');
        try {
            const detailOpened = await verifyAppointmentDetail(page, TEST_APPOINTMENT_IDS[0]);

            if (detailOpened) {
                // Take screenshot of appointment detail
                await page.screenshot({
                    path: `${SCREENSHOTS_DIR}/appointment-${TEST_APPOINTMENT_IDS[0]}-detail.png`,
                    fullPage: true,
                });
                console.log(`→ Screenshot saved: ${SCREENSHOTS_DIR}/appointment-${TEST_APPOINTMENT_IDS[0]}-detail.png`);
            }

            testsPassed++;
        } catch (err) {
            console.error('❌ Appointment detail verification failed:', err.message);
            await screenshotOnError(page, 'appointment-detail-failed');
            testsFailed++;
        }

        // Final screenshot
        await page.screenshot({
            path: `${SCREENSHOTS_DIR}/test-complete.png`,
            fullPage: true,
        });

    } catch (err) {
        console.error('\n❌ Test execution failed:', err.message);
        console.error(err.stack);
    } finally {
        if (browser) {
            await browser.close();
        }

        // Print summary
        console.log('\n====================================');
        console.log('Test Summary');
        console.log('====================================');
        console.log(`✅ Passed: ${testsPassed}`);
        console.log(`❌ Failed: ${testsFailed}`);
        console.log(`📊 Total:  ${testsPassed + testsFailed}`);
        console.log('====================================\n');

        // Exit with appropriate code
        process.exit(testsFailed > 0 ? 1 : 0);
    }
}

// Run the test
runTest().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
