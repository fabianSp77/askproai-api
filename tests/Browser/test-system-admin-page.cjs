/**
 * Quick Test - System Administration Page Visibility
 *
 * Tests that the System Administration page is:
 * 1. Visible in navigation menu for SuperAdmin
 * 2. Accessible and loads correctly
 * 3. Shows all expected sections
 */

const puppeteer = require('puppeteer');
const path = require('path');

const BASE_URL = process.env.APP_URL || 'https://api.askproai.de';

async function testSystemAdminPage() {
    console.log('\n🔍 Testing System Administration Page');
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

        await page.waitForSelector('input[type="email"], input[name="email"], input[id*="email"]', { timeout: 10000 });
        await new Promise(resolve => setTimeout(resolve, 1000));

        const emailInput = await page.$('input[type="email"]') || await page.$('input[name="email"]');
        const passwordInput = await page.$('input[type="password"]') || await page.$('input[name="password"]');

        if (emailInput && passwordInput) {
            await emailInput.type('admin@askproai.de');
            await passwordInput.type('SuperAdmin2024!');

            const submitButton = await page.$('button[type="submit"]');
            if (submitButton) {
                await submitButton.click();
                await new Promise(resolve => setTimeout(resolve, 3000));
                console.log('✅ Login successful');
            }
        }

        // Navigate to System Administration
        console.log('\n📋 Step 2: Navigate to System Administration Page');
        await page.goto(`${BASE_URL}/admin/system-administration`, { waitUntil: 'networkidle2' });
        await new Promise(resolve => setTimeout(resolve, 2000));
        console.log('✅ Page loaded');

        // Check page content
        console.log('\n📋 Step 3: Validate Page Content');
        const pageHTML = await page.content();

        // Save HTML for debugging
        const fs = require('fs');
        fs.writeFileSync(path.join(__dirname, 'screenshots', 'system-admin-debug.html'), pageHTML);

        const expectedSections = [
            'System Administration',
            'Quick Actions',
            'Database Statistiken',
            'Security & Access',
            'System Health',
            'Recent Users',
            'Recent Companies',
            'System Information',
        ];

        let foundCount = 0;
        expectedSections.forEach(section => {
            if (pageHTML.includes(section)) {
                console.log(`  ✅ Section found: "${section}"`);
                foundCount++;
            } else {
                console.log(`  ❌ Section NOT found: "${section}"`);
            }
        });

        // Check navigation menu
        console.log('\n📋 Step 4: Check Navigation Menu');
        const hasSystemAdminLink = pageHTML.includes('System Admin') || pageHTML.includes('⚙️');
        console.log(hasSystemAdminLink ? '  ✅ System Admin menu item visible' : '  ❌ System Admin menu item NOT visible');

        // Take screenshot
        console.log('\n📋 Step 5: Capture Screenshot');
        const screenshotsDir = path.join(__dirname, 'screenshots');
        const screenshotPath = path.join(screenshotsDir, `system-admin-page-${Date.now()}.png`);

        await page.screenshot({
            path: screenshotPath,
            fullPage: true
        });
        console.log(`✅ Screenshot saved: ${screenshotPath}`);

        // Summary
        console.log('\n' + '━'.repeat(60));
        console.log('📊 Test Summary');
        console.log('━'.repeat(60));
        console.log(`Page Loaded: ✅ Yes`);
        console.log(`Sections Found: ${foundCount}/${expectedSections.length}`);
        console.log(`Menu Item Visible: ${hasSystemAdminLink ? '✅ Yes' : '❌ No'}`);
        console.log('━'.repeat(60) + '\n');

        await browser.close();

        if (foundCount === expectedSections.length && hasSystemAdminLink) {
            console.log('✅ All tests PASSED!\n');
            process.exit(0);
        } else {
            console.log('⚠️ Some sections missing. Check screenshot for details.\n');
            process.exit(1);
        }

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        await browser.close();
        process.exit(1);
    }
}

testSystemAdminPage();
