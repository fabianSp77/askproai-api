/**
 * V4 Booking Flow - Comprehensive 500 Error Detection Test
 *
 * Complete end-to-end test with:
 * - Real authentication
 * - 500 error detection
 * - Component validation
 * - Screenshot capture
 * - Detailed logging
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOT_DIR = '/var/www/api-gateway/tests/puppeteer/screenshots';

// Ensure screenshot directory exists
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function runComprehensiveTest() {
    console.log('🧪 V4 Booking Flow - Comprehensive Test');
    console.log('=' .repeat(60));
    console.log('');

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

    // Capture console logs
    const consoleLogs = [];
    page.on('console', msg => {
        consoleLogs.push(`[${msg.type()}] ${msg.text()}`);
    });

    // Capture network errors
    const networkErrors = [];
    page.on('response', response => {
        if (response.status() >= 400) {
            networkErrors.push({
                url: response.url(),
                status: response.status(),
                statusText: response.statusText()
            });
        }
    });

    let testResults = {
        passed: [],
        failed: [],
        warnings: []
    };

    try {
        // ==========================================
        // STEP 1: Login
        // ==========================================
        console.log('🔐 STEP 1: Authenticating...');

        await page.goto(`${BASE_URL}/admin/login`, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        // Take screenshot of login page
        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '01-login-page.png')
        });

        // Check if we're already logged in
        const currentUrl = page.url();
        if (!currentUrl.includes('/login')) {
            console.log('   ✅ Already logged in, redirected to dashboard');
            testResults.passed.push('Already authenticated');
        } else {
            // Fill login form
            await page.waitForSelector('input[type="email"]', { timeout: 5000 });
            await page.type('input[type="email"]', 'admin@askproai.de');
            await page.type('input[type="password"]', 'askpro2024!');

            await page.screenshot({
                path: path.join(SCREENSHOT_DIR, '02-login-filled.png')
            });

            // Submit
            await Promise.all([
                page.click('button[type="submit"]'),
                page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 })
            ]);

            console.log('   ✅ Login successful');
            testResults.passed.push('Login successful');
        }

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '03-after-login.png')
        });

        // ==========================================
        // STEP 2: Navigate to Appointments Create
        // ==========================================
        console.log('\n📄 STEP 2: Loading /admin/appointments/create');

        const response = await page.goto(`${BASE_URL}/admin/appointments/create`, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        const statusCode = response.status();
        console.log(`   HTTP Status: ${statusCode}`);

        // Screenshot immediately after load
        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '04-page-loaded.png'),
            fullPage: true
        });

        // Check for 500 error
        if (statusCode === 500) {
            console.log('   ❌ 500 INTERNAL SERVER ERROR DETECTED!');
            testResults.failed.push('500 Error on page load');

            // Try to extract error details from page
            const pageText = await page.evaluate(() => document.body.innerText);

            if (pageText.includes('ErrorException') || pageText.includes('Exception')) {
                console.log('\n   Error Details Found:');
                const errorLines = pageText.split('\n').slice(0, 20);
                errorLines.forEach(line => {
                    if (line.trim()) console.log('   ' + line.trim());
                });
            }

            await browser.close();
            return testResults;
        }

        if (statusCode === 200) {
            console.log('   ✅ Page loaded successfully (200 OK)');
            testResults.passed.push('Page loads without 500 error');
        } else if (statusCode === 302) {
            console.log('   ⚠️  302 Redirect detected');
            testResults.warnings.push(`Unexpected redirect (${statusCode})`);
        } else {
            console.log(`   ⚠️  Unexpected status: ${statusCode}`);
            testResults.warnings.push(`Unexpected status code: ${statusCode}`);
        }

        // Wait for page to fully render
        console.log('   ⏳ Waiting for Livewire to initialize...');
        await sleep(3000);

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '05-after-livewire.png'),
            fullPage: true
        });

        // ==========================================
        // STEP 3: Check for PHP/Livewire Errors
        // ==========================================
        console.log('\n🔍 STEP 3: Checking for errors on page');

        const errorCheck = await page.evaluate(() => {
            const bodyText = document.body.innerText;
            const bodyHTML = document.body.innerHTML;

            return {
                hasErrorException: bodyText.includes('ErrorException') || bodyText.includes('Exception'),
                hasSQLError: bodyText.includes('SQLSTATE') || bodyText.includes('SQL'),
                hasUndefinedError: bodyText.includes('Undefined') || bodyText.includes('undefined'),
                hasLivewireError: bodyHTML.includes('wire:snapshot') && bodyHTML.includes('error'),
                hasBlankPage: bodyText.trim().length < 100,
                bodyLength: bodyText.length
            };
        });

        if (errorCheck.hasErrorException) {
            console.log('   ❌ PHP Exception detected on page!');
            testResults.failed.push('PHP Exception visible on page');
        } else {
            console.log('   ✅ No PHP exceptions visible');
            testResults.passed.push('No PHP exceptions');
        }

        if (errorCheck.hasSQLError) {
            console.log('   ❌ SQL Error detected!');
            testResults.failed.push('SQL Error visible on page');
        } else {
            console.log('   ✅ No SQL errors');
            testResults.passed.push('No SQL errors');
        }

        if (errorCheck.hasUndefinedError) {
            console.log('   ⚠️  "Undefined" detected on page');
            testResults.warnings.push('Undefined error text found');
        } else {
            console.log('   ✅ No undefined errors');
            testResults.passed.push('No undefined errors');
        }

        if (errorCheck.hasBlankPage) {
            console.log('   ⚠️  Page appears mostly blank');
            testResults.warnings.push('Page content very short');
        }

        // ==========================================
        // STEP 4: Check Component Rendering
        // ==========================================
        console.log('\n🎨 STEP 4: Checking component rendering');

        const componentCheck = await page.evaluate(() => {
            // Check for booking flow component
            const bookingFlow = document.querySelector('.appointment-booking-flow');

            // Check for sections
            const serviceSectionHeader = Array.from(document.querySelectorAll('.fi-section-header'))
                .find(h => h.textContent.includes('Service auswählen'));
            const employeeSectionHeader = Array.from(document.querySelectorAll('.fi-section-header'))
                .find(h => h.textContent.includes('Mitarbeiter-Präferenz'));
            const calendarSectionHeader = Array.from(document.querySelectorAll('.fi-section-header'))
                .find(h => h.textContent.includes('Verfügbare Termine'));

            // Count elements
            const sections = document.querySelectorAll('.fi-section');
            const radioOptions = document.querySelectorAll('.fi-radio-option');
            const calendarGrid = document.querySelector('.fi-calendar-grid');
            const slotButtons = document.querySelectorAll('.fi-slot-button');

            return {
                hasBookingFlow: bookingFlow !== null,
                hasServiceSection: serviceSectionHeader !== null,
                hasEmployeeSection: employeeSectionHeader !== null,
                hasCalendarSection: calendarSectionHeader !== null,
                sectionCount: sections.length,
                radioOptionCount: radioOptions.length,
                hasCalendarGrid: calendarGrid !== null,
                slotButtonCount: slotButtons.length
            };
        });

        console.log(`   Booking Flow Component: ${componentCheck.hasBookingFlow ? '✅ Found' : '❌ Missing'}`);
        console.log(`   Service Section: ${componentCheck.hasServiceSection ? '✅ Found' : '❌ Missing'}`);
        console.log(`   Employee Section: ${componentCheck.hasEmployeeSection ? '✅ Found' : '❌ Missing'}`);
        console.log(`   Calendar Section: ${componentCheck.hasCalendarSection ? '✅ Found' : '❌ Missing'}`);
        console.log(`   Total Sections: ${componentCheck.sectionCount}`);
        console.log(`   Radio Options: ${componentCheck.radioOptionCount}`);
        console.log(`   Calendar Grid: ${componentCheck.hasCalendarGrid ? '✅ Yes' : '❌ No'}`);
        console.log(`   Slot Buttons: ${componentCheck.slotButtonCount}`);

        if (componentCheck.hasBookingFlow) {
            testResults.passed.push('Booking flow component rendered');
        } else {
            testResults.failed.push('Booking flow component NOT found');
        }

        if (componentCheck.hasServiceSection && componentCheck.hasEmployeeSection && componentCheck.hasCalendarSection) {
            testResults.passed.push('All 3 sections rendered');
        } else {
            testResults.failed.push('Missing sections in booking flow');
        }

        // ==========================================
        // STEP 5: Color Scheme Check
        // ==========================================
        console.log('\n🎨 STEP 5: Checking color scheme');

        const colorInfo = await page.evaluate(() => {
            const section = document.querySelector('.fi-section');
            if (!section) return { found: false };

            const styles = window.getComputedStyle(section);
            const bgColor = styles.backgroundColor;
            const rgb = bgColor.match(/\d+/g);

            let brightness = 0;
            if (rgb) {
                brightness = (parseInt(rgb[0]) * 299 + parseInt(rgb[1]) * 587 + parseInt(rgb[2]) * 114) / 1000;
            }

            const isDarkMode = document.documentElement.classList.contains('dark');

            return {
                found: true,
                backgroundColor: bgColor,
                brightness: Math.round(brightness),
                isDarkMode: isDarkMode,
                isTooDark: brightness < 50,
                isReasonable: brightness >= 50 && brightness < 200
            };
        });

        if (colorInfo.found) {
            console.log(`   Theme Mode: ${colorInfo.isDarkMode ? 'Dark' : 'Light'}`);
            console.log(`   Background: ${colorInfo.backgroundColor} (brightness: ${colorInfo.brightness})`);

            if (colorInfo.isTooDark && !colorInfo.isDarkMode) {
                console.log('   ⚠️  WARNING: Very dark colors in light mode!');
                testResults.warnings.push('Dark colors in light mode');
            } else {
                console.log('   ✅ Color scheme appropriate for theme');
                testResults.passed.push('Color scheme matches theme');
            }
        } else {
            console.log('   ⚠️  Could not analyze colors (no .fi-section found)');
            testResults.warnings.push('Could not check colors');
        }

        // ==========================================
        // STEP 6: Network Errors Check
        // ==========================================
        console.log('\n🌐 STEP 6: Checking network requests');

        if (networkErrors.length > 0) {
            console.log(`   ⚠️  Found ${networkErrors.length} HTTP errors:`);
            networkErrors.forEach(err => {
                console.log(`      - ${err.status} ${err.statusText}: ${err.url}`);
            });
            testResults.warnings.push(`${networkErrors.length} HTTP errors detected`);
        } else {
            console.log('   ✅ No HTTP errors (4xx/5xx) detected');
            testResults.passed.push('No network errors');
        }

        // ==========================================
        // STEP 7: Screenshots
        // ==========================================
        console.log('\n📸 STEP 7: Taking final screenshots');

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '06-final-full-page.png'),
            fullPage: true
        });
        console.log('   ✅ Full page: 06-final-full-page.png');

        const component = await page.$('.appointment-booking-flow');
        if (component) {
            await component.screenshot({
                path: path.join(SCREENSHOT_DIR, '07-component-only.png')
            });
            console.log('   ✅ Component: 07-component-only.png');
        }

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '08-viewport.png'),
            fullPage: false
        });
        console.log('   ✅ Viewport: 08-viewport.png');

        // ==========================================
        // STEP 8: Console Logs
        // ==========================================
        console.log('\n📝 STEP 8: Browser console logs');

        const errorLogs = consoleLogs.filter(log =>
            log.includes('[error]') || log.includes('Error') || log.includes('Failed')
        );

        if (errorLogs.length > 0) {
            console.log(`   ⚠️  Found ${errorLogs.length} console errors:`);
            errorLogs.slice(0, 5).forEach(log => {
                console.log(`      ${log}`);
            });
            testResults.warnings.push(`${errorLogs.length} console errors`);
        } else {
            console.log('   ✅ No console errors');
            testResults.passed.push('Clean console');
        }

    } catch (error) {
        console.error(`\n❌ Test failed with error: ${error.message}`);
        testResults.failed.push(`Test error: ${error.message}`);

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, '99-error-screenshot.png'),
            fullPage: true
        });
    } finally {
        await browser.close();
    }

    // ==========================================
    // FINAL SUMMARY
    // ==========================================
    console.log('\n' + '='.repeat(60));
    console.log('📊 TEST SUMMARY');
    console.log('='.repeat(60));

    console.log(`\n✅ PASSED (${testResults.passed.length}):`);
    testResults.passed.forEach(item => console.log(`   - ${item}`));

    if (testResults.warnings.length > 0) {
        console.log(`\n⚠️  WARNINGS (${testResults.warnings.length}):`);
        testResults.warnings.forEach(item => console.log(`   - ${item}`));
    }

    if (testResults.failed.length > 0) {
        console.log(`\n❌ FAILED (${testResults.failed.length}):`);
        testResults.failed.forEach(item => console.log(`   - ${item}`));
    }

    console.log(`\n📁 Screenshots: ${SCREENSHOT_DIR}`);
    console.log('='.repeat(60));

    // Exit code
    if (testResults.failed.length > 0) {
        console.log('\n💥 TEST SUITE FAILED - Review errors above');
        process.exit(1);
    } else if (testResults.warnings.length > 0) {
        console.log('\n⚠️  TEST SUITE PASSED WITH WARNINGS');
        process.exit(0);
    } else {
        console.log('\n✅ TEST SUITE PASSED - All checks successful!');
        process.exit(0);
    }
}

// Run the comprehensive test
runComprehensiveTest().catch(error => {
    console.error('💥 Test suite crashed:', error);
    process.exit(1);
});
