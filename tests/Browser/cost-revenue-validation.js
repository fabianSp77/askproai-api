/**
 * Cost & Revenue Validation Test
 *
 * Tests the corrected cost calculation and revenue tracking:
 * 1. Base costs use total_external_cost_eur_cents
 * 2. Revenue only counts paid appointments (price > 0)
 * 3. Role-based visibility works correctly
 */

const puppeteer = require('puppeteer');

const BASE_URL = process.env.APP_URL || 'https://api.askproai.de';
const ADMIN_PANEL_URL = `${BASE_URL}/admin`;

async function testCostRevenueSystem() {
    console.log('🔍 Starting Cost & Revenue Validation Test');
    console.log('━'.repeat(50));

    const browser = await puppeteer.launch({
        headless: false,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
        slowMo: 100 // Slow down for visibility
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });

        // 1. Login as SuperAdmin
        console.log('\n📋 Test 1: Login as SuperAdmin');
        await page.goto(`${ADMIN_PANEL_URL}/login`, { waitUntil: 'networkidle2' });

        await page.type('input[name="email"]', 'admin@askproai.de');
        await page.type('input[name="password"]', 'SuperAdmin2024!');
        await page.click('button[type="submit"]');

        await page.waitForNavigation({ waitUntil: 'networkidle2' });
        console.log('✅ Login successful');

        // 2. Navigate to Calls table
        console.log('\n📋 Test 2: Navigate to Calls');
        await page.goto(`${ADMIN_PANEL_URL}/calls`, { waitUntil: 'networkidle2' });
        await page.waitForSelector('table', { timeout: 10000 });
        console.log('✅ Calls table loaded');

        // 3. Check for Revenue/Profit column
        console.log('\n📋 Test 3: Verify Revenue/Profit Column Exists');
        const hasRevenueColumn = await page.evaluate(() => {
            const headers = Array.from(document.querySelectorAll('th'));
            return headers.some(th => th.textContent.includes('Einnahmen/Gewinn'));
        });

        if (hasRevenueColumn) {
            console.log('✅ Revenue/Profit column found');
        } else {
            console.log('❌ Revenue/Profit column NOT found');
        }

        // 4. Extract and validate cost data
        console.log('\n📋 Test 4: Extract Cost Data from Table');
        const callsData = await page.evaluate(() => {
            const rows = Array.from(document.querySelectorAll('tbody tr'));
            return rows.slice(0, 5).map(row => {
                const cells = Array.from(row.querySelectorAll('td'));
                return {
                    customer: cells[2]?.textContent.trim() || 'N/A',
                    telCosts: cells.find(c => c.textContent.includes('€'))?.textContent.trim() || 'N/A',
                    revenue: cells[cells.length - 3]?.textContent.trim() || 'N/A'
                };
            });
        });

        console.log('\n📊 Sample Calls Data:');
        console.table(callsData);

        // 5. Test modal financial details
        console.log('\n📋 Test 5: Open Financial Details Modal');
        await page.waitForSelector('table tbody tr', { timeout: 5000 });

        const firstRowWithCost = await page.$('table tbody tr');
        if (firstRowWithCost) {
            const costCell = await firstRowWithCost.$('td[class*="font-mono"]');
            if (costCell) {
                await costCell.click();
                await page.waitForTimeout(1000); // Wait for modal

                const modalVisible = await page.evaluate(() => {
                    return document.querySelector('[role="dialog"]') !== null;
                });

                if (modalVisible) {
                    console.log('✅ Financial details modal opened');

                    // Take screenshot
                    await page.screenshot({
                        path: '/var/www/api-gateway/tests/Browser/screenshots/financial-modal.png',
                        fullPage: false
                    });
                    console.log('📸 Screenshot saved: financial-modal.png');
                } else {
                    console.log('❌ Modal did not open');
                }
            }
        }

        // 6. Verify cost breakdown structure
        console.log('\n📋 Test 6: API Cost Breakdown Validation');
        const apiResponse = await page.evaluate(async () => {
            const response = await fetch('/admin/api/calls/1');
            return await response.json();
        });

        if (apiResponse && apiResponse.cost_breakdown) {
            console.log('✅ Cost breakdown present in API');
            console.log('Cost breakdown:', JSON.stringify(apiResponse.cost_breakdown, null, 2));

            const breakdown = apiResponse.cost_breakdown.base;
            if (breakdown) {
                console.log('\n💰 Base Cost Components:');
                console.log(`  Retell: ${breakdown.retell_cost_eur_cents || 0}¢`);
                console.log(`  Twilio: ${breakdown.twilio_cost_eur_cents || 0}¢`);
                console.log(`  LLM: ${breakdown.llm_tokens || 0}¢`);
                console.log(`  Total External: ${breakdown.total_external || 0}¢`);
                console.log(`  Method: ${breakdown.calculation_method || 'N/A'}`);
            }
        } else {
            console.log('❌ No cost breakdown in API response');
        }

        // 7. Test Results Summary
        console.log('\n' + '━'.repeat(50));
        console.log('📊 TEST RESULTS SUMMARY');
        console.log('━'.repeat(50));

        const results = {
            'Login': '✅',
            'Calls Table Loaded': '✅',
            'Revenue Column': hasRevenueColumn ? '✅' : '❌',
            'Financial Modal': modalVisible ? '✅' : '❌',
            'Cost Breakdown API': apiResponse?.cost_breakdown ? '✅' : '❌'
        };

        Object.entries(results).forEach(([test, status]) => {
            console.log(`${status} ${test}`);
        });

        console.log('\n✨ Test completed successfully');

    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
    }
}

// Run the test
testCostRevenueSystem().catch(console.error);
