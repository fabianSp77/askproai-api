const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Helper for delays (since waitForTimeout might not be available)
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

/**
 * Debug Booking Slots Test
 *
 * PURPOSE: Investigate why appointment slots aren't visible in browser
 * despite backend confirming 147 slots available
 *
 * EXPECTED: Service #45 "30 Minuten Beratung" should show slots for Wed-Sun
 * ACTUAL: User reports no slots visible
 */

const CONFIG = {
    baseUrl: 'https://api.askproai.de',
    loginUrl: 'https://api.askproai.de/admin/login',
    createAppointmentUrl: 'https://api.askproai.de/admin/appointments/create',
    screenshotDir: path.join(__dirname, 'screenshots', 'slot-debug'),
    credentials: {
        email: process.env.ADMIN_EMAIL || 'test-puppeteer@askproai.de',
        password: process.env.ADMIN_PASSWORD || 'TestPass123!'
    },
    serviceId: '45',
    serviceName: '30 Minuten Beratung',
    eventTypeId: '2563193'
};

// Ensure screenshot directory exists
if (!fs.existsSync(CONFIG.screenshotDir)) {
    fs.mkdirSync(CONFIG.screenshotDir, { recursive: true });
}

/**
 * Wait for Livewire to finish loading
 */
async function waitForLivewire(page, timeout = 10000) {
    console.log('⏳ Waiting for Livewire to initialize...');

    try {
        await page.waitForFunction(
            () => window.Livewire !== undefined,
            { timeout }
        );

        // Wait for any pending requests
        await page.waitForFunction(
            () => {
                if (!window.Livewire) return false;
                // Check if Livewire is idle (no pending requests)
                return !document.querySelector('[wire\\:loading]');
            },
            { timeout }
        );

        console.log('✅ Livewire initialized and idle');
        return true;
    } catch (error) {
        console.log('⚠️  Livewire wait timeout - continuing anyway');
        return false;
    }
}

/**
 * Extract console errors from page
 */
function setupConsoleCapture(page) {
    const consoleMessages = [];

    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        consoleMessages.push({ type, text });

        if (type === 'error' || type === 'warning') {
            console.log(`[BROWSER ${type.toUpperCase()}]:`, text);
        }
    });

    page.on('pageerror', error => {
        consoleMessages.push({ type: 'pageerror', text: error.message });
        console.log('[PAGE ERROR]:', error.message);
    });

    page.on('requestfailed', request => {
        consoleMessages.push({
            type: 'requestfailed',
            text: `${request.url()} - ${request.failure().errorText}`
        });
        console.log('[REQUEST FAILED]:', request.url(), request.failure().errorText);
    });

    return consoleMessages;
}

/**
 * Login to admin panel
 */
async function login(page) {
    console.log('\n🔐 Logging in...');

    try {
        await page.goto(CONFIG.loginUrl, { waitUntil: 'networkidle2', timeout: 60000 });
        await page.screenshot({
            path: path.join(CONFIG.screenshotDir, '01-login-page.png'),
            fullPage: true
        });

        // Wait for login form
        await page.waitForSelector('input[type="email"]', { timeout: 10000 });

        // Check if already logged in
        const currentUrl = page.url();
        if (currentUrl.includes('/admin') && !currentUrl.includes('/login')) {
            console.log('✅ Already logged in');
            return;
        }

        // Fill login form
        console.log(`Using credentials: ${CONFIG.credentials.email}`);
        await page.type('input[type="email"]', CONFIG.credentials.email);

        if (!CONFIG.credentials.password) {
            console.log('⚠️  No password provided - check ADMIN_PASSWORD env var');
            throw new Error('Password required for login');
        }

        await page.type('input[type="password"]', CONFIG.credentials.password);

        // Submit and wait for navigation
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }),
            page.click('button[type="submit"]')
        ]);

        // Verify login success
        await delay(2000);
        const finalUrl = page.url();

        if (finalUrl.includes('/login')) {
            await page.screenshot({
                path: path.join(CONFIG.screenshotDir, '01-login-FAILED.png'),
                fullPage: true
            });
            throw new Error('Login failed - still on login page');
        }

        console.log('✅ Login successful');
    } catch (error) {
        console.error('❌ Login error:', error.message);
        throw error;
    }
}

/**
 * Navigate to appointment creation page
 */
async function navigateToCreate(page) {
    console.log('\n📍 Navigating to appointment creation...');

    await page.goto(CONFIG.createAppointmentUrl, {
        waitUntil: 'networkidle2',
        timeout: 30000
    });

    // Wait for page to settle
    await delay(2000);

    await page.screenshot({
        path: path.join(CONFIG.screenshotDir, '02-create-page-initial.png'),
        fullPage: true
    });

    console.log('✅ Page loaded');
}

