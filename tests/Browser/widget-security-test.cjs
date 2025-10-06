/**
 * Widget Security Test - Role-Based Visibility Validation
 *
 * Tests CallStatsOverview widget security across all user roles:
 * - SuperAdmin: Should see ALL widgets including platform profit/margin
 * - Reseller: Should see basic widgets but NOT platform profit stats
 * - Customer: Should NOT see CallStatsOverview widget at all
 *
 * CRITICAL: Validates fix for VUL-004 (Widget Platform Profit Exposure)
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = process.env.APP_URL || 'https://api.askproai.de';

// Ensure screenshots directory exists
const screenshotsDir = path.join(__dirname, 'screenshots');
if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
}

/**
 * Test configuration for each role
 */
const roleConfigs = {
    superadmin: {
        email: process.env.SUPERADMIN_TEST_EMAIL || 'superadmin-test@askproai.de',
        password: process.env.SUPERADMIN_TEST_PASSWORD || 'Test2024!',
        expectedWidgetVisible: true,
        expectedStats: [
            'Anrufe Heute',
            'Erfolgsquote Heute',
            '⌀ Dauer',
            'Kosten Monat',
            'Profit Marge',
            '⌀ Kosten/Anruf',
            'Conversion Rate'
        ],
        forbiddenTerms: [], // SuperAdmin can see everything
        description: 'SuperAdmin should see ALL widgets including platform profit'
    },
    reseller: {
        email: process.env.RESELLER_TEST_EMAIL || 'reseller-test@askproai.de',
        password: process.env.RESELLER_TEST_PASSWORD || 'Test2024!',
        expectedWidgetVisible: true,
        expectedStats: [
            'Anrufe Heute',
            'Erfolgsquote Heute',
            '⌀ Dauer',
            '⌀ Kosten/Anruf',
            'Conversion Rate'
        ],
        forbiddenTerms: [
            'Profit Marge', // Should NOT see platform profit margin
            'platform_profit', // Database field name
            'total_profit', // Total platform profit
        ],
        description: 'Reseller should see basic widgets but NOT platform profit stats'
    },
    customer: {
        email: process.env.CUSTOMER_TEST_EMAIL || 'customer-test@askproai.de',
        password: process.env.CUSTOMER_TEST_PASSWORD || 'Test2024!',
        expectedWidgetVisible: false,
        expectedStats: [], // Should not see any financial widgets
        forbiddenTerms: [
            'CallStatsOverview',
            'Profit Marge',
            'platform_profit',
            'Kosten Monat'
        ],
        description: 'Customer should NOT see CallStatsOverview widget at all'
    }
};

/**
 * Login helper
 */
async function login(page, email, password) {
    await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2' });
    await page.waitForSelector('input[type="email"], input[name="email"], input[id*="email"]', { timeout: 10000 });
    await page.waitForTimeout(1000);

    const emailInput = await page.$('input[type="email"]') || await page.$('input[name="email"]') || await page.$('input[id*="email"]');
    const passwordInput = await page.$('input[type="password"]') || await page.$('input[name="password"]') || await page.$('input[id*="password"]');

    if (emailInput && passwordInput) {
        await emailInput.type(email);
        await passwordInput.type(password);

        const submitButton = await page.$('button[type="submit"]') || await page.$('button[type="button"]');
        if (submitButton) {
            await submitButton.click();
            await page.waitForTimeout(3000); // Wait for redirect
            return true;
        }
    }
    return false;
}

/**
 * Test widget visibility for a specific role
 */
