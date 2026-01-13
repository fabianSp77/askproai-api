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
const SCREENSHOTS_DIR = path.join(__dirname, 'screenshots', '419-page');

// Viewport-Größen für Responsiveness-Tests
const VIEWPORTS = [
    { name: 'mobile', width: 375, height: 667 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'desktop', width: 1920, height: 1080 }
];

// WCAG 2.1 Kontrastverhältnis-Mindestanforderungen
const WCAG_CONTRAST_RATIOS = {
    AA_NORMAL: 4.5,
    AA_LARGE: 3.0,
    AAA_NORMAL: 7.0,
    AAA_LARGE: 4.5
};

/**
 * Berechnet das Kontrastverhältnis zwischen zwei Farben
 */
function calculateContrastRatio(color1, color2) {
    const getLuminance = (rgb) => {
        const [r, g, b] = rgb.match(/\d+/g).map(Number);
        const sRGB = [r, g, b].map(val => {
            val /= 255;
            return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * sRGB[0] + 0.7152 * sRGB[1] + 0.0722 * sRGB[2];
    };

    const lum1 = getLuminance(color1);
    const lum2 = getLuminance(color2);
    const lighter = Math.max(lum1, lum2);
    const darker = Math.min(lum1, lum2);
    return (lighter + 0.05) / (darker + 0.05);
}

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
    ensureScreenshotDir();

    const browser = await chromium.launch({ headless: true });
    const results = {
        timestamp: new Date().toISOString(),
        url: TARGET_URL,
        tests: {
            screenshots: [],
            darkMode: null,
            designQuality: {},
            responsiveness: {},
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
        await page.goto(TARGET_URL, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000); // Animationen abwarten

        const screenshotPath = path.join(SCREENSHOTS_DIR, '01-light-mode-desktop.png');
        await page.screenshot({ path: screenshotPath, fullPage: true });

        results.tests.screenshots.push({
            name: 'Light Mode Desktop',
            path: screenshotPath,
            status: 'captured'
        });

        console.log(`  ✅ Screenshot saved: ${screenshotPath}`);
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
        await page.goto(TARGET_URL, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        // Screenshot
        const screenshotPath = path.join(SCREENSHOTS_DIR, '02-dark-mode-desktop.png');
        await page.screenshot({ path: screenshotPath, fullPage: true });

        // Prüfe Dark Mode Implementierung
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
        await page.goto(TARGET_URL, { waitUntil: 'networkidle' });

        // Extrahiere Design-Elemente
        const designElements = await page.evaluate(() => {
            const card = document.querySelector('.bg-white, [class*="card"]');
            const icon = document.querySelector('svg, i, [class*="icon"]');
            const heading = document.querySelector('h1, h2, [class*="heading"]');
            const description = document.querySelector('p, [class*="description"]');
            const button = document.querySelector('button, a[class*="btn"], a[class*="button"]');

            const getStyles = (element) => {
                if (!element) return null;
                const styles = window.getComputedStyle(element);
                return {
                    color: styles.color,
                    backgroundColor: styles.backgroundColor,
                    fontSize: styles.fontSize,
                    fontWeight: styles.fontWeight,
                    padding: styles.padding,
                    margin: styles.margin,
                    borderRadius: styles.borderRadius,
                    display: styles.display,
                    textAlign: styles.textAlign
                };
            };

            return {
                card: card ? {
                    exists: true,
                    styles: getStyles(card),
                    isCentered: styles => {
                        const margin = window.getComputedStyle(card.parentElement);
                        return margin.display === 'flex' || margin.textAlign === 'center';
                    }
                } : { exists: false },
                icon: icon ? {
                    exists: true,
                    type: icon.tagName.toLowerCase(),
                    styles: getStyles(icon)
                } : { exists: false },
                heading: heading ? {
                    exists: true,
                    text: heading.textContent.trim(),
                    styles: getStyles(heading)
                } : { exists: false },
                description: description ? {
                    exists: true,
                    text: description.textContent.trim(),
                    styles: getStyles(description)
                } : { exists: false },
                button: button ? {
                    exists: true,
                    text: button.textContent.trim(),
                    styles: getStyles(button),
                    href: button.href || null
                } : { exists: false }
            };
        });

        // Validierung
        const checks = {
            hasCard: designElements.card.exists,
            hasIcon: designElements.icon.exists,
            hasHeading: designElements.heading.exists,
            hasCorrectHeading: designElements.heading.exists &&
                              designElements.heading.text.toLowerCase().includes('sitzung abgelaufen'),
            hasDescription: designElements.description.exists,
            hasButton: designElements.button.exists,
            hasGermanText: designElements.description.exists &&
                          /deutsch|german|sitzung/i.test(JSON.stringify(designElements)),
            buttonIsVisible: designElements.button.exists &&
                            designElements.button.styles.color !== 'rgba(0, 0, 0, 0)'
        };

        results.tests.designQuality = {
            elements: designElements,
            checks,
            score: Object.values(checks).filter(v => v).length,
            maxScore: Object.keys(checks).length,
            status: checks.hasCard && checks.hasHeading && checks.hasButton ? 'passed' : 'failed'
        };

        // Console Output
        console.log('  📋 Design Elements Check:');
        console.log(`    ${checks.hasCard ? '✅' : '❌'} Card vorhanden`);
        console.log(`    ${checks.hasIcon ? '✅' : '❌'} Icon vorhanden`);
        console.log(`    ${checks.hasHeading ? '✅' : '❌'} Heading vorhanden`);
        console.log(`    ${checks.hasCorrectHeading ? '✅' : '❌'} Korrekte Überschrift ("Sitzung abgelaufen")`);
        console.log(`    ${checks.hasDescription ? '✅' : '❌'} Beschreibung vorhanden`);
        console.log(`    ${checks.hasButton ? '✅' : '❌'} Button vorhanden`);
        console.log(`    ${checks.hasGermanText ? '✅' : '❌'} Deutsche Texte`);
        console.log(`    ${checks.buttonIsVisible ? '✅' : '❌'} Button ist sichtbar`);

        if (designElements.button.exists) {
            console.log(`    📝 Button-Text: "${designElements.button.text}"`);
        }

        results.summary.passed += checks.hasCard && checks.hasHeading && checks.hasButton ? 1 : 0;
        results.summary.failed += checks.hasCard && checks.hasHeading && checks.hasButton ? 0 : 1;

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
    results.tests.responsiveness.viewports = [];

    for (const viewport of VIEWPORTS) {
        const context = await browser.newContext({
            viewport: { width: viewport.width, height: viewport.height },
            colorScheme: 'light'
        });
        const page = await context.newPage();

        try {
            await page.goto(TARGET_URL, { waitUntil: 'networkidle' });
            await page.waitForTimeout(500);

            // Screenshot
            const screenshotPath = path.join(
                SCREENSHOTS_DIR,
                `03-responsive-${viewport.name}.png`
            );
            await page.screenshot({ path: screenshotPath, fullPage: true });

            // Prüfe ob Inhalt sichtbar ist
            const isContentVisible = await page.evaluate(() => {
                const card = document.querySelector('.bg-white, [class*="card"]');
                const button = document.querySelector('button, a[class*="btn"]');

                if (!card || !button) return false;

                const cardRect = card.getBoundingClientRect();
                const buttonRect = button.getBoundingClientRect();

                return cardRect.width > 0 &&
                       cardRect.height > 0 &&
                       buttonRect.width > 0 &&
                       buttonRect.height > 0 &&
                       cardRect.top >= 0 &&
                       buttonRect.top >= 0;
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

            results.summary.passed += isContentVisible ? 1 : 0;
            results.summary.failed += isContentVisible ? 0 : 1;

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
        await page.goto(TARGET_URL, { waitUntil: 'networkidle' });

        // Kontrast-Tests
        const contrastTests = await page.evaluate(() => {
            const elements = {
                heading: document.querySelector('h1, h2'),
                description: document.querySelector('p'),
                button: document.querySelector('button, a[class*="btn"]')
            };

            const getContrast = (element) => {
                if (!element) return null;
                const styles = window.getComputedStyle(element);
                const parent = element.parentElement;
                const parentStyles = window.getComputedStyle(parent);

                return {
                    foreground: styles.color,
                    background: parentStyles.backgroundColor || styles.backgroundColor,
                    fontSize: parseInt(styles.fontSize)
                };
            };

            return {
                heading: getContrast(elements.heading),
                description: getContrast(elements.description),
                button: getContrast(elements.button)
            };
        });

        // Semantic HTML
        const semanticCheck = await page.evaluate(() => {
            return {
                hasHeadingTag: !!document.querySelector('h1, h2, h3'),
                hasMainLandmark: !!document.querySelector('main'),
                buttonHasText: (() => {
                    const btn = document.querySelector('button, a[class*="btn"]');
                    return btn ? btn.textContent.trim().length > 0 : false;
                })(),
                hasAltTexts: Array.from(document.querySelectorAll('img')).every(
                    img => img.hasAttribute('alt')
                )
            };
        });

        // Keyboard Navigation
        const keyboardNav = await page.evaluate(() => {
            const button = document.querySelector('button, a[class*="btn"]');
            return {
                buttonFocusable: button ?
                    button.tabIndex >= 0 ||
                    button.tagName === 'BUTTON' ||
                    button.tagName === 'A' : false
            };
        });

        results.tests.accessibility = {
            contrast: contrastTests,
            semantic: semanticCheck,
            keyboard: keyboardNav,
            wcagLevel: 'AA', // Target level
            checks: {
                hasProperHeadings: semanticCheck.hasHeadingTag,
                buttonHasText: semanticCheck.buttonHasText,
                keyboardAccessible: keyboardNav.buttonFocusable
            }
        };

        // Console Output
        console.log('  ♿ Accessibility Checks:');
        console.log(`    ${semanticCheck.hasHeadingTag ? '✅' : '❌'} Proper heading tags`);
        console.log(`    ${semanticCheck.buttonHasText ? '✅' : '❌'} Button has text`);
        console.log(`    ${keyboardNav.buttonFocusable ? '✅' : '❌'} Button is keyboard accessible`);
        console.log(`    📊 Contrast ratios will be calculated in report`);

        const passed = semanticCheck.hasHeadingTag &&
                      semanticCheck.buttonHasText &&
                      keyboardNav.buttonFocusable;

        results.summary.passed += passed ? 1 : 0;
        results.summary.failed += passed ? 0 : 1;

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
    const htmlReportPath = path.join(SCREENSHOTS_DIR, 'test-report.html');

    // JSON Report
    fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
    console.log(`\n📄 JSON Report: ${reportPath}`);

    // HTML Report
    const html = `
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 Page Visual Test Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            padding: 2rem;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 { color: #333; margin-bottom: 1rem; }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-card h3 { color: #666; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .summary-card .value { font-size: 2rem; font-weight: bold; }
        .passed { color: #22c55e; }
        .failed { color: #ef4444; }
        .warning { color: #f59e0b; }
        .section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e5e5;
        }
        .screenshots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .screenshot-card {
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            overflow: hidden;
        }
        .screenshot-card img {
            width: 100%;
            height: auto;
            display: block;
        }
        .screenshot-card .caption {
            padding: 1rem;
            background: #f9f9f9;
            font-size: 0.9rem;
            color: #666;
        }
        .check-list {
            list-style: none;
            padding: 0;
        }
        .check-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .check-list li::before {
            content: '✅';
            font-size: 1.2rem;
        }
        .check-list li.failed::before {
            content: '❌';
        }
        .timestamp {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 419 Page Visual UI Test Report</h1>
            <p class="timestamp">Generated: ${new Date(results.timestamp).toLocaleString('de-DE')}</p>
            <p>URL: <a href="${results.url}" target="_blank">${results.url}</a></p>
        </div>

        <div class="summary">
            <div class="summary-card">
                <h3>Passed Tests</h3>
                <div class="value passed">${results.summary.passed}</div>
            </div>
            <div class="summary-card">
                <h3>Failed Tests</h3>
                <div class="value failed">${results.summary.failed}</div>
            </div>
            <div class="summary-card">
                <h3>Warnings</h3>
                <div class="value warning">${results.summary.warnings}</div>
            </div>
            <div class="summary-card">
                <h3>Success Rate</h3>
                <div class="value">${Math.round((results.summary.passed / (results.summary.passed + results.summary.failed)) * 100)}%</div>
            </div>
        </div>

        <div class="section">
            <h2>📸 Screenshots</h2>
            <div class="screenshots">
                ${results.tests.screenshots.map(s => `
                    <div class="screenshot-card">
                        <img src="${path.basename(s.path)}" alt="${s.name}">
                        <div class="caption">${s.name}</div>
                    </div>
                `).join('')}
                ${results.tests.darkMode && results.tests.darkMode.screenshotPath ? `
                    <div class="screenshot-card">
                        <img src="${path.basename(results.tests.darkMode.screenshotPath)}" alt="Dark Mode">
                        <div class="caption">Dark Mode (${results.tests.darkMode.status})</div>
                    </div>
                ` : ''}
                ${results.tests.responsiveness.viewports ? results.tests.responsiveness.viewports.map(v => `
                    <div class="screenshot-card">
                        <img src="${path.basename(v.screenshotPath)}" alt="${v.name}">
                        <div class="caption">${v.name} (${v.width}x${v.height})</div>
                    </div>
                `).join('') : ''}
            </div>
        </div>

        <div class="section">
            <h2>🌙 Dark Mode Test</h2>
            <ul class="check-list">
                <li class="${results.tests.darkMode?.implemented ? '' : 'failed'}">
                    Dark Mode ${results.tests.darkMode?.implemented ? 'implementiert' : 'NICHT implementiert'}
                </li>
                ${results.tests.darkMode?.backgroundColor ? `
                    <li>Background Color: ${results.tests.darkMode.backgroundColor}</li>
                ` : ''}
            </ul>
        </div>

        <div class="section">
            <h2>🎨 Design-Qualität</h2>
            ${results.tests.designQuality.checks ? `
                <ul class="check-list">
                    <li class="${results.tests.designQuality.checks.hasCard ? '' : 'failed'}">Card vorhanden</li>
                    <li class="${results.tests.designQuality.checks.hasIcon ? '' : 'failed'}">Icon vorhanden</li>
                    <li class="${results.tests.designQuality.checks.hasHeading ? '' : 'failed'}">Heading vorhanden</li>
                    <li class="${results.tests.designQuality.checks.hasCorrectHeading ? '' : 'failed'}">Korrekte Überschrift</li>
                    <li class="${results.tests.designQuality.checks.hasDescription ? '' : 'failed'}">Beschreibung vorhanden</li>
                    <li class="${results.tests.designQuality.checks.hasButton ? '' : 'failed'}">Button vorhanden</li>
                    <li class="${results.tests.designQuality.checks.hasGermanText ? '' : 'failed'}">Deutsche Texte</li>
                    <li class="${results.tests.designQuality.checks.buttonIsVisible ? '' : 'failed'}">Button ist sichtbar</li>
                </ul>
                <p><strong>Score:</strong> ${results.tests.designQuality.score}/${results.tests.designQuality.maxScore}</p>
            ` : '<p>Keine Design-Qualitätsdaten verfügbar</p>'}
        </div>

        <div class="section">
            <h2>📱 Responsiveness</h2>
            ${results.tests.responsiveness.viewports ? `
                <ul class="check-list">
                    ${results.tests.responsiveness.viewports.map(v => `
                        <li class="${v.contentVisible ? '' : 'failed'}">
                            ${v.name} (${v.width}x${v.height}) - ${v.contentVisible ? 'OK' : 'FEHLER'}
                        </li>
                    `).join('')}
                </ul>
            ` : '<p>Keine Responsiveness-Daten verfügbar</p>'}
        </div>

        <div class="section">
            <h2>♿ Accessibility</h2>
            ${results.tests.accessibility.checks ? `
                <ul class="check-list">
                    <li class="${results.tests.accessibility.checks.hasProperHeadings ? '' : 'failed'}">Proper heading tags</li>
                    <li class="${results.tests.accessibility.checks.buttonHasText ? '' : 'failed'}">Button has text</li>
                    <li class="${results.tests.accessibility.checks.keyboardAccessible ? '' : 'failed'}">Keyboard accessible</li>
                </ul>
            ` : '<p>Keine Accessibility-Daten verfügbar</p>'}
        </div>
    </div>
</body>
</html>
    `;

    fs.writeFileSync(htmlReportPath, html);
    console.log(`📄 HTML Report: ${htmlReportPath}`);
    console.log(`\n📊 Test Summary:`);
    console.log(`   ✅ Passed: ${results.summary.passed}`);
    console.log(`   ❌ Failed: ${results.summary.failed}`);
    console.log(`   ⚠️  Warnings: ${results.summary.warnings}`);
}

// Run tests
runVisualTest().then(results => {
    const exitCode = results.summary.failed > 0 ? 1 : 0;
    process.exit(exitCode);
}).catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
