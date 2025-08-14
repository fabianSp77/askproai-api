import puppeteer from 'puppeteer';
import fs from 'fs';

async function extendedAdminPortalTest() {
    let browser;
    try {
        console.log('Starting extended admin portal tests...');
        
        browser = await puppeteer.launch({
            headless: true,
            executablePath: '/usr/bin/chromium',
            args: [
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-extensions'
            ]
        });

        const page = await browser.newPage();

        // Enable console and error logging
        const consoleMessages = [];
        const errors = [];
        const requests = [];

        page.on('console', msg => {
            consoleMessages.push({
                type: msg.type(),
                text: msg.text()
            });
        });

        page.on('pageerror', err => {
            errors.push({
                message: err.message,
                stack: err.stack
            });
        });

        page.on('request', request => {
            requests.push({
                url: request.url(),
                method: request.method(),
                headers: request.headers(),
                resourceType: request.resourceType()
            });
        });

        const results = {
            timestamp: new Date().toISOString(),
            tests: []
        };

        // Test 1: Navigate and analyze login page
        console.log('1. Analyzing login page in detail...');
        await page.goto('https://api.askproai.de/admin/login', {
            waitUntil: 'networkidle2',
            timeout: 30000
        });

        // Get page content analysis
        const pageAnalysis = await page.evaluate(() => {
            return {
                title: document.title,
                url: window.location.href,
                doctype: document.doctype ? document.doctype.name : 'None',
                charset: document.charset,
                lang: document.documentElement.lang,
                totalElements: document.querySelectorAll('*').length,
                
                // Meta tags analysis
                metaTags: Array.from(document.querySelectorAll('meta')).map(meta => ({
                    name: meta.name || meta.property || meta.httpEquiv,
                    content: meta.content
                })),
                
                // CSS analysis
                stylesheets: Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(link => link.href),
                
                // JavaScript analysis
                scripts: Array.from(document.querySelectorAll('script')).map(script => ({
                    src: script.src,
                    inline: !script.src,
                    type: script.type
                })),
                
                // Form detailed analysis
                forms: Array.from(document.querySelectorAll('form')).map(form => ({
                    action: form.action,
                    method: form.method,
                    enctype: form.enctype,
                    elements: Array.from(form.elements).map(el => ({
                        tag: el.tagName.toLowerCase(),
                        type: el.type,
                        name: el.name,
                        id: el.id,
                        required: el.required,
                        placeholder: el.placeholder,
                        value: el.type === 'password' ? '[HIDDEN]' : el.value
                    }))
                })),
                
                // Security features
                security: {
                    hasHttps: window.location.protocol === 'https:',
                    hasCsrfToken: !!document.querySelector('meta[name="csrf-token"]') || 
                                  !!document.querySelector('input[name="_token"]'),
                    hasSecureContext: window.isSecureContext,
                    hasServiceWorker: 'serviceWorker' in navigator
                }
            };
        });

        results.tests.push({
            test: 'Detailed page analysis',
            status: 'SUCCESS',
            analysis: pageAnalysis
        });

        // Test 2: Test login form validation
        console.log('2. Testing form validation...');
        try {
            // Try to submit form without credentials
            await page.click('button[type="submit"], input[type="submit"]');
            await new Promise(resolve => setTimeout(resolve, 2000));

            const validationMessages = await page.evaluate(() => {
                const messages = [];
                
                // Check for HTML5 validation messages
                document.querySelectorAll('input').forEach(input => {
                    if (!input.validity.valid) {
                        messages.push({
                            field: input.name || input.id,
                            message: input.validationMessage
                        });
                    }
                });

                // Check for custom validation messages
                document.querySelectorAll('.error, .invalid, .validation-error, [role="alert"]').forEach(el => {
                    if (el.textContent.trim()) {
                        messages.push({
                            type: 'custom',
                            message: el.textContent.trim()
                        });
                    }
                });

                return messages;
            });

            results.tests.push({
                test: 'Form validation test',
                status: 'SUCCESS',
                validation: validationMessages
            });
        } catch (error) {
            results.tests.push({
                test: 'Form validation test',
                status: 'FAILED',
                error: error.message
            });
        }

        // Test 3: Test with invalid credentials
        console.log('3. Testing with invalid credentials...');
        try {
            await page.type('#data\\.email', 'test@example.com');
            await page.type('#data\\.password', 'invalidpassword');
            await page.click('button[type="submit"]');
            
            await new Promise(resolve => setTimeout(resolve, 3000));
            
            const currentUrl = page.url();
            const errorMessages = await page.evaluate(() => {
                const messages = [];
                document.querySelectorAll('.error, .alert-danger, .invalid, [role="alert"]').forEach(el => {
                    if (el.textContent.trim()) {
                        messages.push(el.textContent.trim());
                    }
                });
                return messages;
            });

            results.tests.push({
                test: 'Invalid credentials test',
                status: 'SUCCESS',
                currentUrl: currentUrl,
                errorMessages: errorMessages,
                redirected: currentUrl !== 'https://api.askproai.de/admin/login'
            });
        } catch (error) {
            results.tests.push({
                test: 'Invalid credentials test',
                status: 'FAILED',
                error: error.message
            });
        }

        // Test 4: Mobile responsiveness detailed test
        console.log('4. Testing mobile responsiveness...');
        await page.setViewport({ width: 375, height: 667 });
        await new Promise(resolve => setTimeout(resolve, 1000));

        const mobileAnalysis = await page.evaluate(() => {
            return {
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight
                },
                
                // Check for mobile-specific features
                touchEvents: 'ontouchstart' in window,
                devicePixelRatio: window.devicePixelRatio,
                
                // Layout analysis
                layout: {
                    overflowElements: Array.from(document.querySelectorAll('*')).filter(el => {
                        const style = window.getComputedStyle(el);
                        return parseInt(style.width) > window.innerWidth;
                    }).length,
                    
                    // Check form usability on mobile
                    formElements: Array.from(document.querySelectorAll('input, button')).map(el => {
                        const rect = el.getBoundingClientRect();
                        return {
                            element: el.tagName + (el.type ? `[${el.type}]` : ''),
                            size: { width: rect.width, height: rect.height },
                            touchFriendly: rect.height >= 44 && rect.width >= 44
                        };
                    })
                }
            };
        });

        results.tests.push({
            test: 'Mobile responsiveness analysis',
            status: 'SUCCESS',
            mobile: mobileAnalysis
        });

        // Test 5: Performance and loading analysis
        console.log('5. Analyzing performance...');
        await page.goto('https://api.askproai.de/admin/login', {
            waitUntil: 'networkidle2'
        });

        const performanceMetrics = await page.evaluate(() => {
            const perf = performance.getEntriesByType('navigation')[0];
            const resources = performance.getEntriesByType('resource');
            
            return {
                navigation: {
                    domainLookup: perf.domainLookupEnd - perf.domainLookupStart,
                    connection: perf.connectEnd - perf.connectStart,
                    request: perf.responseStart - perf.requestStart,
                    response: perf.responseEnd - perf.responseStart,
                    domLoading: perf.domContentLoadedEventEnd - perf.domLoading,
                    totalTime: perf.loadEventEnd - perf.navigationStart
                },
                
                resources: {
                    total: resources.length,
                    byType: resources.reduce((acc, resource) => {
                        acc[resource.initiatorType] = (acc[resource.initiatorType] || 0) + 1;
                        return acc;
                    }, {}),
                    slowest: resources
                        .filter(r => r.duration > 100)
                        .map(r => ({ name: r.name, duration: r.duration }))
                        .sort((a, b) => b.duration - a.duration)
                        .slice(0, 10)
                }
            };
        });

        results.tests.push({
            test: 'Performance analysis',
            status: 'SUCCESS',
            performance: performanceMetrics
        });

        // Compile comprehensive results
        results.summary = {
            totalTests: results.tests.length,
            successful: results.tests.filter(t => t.status === 'SUCCESS').length,
            failed: results.tests.filter(t => t.status === 'FAILED').length,
            consoleMessages: consoleMessages,
            errors: errors,
            requestCount: requests.length,
            uniqueDomains: [...new Set(requests.map(r => new URL(r.url).hostname))],
            jsErrors: errors.length,
            hasNoJSErrors: errors.length === 0
        };

        // Save results
        const resultsPath = '/var/www/api-gateway/admin_portal_extended_results.json';
        fs.writeFileSync(resultsPath, JSON.stringify(results, null, 2));
        console.log(`Extended test results saved to: ${resultsPath}`);

        return results;

    } catch (error) {
        console.error('Extended test failed:', error);
        return { error: error.message };
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

// Run the extended tests
extendedAdminPortalTest().then(results => {
    console.log('\n=== EXTENDED TEST SUMMARY ===');
    if (results.summary) {
        console.log(`Total tests: ${results.summary.totalTests}`);
        console.log(`Successful: ${results.summary.successful}`);
        console.log(`Failed: ${results.summary.failed}`);
        console.log(`JavaScript errors: ${results.summary.jsErrors}`);
        console.log(`Requests made: ${results.summary.requestCount}`);
        console.log(`Domains contacted: ${results.summary.uniqueDomains.length}`);
    }
    console.log('\nExtended tests completed!');
    process.exit(0);
}).catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});