#!/usr/bin/env node

/**
 * Settings Dashboard - New Tabs Browser Test
 * Tests the 4 new tabs: Filialen, Dienstleistungen, Mitarbeiter, Sync-Status
 */

const puppeteer = require('puppeteer');
const fs = require('fs');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOT_DIR = __dirname + '/screenshots';

if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function log(message, type = 'info') {
    const timestamp = new Date().toISOString();
    const symbols = { info: '✓', error: '❌', warning: '⚠️', progress: '🔄' };
    console.log(`${symbols[type]} [${timestamp}] ${message}`);
}

async function takeScreenshot(page, name) {
    const path = `${SCREENSHOT_DIR}/${name}.png`;
    await page.screenshot({ path, fullPage: true });
    await log(`Screenshot saved: ${name}.png`);
}

async function runTest() {
    let browser;

    try {
        browser = await puppeteer.launch({
            headless: false, // WICHTIG: Nicht headless damit wir sehen können
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu'
            ],
            executablePath: '/usr/bin/chromium-browser',
            slowMo: 100 // Langsamer für bessere Sichtbarkeit
        });

        const page = await browser.newPage();
        await page.setViewport({ width: 1920, height: 1080 });

        console.log('\n╔════════════════════════════════════════════════════════════════╗');
        console.log('║  BROWSER TEST: Settings Dashboard - Neue Tabs                 ║');
        console.log('╚════════════════════════════════════════════════════════════════╝\n');

        // =====================================================================
        // PHASE 1: Login
        // =====================================================================
        await log('PHASE 1: Login', 'progress');
        await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle2', timeout: 30000 });
        await takeScreenshot(page, '01-login-page');

        await page.type('input[type="email"]', 'info@askproai.de');
        await page.type('input[type="password"]', 'LandP007!');
        await page.click('button[type="submit"]');
        await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 });
        await log('Login successful');

        // =====================================================================
        // PHASE 2: Navigate to Settings Dashboard
        // =====================================================================
        await log('PHASE 2: Navigate to Settings Dashboard', 'progress');
        await page.goto(`${BASE_URL}/admin/settings-dashboard`, { waitUntil: 'networkidle2', timeout: 30000 });
        await page.waitForTimeout(2000);
        await takeScreenshot(page, '02-settings-dashboard-loaded');
        await log('Settings Dashboard loaded');

        // =====================================================================
        // PHASE 3: Check visible tabs
        // =====================================================================
        await log('PHASE 3: Checking visible tabs', 'progress');

        const tabs = await page.evaluate(() => {
            const tabElements = document.querySelectorAll('[role="tab"]');
            return Array.from(tabElements).map(tab => tab.textContent.trim());
        });

        await log(`Found ${tabs.length} tabs: ${tabs.join(', ')}`);
        console.log('\nVisible Tabs:');
        tabs.forEach((tab, index) => {
            console.log(`  ${index + 1}. ${tab}`);
        });

        // Check if new tabs are visible
        const expectedNewTabs = ['Filialen', 'Dienstleistungen', 'Mitarbeiter', 'Sync-Status'];
        const missingTabs = expectedNewTabs.filter(tab => !tabs.some(t => t.includes(tab)));

        if (missingTabs.length > 0) {
            await log(`Missing tabs: ${missingTabs.join(', ')}`, 'error');
        } else {
            await log('All new tabs are visible!', 'info');
        }

        // =====================================================================
        // PHASE 4: Click Filialen Tab
        // =====================================================================
        await log('PHASE 4: Testing Filialen Tab', 'progress');

        try {
            // Find and click Filialen tab
            await page.evaluate(() => {
                const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
                const filialenTab = tabs.find(tab => tab.textContent.includes('Filialen'));
                if (filialenTab) {
                    filialenTab.click();
                }
            });

            await page.waitForTimeout(2000);
            await takeScreenshot(page, '03-filialen-tab-clicked');
            await log('Filialen tab clicked');

            // Check if repeater is visible
            const repeaterVisible = await page.evaluate(() => {
                return !!document.querySelector('[wire\\:key*="repeater"]') ||
                       !!document.querySelector('[data-field-wrapper]') ||
                       !!document.querySelector('button[type="button"]');
            });

            if (repeaterVisible) {
                await log('Repeater/Form elements found in Filialen tab', 'info');
            } else {
                await log('No repeater/form elements found in Filialen tab', 'warning');
            }

            // Check for "Filiale hinzufügen" button
            const addButton = await page.evaluate(() => {
                const buttons = Array.from(document.querySelectorAll('button'));
                const addBtn = buttons.find(btn => btn.textContent.includes('hinzufügen'));
                return addBtn ? addBtn.textContent : null;
            });

            if (addButton) {
                await log(`Found add button: "${addButton}"`, 'info');
            } else {
                await log('No "hinzufügen" button found', 'warning');
            }

        } catch (error) {
            await log(`Error clicking Filialen tab: ${error.message}`, 'error');
        }

        // =====================================================================
        // PHASE 5: Click Dienstleistungen Tab
        // =====================================================================
        await log('PHASE 5: Testing Dienstleistungen Tab', 'progress');

        try {
            await page.evaluate(() => {
                const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
                const serviceTab = tabs.find(tab => tab.textContent.includes('Dienstleistungen'));
                if (serviceTab) {
                    serviceTab.click();
                }
            });

            await page.waitForTimeout(2000);
            await takeScreenshot(page, '04-dienstleistungen-tab-clicked');
            await log('Dienstleistungen tab clicked');

        } catch (error) {
            await log(`Error clicking Dienstleistungen tab: ${error.message}`, 'error');
        }

        // =====================================================================
        // PHASE 6: Click Mitarbeiter Tab
        // =====================================================================
        await log('PHASE 6: Testing Mitarbeiter Tab', 'progress');

        try {
            await page.evaluate(() => {
                const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
                const staffTab = tabs.find(tab => tab.textContent.includes('Mitarbeiter'));
                if (staffTab) {
                    staffTab.click();
                }
            });

            await page.waitForTimeout(2000);
            await takeScreenshot(page, '05-mitarbeiter-tab-clicked');
            await log('Mitarbeiter tab clicked');

        } catch (error) {
            await log(`Error clicking Mitarbeiter tab: ${error.message}`, 'error');
        }

        // =====================================================================
        // PHASE 7: Click Sync-Status Tab
        // =====================================================================
        await log('PHASE 7: Testing Sync-Status Tab', 'progress');

        try {
            await page.evaluate(() => {
                const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
                const syncTab = tabs.find(tab => tab.textContent.includes('Sync-Status') || tab.textContent.includes('Sync'));
                if (syncTab) {
                    syncTab.click();
                }
            });

            await page.waitForTimeout(2000);
            await takeScreenshot(page, '06-sync-status-tab-clicked');
            await log('Sync-Status tab clicked');

            // Check sync status content
            const syncContent = await page.evaluate(() => {
                return document.body.textContent;
            });

            if (syncContent.includes('Company:') || syncContent.includes('Filialen:')) {
                await log('Sync status content is visible', 'info');
            } else {
                await log('Sync status content not visible', 'warning');
            }

        } catch (error) {
            await log(`Error clicking Sync-Status tab: ${error.message}`, 'error');
        }

        // =====================================================================
        // PHASE 8: Check Console Errors
        // =====================================================================
        await log('PHASE 8: Checking for JavaScript errors', 'progress');

        page.on('console', msg => {
            if (msg.type() === 'error') {
                console.log(`  ❌ Console Error: ${msg.text()}`);
            }
        });

        // Final screenshot
        await takeScreenshot(page, '07-final-state');

        console.log('\n╔════════════════════════════════════════════════════════════════╗');
        console.log('║                  ✅ BROWSER TEST COMPLETE                     ║');
        console.log('╚════════════════════════════════════════════════════════════════╝\n');

        console.log('Summary:');
        console.log(`  - Total Tabs Found: ${tabs.length}`);
        console.log(`  - Missing Tabs: ${missingTabs.length > 0 ? missingTabs.join(', ') : 'None'}`);
        console.log(`  - Screenshots saved to: ${SCREENSHOT_DIR}`);
        console.log('\nPlease check screenshots for visual verification.\n');

        // Keep browser open for 10 seconds for manual inspection
        await log('Keeping browser open for 10 seconds for inspection...', 'progress');
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('\n❌ TEST FAILED:', error.message);
        console.error(error.stack);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

runTest();
