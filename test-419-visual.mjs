#!/usr/bin/env node
/**
 * Visual UI Test für Session-Expired-Seite (419)
 * Umfassende Validierung von Design, Accessibility und Responsiveness
 */

import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const TARGET_URL = 'https://api.askproai.de/test-419';
const SCREENSHOTS_DIR = path.join(__dirname, 'tests/screenshots/419-page');

// Viewport-Größen für Responsiveness-Tests
const VIEWPORTS = [
    { name: 'mobile', width: 375, height: 667 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'desktop', width: 1920, height: 1080 }
];

/**
 * Erstellt Screenshot-Verzeichnis
 */
function ensureScreenshotDir() {
    if (!fs.existsSync(SCREENSHOTS_DIR)) {
        fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
    }
}

/**
 * Haupttest-Funktion
 */
async function runVisualTest() {
    console.log('🎨 Starting Visual UI Test for 419 Page...\n');
    console.log(`📍 Target URL: ${TARGET_URL}\n`);
    ensureScreenshotDir();

    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const results = {
        timestamp: new Date().toISOString(),
        url: TARGET_URL,
        tests: {
            screenshots: [],
            darkMode: null,
            designQuality: {},
            responsiveness: { viewports: [] },
            accessibility: {}
        },
        summary: {
            passed: 0,
            failed: 0,
            warnings: 0
        }
    };

    try {
        // Test 1: Light Mode Screenshots
        console.log('📸 Test 1: Light Mode Screenshots');
        await testLightMode(browser, results);

        // Test 2: Dark Mode
        console.log('\n🌙 Test 2: Dark Mode Test');
        await testDarkMode(browser, results);

        // Test 3: Design-Qualität
        console.log('\n🎨 Test 3: Design-Qualität');
        await testDesignQuality(browser, results);

        // Test 4: Responsiveness
        console.log('\n📱 Test 4: Responsiveness');
        await testResponsiveness(browser, results);

        // Test 5: Accessibility
        console.log('\n♿ Test 5: Accessibility');
        await testAccessibility(browser, results);

        // Generate Report
        console.log('\n📊 Generating Test Report...');
        generateReport(results);

    } catch (error) {
        console.error('❌ Test execution failed:', error);
        results.error = error.message;
        results.summary.failed++;
    } finally {
        await browser.close();
    }

    return results;
}

/**
 * Test 1: Light Mode Screenshots
 */
