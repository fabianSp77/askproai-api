/**
 * Week Picker Comprehensive Test
 * Tests appointment creation with Week Picker across different viewports and zoom levels
 *
 * Issue: #701 - User reports problems with Zoom 66.67%
 *
 * Test Scenarios:
 * 1. Desktop (1920x1080) - Normal Zoom (1.0)
 * 2. Desktop (1920x1080) - Zoom 66.67%
 * 3. Desktop (3840x1600) - Zoom 66.67% (User's actual setup)
 * 4. Mobile (375x667) - iPhone SE
 * 5. Tablet (768x1024) - iPad
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Configuration
const BASE_URL = 'https://api.askproai.de';
const SCREENSHOTS_DIR = path.join(__dirname, 'screenshots', 'week-picker-test');
const SESSION_COOKIE = 'askpro_ai_gateway_session=test'; // Replace with valid session

// Test scenarios
const SCENARIOS = [
    {
        name: 'desktop-normal',
        viewport: { width: 1920, height: 1080 },
        deviceScaleFactor: 1.0,
        description: 'Desktop - Normal Zoom'
    },
    {
        name: 'desktop-zoom-67',
        viewport: { width: 1920, height: 1080 },
        deviceScaleFactor: 0.6667,
        description: 'Desktop - Zoom 66.67%'
    },
    {
        name: 'desktop-4k-zoom-67',
        viewport: { width: 1802, height: 1430 }, // User's actual viewport from Issue #701
        deviceScaleFactor: 0.6667,
        description: 'Desktop 4K - Zoom 66.67% (Issue #701)'
    },
    {
        name: 'mobile-iphone',
        viewport: { width: 375, height: 667 },
        deviceScaleFactor: 2.0,
        description: 'iPhone SE'
    },
    {
        name: 'tablet-ipad',
        viewport: { width: 768, height: 1024 },
        deviceScaleFactor: 2.0,
        description: 'iPad'
    }
];

// Create screenshots directory
if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

// Helper function to wait and take screenshot
async function takeScreenshot(page, name, scenario) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    const filename = path.join(SCREENSHOTS_DIR, `${scenario.name}_${name}.png`);
    await page.screenshot({
        path: filename,
        fullPage: true
    });
    console.log(`  📸 Screenshot: ${scenario.name}_${name}.png`);
    return filename;
}

// Helper function to check if element is visible
async function isVisible(page, selector) {
    try {
        const element = await page.$(selector);
        if (!element) return false;

        const box = await element.boundingBox();
        return box !== null && box.width > 0 && box.height > 0;
    } catch (error) {
        return false;
    }
}

// Main test function
async function runTest(scenario) {
    console.log(`\n${'='.repeat(80)}`);
    console.log(`🧪 Testing: ${scenario.description}`);
    console.log(`   Viewport: ${scenario.viewport.width}x${scenario.viewport.height}`);
    console.log(`   Device Scale: ${scenario.deviceScaleFactor}`);
    console.log('='.repeat(80));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-web-security'
        ]
    });

    const page = await browser.newPage();

    // Set viewport
    await page.setViewport({
        width: scenario.viewport.width,
        height: scenario.viewport.height,
        deviceScaleFactor: scenario.deviceScaleFactor
    });

    // Set session cookie
    await page.setCookie({
        name: 'askpro_ai_gateway_session',
        value: 'test',
        domain: 'api.askproai.de',
        path: '/',
        httpOnly: true,
        secure: true
    });

    const results = {
        scenario: scenario.name,
        description: scenario.description,
        viewport: scenario.viewport,
        deviceScaleFactor: scenario.deviceScaleFactor,
        steps: [],
        success: false,
        errors: [],
        screenshots: []
    };

    try {
        // Step 1: Navigate to create appointment page
        console.log('\n📍 Step 1: Navigate to /admin/appointments/create');
        await page.goto(`${BASE_URL}/admin/appointments/create`, {
            waitUntil: 'networkidle0',
            timeout: 30000
        });

        const ss1 = await takeScreenshot(page, '01_initial_load', scenario);
        results.screenshots.push(ss1);
        results.steps.push({ step: 1, name: 'Navigate', status: 'success' });

        // Step 2: Check if we're logged in
        const loginForm = await page.$('input[name="email"]');
        if (loginForm) {
            console.log('  ❌ Not logged in - session invalid');
            results.errors.push('Not logged in - session cookie invalid');
            results.steps.push({ step: 2, name: 'Auth Check', status: 'failed' });
            await browser.close();
            return results;
        }
        console.log('  ✅ Logged in');
        results.steps.push({ step: 2, name: 'Auth Check', status: 'success' });

        // Step 3: Fill company field
        console.log('\n📍 Step 3: Select Company (Filiale)');
        await page.waitForSelector('select[name="company_id"]', { timeout: 5000 });
        await page.select('select[name="company_id"]', '1'); // Select first company
        await new Promise(resolve => setTimeout(resolve, 1000));

        const ss2 = await takeScreenshot(page, '02_company_selected', scenario);
        results.screenshots.push(ss2);
        results.steps.push({ step: 3, name: 'Select Company', status: 'success' });
        console.log('  ✅ Company selected');

        // Step 4: Fill customer field
        console.log('\n📍 Step 4: Select Customer');
        await page.waitForSelector('select[name="customer_id"]', { timeout: 5000 });
        await page.select('select[name="customer_id"]', '1'); // Select anonymous customer
        await new Promise(resolve => setTimeout(resolve, 1000));

        const ss3 = await takeScreenshot(page, '03_customer_selected', scenario);
        results.screenshots.push(ss3);
        results.steps.push({ step: 4, name: 'Select Customer', status: 'success' });
        console.log('  ✅ Customer selected');

        // Step 5: Select Service (triggers Week Picker)
        console.log('\n📍 Step 5: Select Service (should trigger Week Picker)');
        await page.waitForSelector('select[name="service_id"]', { timeout: 5000 });
        await page.select('select[name="service_id"]', '47'); // Service ID 47

        console.log('  ⏳ Waiting for Week Picker to load...');
        await new Promise(resolve => setTimeout(resolve, 3000)); // Give time for Livewire to load

        const ss4 = await takeScreenshot(page, '04_service_selected_week_picker', scenario);
        results.screenshots.push(ss4);
        results.steps.push({ step: 5, name: 'Select Service', status: 'success' });

        // Step 6: Check Week Picker visibility
        console.log('\n📍 Step 6: Verify Week Picker visibility');

        // Check for debug box
        const debugBox = await isVisible(page, '#debug-info');
        console.log(`  Debug Box visible: ${debugBox ? '✅' : '❌'}`);

        if (debugBox) {
            const debugText = await page.$eval('#debug-info', el => el.textContent);
            console.log(`  Debug Info: ${debugText.substring(0, 100)}...`);
        }

        // Check for desktop grid
        const desktopGrid = await isVisible(page, '.hidden.md\\:grid');
        console.log(`  Desktop Grid visible: ${desktopGrid ? '✅' : '❌'}`);

        // Check for mobile list
        const mobileList = await isVisible(page, '.md\\:hidden.space-y-3');
        console.log(`  Mobile List visible: ${mobileList ? '✅' : '❌'}`);

        // Check for slot buttons
        const slotButtons = await page.$$('.slot-button');
        console.log(`  Slot Buttons found: ${slotButtons.length}`);

        if (slotButtons.length === 0) {
            console.log('  ❌ No slot buttons found!');
            results.errors.push('No slot buttons rendered');
            results.steps.push({ step: 6, name: 'Week Picker Visibility', status: 'failed' });
        } else {
            console.log(`  ✅ Found ${slotButtons.length} slot buttons`);
            results.steps.push({ step: 6, name: 'Week Picker Visibility', status: 'success' });
        }

        // Step 7: Select Employee
        console.log('\n📍 Step 7: Select Employee');
        await page.waitForSelector('select[name="employee_id"]', { timeout: 5000 });
        await page.select('select[name="employee_id"]', '1'); // Select first employee
        await new Promise(resolve => setTimeout(resolve, 1000));

        const ss5 = await takeScreenshot(page, '05_employee_selected', scenario);
        results.screenshots.push(ss5);
        results.steps.push({ step: 7, name: 'Select Employee', status: 'success' });
        console.log('  ✅ Employee selected');

        // Step 8: Click on a slot
        console.log('\n📍 Step 8: Click on first available slot');

        if (slotButtons.length > 0) {
            // Enable console logging
            page.on('console', msg => {
                if (msg.text().includes('HYBRID CLICK')) {
                    console.log(`  🎯 Browser Console: ${msg.text()}`);
                }
            });

            // Click first slot
            await slotButtons[0].click();
            await new Promise(resolve => setTimeout(resolve, 2000));

            const ss6 = await takeScreenshot(page, '06_slot_selected', scenario);
            results.screenshots.push(ss6);

            // Check if debug box updated
            if (debugBox) {
                const slotStatusText = await page.$eval('#slot-status', el => el.textContent).catch(() => 'N/A');
                console.log(`  Slot Status: ${slotStatusText}`);

                if (slotStatusText.includes('✅ SLOT GESETZT')) {
                    console.log('  ✅ Slot selection detected by debug box');
                    results.steps.push({ step: 8, name: 'Click Slot', status: 'success' });
                } else {
                    console.log('  ⚠️ Slot selection NOT detected by debug box');
                    results.errors.push('Slot selection not reflected in debug box');
                    results.steps.push({ step: 8, name: 'Click Slot', status: 'warning' });
                }
            }

            // Check if hidden field was populated
            const startsAtValue = await page.$eval('input[name="starts_at"]', el => el.value).catch(() => null);
            console.log(`  Hidden Field value: ${startsAtValue || 'NULL'}`);

            if (startsAtValue && startsAtValue !== '') {
                console.log('  ✅ Hidden field populated correctly');
            } else {
                console.log('  ❌ Hidden field NOT populated');
                results.errors.push('Hidden field starts_at not populated after slot click');
            }

        } else {
            console.log('  ⏭️  Skipped - no slot buttons available');
            results.steps.push({ step: 8, name: 'Click Slot', status: 'skipped' });
        }

        // Step 9: Try to submit form
        console.log('\n📍 Step 9: Attempt form submission');

        const submitButton = await page.$('button[type="submit"]');
        if (submitButton) {
            await submitButton.click();
            await new Promise(resolve => setTimeout(resolve, 3000));

            const ss7 = await takeScreenshot(page, '07_after_submit', scenario);
            results.screenshots.push(ss7);

            // Check for success notification
            const successNotification = await page.$('.fi-notification-success').catch(() => null);
            const errorNotification = await page.$('.fi-notification-error').catch(() => null);
            const validationError = await page.$('.fi-fo-field-wrp-error-message').catch(() => null);

            if (successNotification) {
                console.log('  ✅ Form submitted successfully!');
                results.steps.push({ step: 9, name: 'Submit Form', status: 'success' });
                results.success = true;
            } else if (errorNotification || validationError) {
                console.log('  ❌ Form submission failed with error');
                results.errors.push('Form submission failed - validation or server error');
                results.steps.push({ step: 9, name: 'Submit Form', status: 'failed' });
            } else {
                console.log('  ⚠️ Form submission result unclear');
                results.steps.push({ step: 9, name: 'Submit Form', status: 'warning' });
            }
        } else {
            console.log('  ⏭️  Submit button not found');
            results.steps.push({ step: 9, name: 'Submit Form', status: 'skipped' });
        }

    } catch (error) {
        console.error(`\n❌ Test Error: ${error.message}`);
        results.errors.push(error.message);

        try {
            const errorSs = await takeScreenshot(page, '99_error', scenario);
            results.screenshots.push(errorSs);
        } catch (ssError) {
            console.error('  Could not take error screenshot');
        }
    } finally {
        await browser.close();
    }

    return results;
}

// Run all scenarios
async function runAllTests() {
    console.log('\n╔═══════════════════════════════════════════════════════════════════════════╗');
    console.log('║                  WEEK PICKER COMPREHENSIVE TEST SUITE                     ║');
    console.log('╚═══════════════════════════════════════════════════════════════════════════╝');

    const allResults = [];

    for (const scenario of SCENARIOS) {
        const result = await runTest(scenario);
        allResults.push(result);

        // Wait between tests
        await new Promise(resolve => setTimeout(resolve, 2000));
    }

    // Generate summary report
    console.log('\n\n╔═══════════════════════════════════════════════════════════════════════════╗');
    console.log('║                            TEST SUMMARY REPORT                            ║');
    console.log('╚═══════════════════════════════════════════════════════════════════════════╝\n');

    allResults.forEach(result => {
        console.log(`\n📊 ${result.description}`);
        console.log(`   Viewport: ${result.viewport.width}x${result.viewport.height} @ ${result.deviceScaleFactor}x`);
        console.log(`   Success: ${result.success ? '✅' : '❌'}`);
        console.log(`   Steps Completed: ${result.steps.filter(s => s.status === 'success').length}/${result.steps.length}`);

        if (result.errors.length > 0) {
            console.log(`   Errors:`);
            result.errors.forEach(err => console.log(`     - ${err}`));
        }

        console.log(`   Screenshots: ${result.screenshots.length}`);
    });

    // Write detailed JSON report
    const reportPath = path.join(SCREENSHOTS_DIR, 'test-report.json');
    fs.writeFileSync(reportPath, JSON.stringify(allResults, null, 2));
    console.log(`\n📄 Detailed report: ${reportPath}`);

    // Summary statistics
    const totalTests = allResults.length;
    const successfulTests = allResults.filter(r => r.success).length;
    const failedTests = totalTests - successfulTests;

    console.log('\n' + '='.repeat(80));
    console.log(`📈 OVERALL RESULTS: ${successfulTests}/${totalTests} scenarios successful`);
    console.log(`   ✅ Success: ${successfulTests}`);
    console.log(`   ❌ Failed: ${failedTests}`);
    console.log('='.repeat(80) + '\n');

    return allResults;
}

// Execute tests
runAllTests()
    .then(results => {
        const allSuccess = results.every(r => r.success);
        process.exit(allSuccess ? 0 : 1);
    })
    .catch(error => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
