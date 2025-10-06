/**
 * Validate Revenue/Profit Column Display
 */

const puppeteer = require('puppeteer');

const BASE_URL = process.env.APP_URL || 'https://api.askproai.de';

async function validateRevenueColumn() {
    console.log('🔍 Validating Revenue/Profit Column Display');
    console.log('━'.repeat(60));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        slowMo: 50
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });

        // Login
        console.log('\n📋 Step 1: Login as SuperAdmin');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2' });

        await page.type('input[name="email"]', 'admin@askproai.de');
        await page.type('input[name="password"]', 'SuperAdmin2024!');
        await page.click('button[type="submit"]');
        await page.waitForNavigation({ waitUntil: 'networkidle2' });
        console.log('✅ Logged in');

        // Navigate to Calls
        console.log('\n📋 Step 2: Navigate to Calls');
        await page.goto(`${BASE_URL}/admin/calls`, { waitUntil: 'networkidle2' });
        await page.waitForSelector('table', { timeout: 10000 });
        console.log('✅ Calls table loaded');

        // Check for Revenue/Profit column header
        console.log('\n📋 Step 3: Check for Revenue/Profit Column');
        const columnExists = await page.evaluate(() => {
            const headers = Array.from(document.querySelectorAll('th'));
            const revenueHeader = headers.find(th =>
                th.textContent.includes('Einnahmen') ||
                th.textContent.includes('Gewinn')
            );
            return {
                exists: !!revenueHeader,
                text: revenueHeader?.textContent.trim() || 'NOT FOUND'
            };
        });

        if (columnExists.exists) {
            console.log(`✅ Column found: "${columnExists.text}"`);
        } else {
            console.log('❌ Column NOT found!');
            console.log('Available headers:');
            const headers = await page.evaluate(() => {
                return Array.from(document.querySelectorAll('th'))
                    .map(th => th.textContent.trim());
            });
            console.table(headers);
        }

        // Extract revenue data from visible rows
        console.log('\n📋 Step 4: Extract Revenue Data');
        const revenueData = await page.evaluate(() => {
            const rows = Array.from(document.querySelectorAll('tbody tr'));
            return rows.slice(0, 5).map((row, index) => {
                const cells = Array.from(row.querySelectorAll('td'));

                // Find revenue column (look for euro symbol or numbers)
                let revenueCell = null;
                for (let i = cells.length - 1; i >= 0; i--) {
                    const text = cells[i].textContent;
                    if (text.includes('€') && (text.includes('+') || text.includes('-') || text.includes(','))) {
                        revenueCell = cells[i];
                        break;
                    }
                }

                return {
                    row: index + 1,
                    revenue: revenueCell?.textContent.trim() || 'N/A',
                    cellCount: cells.length
                };
            });
        });

        console.log('\n📊 Revenue Data (first 5 rows):');
        console.table(revenueData);

        // Take screenshot
        const screenshotPath = '/var/www/api-gateway/tests/Browser/screenshots/revenue-column-' + Date.now() + '.png';
        await page.screenshot({
            path: screenshotPath,
            fullPage: true
        });
        console.log(`\n📸 Screenshot saved: ${screenshotPath}`);

        // Check column toggle status
        console.log('\n📋 Step 5: Check Column Toggle Menu');
        const toggleButton = await page.$('button[title*="Toggle"]');
        if (toggleButton) {
            await toggleButton.click();
            await page.waitForTimeout(500);

            const toggleOptions = await page.evaluate(() => {
                const options = Array.from(document.querySelectorAll('[role="menuitem"]'));
                return options.map(opt => ({
                    text: opt.textContent.trim(),
                    checked: opt.getAttribute('aria-checked') === 'true'
                }));
            });

            console.log('\n📋 Column Toggle Status:');
            console.table(toggleOptions);
        }

        console.log('\n' + '━'.repeat(60));
        console.log('✅ Validation Complete');

    } catch (error) {
        console.error('❌ Error:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
    }
}

validateRevenueColumn().catch(console.error);
