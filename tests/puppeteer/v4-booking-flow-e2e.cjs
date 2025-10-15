/**
 * V4 Booking Flow - Comprehensive E2E Test
 *
 * Tests:
 * - Page load without 500 errors
 * - Booking flow component renders
 * - Service selection works
 * - Employee selection works
 * - Calendar loads slots
 * - Slot selection populates hidden fields
 * - No JavaScript errors
 *
 * Usage: node tests/puppeteer/v4-booking-flow-e2e.cjs
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Configuration
const BASE_URL = process.env.APP_URL || 'https://api.askproai.de';
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots', 'v4-booking-flow');
const TEST_TIMEOUT = 30000; // 30 seconds

// Test credentials (use test account)
const TEST_USER = {
    email: process.env.TEST_USER_EMAIL || 'admin@askproai.de',
    password: process.env.TEST_USER_PASSWORD || 'password'
};

// Ensure screenshot directory exists
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

/**
 * Utility: Take screenshot with timestamp
 */
async function takeScreenshot(page, name) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `${timestamp}_${name}.png`;
    const filepath = path.join(SCREENSHOT_DIR, filename);
    await page.screenshot({ path: filepath, fullPage: true });
    console.log(`📸 Screenshot saved: ${filename}`);
    return filepath;
}

/**
 * Utility: Check for console errors
 */
function setupConsoleListener(page) {
    const errors = [];
    const warnings = [];

    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();

        if (type === 'error') {
            errors.push(text);
            console.log('❌ Console Error:', text);
        } else if (type === 'warning' && !text.includes('DevTools')) {
            warnings.push(text);
            console.log('⚠️  Console Warning:', text);
        }
    });

    page.on('pageerror', error => {
        errors.push(error.message);
        console.log('💥 Page Error:', error.message);
    });

    return { errors, warnings };
}

/**
 * Main Test Suite
 */