/**
 * Inspect service dropdown
 */
async function inspectServiceDropdown(page) {
    console.log('\n🔍 Inspecting service dropdown...');

    const dropdownInfo = await page.evaluate(() => {
        // Look for various possible selectors
        const selectors = [
            'select[wire\\:model="data.service_id"]',
            'select[name="service_id"]',
            '[data-field="service_id"]',
            'div[wire\\:key*="service"]',
            '.fi-fo-select[data-field-wrapper]'
        ];

        const results = {};

        selectors.forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                results[selector] = {
                    found: true,
                    tagName: element.tagName,
                    html: element.outerHTML.substring(0, 500),
                    visible: element.offsetParent !== null,
                    options: element.tagName === 'SELECT' ?
                        Array.from(element.options).map(opt => ({
                            value: opt.value,
                            text: opt.text
                        })) : null
                };
            } else {
                results[selector] = { found: false };
            }
        });

        return results;
    });

    console.log('Service dropdown inspection:', JSON.stringify(dropdownInfo, null, 2));

    return dropdownInfo;
}

/**
 * Select service - Updated to handle radio button UI
 */
async function selectService(page) {
    console.log('\n🎯 Selecting service...');

    // First, dump the entire page HTML to see structure
    const pageStructure = await page.evaluate(() => {
        return {
            allRadios: document.querySelectorAll('input[type="radio"]').length,
            allInputs: document.querySelectorAll('input').length,
            serviceSection: document.querySelector('h3, h2, .text-lg')?.textContent || 'Not found',
            bodySnippet: document.body.innerHTML.substring(0, 5000) // First 5KB
        };
    });

    console.log('Page structure:', JSON.stringify(pageStructure, null, 2));

    // Save HTML to file for inspection
    const fullHtml = await page.content();
    fs.writeFileSync(
        path.join(CONFIG.screenshotDir, 'page-html.html'),
        fullHtml
    );
    console.log('Full HTML saved to page-html.html');

    // First, inspect what services are available
    const availableServices = await page.evaluate(() => {
        const services = [];

        // Try multiple selectors
        const selectors = [
            'input[type="radio"][wire\\:model*="service"]',
            'input[type="radio"]',
            'input[wire\\:model*="service"]',
            '[role="radio"]'
        ];

        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            if (elements.length > 0) {
                console.log(`Found ${elements.length} elements with selector: ${selector}`);
                elements.forEach(el => {
                    const label = el.closest('label') || el.parentElement;
                    services.push({
                        selector,
                        value: el.value || el.getAttribute('value'),
                        name: label ? label.textContent.trim().substring(0, 100) : 'Unknown',
                        checked: el.checked || el.getAttribute('aria-checked') === 'true'
                    });
                });
            }
        });

        return services;
    });

    console.log(`Found ${availableServices.length} services:`, JSON.stringify(availableServices, null, 2));

    // Try to find and select the target service
    const selectionResult = await page.evaluate((serviceName) => {
        // Look for radio buttons
        const radioButtons = document.querySelectorAll('input[type="radio"][wire\\:model*="service"]');

        for (const radio of radioButtons) {
            const label = radio.closest('label') || radio.nextElementSibling;
            const labelText = label ? label.textContent.trim() : '';

            // Check if this is our target service or contains "Beratung" or is already checked
            if (labelText.includes(serviceName) ||
                labelText.includes('Beratung') ||
                labelText.includes('30 Minuten')) {

                // Click the radio button
                radio.click();
                radio.dispatchEvent(new Event('change', { bubbles: true }));

                return {
                    success: true,
                    method: 'radio-button',
                    selectedValue: radio.value,
                    selectedText: labelText
                };
            }
        }

        // If not found, just use the first one or already selected
        const checkedRadio = document.querySelector('input[type="radio"][wire\\:model*="service"]:checked');
        if (checkedRadio) {
            const label = checkedRadio.closest('label') || checkedRadio.nextElementSibling;
            return {
                success: true,
                method: 'already-selected',
                selectedValue: checkedRadio.value,
                selectedText: label ? label.textContent.trim() : 'Unknown',
                note: 'Using already selected service'
            };
        }

        return {
            success: false,
            error: 'Could not find any service radio buttons',
            availableServices: Array.from(radioButtons).length
        };
    }, CONFIG.serviceName);

    console.log('Selection result:', JSON.stringify(selectionResult, null, 2));

    if (!selectionResult.success) {
        throw new Error('Failed to select service');
    }

    // Wait for Livewire to process the change
    await delay(3000);
    await waitForLivewire(page);

    await page.screenshot({
        path: path.join(CONFIG.screenshotDir, '03-after-service-selection.png'),
        fullPage: true
    });

    console.log('✅ Service selected:', selectionResult.selectedText);

    return selectionResult;
}