async function testRole(browser, roleName, config) {
    console.log(`\n${'━'.repeat(60)}`);
    console.log(`🔍 Testing Role: ${roleName.toUpperCase()}`);
    console.log(`   ${config.description}`);
    console.log(`${'━'.repeat(60)}`);

    // Skip if credentials explicitly set to null
    if (!config.email || !config.password) {
        console.log(`⚠️  Skipping ${roleName}: Test credentials not configured`);
        return { skipped: true };
    }

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Step 1: Login
        console.log('\n📋 Step 1: Login');
        const loginSuccess = await login(page, config.email, config.password);
        if (!loginSuccess) {
            throw new Error('Login failed');
        }
        console.log('✅ Login successful');

        // Step 2: Navigate to calls page
        console.log('\n📋 Step 2: Navigate to /admin/calls');
        await page.goto(`${BASE_URL}/admin/calls`, { waitUntil: 'networkidle2' });
        await page.waitForTimeout(2000); // Wait for widgets to load
        console.log('✅ Page loaded');

        // Step 3: Extract page HTML
        const pageHTML = await page.content();

        // Step 4: Check widget visibility
        console.log('\n📋 Step 3: Validate Widget Visibility');
        const widgetExists = pageHTML.includes('CallStatsOverview') ||
                           pageHTML.includes('Anrufe Heute') ||
                           pageHTML.includes('Erfolgsquote Heute');

        if (config.expectedWidgetVisible) {
            if (widgetExists) {
                console.log('✅ Widget IS visible (as expected)');
            } else {
                console.log('❌ Widget NOT visible (UNEXPECTED - should be visible!)');
            }
        } else {
            if (!widgetExists) {
                console.log('✅ Widget NOT visible (as expected for customers)');
            } else {
                console.log('❌ Widget IS visible (SECURITY ISSUE - should be hidden!)');
            }
        }

        // Step 5: Validate expected stats
        console.log('\n📋 Step 4: Validate Expected Stats');
        const foundStats = [];
        const missingStats = [];

        config.expectedStats.forEach(statName => {
            if (pageHTML.includes(statName)) {
                console.log(`  ✅ "${statName}" found`);
                foundStats.push(statName);
            } else {
                console.log(`  ❌ "${statName}" NOT found`);
                missingStats.push(statName);
            }
        });

        // Step 6: Check for forbidden data exposure
        console.log('\n📋 Step 5: Check for Forbidden Data Exposure');
        const exposedData = [];

        config.forbiddenTerms.forEach(term => {
            if (pageHTML.includes(term)) {
                console.log(`  🚨 SECURITY ISSUE: "${term}" found in HTML (should NOT be visible!)`);
                exposedData.push(term);
            } else {
                console.log(`  ✅ "${term}" NOT found (correctly hidden)`);
            }
        });

        // Step 7: Screenshot
        console.log('\n📋 Step 6: Capture Screenshot');
        const screenshotPath = path.join(screenshotsDir, `widget-test-${roleName}-${Date.now()}.png`);
        await page.screenshot({
            path: screenshotPath,
            fullPage: true
        });
        console.log(`✅ Screenshot saved: ${screenshotPath}`);

        // Summary
        console.log(`\n${'─'.repeat(60)}`);
        console.log(`📊 Summary for ${roleName.toUpperCase()}`);
        console.log(`${'─'.repeat(60)}`);
        console.log(`Widget Visibility: ${widgetExists ? '✅ Visible' : '❌ Hidden'}`);
        console.log(`Expected Stats Found: ${foundStats.length}/${config.expectedStats.length}`);
        console.log(`Security Issues: ${exposedData.length > 0 ? '🚨 ' + exposedData.length + ' FOUND' : '✅ None'}`);

        if (exposedData.length > 0) {
            console.log(`\n🚨 CRITICAL: Exposed data: ${exposedData.join(', ')}`);
        }

        await page.close();

        return {
            role: roleName,
            widgetVisible: widgetExists,
            expectedVisible: config.expectedWidgetVisible,
            foundStats,
            missingStats,
            exposedData,
            screenshotPath,
            passed: widgetExists === config.expectedWidgetVisible &&
                   missingStats.length === 0 &&
                   exposedData.length === 0
        };

    } catch (error) {
        console.error(`\n❌ ERROR testing ${roleName}:`, error.message);

        // Screenshot on error
        try {
            const errorScreenshotPath = path.join(screenshotsDir, `widget-test-${roleName}-ERROR-${Date.now()}.png`);
            await page.screenshot({ path: errorScreenshotPath, fullPage: true });
            console.log(`📸 Error screenshot: ${errorScreenshotPath}`);
        } catch (screenshotError) {
            // Ignore screenshot errors
        }

        await page.close();

        return {
            role: roleName,
            error: error.message,
            passed: false
        };
    }
}

/**
 * Main test execution
 */
async function runWidgetSecurityTests() {
    console.log('\n🔐 Widget Security Test Suite');
    console.log('━'.repeat(60));
    console.log('Testing CallStatsOverview widget visibility across roles');
    console.log('━'.repeat(60));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        slowMo: 50
    });

    try {
        const results = [];

        // Test each role
        for (const [roleName, config] of Object.entries(roleConfigs)) {
            const result = await testRole(browser, roleName, config);
            results.push(result);
        }

        // Final Summary
        console.log('\n\n' + '═'.repeat(60));
        console.log('📊 FINAL TEST SUMMARY');
        console.log('═'.repeat(60));

        let totalTests = 0;
        let passedTests = 0;
        let skippedTests = 0;
        let securityIssues = 0;

        results.forEach(result => {
            if (result.skipped) {
                skippedTests++;
                console.log(`⚠️  ${result.role ? result.role.toUpperCase() : 'UNKNOWN'}: SKIPPED (no credentials)`);
            } else {
                totalTests++;
                const status = result.passed ? '✅ PASSED' : '❌ FAILED';
                console.log(`${status}: ${result.role.toUpperCase()}`);

                if (result.passed) {
                    passedTests++;
                } else {
                    if (result.exposedData && result.exposedData.length > 0) {
                        securityIssues += result.exposedData.length;
                    }
                }
            }
        });

        console.log('─'.repeat(60));
        console.log(`Total Tests: ${totalTests}`);
        console.log(`Passed: ${passedTests}`);
        console.log(`Failed: ${totalTests - passedTests}`);
        console.log(`Skipped: ${skippedTests}`);
        console.log(`Security Issues: ${securityIssues > 0 ? '🚨 ' + securityIssues : '✅ 0'}`);
        console.log('═'.repeat(60));

        if (securityIssues > 0) {
            console.log('\n🚨 CRITICAL: Security vulnerabilities detected!');
            console.log('   Review screenshots and fix exposed data immediately.');
        } else if (totalTests > 0 && passedTests === totalTests) {
            console.log('\n✅ All security tests PASSED!');
            console.log('   Widget role-based visibility is working correctly.');
        }

        console.log(`\n📁 Screenshots saved in: ${screenshotsDir}`);
        console.log('═'.repeat(60) + '\n');

        await browser.close();

        // Exit with appropriate code
        if (securityIssues > 0 || (totalTests > 0 && passedTests < totalTests)) {
            process.exit(1);
        } else {
            process.exit(0);
        }

    } catch (error) {
        console.error('\n❌ FATAL ERROR:', error.message);
        await browser.close();
        process.exit(1);
    }
}

// Run tests
runWidgetSecurityTests();
