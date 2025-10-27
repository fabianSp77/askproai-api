#!/usr/bin/env node

/**
 * Puppeteer Auto-Publish for Retell Dashboard
 *
 * This script automates the manual "Publish" button click in Retell Dashboard
 * because the Retell API /publish-agent endpoint has a bug.
 *
 * Usage:
 *   node scripts/deployment/puppeteer_auto_publish.js <version_number>
 *
 * Example:
 *   node scripts/deployment/puppeteer_auto_publish.js 58
 */

const puppeteer = require('puppeteer');

const AGENT_ID = 'agent_f1ce85d06a84afb989dfbb16a9';
const DASHBOARD_URL = `https://dashboard.retellai.com/agent/${AGENT_ID}`;

async function autoPublish(targetVersion) {
    console.log('\n═══════════════════════════════════════════════════════════');
    console.log('PUPPETEER AUTO-PUBLISH');
    console.log('═══════════════════════════════════════════════════════════\n');

    console.log(`Target version: ${targetVersion}`);
    console.log(`Dashboard URL: ${DASHBOARD_URL}\n`);

    const browser = await puppeteer.launch({
        headless: false, // Show browser so user can see what's happening
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();

        console.log('🌐 Opening Retell Dashboard...\n');
        await page.goto(DASHBOARD_URL, { waitUntil: 'networkidle2', timeout: 30000 });

        // Wait a moment for page to load
        await page.waitForTimeout(2000);

        // Check if we need to login
        const currentUrl = page.url();

        if (currentUrl.includes('login') || currentUrl.includes('auth')) {
            console.log('🔐 Login required\n');
            console.log('Please login manually in the browser window...\n');
            console.log('Waiting 60 seconds for you to login...\n');

            // Wait for user to login
            await page.waitForTimeout(60000);

            // Navigate back to agent page
            console.log('🌐 Navigating back to agent page...\n');
            await page.goto(DASHBOARD_URL, { waitUntil: 'networkidle2' });
            await page.waitForTimeout(2000);
        }

        console.log('✅ Dashboard loaded\n');

        // Look for versions section
        console.log('🔍 Looking for version history...\n');

        // Try to find the version
        // This is a placeholder - we need to inspect the actual Dashboard HTML
        const versionText = `Version ${targetVersion}`;

        console.log(`Looking for: ${versionText}\n`);

        // Wait for versions to load
        await page.waitForTimeout(3000);

        // Take a screenshot for debugging
        await page.screenshot({ path: 'retell_dashboard.png', fullPage: true });
        console.log('📸 Screenshot saved: retell_dashboard.png\n');

        // Try to find and click publish button
        // Note: We need to inspect actual Dashboard HTML to know correct selectors
        const publishButtonSelectors = [
            'button:contains("Publish")',
            'button:contains("Make Live")',
            '[data-testid="publish-button"]',
            '.publish-button'
        ];

        let publishClicked = false;

        for (const selector of publishButtonSelectors) {
            try {
                await page.waitForSelector(selector, { timeout: 5000 });
                await page.click(selector);
                publishClicked = true;
                console.log(`✅ Clicked publish button: ${selector}\n`);
                break;
            } catch (e) {
                // Selector not found, try next one
            }
        }

        if (!publishClicked) {
            console.log('❌ Could not find publish button automatically\n');
            console.log('📸 Check screenshot: retell_dashboard.png\n');
            console.log('Please click "Publish" manually in the browser window\n');
            console.log('Waiting 30 seconds...\n');
            await page.waitForTimeout(30000);
        }

        // Wait for publish to complete
        console.log('⏳ Waiting for publish to complete...\n');
        await page.waitForTimeout(5000);

        // Take final screenshot
        await page.screenshot({ path: 'retell_dashboard_after.png', fullPage: true });
        console.log('📸 Final screenshot saved: retell_dashboard_after.png\n');

        console.log('═══════════════════════════════════════════════════════════');
        console.log('✅ DONE');
        console.log('═══════════════════════════════════════════════════════════\n');

        console.log('Please verify in Dashboard that version was published.\n');

    } catch (error) {
        console.error('❌ Error:', error.message);
        await browser.screenshot({ path: 'retell_error.png', fullPage: true });
        console.log('📸 Error screenshot saved: retell_error.png\n');
    } finally {
        // Keep browser open for 10 seconds so user can see result
        console.log('Browser will close in 10 seconds...\n');
        await page.waitForTimeout(10000);
        await browser.close();
    }
}

// Get version from command line
const targetVersion = process.argv[2];

if (!targetVersion) {
    console.error('❌ Please provide version number:');
    console.error('   node puppeteer_auto_publish.js <version>\n');
    console.error('Example:');
    console.error('   node puppeteer_auto_publish.js 58\n');
    process.exit(1);
}

autoPublish(targetVersion).catch(console.error);
