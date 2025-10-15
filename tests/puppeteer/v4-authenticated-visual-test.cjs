/**
 * V4 Booking Flow - Authenticated Visual Test
 *
 * Logs in first, then validates the booking flow component
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOT_DIR = '/var/www/api-gateway/tests/puppeteer/screenshots';
const LOGIN_EMAIL = 'admin@askproai.de';
const LOGIN_PASSWORD = 'askpro2024!';

// Create screenshots directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function runAuthenticatedTest() {
    console.log('🔐 Starting Authenticated Visual Test\n');

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

    let testsPassed = 0;
    let testsFailed = 0;

    try {
        // ===== STEP 1: Login =====
        console.log('🔑 STEP 1: Logging in');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2' });

        await page.type('input[type="email"]', LOGIN_EMAIL);
        await page.type('input[type="password"]', LOGIN_PASSWORD);
        await page.click('button[type="submit"]');

        await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 15000 });
        console.log('   ✅ Login successful\n');

        // ===== STEP 2: Navigate to Appointments Create =====
        console.log('📄 STEP 2: Navigating to /admin/appointments/create');
        const response = await page.goto(`${BASE_URL}/admin/appointments/create`, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        const statusCode = response.status();
        console.log(`   Status: ${statusCode}`);

        if (statusCode !== 200) {
            console.log(`   ❌ Failed with status ${statusCode}\n`);
            testsFailed++;
            await page.screenshot({
                path: path.join(SCREENSHOT_DIR, 'error-page.png'),
                fullPage: true
            });
            await browser.close();
            return;
        }

        console.log('   ✅ Page loaded (200 OK)\n');
        testsPassed++;

        // Wait for Livewire
        await sleep(3000);

        // Screenshot after load
        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, 'v4-after-load.png'),
            fullPage: true
        });

        // ===== STEP 3: Check Component Exists =====
        console.log('🔍 STEP 3: Component existence check');
        const componentExists = await page.evaluate(() => {
            return document.querySelector('.appointment-booking-flow') !== null;
        });

        if (componentExists) {
            console.log('   ✅ Component found (.appointment-booking-flow)\n');
            testsPassed++;
        } else {
            console.log('   ❌ Component NOT found\n');
            console.log('   Checking what IS rendered...');

            const debugInfo = await page.evaluate(() => {
                const viewFields = document.querySelectorAll('[wire\\:id], [x-data]');
                const forms = document.querySelectorAll('form');
                const livewireComponents = document.querySelectorAll('[wire\\:id]');

                return {
                    viewFieldCount: viewFields.length,
                    formCount: forms.length,
                    livewireCount: livewireComponents.length,
                    bodyClasses: document.body.className,
                    hasBookingFlowField: Array.from(document.querySelectorAll('*')).some(el =>
                        el.className && el.className.includes('booking-flow')
                    )
                };
            });

            console.log('   Debug info:', debugInfo);
            testsFailed++;
        }

        // ===== STEP 4: Detailed Element Check =====
        console.log('\n🔎 STEP 4: Detailed element analysis');
        const elements = await page.evaluate(() => {
            const serviceSection = Array.from(document.querySelectorAll('*')).find(el =>
                el.textContent && el.textContent.includes('Service auswählen')
            );
            const employeeSection = Array.from(document.querySelectorAll('*')).find(el =>
                el.textContent && el.textContent.includes('Mitarbeiter-Präferenz')
            );
            const calendarSection = Array.from(document.querySelectorAll('*')).find(el =>
                el.textContent && el.textContent.includes('Verfügbare Termine')
            );

            return {
                hasServiceSection: serviceSection !== undefined,
                hasEmployeeSection: employeeSection !== undefined,
                hasCalendarSection: calendarSection !== undefined,
                serviceParentClass: serviceSection?.parentElement?.className || 'none',
                employeeParentClass: employeeSection?.parentElement?.className || 'none',
            };
        });

        console.log('   Service section:', elements.hasServiceSection ? '✅ Found' : '❌ Missing');
        console.log('   Employee section:', elements.hasEmployeeSection ? '✅ Found' : '❌ Missing');
        console.log('   Calendar section:', elements.hasCalendarSection ? '✅ Found' : '❌ Missing');

        if (elements.hasServiceSection && elements.hasEmployeeSection && elements.hasCalendarSection) {
            testsPassed++;
        } else {
            testsFailed++;
        }

        // ===== STEP 5: Color Scheme Check =====
        console.log('\n🎨 STEP 5: Color scheme analysis');
        const colorInfo = await page.evaluate(() => {
            const sections = document.querySelectorAll('.fi-section');
            if (sections.length === 0) return { found: false };

            const firstSection = sections[0];
            const styles = window.getComputedStyle(firstSection);
            const bgColor = styles.backgroundColor;
            const rgb = bgColor.match(/\d+/g);
            const brightness = rgb ? (parseInt(rgb[0]) * 299 + parseInt(rgb[1]) * 587 + parseInt(rgb[2]) * 114) / 1000 : 0;

            return {
                found: true,
                backgroundColor: bgColor,
                borderColor: styles.borderColor,
                textColor: styles.color,
                brightness: Math.round(brightness),
                isVeryDark: brightness < 50,
                isDark: brightness < 128
            };
        });

        if (colorInfo.found) {
            console.log(`   Background: ${colorInfo.backgroundColor} (brightness: ${colorInfo.brightness})`);
            console.log(`   Border: ${colorInfo.borderColor}`);
            console.log(`   Text: ${colorInfo.textColor}`);

            if (colorInfo.isVeryDark) {
                console.log('   ⚠️  WARNING: Very dark colors (brightness < 50)');
                console.log('   This may not match Filament light theme expectations');
            } else if (colorInfo.isDark) {
                console.log('   ℹ️  Dark color scheme detected (brightness < 128)');
            } else {
                console.log('   ✅ Light/moderate color scheme');
                testsPassed++;
            }
        } else {
            console.log('   ❌ No .fi-section elements found for analysis');
            testsFailed++;
        }

        // ===== STEP 6: Filament Theme Check =====
        console.log('\n🎭 STEP 6: Filament theme mode');
        const themeInfo = await page.evaluate(() => {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            const theme = html.getAttribute('class');

            return {
                mode: isDark ? 'dark' : 'light',
                classes: theme
            };
        });

        console.log(`   Theme mode: ${themeInfo.mode}`);
        console.log(`   HTML classes: ${themeInfo.classes}`);

        // ===== FINAL SCREENSHOTS =====
        console.log('\n📸 STEP 7: Taking final screenshots');

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, 'v4-authenticated-full.png'),
            fullPage: true
        });
        console.log(`   ✅ Full page: v4-authenticated-full.png`);

        const component = await page.$('.appointment-booking-flow');
        if (component) {
            await component.screenshot({
                path: path.join(SCREENSHOT_DIR, 'v4-component.png')
            });
            console.log(`   ✅ Component: v4-component.png`);
            testsPassed++;
        } else {
            console.log('   ⚠️  Component not found for screenshot');
        }

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, 'v4-viewport.png'),
            fullPage: false
        });
        console.log(`   ✅ Viewport: v4-viewport.png`);

    } catch (error) {
        console.error(`\n❌ Test failed: ${error.message}`);
        testsFailed++;

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, 'error-screenshot.png'),
            fullPage: true
        });
    } finally {
        await browser.close();
    }

    // ===== SUMMARY =====
    console.log('\n' + '='.repeat(60));
    console.log('📊 TEST SUMMARY');
    console.log('='.repeat(60));
    console.log(`✅ Passed: ${testsPassed}`);
    console.log(`❌ Failed: ${testsFailed}`);
    console.log(`📁 Screenshots: ${SCREENSHOT_DIR}`);
    console.log('='.repeat(60));

    if (testsFailed > 0) {
        console.log('\n⚠️  Review screenshots to diagnose issues');
        process.exit(1);
    } else {
        console.log('\n✅ All checks passed!');
        process.exit(0);
    }
}

runAuthenticatedTest().catch(error => {
    console.error('💥 Test crashed:', error);
    process.exit(1);
});