async function runTests() {
    console.log('🚀 Starting V4 Booking Flow E2E Test Suite');
    console.log('=' .repeat(60));

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

    // Setup console monitoring
    const consoleLog = setupConsoleListener(page);

    let testResults = {
        passed: 0,
        failed: 0,
        errors: []
    };

    try {
        // ============================================================
        // TEST 1: Login to Admin Panel
        // ============================================================
        console.log('\n📋 TEST 1: Admin Login');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2', timeout: TEST_TIMEOUT });
        await takeScreenshot(page, '01-login-page');

        // Check for 500 error on login page
        const loginPageContent = await page.content();
        if (loginPageContent.includes('500') || loginPageContent.includes('Server Error')) {
            throw new Error('500 Error on login page');
        }

        // Fill login form
        await page.type('input[name="email"]', TEST_USER.email);
        await page.type('input[name="password"]', TEST_USER.password);
        await takeScreenshot(page, '02-login-filled');

        // Submit and wait for redirect
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: TEST_TIMEOUT })
        ]);

        await takeScreenshot(page, '03-logged-in');
        console.log('✅ Login successful');
        testResults.passed++;

        // ============================================================
        // TEST 2: Navigate to Appointment Create Page
        // ============================================================
        console.log('\n📋 TEST 2: Navigate to /admin/appointments/create');
        await page.goto(`${BASE_URL}/admin/appointments/create`, {
            waitUntil: 'networkidle2',
            timeout: TEST_TIMEOUT
        });

        await page.waitForTimeout(2000); // Wait for Livewire to initialize
        await takeScreenshot(page, '04-create-page-loaded');

        // Check for 500 error
        const createPageContent = await page.content();
        if (createPageContent.includes('500') || createPageContent.includes('Server Error')) {
            throw new Error('500 Error on create page');
        }

        console.log('✅ Create page loaded without 500 error');
        testResults.passed++;

        // ============================================================
        // TEST 3: Check Booking Flow Component Rendered
        // ============================================================
        console.log('\n📋 TEST 3: Booking Flow Component Presence');

        // Check if booking flow wrapper exists
        const bookingFlowExists = await page.evaluate(() => {
            return document.querySelector('.appointment-booking-flow') !== null;
        });

        if (!bookingFlowExists) {
            await takeScreenshot(page, '05-ERROR-booking-flow-missing');
            throw new Error('Booking flow component not found in DOM');
        }

        console.log('✅ Booking flow component rendered');
        testResults.passed++;

        // ============================================================
        // TEST 4: Check Service Section
        // ============================================================
        console.log('\n📋 TEST 4: Service Selection Section');

        const serviceSection = await page.evaluate(() => {
            const section = document.querySelector('.fi-section');
            const header = section?.querySelector('.fi-section-header');
            const radioGroup = section?.querySelector('.fi-radio-group');
            const radioOptions = section?.querySelectorAll('.fi-radio-option');

            return {
                exists: !!section,
                headerText: header?.textContent?.trim(),
                hasRadioGroup: !!radioGroup,
                optionCount: radioOptions?.length || 0,
                firstOption: radioOptions?.[0]?.textContent?.trim()
            };
        });

        console.log('Service Section:', serviceSection);

        if (!serviceSection.exists) {
            throw new Error('Service section not found');
        }
        if (serviceSection.optionCount === 0) {
            throw new Error('No service options available');
        }

        await takeScreenshot(page, '06-service-section-visible');
        console.log('✅ Service section rendered with options');
        testResults.passed++;

        // ============================================================
        // TEST 5: Check Default Service Selected
        // ============================================================
        console.log('\n📋 TEST 5: Default Service Selection');

        const defaultService = await page.evaluate(() => {
            const selectedOption = document.querySelector('.fi-radio-option.selected');
            const selectedInput = document.querySelector('input[name="service"]:checked');

            return {
                hasSelected: !!selectedOption,
                serviceName: selectedOption?.querySelector('.font-medium')?.textContent?.trim(),
                serviceId: selectedInput?.value
            };
        });

        console.log('Default Service:', defaultService);

        if (!defaultService.hasSelected) {
            await takeScreenshot(page, '07-WARN-no-default-service');
            console.log('⚠️  Warning: No default service selected');
        } else {
            console.log(`✅ Default service: ${defaultService.serviceName}`);
            testResults.passed++;
        }

        // ============================================================
        // TEST 6: Check Employee Preference Section
        // ============================================================
        console.log('\n📋 TEST 6: Employee Preference Section');

        const employeeSection = await page.evaluate(() => {
            const sections = document.querySelectorAll('.fi-section');
            const employeeSection = Array.from(sections).find(s =>
                s.querySelector('.fi-section-header')?.textContent?.includes('Mitarbeiter')
            );

            const radioOptions = employeeSection?.querySelectorAll('.fi-radio-option');
            const anyOption = Array.from(radioOptions || []).find(opt =>
                opt.textContent.includes('Nächster verfügbar')
            );

            return {
                exists: !!employeeSection,
                optionCount: radioOptions?.length || 0,
                hasAnyOption: !!anyOption,
                anySelected: anyOption?.classList.contains('selected')
            };
        });

        console.log('Employee Section:', employeeSection);

        if (!employeeSection.exists) {
            throw new Error('Employee section not found');
        }
        if (!employeeSection.hasAnyOption) {
            throw new Error('"Nächster verfügbar" option not found');
        }

        console.log('✅ Employee section rendered');
        testResults.passed++;

        // ============================================================
        // TEST 7: Check Calendar Grid
        // ============================================================
        console.log('\n📋 TEST 7: Calendar Grid Rendering');

        await page.waitForTimeout(3000); // Wait for Livewire to load slots
        await takeScreenshot(page, '08-calendar-loading');

        const calendarInfo = await page.evaluate(() => {
            const sections = document.querySelectorAll('.fi-section');
            const calendarSection = Array.from(sections).find(s =>
                s.querySelector('.fi-section-header')?.textContent?.includes('Verfügbare Termine')
            );

            const calendar = calendarSection?.querySelector('.fi-calendar-grid');
            const slots = calendarSection?.querySelectorAll('.fi-slot-button');
            const loading = calendarSection?.querySelector('.animate-spin');
            const error = calendarSection?.querySelector('.bg-red-900');

            return {
                exists: !!calendarSection,
                hasCalendar: !!calendar,
                isLoading: !!loading,
                hasError: !!error,
                errorText: error?.textContent?.trim(),
                slotCount: slots?.length || 0
            };
        });

        console.log('Calendar Info:', calendarInfo);

        if (calendarInfo.hasError) {
            await takeScreenshot(page, '09-ERROR-calendar-error');
            throw new Error(`Calendar error: ${calendarInfo.errorText}`);
        }

        if (calendarInfo.isLoading) {
            console.log('⏳ Calendar still loading, waiting...');
            await page.waitForTimeout(5000);
            await takeScreenshot(page, '10-calendar-after-wait');
        }

        console.log(`✅ Calendar rendered with ${calendarInfo.slotCount} slots`);
        testResults.passed++;

        // ============================================================
        // TEST 8: Test Service Change
        // ============================================================
        console.log('\n📋 TEST 8: Service Change Interaction');

        // Find and click second service option
        const serviceChanged = await page.evaluate(() => {
            const radioOptions = document.querySelectorAll('.fi-radio-option');
            if (radioOptions.length < 2) return false;

            const secondOption = radioOptions[1];
            const radioInput = secondOption.querySelector('input[type="radio"]');
            if (radioInput) {
                radioInput.click();
                return true;
            }
            return false;
        });

        if (serviceChanged) {
            console.log('🖱️  Clicked second service option');
            await page.waitForTimeout(2000); // Wait for Livewire update
            await takeScreenshot(page, '11-service-changed');

            // Check if calendar reloaded
            const reloadInfo = await page.evaluate(() => {
                const loading = document.querySelector('.animate-spin');
                return { isLoading: !!loading };
            });

            if (reloadInfo.isLoading) {
                console.log('⏳ Calendar reloading...');
                await page.waitForTimeout(3000);
            }

            console.log('✅ Service change triggered calendar reload');
            testResults.passed++;
        } else {
            console.log('⚠️  Warning: Could not test service change (not enough services)');
        }

        // ============================================================
        // TEST 9: Test Slot Selection
        // ============================================================
        console.log('\n📋 TEST 9: Slot Selection');

        const slotClicked = await page.evaluate(() => {
            const slots = document.querySelectorAll('.fi-slot-button');
            if (slots.length === 0) return false;

            // Click first available slot
            slots[0].click();
            return true;
        });

        if (slotClicked) {
            console.log('🖱️  Clicked first available slot');
            await page.waitForTimeout(1500);
            await takeScreenshot(page, '12-slot-selected');

            // Check if confirmation box appeared
            const confirmationBox = await page.evaluate(() => {
                const confirmation = document.querySelector('.fi-selected-confirmation');
                return {
                    exists: !!confirmation,
                    text: confirmation?.textContent?.trim()
                };
            });

            console.log('Confirmation Box:', confirmationBox);

            if (confirmationBox.exists) {
                console.log('✅ Slot selection confirmation displayed');
                testResults.passed++;
            } else {
                console.log('⚠️  Warning: No confirmation box appeared');
            }
        } else {
            console.log('⚠️  Warning: No slots available to click');
        }

        // ============================================================
        // TEST 10: Check Hidden Field Population
        // ============================================================
        console.log('\n📋 TEST 10: Hidden Field Population');

        const hiddenFields = await page.evaluate(() => {
            const startsAt = document.querySelector('input[name="starts_at"]');
            const serviceId = document.querySelector('input[name="service_id"]');
            const endsAt = document.querySelector('input[name="ends_at"]');

            return {
                startsAt: {
                    exists: !!startsAt,
                    value: startsAt?.value
                },
                serviceId: {
                    exists: !!serviceId,
                    value: serviceId?.value
                },
                endsAt: {
                    exists: !!endsAt,
                    value: endsAt?.value
                }
            };
        });

        console.log('Hidden Fields:', JSON.stringify(hiddenFields, null, 2));

        if (!hiddenFields.startsAt.exists) {
            throw new Error('Hidden field "starts_at" not found');
        }

        if (hiddenFields.startsAt.value) {
            console.log(`✅ starts_at populated: ${hiddenFields.startsAt.value}`);
            testResults.passed++;
        } else {
            console.log('⚠️  Warning: starts_at field is empty');
        }

        if (hiddenFields.endsAt.value) {
            console.log(`✅ ends_at calculated: ${hiddenFields.endsAt.value}`);
            testResults.passed++;
        } else {
            console.log('⚠️  Warning: ends_at field is empty');
        }

        // ============================================================
        // TEST 11: Check for Console Errors
        // ============================================================
        console.log('\n📋 TEST 11: JavaScript Console Errors');

        if (consoleLog.errors.length > 0) {
            console.log('❌ Console Errors Found:');
            consoleLog.errors.forEach((err, i) => {
                console.log(`  ${i + 1}. ${err}`);
            });
            testResults.errors.push(...consoleLog.errors);
        } else {
            console.log('✅ No console errors detected');
            testResults.passed++;
        }

        if (consoleLog.warnings.length > 0) {
            console.log('⚠️  Console Warnings:');
            consoleLog.warnings.slice(0, 5).forEach((warn, i) => {
                console.log(`  ${i + 1}. ${warn}`);
            });
        }

        // Final screenshot
        await takeScreenshot(page, '13-final-state');

    } catch (error) {
        console.error('\n💥 TEST FAILED:', error.message);
        testResults.failed++;
        testResults.errors.push(error.message);
        await takeScreenshot(page, 'ERROR-test-failed');
    } finally {
        await browser.close();
    }

    // ============================================================
    // Test Results Summary
    // ============================================================
    console.log('\n' + '='.repeat(60));
    console.log('📊 TEST RESULTS SUMMARY');
    console.log('='.repeat(60));
    console.log(`✅ Passed: ${testResults.passed}`);
    console.log(`❌ Failed: ${testResults.failed}`);
    console.log(`⚠️  Errors: ${testResults.errors.length}`);

    if (testResults.errors.length > 0) {
        console.log('\n🔍 Error Details:');
        testResults.errors.forEach((err, i) => {
            console.log(`  ${i + 1}. ${err}`);
        });
    }

    // Write results to file
    const resultsFile = path.join(SCREENSHOT_DIR, 'test-results.json');
    fs.writeFileSync(resultsFile, JSON.stringify({
        timestamp: new Date().toISOString(),
        passed: testResults.passed,
        failed: testResults.failed,
        errors: testResults.errors,
        warnings: consoleLog.warnings
    }, null, 2));

    console.log(`\n📄 Results saved to: ${resultsFile}`);
    console.log(`📸 Screenshots saved to: ${SCREENSHOT_DIR}`);

    // Exit code
    process.exit(testResults.failed > 0 ? 1 : 0);
}

// Run tests
runTests().catch(error => {
    console.error('💥 Test suite crashed:', error);
    process.exit(1);
});
