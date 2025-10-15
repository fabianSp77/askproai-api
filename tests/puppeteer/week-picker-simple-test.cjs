/**
 * Week Picker Simple Test - Issue #701
 * Direct navigation test without login (assumes already logged in browser)
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOTS_DIR = path.join(__dirname, 'screenshots', 'week-picker-simple');

// Create screenshots directory
if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

async function runSimpleTest() {
    console.log('\n╔═══════════════════════════════════════════════════════════════════════════╗');
    console.log('║                  WEEK PICKER SIMPLE TEST (Issue #701)                     ║');
    console.log('╚═══════════════════════════════════════════════════════════════════════════╝\n');

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

    // Set viewport to match Issue #701: 1802x1430 @ 66.67% zoom
    await page.setViewport({
        width: 1802,
        height: 1430,
        deviceScaleFactor: 0.6667
    });

    console.log('📊 Viewport: 1802x1430 @ 66.67% zoom (Issue #701 config)');

    try {
        // Navigate directly to the page
        console.log('\n📍 Step 1: Navigate to appointment creation page');
        await page.goto(`${BASE_URL}/admin/appointments/create`, {
            waitUntil: 'networkidle0',
            timeout: 30000
        });

        await new Promise(resolve => setTimeout(resolve, 2000));

        // Take initial screenshot
        await page.screenshot({
            path: path.join(SCREENSHOTS_DIR, '01_initial_load.png'),
            fullPage: true
        });
        console.log('  📸 Screenshot: 01_initial_load.png');

        // Check if logged in
        const loginForm = await page.$('input[name="email"]');
        if (loginForm) {
            console.log('\n❌ NOT LOGGED IN');
            console.log('  Please login manually and run the test again.');
            console.log('  Or use the MCP Puppeteer server that maintains session.');
            await browser.close();
            return;
        }

        console.log('  ✅ Logged in');

        // Check page title
        const title = await page.title();
        console.log(`  Page title: "${title}"`);

        // Check for form
        const form = await page.$('form');
        if (form) {
            console.log('  ✅ Form found');
        } else {
            console.log('  ❌ Form not found');
        }

        // Check for Week Picker elements
        console.log('\n📍 Step 2: Analyze page elements');

        const debugBox = await page.$('#debug-info');
        console.log(`  Debug Box: ${debugBox ? '✅ Found' : '❌ Not found'}`);

        const desktopGrid = await page.$('.hidden.md\\:grid');
        console.log(`  Desktop Grid: ${desktopGrid ? '✅ Found' : '❌ Not found'}`);

        const mobileList = await page.$('.md\\:hidden.space-y-3');
        console.log(`  Mobile List: ${mobileList ? '✅ Found' : '❌ Not found'}`);

        const slotButtons = await page.$$('.slot-button');
        console.log(`  Slot Buttons: ${slotButtons.length} found`);

        // Check for service selector
        const serviceSelect = await page.$('select[name="service_id"]');
        console.log(`  Service Select: ${serviceSelect ? '✅ Found' : '❌ Not found'}`);

        if (serviceSelect) {
            console.log('\n📍 Step 3: Select service to trigger Week Picker');

            // Get available options
            const options = await page.$$eval('select[name="service_id"] option', opts =>
                opts.map(opt => ({ value: opt.value, text: opt.textContent }))
            );
            console.log(`  Available services: ${options.length}`);

            // But first select other required fields
            console.log('\n  Filling required fields first...');

            // Company
            const companySelect = await page.$('select[name="company_id"]');
            if (companySelect) {
                await page.select('select[name="company_id"]', '1');
                console.log('  ✅ Company selected');
                await new Promise(resolve => setTimeout(resolve, 1000));
            }

            // Customer
            const customerSelect = await page.$('select[name="customer_id"]');
            if (customerSelect) {
                await page.select('select[name="customer_id"]', '1');
                console.log('  ✅ Customer selected');
                await new Promise(resolve => setTimeout(resolve, 1000));
            }

            // Now select service
            await page.select('select[name="service_id"]', '47');
            console.log('  ✅ Service 47 selected');
            console.log('  ⏳ Waiting for Week Picker to load (3s)...');
            await new Promise(resolve => setTimeout(resolve, 3000));

            // Take screenshot after service selection
            await page.screenshot({
                path: path.join(SCREENSHOTS_DIR, '02_after_service_selection.png'),
                fullPage: true
            });
            console.log('  📸 Screenshot: 02_after_service_selection.png');

            // Check Week Picker again
            console.log('\n📍 Step 4: Verify Week Picker appeared');

            const debugBoxAfter = await page.$('#debug-info');
            console.log(`  Debug Box: ${debugBoxAfter ? '✅ Visible' : '❌ Not visible'}`);

            if (debugBoxAfter) {
                const debugText = await page.$eval('#debug-info', el => el.textContent);
                console.log(`  Debug content: ${debugText.replace(/\s+/g, ' ').substring(0, 150)}`);
            }

            const desktopGridAfter = await page.$('.hidden.md\\:grid');
            const mobileListAfter = await page.$('.md\\:hidden.space-y-3');
            const slotButtonsAfter = await page.$$('.slot-button');

            console.log(`  Desktop Grid: ${desktopGridAfter ? '✅ Visible' : '❌ Not visible'}`);
            console.log(`  Mobile List: ${mobileListAfter ? '✅ Visible' : '❌ Not visible'}`);
            console.log(`  Slot Buttons: ${slotButtonsAfter.length} found`);

            // Check which view is actually visible (accounting for responsive classes)
            const desktopVisible = await page.evaluate(() => {
                const el = document.querySelector('.hidden.md\\:grid');
                if (!el) return false;
                const style = window.getComputedStyle(el);
                return style.display !== 'none';
            });

            const mobileVisible = await page.evaluate(() => {
                const el = document.querySelector('.md\\:hidden');
                if (!el) return false;
                const style = window.getComputedStyle(el);
                return style.display !== 'none';
            });

            console.log(`\n  📱 Computed Visibility:`);
            console.log(`     Desktop Grid: ${desktopVisible ? '👁️  VISIBLE' : '❌ HIDDEN'}`);
            console.log(`     Mobile List: ${mobileVisible ? '👁️  VISIBLE' : '❌ HIDDEN'}`);

            if (slotButtonsAfter.length > 0) {
                console.log('\n📍 Step 5: Test slot selection');

                // Enable console logging
                page.on('console', msg => {
                    if (msg.text().includes('HYBRID')) {
                        console.log(`  🎯 Browser: ${msg.text()}`);
                    }
                });

                // Click first slot
                await slotButtonsAfter[0].click();
                console.log('  ✅ Clicked first slot');
                await new Promise(resolve => setTimeout(resolve, 2000));

                // Take screenshot
                await page.screenshot({
                    path: path.join(SCREENSHOTS_DIR, '03_after_slot_click.png'),
                    fullPage: true
                });
                console.log('  📸 Screenshot: 03_after_slot_click.png');

                // Check hidden field
                const startsAtValue = await page.$eval('input[name="starts_at"]', el => el.value).catch(() => null);
                console.log(`  Hidden field value: ${startsAtValue || 'NULL'}`);

                if (startsAtValue) {
                    console.log('  ✅ SUCCESS: Hidden field populated!');
                } else {
                    console.log('  ❌ FAILURE: Hidden field still empty!');
                }

                // Check debug box
                const slotStatus = await page.$eval('#slot-status', el => el.textContent).catch(() => 'N/A');
                console.log(`  Debug status: ${slotStatus}`);
            } else {
                console.log('\n  ⚠️  No slots available for testing');
            }
        }

        console.log('\n' + '='.repeat(80));
        console.log('✅ Test completed - Check screenshots in:');
        console.log(`   ${SCREENSHOTS_DIR}`);
        console.log('='.repeat(80) + '\n');

    } catch (error) {
        console.error(`\n❌ Test Error: ${error.message}`);
        try {
            await page.screenshot({
                path: path.join(SCREENSHOTS_DIR, '99_error.png'),
                fullPage: true
            });
            console.log('  📸 Error screenshot saved');
        } catch (e) {
            console.error('  Could not save error screenshot');
        }
    } finally {
        await browser.close();
    }
}

runSimpleTest()
    .then(() => {
        console.log('Test finished');
        process.exit(0);
    })
    .catch(error => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