async function testLightMode(browser, results) {
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        colorScheme: 'light'
    });
    const page = await context.newPage();

    try {
        console.log('  🔄 Loading page...');
        await page.goto(TARGET_URL, { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForTimeout(1000);

        const screenshotPath = path.join(SCREENSHOTS_DIR, '01-light-mode-desktop.png');
        await page.screenshot({ path: screenshotPath, fullPage: true });

        results.tests.screenshots.push({
            name: 'Light Mode Desktop',
            path: screenshotPath,
            status: 'captured'
        });

        console.log(`  ✅ Screenshot saved: ${screenshotPath}`);
        results.summary.passed++;
    } catch (error) {
        console.error(`  ❌ Failed: ${error.message}`);
        results.summary.failed++;
    } finally {
        await context.close();
    }
}

/**
 * Test 2: Dark Mode
 */
async function testDarkMode(browser, results) {
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        colorScheme: 'dark'
    });
    const page = await context.newPage();

    try {
        console.log('  🔄 Loading page in dark mode...');
        await page.goto(TARGET_URL, { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForTimeout(1000);

        const screenshotPath = path.join(SCREENSHOTS_DIR, '02-dark-mode-desktop.png');
        await page.screenshot({ path: screenshotPath, fullPage: true });

        const bodyBg = await page.evaluate(() => {
            return window.getComputedStyle(document.body).backgroundColor;
        });

        const isDarkBackground = await page.evaluate(() => {
            const bg = window.getComputedStyle(document.body).backgroundColor;
            const rgb = bg.match(/\d+/g).map(Number);
            const luminance = (0.299 * rgb[0] + 0.587 * rgb[1] + 0.114 * rgb[2]) / 255;
            return luminance < 0.5;
        });

        results.tests.darkMode = {
            implemented: isDarkBackground,
            backgroundColor: bodyBg,
            screenshotPath,
            status: isDarkBackground ? 'passed' : 'failed'
        };

        if (isDarkBackground) {
            console.log('  ✅ Dark Mode is properly implemented');
            console.log(`  📊 Background: ${bodyBg}`);
            results.summary.passed++;
        } else {
            console.log('  ❌ Dark Mode NOT working - background is light');
            console.log(`  📊 Background: ${bodyBg}`);
            results.summary.failed++;
        }

    } catch (error) {
        console.error(`  ❌ Failed: ${error.message}`);
        results.summary.failed++;
    } finally {
        await context.close();
    }
}

/**
 * Test 3: Design-Qualität
 */
async function testDesignQuality(browser, results) {
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        colorScheme: 'light'
    });
    const page = await context.newPage();

    try {
        await page.goto(TARGET_URL, { waitUntil: 'networkidle', timeout: 30000 });

        const designElements = await page.evaluate(() => {
            const getStyles = (element) => {
                if (!element) return null;
                const styles = window.getComputedStyle(element);
                return {
                    color: styles.color,
                    backgroundColor: styles.backgroundColor,
                    fontSize: styles.fontSize,
                    fontWeight: styles.fontWeight,
                    display: styles.display
                };
            };

            const card = document.querySelector('.bg-white, [class*="card"], [class*="container"]');
            const icon = document.querySelector('svg, i, [class*="icon"]');
            const heading = document.querySelector('h1, h2, [class*="heading"], [class*="title"]');
            const description = document.querySelector('p, [class*="description"], [class*="text"]');
            const button = document.querySelector('button, a[class*="btn"], a[class*="button"]');

            return {
                card: card ? { exists: true, styles: getStyles(card) } : { exists: false },
                icon: icon ? { exists: true, type: icon.tagName.toLowerCase() } : { exists: false },
                heading: heading ? { exists: true, text: heading.textContent.trim() } : { exists: false },
                description: description ? { exists: true, text: description.textContent.trim() } : { exists: false },
                button: button ? { exists: true, text: button.textContent.trim(), href: button.href || null } : { exists: false }
            };
        });

        const checks = {
            hasCard: designElements.card.exists,
            hasIcon: designElements.icon.exists,
            hasHeading: designElements.heading.exists,
            hasCorrectHeading: designElements.heading.exists &&
                              designElements.heading.text.toLowerCase().includes('sitzung'),
            hasDescription: designElements.description.exists,
            hasButton: designElements.button.exists,
            hasGermanText: JSON.stringify(designElements).match(/sitzung|abgelaufen|anmeldung/i) !== null,
            buttonHasText: designElements.button.exists && designElements.button.text.length > 0
        };

        results.tests.designQuality = {
            elements: designElements,
            checks,
            score: Object.values(checks).filter(v => v).length,
            maxScore: Object.keys(checks).length,
            status: checks.hasHeading && checks.hasButton ? 'passed' : 'failed'
        };

        console.log('  📋 Design Elements Check:');
        console.log(`    ${checks.hasCard ? '✅' : '❌'} Card vorhanden`);
        console.log(`    ${checks.hasIcon ? '✅' : '❌'} Icon vorhanden`);
        console.log(`    ${checks.hasHeading ? '✅' : '❌'} Heading vorhanden`);
        console.log(`    ${checks.hasCorrectHeading ? '✅' : '⚠️ '} Korrekte Überschrift ("Sitzung abgelaufen")`);
        console.log(`    ${checks.hasDescription ? '✅' : '❌'} Beschreibung vorhanden`);
        console.log(`    ${checks.hasButton ? '✅' : '❌'} Button vorhanden`);
        console.log(`    ${checks.hasGermanText ? '✅' : '❌'} Deutsche Texte`);
        console.log(`    ${checks.buttonHasText ? '✅' : '❌'} Button hat Text`);

        if (designElements.heading.exists) {
            console.log(`    📝 Heading-Text: "${designElements.heading.text}"`);
        }
        if (designElements.button.exists) {
            console.log(`    📝 Button-Text: "${designElements.button.text}"`);
        }

        console.log(`  📊 Design Score: ${results.tests.designQuality.score}/${results.tests.designQuality.maxScore}`);

        if (checks.hasHeading && checks.hasButton) {
            results.summary.passed++;
        } else {
            results.summary.failed++;
        }

    } catch (error) {
        console.error(`  ❌ Failed: ${error.message}`);
        results.summary.failed++;
    } finally {
        await context.close();
    }
}

/**
 * Test 4: Responsiveness
 */