/**
 * Inspect calendar/slot availability
 */
async function inspectSlots(page) {
    console.log('\n📅 Inspecting slot availability...');

    const slotInfo = await page.evaluate(() => {
        const result = {
            livewireComponents: [],
            weekData: null,
            visibleSlots: [],
            calendarElements: {},
            errors: []
        };

        // Check for Livewire components
        const livewireElements = document.querySelectorAll('[wire\\:id]');
        livewireElements.forEach(el => {
            const wireId = el.getAttribute('wire:id');
            const wireName = el.getAttribute('wire:key') || 'unknown';
            result.livewireComponents.push({
                wireId,
                wireName,
                tag: el.tagName,
                classes: el.className,
                visible: el.offsetParent !== null
            });
        });

        // Check for week picker component
        const weekPicker = document.querySelector('[wire\\:key*="week"]') ||
                          document.querySelector('.week-picker') ||
                          document.querySelector('[x-data*="week"]');

        if (weekPicker) {
            result.calendarElements.weekPicker = {
                found: true,
                visible: weekPicker.offsetParent !== null,
                html: weekPicker.outerHTML.substring(0, 1000)
            };
        }

        // Check for Alpine.js data
        if (window.Alpine) {
            const alpineElements = document.querySelectorAll('[x-data]');
            alpineElements.forEach(el => {
                const xData = el.getAttribute('x-data');
                if (xData && xData.includes('week')) {
                    result.calendarElements.alpineWeekComponent = {
                        found: true,
                        xData: xData.substring(0, 200),
                        visible: el.offsetParent !== null
                    };
                }
            });
        }

        // Check for visible slot buttons/elements
        const slotSelectors = [
            '.slot-button',
            '[data-slot]',
            'button[wire\\:click*="slot"]',
            '.time-slot',
            '.available-slot'
        ];

        slotSelectors.forEach(selector => {
            const slots = document.querySelectorAll(selector);
            if (slots.length > 0) {
                result.visibleSlots.push({
                    selector,
                    count: slots.length,
                    firstSlot: {
                        text: slots[0].textContent.trim(),
                        visible: slots[0].offsetParent !== null,
                        attributes: Array.from(slots[0].attributes).map(attr => ({
                            name: attr.name,
                            value: attr.value.substring(0, 100)
                        }))
                    }
                });
            }
        });

        // Check for error messages
        const errorSelectors = [
            '.fi-fo-field-wrp-error-message',
            '[role="alert"]',
            '.error',
            '.text-danger'
        ];

        errorSelectors.forEach(selector => {
            const errors = document.querySelectorAll(selector);
            errors.forEach(error => {
                if (error.offsetParent !== null) { // Only visible errors
                    result.errors.push({
                        selector,
                        text: error.textContent.trim()
                    });
                }
            });
        });

        // Try to access Livewire component data
        if (window.Livewire) {
            try {
                const components = window.Livewire.all();
                components.forEach(component => {
                    if (component.get && typeof component.get === 'function') {
                        try {
                            const data = component.get('weekData');
                            if (data) {
                                result.weekData = data;
                            }
                        } catch (e) {
                            // Component doesn't have weekData
                        }
                    }
                });
            } catch (e) {
                result.errors.push({
                    type: 'livewire-access',
                    message: e.message
                });
            }
        }

        return result;
    });

    console.log('\n=== SLOT INSPECTION RESULTS ===');
    console.log('Livewire components found:', slotInfo.livewireComponents.length);
    console.log('Week data:', slotInfo.weekData ? 'Found' : 'Not found');
    console.log('Visible slots:', slotInfo.visibleSlots.length);
    console.log('Calendar elements:', Object.keys(slotInfo.calendarElements));
    console.log('Errors:', slotInfo.errors.length);

    // Write detailed results to file
    fs.writeFileSync(
        path.join(CONFIG.screenshotDir, 'slot-inspection-results.json'),
        JSON.stringify(slotInfo, null, 2)
    );

    return slotInfo;
}

/**
 * Check network requests
 */
