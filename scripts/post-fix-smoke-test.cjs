const puppeteer = require('puppeteer');
const fs = require('fs');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOT_DIR = '/var/www/api-gateway/storage/screenshots/post-fix';

if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function runSmokeTest() {
    console.log('🔍 POST-FIX SMOKE TEST\n');

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        executablePath: '/usr/bin/chromium'
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    const consoleErrors = [];
    const networkErrors = [];

    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    page.on('response', response => {
        if (response.status() >= 400) {
            networkErrors.push(`${response.status()} ${response.url()}`);
        }
    });

    try {
        // Login
        console.log('1. Login page...');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle0', timeout: 15000 });
        await page.screenshot({ path: `${SCREENSHOT_DIR}/1-login.png`, fullPage: true });

        await page.type('input[type="email"]', 'fabian@askproai.de');
        await page.type('input[type="password"]', 'Fabian2024!');
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 })
        ]);

        console.log('   ✅ Login successful\n');

        // Dashboard
        console.log('2. Dashboard...');
        await page.screenshot({ path: `${SCREENSHOT_DIR}/2-dashboard.png`, fullPage: true });
        console.log('   ✅ Dashboard loaded\n');

        // Users page
        console.log('3. Users page...');
        await page.goto(`${BASE_URL}/admin/users`, { waitUntil: 'networkidle0', timeout: 15000 });
        await page.screenshot({ path: `${SCREENSHOT_DIR}/3-users-page.png`, fullPage: true });

        // Count users in table
        const userRows = await page.$$('table tbody tr');
        const userCount = userRows.length;
        console.log(`   Users displayed: ${userCount}`);

        // Get company info from displayed users
        const userEmails = await page.$$eval('table tbody tr', rows =>
            rows.map(row => {
                const emailCell = row.querySelector('td');
                return emailCell ? emailCell.textContent.trim() : '';
            }).filter(email => email.includes('@'))
        );

        console.log(`   User emails visible: ${userEmails.join(', ')}`);

        // Check for cross-company users
        const hasCrossCompanyUser = userEmails.some(email =>
            email.includes('admin@askproai.de') // Company B user
        );

        if (hasCrossCompanyUser) {
            console.log('   ❌ FAIL: Cross-company user visible in UI\n');
        } else {
            console.log('   ✅ PASS: Only own company users visible\n');
        }

        // Summary
        console.log('═'.repeat(60));
        console.log('📊 SMOKE TEST RESULTS');
        console.log('═'.repeat(60));
        console.log(`Users displayed: ${userCount}`);
        console.log(`Console errors: ${consoleErrors.length}`);
        console.log(`Network errors: ${networkErrors.length}`);
        console.log(`Cross-company leak: ${hasCrossCompanyUser ? 'YES ❌' : 'NO ✅'}`);
        console.log(`Screenshots: ${SCREENSHOT_DIR}`);

        if (consoleErrors.length > 0) {
            console.log('\n⚠️ Console Errors:');
            consoleErrors.slice(0, 5).forEach(err => console.log(`  - ${err}`));
        }

        if (networkErrors.length > 0) {
            console.log('\n⚠️ Network Errors:');
            networkErrors.slice(0, 5).forEach(err => console.log(`  - ${err}`));
        }

        console.log('\n' + '═'.repeat(60));

        const testPassed = !hasCrossCompanyUser && consoleErrors.length === 0;

        if (testPassed) {
            console.log('✅ VERDICT: SMOKE TEST PASSED');
            process.exit(0);
        } else {
            console.log('❌ VERDICT: SMOKE TEST FAILED');
            process.exit(1);
        }

    } catch (error) {
        console.error('❌ ERROR:', error.message);
        await page.screenshot({ path: `${SCREENSHOT_DIR}/error.png`, fullPage: true });
        process.exit(1);
    } finally {
        await browser.close();
    }
}

runSmokeTest().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
