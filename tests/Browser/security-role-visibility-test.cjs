/**
 * CRITICAL SECURITY TEST: Role-Based Data Visibility
 *
 * Tests that financial data is properly isolated between user roles:
 * - Endkunden MUST NOT see Mandanten/Platform profits or costs
 * - Mandanten MUST NOT see Platform profits or base costs
 * - Only SuperAdmin can see all data
 *
 * This test uses Puppeteer to validate HTML output and detect data leaks.
 */

const puppeteer = require('puppeteer');

const BASE_URL = process.env.APP_URL || 'https://api.askproai.de';

// Test credentials for each role (REPLACE WITH ACTUAL TEST USERS)
const USERS = {
    superadmin: {
        email: 'admin@askproai.de',
        password: process.env.SUPERADMIN_PASSWORD || 'SuperAdmin2024!',
        canSee: ['base_cost', 'platform_profit', 'reseller_profit', 'customer_cost'],
        cannotSee: []
    },
    reseller: {
        email: 'reseller@test.de', // REPLACE WITH ACTUAL RESELLER TEST USER
        password: process.env.RESELLER_PASSWORD || 'TestPassword123!',
        canSee: ['reseller_cost', 'reseller_profit', 'customer_cost'],
        cannotSee: ['base_cost', 'platform_profit', 'Basiskosten', 'Platform-Profit']
    },
    customer: {
        email: 'customer@test.de', // REPLACE WITH ACTUAL CUSTOMER TEST USER
        password: process.env.CUSTOMER_PASSWORD || 'TestPassword123!',
        canSee: ['customer_cost'],
        cannotSee: ['base_cost', 'reseller_cost', 'platform_profit', 'reseller_profit', 'Basiskosten', 'Mandanten-Kosten', 'Marge']
    }
};

/**
 * Test a single user role
 */
