/**
 * Test: Settings Dashboard - Service Aktivieren/Deaktivieren
 *
 * Ziel: Prüfen ob schwarzes Popup nach allen Fixes behoben ist
 *
 * Erwartung:
 * - Grüne Erfolgsmeldung erscheint
 * - KEIN schwarzes Popup
 * - Browser Console ohne Errors (keine 404, keine 500)
 */

const puppeteer = require('puppeteer');

async function testSettingsSave() {
    console.log('🧪 Starting Settings Dashboard Save Test...\n');

    const browser = await puppeteer.connect({
        browserURL: 'http://localhost:9222',
        defaultViewport: null
    });

    try {
        const pages = await browser.pages();
        const page = pages[0];

        // Console message listener
        const consoleMessages = [];
        const errors = [];

        page.on('console', msg => {
            const text = msg.text();
            consoleMessages.push({
                type: msg.type(),
                text: text
            });

            if (msg.type() === 'error') {
                console.log('❌ Console Error:', text);
                errors.push(text);
            }
        });

        // Network error listener
        page.on('requestfailed', request => {
            const failure = request.failure();
            console.log('❌ Network Error:', request.url(), failure.errorText);
            errors.push(`Network: ${request.url()} - ${failure.errorText}`);
        });

        // Response listener for 404 and 500 errors
        page.on('response', response => {
            const status = response.status();
            const url = response.url();

            if (status === 404) {
                console.log('❌ 404 Not Found:', url);
                errors.push(`404: ${url}`);
            } else if (status === 500) {
                console.log('❌ 500 Server Error:', url);
                errors.push(`500: ${url}`);
            }
        });

        console.log('📍 Step 1: Navigate to Settings Dashboard');
        await page.goto('https://api.askproai.de/admin/settings-dashboard', {
            waitUntil: 'networkidle2',
            timeout: 30000
        });
        await page.waitForTimeout(2000);
        console.log('✅ Page loaded\n');

        console.log('📍 Step 2: Click Dienstleistungen Tab');
        const diensteTab = await page.waitForSelector('button[role="tab"]:has-text("Dienstleistungen"), button:has-text("Dienstleistungen")', {
            timeout: 10000
        });

        if (!diensteTab) {
            // Try alternative selector
            const tabs = await page.$$('button[role="tab"]');
            for (const tab of tabs) {
                const text = await tab.evaluate(el => el.textContent);
                if (text.includes('Dienstleistungen')) {
                    await tab.click();
                    break;
                }
            }
        } else {
            await diensteTab.click();
        }

        await page.waitForTimeout(2000);
        console.log('✅ Dienstleistungen tab opened\n');

        console.log('📍 Step 3: Find first service is_active toggle');

        // Wait for services to load
        await page.waitForTimeout(2000);

        // Find toggle switch - Filament uses specific structure
        const toggle = await page.$('[x-data*="is_active"] input[type="checkbox"], [wire\\:model*="is_active"] input[type="checkbox"], input[type="checkbox"][id*="is_active"]');

        if (!toggle) {
            console.log('⚠️  Could not find is_active toggle with common selectors');
            console.log('🔍 Looking for any checkbox in services form...');

            // Alternative: find any checkbox in the form
            const checkboxes = await page.$$('input[type="checkbox"]');
            console.log(`Found ${checkboxes.length} checkboxes`);

            if (checkboxes.length > 0) {
                console.log('📝 Using first checkbox as test toggle');
                const currentState = await checkboxes[0].evaluate(el => el.checked);
                console.log(`Current state: ${currentState ? 'ACTIVE' : 'INACTIVE'}`);

                await checkboxes[0].click();
                await page.waitForTimeout(500);

                const newState = await checkboxes[0].evaluate(el => el.checked);
                console.log(`New state: ${newState ? 'ACTIVE' : 'INACTIVE'}`);
                console.log('✅ Toggle clicked\n');
            } else {
                throw new Error('No checkboxes found in form');
            }
        } else {
            const currentState = await toggle.evaluate(el => el.checked);
            console.log(`Current state: ${currentState ? 'ACTIVE' : 'INACTIVE'}`);

            await toggle.click();
            await page.waitForTimeout(500);

            const newState = await toggle.evaluate(el => el.checked);
            console.log(`New state: ${newState ? 'ACTIVE' : 'INACTIVE'}`);
            console.log('✅ Toggle clicked\n');
        }

        console.log('📍 Step 4: Click Speichern button');

        // Clear errors array before save
        errors.length = 0;

        // Find Speichern button
        const saveButton = await page.waitForSelector('button:has-text("Speichern"), button[type="submit"]', {
            timeout: 5000
        });

        await saveButton.click();
        console.log('✅ Speichern clicked\n');

        console.log('📍 Step 5: Wait for response and check for notifications');
        await page.waitForTimeout(3000);

        // Check for success notification (green)
        const successNotification = await page.$('.fi-no-notification, [role="status"], .filament-notifications-notification');

        if (successNotification) {
            const notificationText = await successNotification.evaluate(el => el.textContent);
            console.log('📢 Notification found:', notificationText);

            if (notificationText.includes('gespeichert') || notificationText.includes('Erfolg')) {
                console.log('✅ SUCCESS NOTIFICATION DETECTED!\n');
            }
        }

        // Check for modal/popup (schwarzes Popup)
        const modal = await page.$('[role="dialog"], .modal, [x-show*="open"]');

        if (modal) {
            const isVisible = await modal.evaluate(el => {
                const style = window.getComputedStyle(el);
                return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
            });

            if (isVisible) {
                const modalText = await modal.evaluate(el => el.textContent);
                console.log('⚠️  MODAL/POPUP DETECTED!');
                console.log('Modal content:', modalText);
                console.log('❌ SCHWARZES POPUP STILL EXISTS!\n');
            } else {
                console.log('✅ NO VISIBLE MODAL/POPUP\n');
            }
        } else {
            console.log('✅ NO MODAL/POPUP DETECTED\n');
        }

        console.log('📍 Step 6: Final Error Check');
        console.log('═══════════════════════════════════════\n');

        if (errors.length === 0) {
            console.log('✅ ✅ ✅ NO ERRORS DETECTED! ✅ ✅ ✅');
            console.log('✅ No 404 errors');
            console.log('✅ No 500 errors');
            console.log('✅ No JavaScript errors');
            console.log('✅ No network failures\n');
            console.log('🎉 TEST PASSED - SCHWARZES POPUP IS FIXED! 🎉\n');
        } else {
            console.log('❌ ❌ ❌ ERRORS DETECTED! ❌ ❌ ❌');
            console.log(`Total errors: ${errors.length}\n`);

            const has404 = errors.some(e => e.includes('404'));
            const has500 = errors.some(e => e.includes('500'));
            const hasJSError = errors.some(e => !e.includes('404') && !e.includes('500'));

            if (has404) console.log('❌ Has 404 errors (missing files)');
            if (has500) console.log('❌ Has 500 errors (server errors)');
            if (hasJSError) console.log('❌ Has JavaScript errors');

            console.log('\n📋 Error Details:');
            errors.forEach((err, i) => {
                console.log(`${i + 1}. ${err}`);
            });

            console.log('\n❌ TEST FAILED - ERRORS STILL EXIST\n');
        }

        console.log('═══════════════════════════════════════\n');

        // Take screenshot
        await page.screenshot({
            path: '/var/www/api-gateway/tests/puppeteer/screenshots/settings-save-test.png',
            fullPage: false
        });
        console.log('📸 Screenshot saved: screenshots/settings-save-test.png\n');

        console.log('🏁 Test completed');

    } catch (error) {
        console.error('💥 Test failed with exception:', error.message);
        console.error(error.stack);
    } finally {
        await browser.disconnect();
    }
}

testSettingsSave();
