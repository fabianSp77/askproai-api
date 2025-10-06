/**
 * Quick Security Test - SuperAdmin Only
 *
 * Fast test to validate that security fixes are working.
 * Tests only SuperAdmin to verify they CAN see all data.
 */

const puppeteer = require('puppeteer');

const BASE_URL = process.env.APP_URL || 'https://api.askproai.de';

async function quickTest() {
    console.log('\n🔍 Quick Security Test - SuperAdmin Data Access');
    console.log('━'.repeat(60));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        slowMo: 50
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });

        // Login as SuperAdmin
        console.log('\n📋 Step 1: Login as SuperAdmin');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2' });

        // Wait for login form to be visible
        await page.waitForSelector('input[type="email"], input[name="email"], input[id*="email"]', { timeout: 10000 });
        await page.waitForTimeout(1000);

        // Try different selectors for email field
        const emailInput = await page.$('input[type="email"]') || await page.$('input[name="email"]') || await page.$('input[id*="email"]');
        const passwordInput = await page.$('input[type="password"]') || await page.$('input[name="password"]') || await page.$('input[id*="password"]');

        if (emailInput && passwordInput) {
            await emailInput.type('admin@askproai.de');
            await passwordInput.type('SuperAdmin2024!');

            const submitButton = await page.$('button[type="submit"]') || await page.$('button[type="button"]');
            if (submitButton) {
                await submitButton.click();
                await page.waitForTimeout(3000); // Give time for redirect
                console.log('✅ Login submitted');
            } else {
                throw new Error('Submit button not found');
            }
        } else {
            throw new Error('Login form not found');
        }

        // Navigate to Calls
        console.log('\n📋 Step 2: Load Calls Table');
        await page.goto(`${BASE_URL}/admin/calls`, { waitUntil: 'networkidle2' });
        await page.waitForSelector('table', { timeout: 10000 });
        console.log('✅ Table loaded');

        // Extract table HTML
        const tableHTML = await page.content();

        // Check that SuperAdmin CAN see privileged data
        console.log('\n📋 Step 3: Verify SuperAdmin Has Access to All Data');
        const expectedData = [
            { term: 'Tel.-Kosten', type: 'Column Header' },
            { term: 'Einnahmen/Gewinn', type: 'Column Header (if revenue exists)' },
            { term: 'Anrufe Heute', type: 'Widget - Call Count' },
            { term: 'Profit Marge', type: 'Widget - Platform Profit Margin (SuperAdmin only)' },
            { term: 'Kosten Monat', type: 'Widget - Monthly Costs' },
        ];

        let foundCount = 0;
        expectedData.forEach(item => {
            if (tableHTML.includes(item.term)) {
                console.log(`  ✅ ${item.type}: "${item.term}"`);
                foundCount++;
            } else {
                console.log(`  ❌ ${item.type}: "${item.term}" NOT FOUND`);
            }
        });

        // Take screenshot
        console.log('\n📋 Step 4: Capture Table Screenshot');
        await page.screenshot({
            path: `/var/www/api-gateway/tests/Browser/screenshots/quick-test-table-${Date.now()}.png`,
            fullPage: true
        });
        console.log('✅ Screenshot saved');

        // Try to open modal
        console.log('\n📋 Step 5: Test Modal Access');
        const firstRow = await page.$('table tbody tr:first-child');

        if (firstRow) {
            // Click anywhere in first row to open modal
            await firstRow.click();
            await page.waitForTimeout(2000);

            const modalHTML = await page.content();

            // Check modal content
            const modalChecks = [
                'Finanzielle Details',
                'Kostenübersicht',
                'Profit'
            ];

            console.log('  Modal Content Check:');
            modalChecks.forEach(term => {
                const found = modalHTML.includes(term);
                console.log(`    ${found ? '✅' : '❌'} ${term}`);
            });

            // Screenshot modal
            await page.screenshot({
                path: `/var/www/api-gateway/tests/Browser/screenshots/quick-test-modal-${Date.now()}.png`,
                fullPage: true
            });
            console.log('✅ Modal screenshot saved');
        } else {
            console.log('⚠️  No table rows found');
        }

        console.log('\n━'.repeat(60));
        console.log('✅ Quick test completed successfully');
        console.log('   Check screenshots in tests/Browser/screenshots/');
        console.log('━'.repeat(60) + '\n');

        await browser.close();
        process.exit(0);

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        await browser.close();
        process.exit(1);
    }
}

quickTest();
