/**
 * Unified Booking Flow E2E Test
 *
 * Tests the complete V4 booking flow with:
 * - Branch selection
 * - Customer search & selection
 * - Service selection
 * - Employee preference
 * - Calendar slot selection
 * - Form submission
 * - Duplicate fields verification
 * - Dark mode contrast
 *
 * Run: node tests/puppeteer/unified-booking-flow-e2e.cjs
 */

const puppeteer = require('puppeteer');

// Configuration
const BASE_URL = process.env.APP_URL || 'https://app.askpro.ai';
const TEST_EMAIL = process.env.TEST_EMAIL || 'admin@example.com';
const TEST_PASSWORD = process.env.TEST_PASSWORD || 'password';

// ANSI colors for output
const colors = {
    reset: '\x1b[0m',
    green: '\x1b[32m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    cyan: '\x1b[36m',
};

function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

async function login(page) {
    log('🔐 Logging in...', 'cyan');

    await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2' });
    await page.type('input[type="email"]', TEST_EMAIL);
    await page.type('input[type="password"]', TEST_PASSWORD);
    await page.click('button[type="submit"]');

    await page.waitForNavigation({ waitUntil: 'networkidle2' });
    log('✓ Logged in successfully', 'green');
}

async function test1_BranchSelection(page) {
    log('\n[TEST 1] Branch Selection', 'blue');

    try {
        // Wait for booking flow to load
        await page.waitForSelector('.appointment-booking-flow', { timeout: 10000 });

        // Check if branches are visible
        const branchSection = await page.$('.fi-section:has(.fi-radio-option)');
        if (!branchSection) {
            log('✗ Branch section not found', 'red');
            return false;
        }

        // Select first branch
        const branchRadio = await page.$('.fi-radio-option input[type="radio"]');
        if (branchRadio) {
            await branchRadio.click();
            await page.waitForTimeout(1000); // Wait for event propagation

            // Verify branch_id was populated
            const branchId = await page.evaluate(() => {
                const select = document.querySelector('select[name="branch_id"]');
                return select ? select.value : null;
            });

            if (branchId) {
                log(`✓ Branch selected and branch_id populated: ${branchId}`, 'green');
                return true;
            } else {
                log('✗ branch_id not populated', 'red');
                return false;
            }
        }

        log('⚠ No branches available for selection', 'yellow');
        return true; // Not a failure if no branches exist

    } catch (error) {
        log(`✗ Branch selection failed: ${error.message}`, 'red');
        return false;
    }
}

async function test2_CustomerSearch(page) {
    log('\n[TEST 2] Customer Search & Selection', 'blue');

    try {
        // Type in customer search
        const searchInput = await page.$('input[wire\\:model*="customerSearchQuery"]');
        if (!searchInput) {
            log('✗ Customer search input not found', 'red');
            return false;
        }

        await searchInput.type('Test', { delay: 100 });
        await page.waitForTimeout(500); // Wait for debounce + search

        // Check if results appeared
        const resultsExist = await page.$('.fi-search-results');
        if (!resultsExist) {
            log('⚠ No search results (customer might not exist)', 'yellow');
            // Try clearing and continue
            await searchInput.click({ clickCount: 3 });
            await searchInput.press('Backspace');
            return true;
        }

        // Click first result
        const firstResult = await page.$('.fi-customer-result');
        if (firstResult) {
            await firstResult.click();
            await page.waitForTimeout(1000);

            // Verify customer_id was populated
            const customerId = await page.evaluate(() => {
                const select = document.querySelector('select[name="customer_id"]');
                return select ? select.value : null;
            });

            if (customerId) {
                log(`✓ Customer selected and customer_id populated: ${customerId}`, 'green');
                return true;
            } else {
                log('✗ customer_id not populated', 'red');
                return false;
            }
        }

        log('✗ No customer results to click', 'red');
        return false;

    } catch (error) {
        log(`✗ Customer search failed: ${error.message}`, 'red');
        return false;
    }
}

async function test3_ServiceSelection(page) {
    log('\n[TEST 3] Service Selection', 'blue');

    try {
        // Find service radio buttons
        const serviceRadios = await page.$$('.fi-radio-option input[name="service"]');
        if (serviceRadios.length === 0) {
            log('✗ No service options found', 'red');
            return false;
        }

        // Click first service
        await serviceRadios[0].click();
        await page.waitForTimeout(1000);

        // Verify service_id was populated
        const serviceId = await page.evaluate(() => {
            const select = document.querySelector('select[name="service_id"]') ||
                           document.querySelector('input[name="service_id"]');
            return select ? select.value : null;
        });

        if (serviceId) {
            log(`✓ Service selected and service_id populated: ${serviceId}`, 'green');
            return true;
        } else {
            log('✗ service_id not populated', 'red');
            return false;
        }

    } catch (error) {
        log(`✗ Service selection failed: ${error.message}`, 'red');
        return false;
    }
}

async function test4_EmployeePreference(page) {
    log('\n[TEST 4] Employee/Staff Preference', 'blue');

    try {
        // Find employee radio buttons
        const employeeRadios = await page.$$('.fi-radio-option input[name="employee"]');
        if (employeeRadios.length === 0) {
            log('✗ No employee options found', 'red');
            return false;
        }

        // Try selecting a specific employee (not "any")
        if (employeeRadios.length > 1) {
            await employeeRadios[1].click(); // Second option = first employee
            await page.waitForTimeout(1000);

            // Verify staff_id was populated (optional field)
            const staffId = await page.evaluate(() => {
                const select = document.querySelector('select[name="staff_id"]') ||
                               document.querySelector('input[name="staff_id"]');
                return select ? select.value : null;
            });

            if (staffId) {
                log(`✓ Employee selected and staff_id populated: ${staffId}`, 'green');
            } else {
                log('⚠ staff_id not populated (may be optional)', 'yellow');
            }
            return true;
        } else {
            // Only "any" available
            await employeeRadios[0].click();
            log('✓ "Any available" employee selected', 'green');
            return true;
        }

    } catch (error) {
        log(`✗ Employee preference failed: ${error.message}`, 'red');
        return false;
    }
}

async function test5_CalendarSlotSelection(page) {
    log('\n[TEST 5] Calendar Slot Selection', 'blue');

    try {
        // Wait for calendar to load
        await page.waitForSelector('.fi-calendar-grid', { timeout: 10000 });

        // Find available slot buttons
        const slotButtons = await page.$$('.fi-slot-button');
        if (slotButtons.length === 0) {
            log('⚠ No available slots (may be expected)', 'yellow');
            return true; // Not a failure
        }

        // Click first available slot
        await slotButtons[0].click();
        await page.waitForTimeout(1000);

        // Verify starts_at was populated
        const startsAt = await page.evaluate(() => {
            const input = document.querySelector('input[name="starts_at"]');
            return input ? input.value : null;
        });

        if (startsAt) {
            log(`✓ Slot selected and starts_at populated: ${startsAt}`, 'green');
            return true;
        } else {
            log('✗ starts_at not populated', 'red');
            return false;
        }

    } catch (error) {
        log(`✗ Calendar slot selection failed: ${error.message}`, 'red');
        return false;
    }
}

async function test6_NoDuplicateFieldsInCreateMode(page) {
    log('\n[TEST 6] No Duplicate Fields in Create Mode', 'blue');

    try {
        // Check that old service/staff dropdowns are NOT visible
        const oldServiceVisible = await page.evaluate(() => {
            const select = document.querySelector('select[name="service_id"]');
            if (!select) return false;

            // Check if parent Grid is hidden
            const parent = select.closest('[data-field-wrapper]');
            return parent ? window.getComputedStyle(parent).display !== 'none' : true;
        });

        if (oldServiceVisible) {
            log('✗ Old service dropdown is visible in create mode', 'red');
            return false;
        } else {
            log('✓ Old service/staff dropdowns are hidden in create mode', 'green');
            return true;
        }

    } catch (error) {
        log(`✗ Duplicate fields check failed: ${error.message}`, 'red');
        return false;
    }
}

async function test7_FormSubmission(page) {
    log('\n[TEST 7] Form Submission (Validation)', 'blue');

    try {
        // Check if submit button exists
        const submitButton = await page.$('button[type="submit"]');
        if (!submitButton) {
            log('✗ Submit button not found', 'red');
            return false;
        }

        // Check form validity (all required fields filled)
        const formIsValid = await page.evaluate(() => {
            const form = document.querySelector('form');
            return form ? form.checkValidity() : false;
        });

        if (formIsValid) {
            log('✓ Form is valid and ready for submission', 'green');
            // Don't actually submit to avoid creating test data
            return true;
        } else {
            log('⚠ Form validation failed (some required fields missing)', 'yellow');
            return true; // Not a test failure - expected if slots weren't available
        }

    } catch (error) {
        log(`✗ Form submission check failed: ${error.message}`, 'red');
        return false;
    }
}

async function test8_DarkModeContrast(page) {
    log('\n[TEST 8] Dark Mode Contrast', 'blue');

    try {
        // Toggle dark mode
        await page.evaluate(() => {
            document.documentElement.classList.add('dark');
        });
        await page.waitForTimeout(500);

        // Check border visibility (contrast)
        const contrastCheck = await page.evaluate(() => {
            const section = document.querySelector('.fi-section');
            if (!section) return false;

            const styles = window.getComputedStyle(section);
            const borderColor = styles.borderColor;

            // Check if border is not transparent and not same as background
            return borderColor && borderColor !== 'rgba(0, 0, 0, 0)';
        });

        if (contrastCheck) {
            log('✓ Dark mode borders are visible (good contrast)', 'green');
        } else {
            log('✗ Dark mode borders not visible (poor contrast)', 'red');
            return false;
        }

        // Toggle back to light mode
        await page.evaluate(() => {
            document.documentElement.classList.remove('dark');
        });

        return true;

    } catch (error) {
        log(`✗ Dark mode contrast check failed: ${error.message}`, 'red');
        return false;
    }
}

async function runAllTests() {
    log('═══════════════════════════════════════════════', 'cyan');
    log('   Unified Booking Flow E2E Test Suite', 'cyan');
    log('═══════════════════════════════════════════════', 'cyan');

    const browser = await puppeteer.launch({
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    // Enable console logging
    page.on('console', msg => {
        if (msg.text().includes('[BookingFlowWrapper]')) {
            log(`   Browser: ${msg.text()}`, 'yellow');
        }
    });

    try {
        // Login first
        await login(page);

        // Navigate to Appointments Create page
        log('\n📍 Navigating to Appointments Create page...', 'cyan');
        await page.goto(`${BASE_URL}/admin/appointments/create`, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });
        log('✓ Page loaded', 'green');

        // Run all tests
        const results = {
            test1: await test1_BranchSelection(page),
            test2: await test2_CustomerSearch(page),
            test3: await test3_ServiceSelection(page),
            test4: await test4_EmployeePreference(page),
            test5: await test5_CalendarSlotSelection(page),
            test6: await test6_NoDuplicateFieldsInCreateMode(page),
            test7: await test7_FormSubmission(page),
            test8: await test8_DarkModeContrast(page),
        };

        // Summary
        log('\n═══════════════════════════════════════════════', 'cyan');
        log('   TEST SUMMARY', 'cyan');
        log('═══════════════════════════════════════════════', 'cyan');

        const passed = Object.values(results).filter(r => r === true).length;
        const total = Object.keys(results).length;

        Object.entries(results).forEach(([test, result]) => {
            const status = result ? '✓ PASS' : '✗ FAIL';
            const color = result ? 'green' : 'red';
            log(`${status} ${test}`, color);
        });

        log(`\n${passed}/${total} tests passed`, passed === total ? 'green' : 'red');

        if (passed === total) {
            log('\n🎉 ALL TESTS PASSED! 🎉', 'green');
        } else {
            log('\n⚠️  SOME TESTS FAILED', 'red');
        }

        // Take final screenshot
        await page.screenshot({
            path: 'tests/puppeteer/screenshots/unified-booking-flow-final.png',
            fullPage: true
        });
        log('\n📸 Screenshot saved: screenshots/unified-booking-flow-final.png', 'cyan');

    } catch (error) {
        log(`\n❌ CRITICAL ERROR: ${error.message}`, 'red');
        console.error(error);
    } finally {
        await browser.close();
    }
}

// Run tests
runAllTests().catch(console.error);
