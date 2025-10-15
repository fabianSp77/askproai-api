/**
 * V4 Booking Flow - Direct HTTP Test (No Login)
 *
 * Simple test to check if page responds without authentication issues
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOT_DIR = '/var/www/api-gateway/tests/puppeteer/screenshots';

if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function runDirectTest() {
    console.log('🔍 Direct HTTP Test - No Login\n');

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Test 1: Direct access
        console.log('TEST 1: Accessing /admin/appointments/create');
        const response = await page.goto(`${BASE_URL}/admin/appointments/create`, {
            waitUntil: 'domcontentloaded',
            timeout: 15000
        });

        const status = response.status();
        const url = page.url();

        console.log(`   Status: ${status}`);
        console.log(`   Final URL: ${url}`);

        await page.screenshot({
            path: path.join(SCREENSHOT_DIR, 'direct-test-page.png'),
            fullPage: true
        });

        if (status === 200) {
            console.log('   ✅ Page accessible (200 OK)');

            // Check page content
            const pageCheck = await page.evaluate(() => {
                const body = document.body.innerText;
                return {
                    hasError: body.includes('Exception') || body.includes('Error'),
                    hasForm: document.querySelector('form') !== null,
                    bodyLength: body.length,
                    title: document.title
                };
            });

            console.log(`   Title: ${pageCheck.title}`);
            console.log(`   Has Form: ${pageCheck.hasForm}`);
            console.log(`   Has Error: ${pageCheck.hasError}`);
            console.log(`   Body Length: ${pageCheck.bodyLength} chars`);

            if (pageCheck.hasError) {
                console.log('   ❌ Error detected on page');
                process.exit(1);
            }

        } else if (status === 302 || url.includes('/login')) {
            console.log('   ℹ️  Redirected to login (expected for auth-required page)');
            console.log('   ✅ This is NORMAL behavior');

        } else if (status === 500) {
            console.log('   ❌ 500 INTERNAL SERVER ERROR!');

            const bodyText = await page.evaluate(() => document.body.innerText);
            console.log('\n   Error snippet:');
            console.log(bodyText.substring(0, 500));

            process.exit(1);
        }

        console.log('\n✅ Direct test passed - no 500 errors detected');

    } catch (error) {
        console.error(`❌ Test failed: ${error.message}`);
        process.exit(1);
    } finally {
        await browser.close();
    }
}

runDirectTest();