async function testResponsiveness(browser, results) {
    for (const viewport of VIEWPORTS) {
        const context = await browser.newContext({
            viewport: { width: viewport.width, height: viewport.height },
            colorScheme: 'light'
        });
        const page = await context.newPage();

        try {
            await page.goto(TARGET_URL, { waitUntil: 'networkidle', timeout: 30000 });
            await page.waitForTimeout(500);

            const screenshotPath = path.join(SCREENSHOTS_DIR, `03-responsive-${viewport.name}.png`);
            await page.screenshot({ path: screenshotPath, fullPage: true });

            const isContentVisible = await page.evaluate(() => {
                const heading = document.querySelector('h1, h2');
                const button = document.querySelector('button, a[class*="btn"]');

                if (!heading || !button) return false;

                const headingRect = heading.getBoundingClientRect();
                const buttonRect = button.getBoundingClientRect();

                return headingRect.width > 0 &&
                       headingRect.height > 0 &&
                       buttonRect.width > 0 &&
                       buttonRect.height > 0;
            });

            results.tests.responsiveness.viewports.push({
                name: viewport.name,
                width: viewport.width,
                height: viewport.height,
                screenshotPath,
                contentVisible: isContentVisible,
                status: isContentVisible ? 'passed' : 'failed'
            });

            console.log(`  ${isContentVisible ? '✅' : '❌'} ${viewport.name} (${viewport.width}x${viewport.height})`);

            if (isContentVisible) {
                results.summary.passed++;
            } else {
                results.summary.failed++;
            }

        } catch (error) {
            console.error(`  ❌ ${viewport.name} failed: ${error.message}`);
            results.summary.failed++;
        } finally {
            await context.close();
        }
    }
}

/**
 * Test 5: Accessibility
 */
async function testAccessibility(browser, results) {
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        colorScheme: 'light'
    });
    const page = await context.newPage();

    try {
        await page.goto(TARGET_URL, { waitUntil: 'networkidle', timeout: 30000 });

        const accessibilityChecks = await page.evaluate(() => {
            const heading = document.querySelector('h1, h2, h3');
            const button = document.querySelector('button, a[class*="btn"]');

            return {
                hasHeadingTag: !!heading,
                headingText: heading ? heading.textContent.trim() : '',
                hasMainLandmark: !!document.querySelector('main'),
                buttonHasText: button ? button.textContent.trim().length > 0 : false,
                buttonText: button ? button.textContent.trim() : '',
                buttonFocusable: button ? (
                    button.tabIndex >= 0 ||
                    button.tagName === 'BUTTON' ||
                    button.tagName === 'A'
                ) : false,
                hasAltTexts: Array.from(document.querySelectorAll('img')).every(
                    img => img.hasAttribute('alt')
                ),
                imageCount: document.querySelectorAll('img').length
            };
        });

        results.tests.accessibility = {
            checks: accessibilityChecks,
            wcagLevel: 'AA',
            status: accessibilityChecks.hasHeadingTag &&
                   accessibilityChecks.buttonHasText &&
                   accessibilityChecks.buttonFocusable ? 'passed' : 'failed'
        };

        console.log('  ♿ Accessibility Checks:');
        console.log(`    ${accessibilityChecks.hasHeadingTag ? '✅' : '❌'} Proper heading tags`);
        console.log(`    ${accessibilityChecks.buttonHasText ? '✅' : '❌'} Button has text`);
        console.log(`    ${accessibilityChecks.buttonFocusable ? '✅' : '❌'} Button is keyboard accessible`);
        console.log(`    ${accessibilityChecks.hasMainLandmark ? '✅' : '⚠️ '} Main landmark present`);

        if (accessibilityChecks.imageCount > 0) {
            console.log(`    ${accessibilityChecks.hasAltTexts ? '✅' : '❌'} All images have alt text (${accessibilityChecks.imageCount} images)`);
        }

        if (results.tests.accessibility.status === 'passed') {
            results.summary.passed++;
        } else {
            results.summary.failed++;
        }

    } catch (error) {
        console.error(`  ❌ Failed: ${error.message}`);
        results.summary.failed++;
    } finally {
        await context.close();
    }
}

/**
 * Generiert Test-Report
 */
function generateReport(results) {
    const reportPath = path.join(SCREENSHOTS_DIR, 'test-report.json');

    fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
    console.log(`\n📄 JSON Report: ${reportPath}`);

    console.log(`\n${'='.repeat(60)}`);
    console.log('📊 FINAL TEST SUMMARY');
    console.log('='.repeat(60));
    console.log(`✅ Passed Tests:   ${results.summary.passed}`);
    console.log(`❌ Failed Tests:   ${results.summary.failed}`);
    console.log(`⚠️  Warnings:       ${results.summary.warnings}`);

    const total = results.summary.passed + results.summary.failed;
    const successRate = total > 0 ? Math.round((results.summary.passed / total) * 100) : 0;
    console.log(`📈 Success Rate:   ${successRate}%`);
    console.log('='.repeat(60));

    console.log('\n📁 Screenshots saved in:');
    console.log(`   ${SCREENSHOTS_DIR}`);

    return results;
}

// Run tests
runVisualTest().then(results => {
    const exitCode = results.summary.failed > 0 ? 1 : 0;
    process.exit(exitCode);
}).catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
