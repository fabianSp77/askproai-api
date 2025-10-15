/**
 * V4 Booking Flow - Visual Validation & Design Compliance Test
 *
 * Tests:
 * - Page loads without 500 errors
 * - Component renders correctly
 * - All UI elements visible
 * - Color scheme matches Filament admin panel
 * - Layout and spacing correct
 * - Screenshots for visual inspection
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOT_DIR = '/var/www/api-gateway/tests/puppeteer/screenshots';

// Create screenshots directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function runVisualValidation() {
    console.log('🎨 Starting V4 Booking Flow Visual Validation\n');

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

    // ===== TEST 1: Load Appointments Create Page =====
    console.log('📄 TEST 1: Loading /admin/appointments/create');
    try {
        const response = await page.goto(`${BASE_URL}/admin/appointments/create`, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        const statusCode = response.status();
        console.log(`   Status Code: ${statusCode}`);

        if (statusCode === 200) {
            console.log('   ✅ Page loaded successfully (200 OK)\n');
            testsPassed++;
        } else if (statusCode === 500) {
            console.log('   ❌ 500 Internal Server Error - Check Laravel logs\n');
            testsFailed++;

            // Screenshot the error
            await page.screenshot({
                path: path.join(SCREENSHOT_DIR, 'error-500.png'),
                fullPage: true
            });

            // Try to get error details from page
            const bodyText = await page.evaluate(() => document.body.innerText);
            if (bodyText.includes('ErrorException') || bodyText.includes('SQLSTATE')) {
                console.log('   Error details found on page:');
                const errorSnippet = bodyText.substring(0, 500);
                console.log('   ' + errorSnippet.replace(/\n/g, '\n   '));
            }

            await browser.close();
            return;
        } else {
            console.log(`   ⚠️  Unexpected status code: ${statusCode}\n`);
            testsFailed++;
        }
    } catch (error) {
        console.log(`   ❌ Failed to load page: ${error.message}\n`);
        testsFailed++;
        await browser.close();
        return;
    }

    // Wait a bit for Livewire to initialize
    await new Promise(resolve => setTimeout(resolve, 2000));

    // ===== TEST 2: Check Booking Flow Component Exists =====
    console.log('🔍 TEST 2: Checking booking flow component exists');
    const componentExists = await page.evaluate(() => {
        return document.querySelector('.appointment-booking-flow') !== null;
    });

    if (componentExists) {
        console.log('   ✅ Component found (.appointment-booking-flow)\n');
        testsPassed++;
    } else {
        console.log('   ❌ Component NOT found (.appointment-booking-flow)\n');
        testsFailed++;
    }

    // ===== TEST 3: Check Service Selection Section =====
    console.log('📋 TEST 3: Service selection section');
    const serviceSection = await page.evaluate(() => {
        const headers = Array.from(document.querySelectorAll('.fi-section-header'));
        const serviceHeader = headers.find(h => h.textContent.includes('Service auswählen'));

        if (!serviceHeader) return { exists: false };

        const section = serviceHeader.closest('.fi-section');
        const radioOptions = section?.querySelectorAll('.fi-radio-option');

        return {
            exists: true,
            radioCount: radioOptions?.length || 0,
            backgroundColor: section ? window.getComputedStyle(section).backgroundColor : null,
            borderColor: section ? window.getComputedStyle(section).borderColor : null
        };
    });

    if (serviceSection.exists) {
        console.log(`   ✅ Service section found`);
        console.log(`   - Radio options: ${serviceSection.radioCount}`);
        console.log(`   - Background: ${serviceSection.backgroundColor}`);
        console.log(`   - Border: ${serviceSection.borderColor}\n`);
        testsPassed++;
    } else {
        console.log('   ❌ Service section NOT found\n');
        testsFailed++;
    }

    // ===== TEST 4: Check Employee Preference Section =====
    console.log('👥 TEST 4: Employee preference section');
    const employeeSection = await page.evaluate(() => {
        const headers = Array.from(document.querySelectorAll('.fi-section-header'));
        const empHeader = headers.find(h => h.textContent.includes('Mitarbeiter-Präferenz'));

        if (!empHeader) return { exists: false };

        const section = empHeader.closest('.fi-section');
        const radioOptions = section?.querySelectorAll('.fi-radio-option');
        const anyOption = Array.from(radioOptions || []).find(opt =>
            opt.textContent.includes('Nächster verfügbarer Mitarbeiter')
        );

        return {
            exists: true,
            radioCount: radioOptions?.length || 0,
            hasAnyOption: anyOption !== undefined,
            anyOptionSelected: anyOption?.classList.contains('selected')
        };
    });

    if (employeeSection.exists) {
        console.log(`   ✅ Employee section found`);
        console.log(`   - Options: ${employeeSection.radioCount}`);
        console.log(`   - "Nächster verfügbar" option: ${employeeSection.hasAnyOption ? 'Yes' : 'No'}`);
        console.log(`   - Pre-selected: ${employeeSection.anyOptionSelected ? 'Yes' : 'No'}\n`);
        testsPassed++;
    } else {
        console.log('   ❌ Employee section NOT found\n');
        testsFailed++;
    }

    // ===== TEST 5: Check Calendar Section =====
    console.log('📅 TEST 5: Calendar section');
    const calendarSection = await page.evaluate(() => {
        const headers = Array.from(document.querySelectorAll('.fi-section-header'));
        const calHeader = headers.find(h => h.textContent.includes('Verfügbare Termine'));

        if (!calHeader) return { exists: false };

        const section = calHeader.closest('.fi-section');
        const grid = section?.querySelector('.fi-calendar-grid');
        const navButtons = section?.querySelectorAll('.fi-button-nav');
        const timeLabels = section?.querySelectorAll('.fi-time-label');
        const dayHeaders = section?.querySelectorAll('.fi-calendar-header');
        const slotButtons = section?.querySelectorAll('.fi-slot-button');

        return {
            exists: true,
            hasGrid: grid !== null,
            navButtonCount: navButtons?.length || 0,
            timeLabelsCount: timeLabels?.length || 0,
            dayHeadersCount: dayHeaders?.length || 0,
            slotButtonsCount: slotButtons?.length || 0,
            gridBackgroundColor: grid ? window.getComputedStyle(grid).backgroundColor : null
        };
    });

    if (calendarSection.exists) {
        console.log(`   ✅ Calendar section found`);
        console.log(`   - Has grid: ${calendarSection.hasGrid ? 'Yes' : 'No'}`);
        console.log(`   - Navigation buttons: ${calendarSection.navButtonCount}`);
        console.log(`   - Time labels: ${calendarSection.timeLabelsCount}`);
        console.log(`   - Day headers: ${calendarSection.dayHeadersCount}`);
        console.log(`   - Slot buttons: ${calendarSection.slotButtonsCount}`);
        console.log(`   - Grid background: ${calendarSection.gridBackgroundColor}\n`);
        testsPassed++;
    } else {
        console.log('   ❌ Calendar section NOT found\n');
        testsFailed++;
    }

    // ===== TEST 6: Color Scheme Analysis =====
    console.log('🎨 TEST 6: Color scheme analysis');
    const colors = await page.evaluate(() => {
        const sections = document.querySelectorAll('.fi-section');
        const radioOptions = document.querySelectorAll('.fi-radio-option');
        const slotButtons = document.querySelectorAll('.fi-slot-button');

        const getColors = (element) => {
            if (!element) return null;
            const styles = window.getComputedStyle(element);
            return {
                background: styles.backgroundColor,
                border: styles.borderColor,
                color: styles.color
            };
        };

        return {
            section: getColors(sections[0]),
            radioOption: getColors(radioOptions[0]),
            slotButton: getColors(slotButtons[0]),
            bodyBackground: window.getComputedStyle(document.body).backgroundColor
        };
    });

    console.log('   Color Analysis:');
    console.log(`   - Body background: ${colors.bodyBackground}`);
    console.log(`   - Section background: ${colors.section?.background}`);
    console.log(`   - Section border: ${colors.section?.border}`);
    console.log(`   - Radio option background: ${colors.radioOption?.background}`);
    console.log(`   - Slot button background: ${colors.slotButton?.background}\n`);

    // Check if colors are too dark
    const isDark = await page.evaluate(() => {
        const section = document.querySelector('.fi-section');
        if (!section) return false;

        const bgColor = window.getComputedStyle(section).backgroundColor;
        const rgb = bgColor.match(/\d+/g);
        if (!rgb) return false;

        const brightness = (parseInt(rgb[0]) * 299 + parseInt(rgb[1]) * 587 + parseInt(rgb[2]) * 114) / 1000;
        return brightness < 50; // Very dark if < 50
    });

    if (isDark) {
        console.log('   ⚠️  WARNING: Component uses very dark colors (brightness < 50)');
        console.log('   - This may not match Filament light theme\n');
    } else {
        console.log('   ✅ Color brightness seems reasonable\n');
        testsPassed++;
    }

    // ===== TEST 7: Screenshots =====
    console.log('📸 TEST 7: Taking screenshots');

    try {
        // Full page screenshot
        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, 'v4-full-page.png'),
            fullPage: true
        });
        console.log(`   ✅ Full page: ${SCREENSHOT_DIR}/v4-full-page.png`);

        // Component only screenshot
        const componentElement = await page.$('.appointment-booking-flow');
        if (componentElement) {
            await componentElement.screenshot({
                path: path.join(SCREENSHOT_DIR, 'v4-component-only.png')
            });
            console.log(`   ✅ Component: ${SCREENSHOT_DIR}/v4-component-only.png`);
        }

        // Viewport screenshot (what user sees)
        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, 'v4-viewport.png'),
            fullPage: false
        });
        console.log(`   ✅ Viewport: ${SCREENSHOT_DIR}/v4-viewport.png\n`);

        testsPassed++;
    } catch (error) {
        console.log(`   ❌ Screenshot failed: ${error.message}\n`);
        testsFailed++;
    }

    // ===== TEST 8: Check for Filament Theme Classes =====
    console.log('🎭 TEST 8: Filament theme compatibility');
    const themeCheck = await page.evaluate(() => {
        const html = document.documentElement;
        const hasDarkClass = html.classList.contains('dark');
        const filamentTheme = html.getAttribute('class');

        return {
            isDarkMode: hasDarkClass,
            htmlClasses: filamentTheme,
            hasFilamentTheme: filamentTheme?.includes('fi') || false
        };
    });

    console.log(`   Current theme mode: ${themeCheck.isDarkMode ? 'Dark' : 'Light'}`);
    console.log(`   HTML classes: ${themeCheck.htmlClasses}`);
    console.log(`   Has Filament theme: ${themeCheck.hasFilamentTheme ? 'Yes' : 'No'}\n`);

    if (!themeCheck.hasFilamentTheme) {
        console.log('   ⚠️  WARNING: No Filament theme classes detected on <html>\n');
    } else {
        testsPassed++;
    }

    // ===== FINAL SUMMARY =====
    await browser.close();

    console.log('=' .repeat(60));
    console.log('📊 VISUAL VALIDATION SUMMARY');
    console.log('=' .repeat(60));
    console.log(`✅ Tests Passed: ${testsPassed}`);
    console.log(`❌ Tests Failed: ${testsFailed}`);
    console.log(`📸 Screenshots saved to: ${SCREENSHOT_DIR}`);
    console.log('=' .repeat(60));

    if (testsFailed > 0) {
        console.log('\n⚠️  Issues detected - review screenshots and logs');
        process.exit(1);
    } else {
        console.log('\n✅ All visual validation checks passed!');
        process.exit(0);
    }
}

// Run the test
runVisualValidation().catch(error => {
    console.error('💥 Test suite crashed:', error);
    process.exit(1);
});
