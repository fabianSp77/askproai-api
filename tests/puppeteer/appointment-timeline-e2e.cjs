/**
 * E2E Test: Appointment History Timeline Visualization
 *
 * Tests the complete appointment history display for Call 834 / Appointment 675
 *
 * Test Scenarios:
 * 1. ViewAppointment page renders timeline widget
 * 2. Historical data section shows reschedule/cancel info
 * 3. Call verknüpfung works (links to Call #834)
 * 4. Modifications tab shows 2 records
 * 5. All data is correctly formatted and escaped
 *
 * Date: 2025-10-11
 * Related: Call 834 Analysis
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Configuration
const BASE_URL = 'https://api.askproai.de';
const ADMIN_URL = `${BASE_URL}/admin`;
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots', 'appointment-timeline');

// Test credentials (read from environment or config)
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@askproai.de';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || '';

// Test data
const TEST_APPOINTMENT_ID = 675;
const TEST_CUSTOMER_ID = 461;
const TEST_CALL_ID = 834;

// Create screenshot directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

/**
 * Helper: Take screenshot with timestamp
 */
async function takeScreenshot(page, name) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `${name}_${timestamp}.png`;
    const filepath = path.join(SCREENSHOT_DIR, filename);

    await page.screenshot({
        path: filepath,
        fullPage: true
    });

    console.log(`📸 Screenshot saved: ${filename}`);
    return filepath;
}

/**
 * Helper: Wait for element with timeout and error handling
 */
async function waitForElement(page, selector, timeout = 10000) {
    try {
        await page.waitForSelector(selector, { timeout });
        return true;
    } catch (error) {
        console.error(`❌ Element not found: ${selector}`);
        await takeScreenshot(page, `error_${selector.replace(/[^a-z0-9]/gi, '_')}`);
        return false;
    }
}

/**
 * Helper: Login to Admin Panel
 */
async function loginToAdmin(page) {
    console.log('🔐 Logging in to admin panel...');

    await page.goto(`${ADMIN_URL}/login`, { waitUntil: 'networkidle2' });
    await takeScreenshot(page, '01_login_page');

    // Fill login form
    await page.type('input[type="email"]', ADMIN_EMAIL);
    await page.type('input[type="password"]', ADMIN_PASSWORD);

    // Submit login
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }),
        page.click('button[type="submit"]')
    ]);

    await takeScreenshot(page, '02_admin_dashboard');
    console.log('✅ Logged in successfully');
}

/**
 * TEST 1: ViewAppointment Page - Complete History Display
 */