async function inspectNetworkRequests(page) {
    console.log('\n🌐 Monitoring network requests...');

    const requests = [];

    page.on('request', request => {
        if (request.url().includes('livewire') ||
            request.url().includes('availability') ||
            request.url().includes('slots')) {
            requests.push({
                type: 'request',
                url: request.url(),
                method: request.method(),
                postData: request.postData()
            });
        }
    });

    page.on('response', async response => {
        if (response.url().includes('livewire') ||
            response.url().includes('availability') ||
            response.url().includes('slots')) {
            try {
                const body = await response.text();
                requests.push({
                    type: 'response',
                    url: response.url(),
                    status: response.status(),
                    body: body.substring(0, 5000) // First 5KB
                });
            } catch (e) {
                requests.push({
                    type: 'response',
                    url: response.url(),
                    status: response.status(),
                    error: 'Could not read body'
                });
            }
        }
    });

    return requests;
}

/**
 * Main test execution
 */
async function runTest() {
    console.log('🚀 Starting Booking Slots Debug Test\n');
    console.log('Target:', CONFIG.createAppointmentUrl);
    console.log('Service:', CONFIG.serviceName, `(ID: ${CONFIG.serviceId})`);
    console.log('Expected:', '147 slots available (Wed-Sun)');
    console.log('Screenshots:', CONFIG.screenshotDir);

    const browser = await puppeteer.launch({
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ],
        defaultViewport: {
            width: 1920,
            height: 1080
        }
    });

    const page = await browser.newPage();

    // Setup console capture
    const consoleMessages = setupConsoleCapture(page);

    try {
        // Network monitoring
        const networkRequests = await inspectNetworkRequests(page);

        // Step 1: Login
        await login(page);

        // Step 2: Navigate to create page
        await navigateToCreate(page);

        // Step 3: Wait for Livewire
        await waitForLivewire(page);

        // Step 4: Inspect service dropdown
        const dropdownInfo = await inspectServiceDropdown(page);

        // Step 5: Select service
        const selectionResult = await selectService(page);

        // Step 6: Inspect slots after selection
        const slotInfo = await inspectSlots(page);

        // Step 7: Take final screenshot
        await page.screenshot({
            path: path.join(CONFIG.screenshotDir, '04-final-state.png'),
            fullPage: true
        });

        // Generate report
        const report = {
            testRun: new Date().toISOString(),
            config: CONFIG,
            results: {
                login: 'SUCCESS',
                navigation: 'SUCCESS',
                serviceSelection: selectionResult,
                slotInspection: slotInfo,
                dropdownInfo: dropdownInfo
            },
            consoleMessages: consoleMessages.filter(msg =>
                msg.type === 'error' || msg.type === 'warning' || msg.type === 'pageerror'
            ),
            networkRequests: networkRequests.slice(-20) // Last 20 requests
        };

        // Write report
        fs.writeFileSync(
            path.join(CONFIG.screenshotDir, 'test-report.json'),
            JSON.stringify(report, null, 2)
        );

        console.log('\n✅ Test completed successfully');
        console.log('📊 Report saved to:', path.join(CONFIG.screenshotDir, 'test-report.json'));

        // Analysis
        console.log('\n=== ANALYSIS ===');

        if (slotInfo.visibleSlots.length === 0) {
            console.log('❌ NO SLOTS VISIBLE IN DOM');
            console.log('Possible causes:');
            console.log('  1. Week picker not rendered');
            console.log('  2. Livewire not loading weekData');
            console.log('  3. JavaScript error preventing rendering');
            console.log('  4. CSS hiding slots (check visibility)');
            console.log('  5. Wrong component mounted');
        } else {
            console.log(`✅ Found ${slotInfo.visibleSlots.length} slot elements`);
        }

        if (slotInfo.errors.length > 0) {
            console.log(`\n⚠️  Errors found: ${slotInfo.errors.length}`);
            slotInfo.errors.forEach(err => {
                console.log(`  - ${err.text || err.message}`);
            });
        }

        if (!slotInfo.weekData) {
            console.log('\n⚠️  weekData not accessible from Livewire component');
            console.log('This suggests the component might not be properly initialized');
        }

        if (consoleMessages.filter(m => m.type === 'error').length > 0) {
            console.log('\n❌ Browser console errors detected - check test-report.json');
        }

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);

        // Take error screenshot
        await page.screenshot({
            path: path.join(CONFIG.screenshotDir, 'ERROR-state.png'),
            fullPage: true
        });

        throw error;
    } finally {
        await browser.close();
    }
}

// Execute test
runTest()
    .then(() => {
        console.log('\n✅ All tests completed');
        process.exit(0);
    })
    .catch(error => {
        console.error('\n❌ Test suite failed:', error);
        process.exit(1);
    });
