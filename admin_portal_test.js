import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';

async function testAdminPortal() {
    let browser;
    try {
        console.log('Starting browser automation tests...');
        
        browser = await puppeteer.launch({
            headless: true,
            executablePath: '/usr/bin/chromium',
            args: [
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-extensions',
                '--disable-background-timer-throttling',
                '--disable-backgrounding-occluded-windows',
                '--disable-renderer-backgrounding'
            ]
        });

        const page = await browser.newPage();

        // Enable console logging
        page.on('console', msg => {
            console.log(`Console ${msg.type()}: ${msg.text()}`);
        });

        // Enable error logging
        page.on('pageerror', err => {
            console.error('Page error:', err.message);
        });

        // Enable request failure logging
        page.on('requestfailed', request => {
            console.error(`Request failed: ${request.url()} - ${request.failure().errorText}`);
        });

        const results = {
            timestamp: new Date().toISOString(),
            tests: []
        };

        // Test 1: Navigate to login page
        console.log('\n1. Navigating to login page...');
        try {
            await page.goto('https://api.askproai.de/admin/login', {
                waitUntil: 'networkidle2',
                timeout: 30000
            });
            
            const title = await page.title();
            console.log(`Page title: ${title}`);
            
            results.tests.push({
                test: 'Navigation to login page',
                status: 'SUCCESS',
                title: title,
                url: page.url()
            });
        } catch (error) {
            console.error('Navigation failed:', error.message);
            results.tests.push({
                test: 'Navigation to login page',
                status: 'FAILED',
                error: error.message
            });
            return results;
        }

        // Test 2: Test different viewport sizes and take screenshots
        console.log('\n2. Testing different viewport sizes...');
        const viewports = [
            { name: 'Desktop', width: 1920, height: 1080 },
            { name: 'Tablet', width: 768, height: 1024 },
            { name: 'Mobile', width: 375, height: 667 }
        ];

        for (const viewport of viewports) {
            try {
                console.log(`Testing ${viewport.name} viewport (${viewport.width}x${viewport.height})`);
                await page.setViewport({
                    width: viewport.width,
                    height: viewport.height
                });

                // Wait for page to adjust
                await new Promise(resolve => setTimeout(resolve, 1000));

                // Take screenshot
                const screenshotPath = `/var/www/api-gateway/public/screenshots/admin-login-${viewport.name.toLowerCase()}.png`;
                await page.screenshot({
                    path: screenshotPath,
                    fullPage: true
                });

                console.log(`Screenshot saved: ${screenshotPath}`);

                results.tests.push({
                    test: `${viewport.name} viewport test`,
                    status: 'SUCCESS',
                    viewport: viewport,
                    screenshot: screenshotPath
                });
            } catch (error) {
                console.error(`Viewport test failed for ${viewport.name}:`, error.message);
                results.tests.push({
                    test: `${viewport.name} viewport test`,
                    status: 'FAILED',
                    error: error.message
                });
            }
        }

        // Test 3: Accessibility audit
        console.log('\n3. Running accessibility checks...');
        try {
            await page.setViewport({ width: 1920, height: 1080 });
            
            // Check for basic accessibility features
            const accessibilityChecks = await page.evaluate(() => {
                const checks = {};
                
                // Check for form labels
                const inputs = document.querySelectorAll('input');
                const inputsWithLabels = Array.from(inputs).filter(input => {
                    return document.querySelector(`label[for="${input.id}"]`) || 
                           input.closest('label') || 
                           input.getAttribute('aria-label') ||
                           input.getAttribute('aria-labelledby');
                });
                checks.inputLabeling = {
                    total: inputs.length,
                    labeled: inputsWithLabels.length,
                    percentage: inputs.length > 0 ? Math.round((inputsWithLabels.length / inputs.length) * 100) : 0
                };

                // Check for alt text on images
                const images = document.querySelectorAll('img');
                const imagesWithAlt = Array.from(images).filter(img => img.alt && img.alt.trim() !== '');
                checks.imageAltText = {
                    total: images.length,
                    withAlt: imagesWithAlt.length,
                    percentage: images.length > 0 ? Math.round((imagesWithAlt.length / images.length) * 100) : 0
                };

                // Check for heading structure
                const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
                checks.headings = Array.from(headings).map(h => ({
                    level: h.tagName.toLowerCase(),
                    text: h.textContent.trim()
                }));

                // Check for focus indicators
                const focusableElements = document.querySelectorAll('button, input, select, textarea, a[href]');
                checks.focusableElements = focusableElements.length;

                return checks;
            });

            results.tests.push({
                test: 'Accessibility audit',
                status: 'SUCCESS',
                accessibility: accessibilityChecks
            });
        } catch (error) {
            console.error('Accessibility audit failed:', error.message);
            results.tests.push({
                test: 'Accessibility audit',
                status: 'FAILED',
                error: error.message
            });
        }

        // Test 4: Performance metrics
        console.log('\n4. Collecting performance metrics...');
        try {
            const metrics = await page.metrics();
            const performanceMetrics = await page.evaluate(() => {
                const perf = performance.getEntriesByType('navigation')[0];
                return {
                    loadTime: perf.loadEventEnd - perf.fetchStart,
                    domContentLoaded: perf.domContentLoadedEventEnd - perf.fetchStart,
                    firstPaint: perf.responseEnd - perf.fetchStart
                };
            });

            results.tests.push({
                test: 'Performance metrics',
                status: 'SUCCESS',
                metrics: {
                    ...metrics,
                    ...performanceMetrics
                }
            });
        } catch (error) {
            console.error('Performance metrics collection failed:', error.message);
            results.tests.push({
                test: 'Performance metrics',
                status: 'FAILED',
                error: error.message
            });
        }

        // Test 5: Check for login form elements
        console.log('\n5. Analyzing login form...');
        try {
            const formAnalysis = await page.evaluate(() => {
                const form = document.querySelector('form');
                if (!form) return { found: false };

                const inputs = Array.from(form.querySelectorAll('input')).map(input => ({
                    type: input.type,
                    name: input.name,
                    id: input.id,
                    placeholder: input.placeholder,
                    required: input.required
                }));

                const buttons = Array.from(form.querySelectorAll('button, input[type="submit"]')).map(btn => ({
                    type: btn.type,
                    text: btn.textContent || btn.value,
                    id: btn.id
                }));

                return {
                    found: true,
                    action: form.action,
                    method: form.method,
                    inputs: inputs,
                    buttons: buttons
                };
            });

            results.tests.push({
                test: 'Login form analysis',
                status: 'SUCCESS',
                form: formAnalysis
            });
        } catch (error) {
            console.error('Login form analysis failed:', error.message);
            results.tests.push({
                test: 'Login form analysis',
                status: 'FAILED',
                error: error.message
            });
        }

        // Save results to file
        const resultsPath = '/var/www/api-gateway/admin_portal_test_results.json';
        fs.writeFileSync(resultsPath, JSON.stringify(results, null, 2));
        console.log(`\nTest results saved to: ${resultsPath}`);

        return results;

    } catch (error) {
        console.error('Browser automation failed:', error);
        return { error: error.message };
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

// Run the tests
testAdminPortal().then(results => {
    console.log('\n=== TEST SUMMARY ===');
    console.log(`Total tests: ${results.tests ? results.tests.length : 0}`);
    if (results.tests) {
        const successful = results.tests.filter(t => t.status === 'SUCCESS').length;
        const failed = results.tests.filter(t => t.status === 'FAILED').length;
        console.log(`Successful: ${successful}`);
        console.log(`Failed: ${failed}`);
    }
    console.log('\nTests completed!');
    process.exit(0);
}).catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});