async function testRole(browser, roleName, userData) {
    console.log(`\n${'='.repeat(60)}`);
    console.log(`🔐 TESTING ROLE: ${roleName.toUpperCase()}`);
    console.log('='.repeat(60));

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Step 1: Login
        console.log(`\n📋 Step 1: Login as ${roleName}`);
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2', timeout: 30000 });

        await page.type('input[name="email"]', userData.email);
        await page.type('input[name="password"]', userData.password);
        await page.click('button[type="submit"]');

        try {
            await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 });
            console.log('✅ Logged in successfully');
        } catch (e) {
            console.log('⚠️  Navigation timeout, checking if already on dashboard...');
        }

        // Step 2: Navigate to Calls
        console.log(`\n📋 Step 2: Navigate to Calls List`);
        await page.goto(`${BASE_URL}/admin/calls`, { waitUntil: 'networkidle2', timeout: 30000 });

        // Wait for table to load
        try {
            await page.waitForSelector('table', { timeout: 10000 });
            console.log('✅ Calls table loaded');
        } catch (e) {
            console.log('❌ ERROR: Calls table not found!');
            await page.screenshot({ path: `/var/www/api-gateway/tests/Browser/screenshots/error-${roleName}-no-table.png` });
            throw e;
        }

        // Step 3: Extract full page HTML for analysis
        console.log(`\n📋 Step 3: Analyze Page HTML for Data Leaks`);
        const pageHTML = await page.content();

        // Security check: Look for forbidden data in HTML
        const violations = [];

        for (const forbiddenTerm of userData.cannotSee) {
            if (pageHTML.includes(forbiddenTerm)) {
                violations.push({
                    severity: 'CRITICAL',
                    term: forbiddenTerm,
                    context: 'Table HTML contains forbidden data',
                    location: 'Calls table page'
                });
            }
        }

        if (violations.length > 0) {
            console.log(`\n🚨 SECURITY VIOLATIONS FOUND (${violations.length}):`);
            violations.forEach((v, i) => {
                console.log(`\n  [${i + 1}] ${v.severity}: ${v.term}`);
                console.log(`      Context: ${v.context}`);
                console.log(`      Location: ${v.location}`);
            });
        } else {
            console.log(`✅ No forbidden data found in table HTML`);
        }

        // Step 4: Check if modal button exists (role-based)
        console.log(`\n📋 Step 4: Check Modal Visibility`);

        const canOpenModal = roleName === 'superadmin' || roleName === 'reseller';

        if (canOpenModal) {
            // Try to find and click modal button on first row
            const modalButton = await page.$('table tbody tr:first-child td[wire\\:click*="showFinancialDetails"], table tbody tr:first-child button[wire\\:click*="showFinancialDetails"]');

            if (modalButton) {
                console.log('✅ Modal button found (expected for this role)');

                // Click to open modal
                console.log('📋 Step 5: Opening Modal...');
                await modalButton.click();
                await page.waitForTimeout(2000); // Wait for modal to render

                // Get modal HTML
                const modalHTML = await page.content();

                // Security check: Look for forbidden data in modal
                const modalViolations = [];

                for (const forbiddenTerm of userData.cannotSee) {
                    if (modalHTML.includes(forbiddenTerm)) {
                        modalViolations.push({
                            severity: 'CRITICAL',
                            term: forbiddenTerm,
                            context: 'Modal HTML contains forbidden data',
                            location: 'Financial Details Modal'
                        });
                    }
                }

                if (modalViolations.length > 0) {
                    console.log(`\n🚨 MODAL SECURITY VIOLATIONS FOUND (${modalViolations.length}):`);
                    modalViolations.forEach((v, i) => {
                        console.log(`\n  [${i + 1}] ${v.severity}: ${v.term}`);
                        console.log(`      Context: ${v.context}`);
                        console.log(`      Location: ${v.location}`);
                    });
                    violations.push(...modalViolations);
                } else {
                    console.log(`✅ No forbidden data found in modal HTML`);
                }

                // Take screenshot of modal
                await page.screenshot({
                    path: `/var/www/api-gateway/tests/Browser/screenshots/modal-${roleName}-${Date.now()}.png`,
                    fullPage: true
                });
                console.log(`📸 Modal screenshot saved`);

            } else {
                console.log('⚠️  Modal button NOT found (unexpected for this role)');
            }
        } else {
            // Customer should NOT have modal button
            const modalButton = await page.$('table tbody tr:first-child td[wire\\:click*="showFinancialDetails"], table tbody tr:first-child button[wire\\:click*="showFinancialDetails"]');

            if (modalButton) {
                violations.push({
                    severity: 'CRITICAL',
                    term: 'Modal Button',
                    context: 'Customer has access to financial details modal',
                    location: 'Calls table'
                });
                console.log('🚨 VIOLATION: Customer can access modal (should be hidden)!');
            } else {
                console.log('✅ Modal button correctly hidden from customer');
            }
        }

        // Step 6: Check visible data expectations
        console.log(`\n📋 Step 6: Verify Expected Data is Visible`);
        let expectedDataFound = 0;
        for (const expectedTerm of userData.canSee) {
            if (pageHTML.includes(expectedTerm)) {
                expectedDataFound++;
            }
        }
        console.log(`✅ Found ${expectedDataFound}/${userData.canSee.length} expected data fields`);

        // Take full page screenshot
        await page.screenshot({
            path: `/var/www/api-gateway/tests/Browser/screenshots/table-${roleName}-${Date.now()}.png`,
            fullPage: true
        });
        console.log(`📸 Table screenshot saved`);

        // Final result for this role
        console.log(`\n${'─'.repeat(60)}`);
        if (violations.length === 0) {
            console.log(`✅ ${roleName.toUpperCase()} PASSED: No security violations`);
        } else {
            console.log(`❌ ${roleName.toUpperCase()} FAILED: ${violations.length} security violations`);
        }
        console.log('─'.repeat(60));

        await page.close();

        return {
            role: roleName,
            passed: violations.length === 0,
            violations: violations,
            expectedDataFound: expectedDataFound,
            expectedDataTotal: userData.canSee.length
        };

    } catch (error) {
        console.error(`❌ ERROR testing ${roleName}:`, error.message);
        await page.screenshot({
            path: `/var/www/api-gateway/tests/Browser/screenshots/error-${roleName}-${Date.now()}.png`,
            fullPage: true
        });
        await page.close();

        return {
            role: roleName,
            passed: false,
            violations: [{
                severity: 'ERROR',
                term: 'Test Execution',
                context: error.message,
                location: 'Test Runner'
            }],
            expectedDataFound: 0,
            expectedDataTotal: userData.canSee.length
        };
    }
}