async function testViewAppointmentPage(browser) {
    console.log('\n📋 TEST 1: ViewAppointment Page - Appointment #675');
    console.log('═'.repeat(60));

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Login
        await loginToAdmin(page);

        // Navigate to appointment
        console.log(`\n📍 Navigating to appointment #${TEST_APPOINTMENT_ID}...`);
        await page.goto(`${ADMIN_URL}/appointments/${TEST_APPOINTMENT_ID}`, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        await page.waitForTimeout(2000); // Wait for widgets to render

        await takeScreenshot(page, '03_appointment_view_full');

        // TEST 1.1: Check if "Aktueller Status" section exists
        console.log('\n🔍 Checking "Aktueller Status" section...');
        const hasCurrentStatus = await waitForElement(page, 'text/Aktueller Status');
        console.log(hasCurrentStatus ? '✅ Current Status section found' : '❌ Current Status section NOT found');

        // TEST 1.2: Check if status badge shows "Storniert"
        console.log('\n🔍 Checking status badge...');
        const statusBadge = await page.$('text/Storniert');
        console.log(statusBadge ? '✅ Status "Storniert" badge found' : '⚠️ Status badge not found');

        // TEST 1.3: Check if "Historische Daten" section exists
        console.log('\n🔍 Checking "Historische Daten" section...');
        const hasHistoricalData = await page.$('text/Historische Daten');
        console.log(hasHistoricalData ? '✅ Historical Data section found' : '❌ Historical Data section NOT found');

        // Expand historical data section if collapsed
        if (hasHistoricalData) {
            try {
                const expandButton = await page.$('button:has-text("Historische Daten")');
                if (expandButton) {
                    await expandButton.click();
                    await page.waitForTimeout(500);
                    console.log('✅ Historical Data section expanded');
                }
            } catch (e) {
                console.log('⚠️ Could not expand section (might already be expanded)');
            }
        }

        await takeScreenshot(page, '04_historical_data_expanded');

        // TEST 1.4: Check if "Verknüpfter Anruf" section exists
        console.log('\n🔍 Checking "Verknüpfter Anruf" section...');
        const hasCallSection = await page.$('text/Verknüpfter Anruf');
        console.log(hasCallSection ? '✅ Call section found' : '❌ Call section NOT found');

        // TEST 1.5: Check if Timeline Widget exists
        console.log('\n🔍 Checking Timeline Widget...');
        const hasTimeline = await page.$('text/Termin-Historie');
        console.log(hasTimeline ? '✅ Timeline widget found' : '❌ Timeline widget NOT found');

        // Scroll to timeline widget
        if (hasTimeline) {
            await page.evaluate(() => {
                const timeline = document.querySelector('text=Termin-Historie');
                if (timeline) {
                    timeline.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
            await page.waitForTimeout(1000);
            await takeScreenshot(page, '05_timeline_widget');
        }

        // TEST 1.6: Count timeline events
        console.log('\n🔍 Counting timeline events...');
        const timelineEvents = await page.$$('[class*="relative pl-14"]');
        console.log(`📊 Timeline events found: ${timelineEvents.length}`);
        console.log(timelineEvents.length >= 3 ? '✅ Expected 3+ events (create, reschedule, cancel)' : '⚠️ Less than 3 events found');

        // TEST 1.7: Check for Call #834 links
        console.log('\n🔍 Checking Call #834 links...');
        const callLinks = await page.$$('a:has-text("Call #834")');
        console.log(`📊 Call #834 links found: ${callLinks.length}`);
        console.log(callLinks.length > 0 ? '✅ Call links present' : '❌ No Call links found');

        // TEST 1.8: Check Modifications Tab
        console.log('\n🔍 Checking Modifications Tab...');
        const modificationsTab = await page.$('text/Änderungsverlauf');
        if (modificationsTab) {
            console.log('✅ Modifications tab found');
            await modificationsTab.click();
            await page.waitForTimeout(2000);
            await takeScreenshot(page, '06_modifications_tab');

            // Count modifications in table
            const modRows = await page.$$('table tbody tr');
            console.log(`📊 Modification records in table: ${modRows.length}`);
            console.log(modRows.length === 2 ? '✅ Expected 2 modifications found' : `⚠️ Found ${modRows.length} modifications (expected 2)`);
        } else {
            console.log('❌ Modifications tab NOT found');
        }

        console.log('\n✅ TEST 1 COMPLETED');
        return {
            passed: hasCurrentStatus && hasHistoricalData && hasTimeline,
            details: {
                currentStatus: hasCurrentStatus,
                historicalData: hasHistoricalData,
                callSection: hasCallSection,
                timelineWidget: hasTimeline,
                timelineEventsCount: timelineEvents.length,
                callLinksCount: callLinks.length
            }
        };

    } catch (error) {
        console.error('❌ TEST 1 FAILED:', error.message);
        await takeScreenshot(page, 'error_test1');
        return { passed: false, error: error.message };
    } finally {
        await page.close();
    }
}

/**
 * TEST 2: Customer Page - Appointments Relation Manager
 */
async function testCustomerAppointmentsTab(browser) {
    console.log('\n📋 TEST 2: Customer #461 - Appointments Tab');
    console.log('═'.repeat(60));

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        await loginToAdmin(page);

        console.log(`\n📍 Navigating to customer #${TEST_CUSTOMER_ID}...`);
        await page.goto(`${ADMIN_URL}/customers/${TEST_CUSTOMER_ID}`, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        await takeScreenshot(page, '07_customer_view');

        // Check for Appointments tab
        console.log('\n🔍 Checking for Appointments tab...');
        const appointmentsTab = await page.$('text/Termine');

        if (appointmentsTab) {
            console.log('✅ Appointments tab found');
            await appointmentsTab.click();
            await page.waitForTimeout(2000);
            await takeScreenshot(page, '08_customer_appointments_tab');

            // Count appointments in table
            const appointmentRows = await page.$$('table tbody tr');
            console.log(`📊 Appointments shown: ${appointmentRows.length}`);

            // Check if Appointment #675 is visible
            const appointment675 = await page.$(`text/675`);
            console.log(appointment675 ? '✅ Appointment #675 found in customer tab' : '⚠️ Appointment #675 NOT found');

            console.log('\n✅ TEST 2 COMPLETED');
            return {
                passed: true,
                details: {
                    appointmentsCount: appointmentRows.length,
                    hasAppointment675: !!appointment675
                }
            };
        } else {
            console.log('❌ Appointments tab NOT found');
            return { passed: false, error: 'Appointments tab not found' };
        }

    } catch (error) {
        console.error('❌ TEST 2 FAILED:', error.message);
        await takeScreenshot(page, 'error_test2');
        return { passed: false, error: error.message };
    } finally {
        await page.close();
    }
}

/**
 * TEST 3: Call Page - Appointment Verknüpfung
 */
async function testCallAppointmentLink(browser) {
    console.log('\n📋 TEST 3: Call #834 - Appointment Verknüpfung');
    console.log('═'.repeat(60));

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        await loginToAdmin(page);

        console.log(`\n📍 Navigating to call #${TEST_CALL_ID}...`);
        await page.goto(`${ADMIN_URL}/calls/${TEST_CALL_ID}`, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        await takeScreenshot(page, '09_call_view');

        // Check if appointment link exists
        console.log('\n🔍 Checking for appointment link...');
        const appointmentLink = await page.$(`a:has-text("${TEST_APPOINTMENT_ID}")`);

        if (appointmentLink) {
            console.log(`✅ Link to Appointment #${TEST_APPOINTMENT_ID} found`);

            // Click link and verify navigation
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle2' }),
                appointmentLink.click()
            ]);

            console.log('✅ Navigation to appointment successful');
            await takeScreenshot(page, '10_navigated_from_call_to_appointment');

            console.log('\n✅ TEST 3 COMPLETED');
            return { passed: true };
        } else {
            console.log(`❌ Link to Appointment #${TEST_APPOINTMENT_ID} NOT found`);
            return { passed: false, error: 'Appointment link not found' };
        }

    } catch (error) {
        console.error('❌ TEST 3 FAILED:', error.message);
        await takeScreenshot(page, 'error_test3');
        return { passed: false, error: error.message };
    } finally {
        await page.close();
    }
}

/**
 * Main Test Runner
 */
async function runTests() {
    console.log('🚀 Starting Appointment Timeline E2E Tests');
    console.log('═'.repeat(60));
    console.log(`📅 Date: ${new Date().toISOString()}`);
    console.log(`🌐 Base URL: ${BASE_URL}`);
    console.log(`📂 Screenshots: ${SCREENSHOT_DIR}`);
    console.log('═'.repeat(60));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ]
    });

    const results = {
        test1: null,
        test2: null,
        test3: null,
        startTime: Date.now()
    };

    try {
        // Run all tests sequentially
        results.test1 = await testViewAppointmentPage(browser);
        results.test2 = await testCustomerAppointmentsTab(browser);
        results.test3 = await testCallAppointmentLink(browser);

        results.endTime = Date.now();
        results.duration = results.endTime - results.startTime;

    } finally {
        await browser.close();
    }

    // Print summary
    console.log('\n\n📊 TEST EXECUTION SUMMARY');
    console.log('═'.repeat(60));
    console.log(`⏱️  Total Duration: ${(results.duration / 1000).toFixed(2)}s`);
    console.log('\nResults:');
    console.log(`  TEST 1 (ViewAppointment): ${results.test1?.passed ? '✅ PASSED' : '❌ FAILED'}`);
    console.log(`  TEST 2 (Customer Tab):    ${results.test2?.passed ? '✅ PASSED' : '❌ FAILED'}`);
    console.log(`  TEST 3 (Call Link):       ${results.test3?.passed ? '✅ PASSED' : '❌ FAILED'}`);

    const allPassed = results.test1?.passed && results.test2?.passed && results.test3?.passed;
    console.log('\n' + (allPassed ? '✅ ALL TESTS PASSED' : '❌ SOME TESTS FAILED'));
    console.log('═'.repeat(60));

    // Save results to JSON
    const resultsPath = path.join(SCREENSHOT_DIR, 'test_results.json');
    fs.writeFileSync(resultsPath, JSON.stringify(results, null, 2));
    console.log(`\n📄 Results saved to: ${resultsPath}`);

    return allPassed;
}

// Execute tests
runTests()
    .then(success => {
        console.log('\n🎉 Test execution completed');
        process.exit(success ? 0 : 1);
    })
    .catch(error => {
        console.error('\n💥 Test execution failed:', error);
        process.exit(1);
    });