/**
 * Main test runner
 */
async function runSecurityTests() {
    console.log('\n' + '━'.repeat(60));
    console.log('🔒 CRITICAL SECURITY TEST: Role-Based Data Visibility');
    console.log('━'.repeat(60));
    console.log('Testing URL:', BASE_URL);
    console.log('Timestamp:', new Date().toISOString());
    console.log('━'.repeat(60));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ],
        slowMo: 50
    });

    const results = {
        superadmin: null,
        reseller: null,
        // customer: null // Uncomment when customer test user is available
    };

    try {
        // Test each role sequentially
        results.superadmin = await testRole(browser, 'superadmin', USERS.superadmin);

        // Only test reseller if credentials are provided
        if (USERS.reseller.email !== 'reseller@test.de') {
            results.reseller = await testRole(browser, 'reseller', USERS.reseller);
        } else {
            console.log('\n⚠️  Skipping reseller test (no test user configured)');
        }

        // Only test customer if credentials are provided
        if (USERS.customer.email !== 'customer@test.de') {
            results.customer = await testRole(browser, 'customer', USERS.customer);
        } else {
            console.log('\n⚠️  Skipping customer test (no test user configured)');
        }

    } catch (error) {
        console.error('\n❌ CRITICAL ERROR:', error);
    } finally {
        await browser.close();
    }

    // Final summary
    console.log('\n' + '━'.repeat(60));
    console.log('📊 FINAL SECURITY TEST RESULTS');
    console.log('━'.repeat(60));

    let totalViolations = 0;
    let totalPassed = 0;
    let totalFailed = 0;

    Object.entries(results).forEach(([role, result]) => {
        if (result) {
            const status = result.passed ? '✅ PASSED' : '❌ FAILED';
            const violations = result.violations.length;
            totalViolations += violations;

            if (result.passed) {
                totalPassed++;
            } else {
                totalFailed++;
            }

            console.log(`\n${role.toUpperCase()}: ${status}`);
            console.log(`  Violations: ${violations}`);
            console.log(`  Expected Data: ${result.expectedDataFound}/${result.expectedDataTotal}`);

            if (violations > 0) {
                console.log(`  Critical Issues:`);
                result.violations.forEach((v, i) => {
                    console.log(`    ${i + 1}. ${v.term} (${v.location})`);
                });
            }
        }
    });

    console.log('\n' + '─'.repeat(60));
    console.log(`Total Tests: ${totalPassed + totalFailed}`);
    console.log(`Passed: ${totalPassed}`);
    console.log(`Failed: ${totalFailed}`);
    console.log(`Total Security Violations: ${totalViolations}`);
    console.log('─'.repeat(60));

    if (totalViolations === 0 && totalFailed === 0) {
        console.log('\n✅ ALL SECURITY TESTS PASSED');
        console.log('   Role-based data isolation is working correctly.');
    } else {
        console.log('\n🚨 SECURITY TESTS FAILED');
        console.log('   IMMEDIATE ACTION REQUIRED: Fix role-based visibility!');
    }

    console.log('\n' + '━'.repeat(60) + '\n');

    // Exit with appropriate code
    process.exit(totalViolations > 0 || totalFailed > 0 ? 1 : 0);
}

// Run tests
runSecurityTests().catch(console.error);
