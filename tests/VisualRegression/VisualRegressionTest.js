import { test, expect } from '@playwright/test';
import { argosScreenshot } from '@argos-ci/playwright';
import percySnapshot from '@percy/playwright';

// Visual regression testing for UI consistency
test.describe('Visual Regression Tests', () => {
    let authToken;

    test.beforeAll(async ({ request }) => {
        // Login and get auth token
        const response = await request.post('/api/login', {
            data: {
                email: 'admin@example.com',
                password: 'password'
            }
        });
        const data = await response.json();
        authToken = data.data.token;
    });

    test.beforeEach(async ({ page }) => {
        // Set auth token
        await page.addInitScript((token) => {
            window.localStorage.setItem('auth_token', token);
        }, authToken);
    });

    test('Dashboard visual consistency', async ({ page }) => {
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');

        // Wait for dynamic content
        await page.waitForSelector('.dashboard-stats');
        await page.waitForSelector('.appointment-list');
        
        // Take screenshots at different viewport sizes
        const viewports = [
            { width: 1920, height: 1080, name: 'desktop-full-hd' },
            { width: 1366, height: 768, name: 'desktop-standard' },
            { width: 768, height: 1024, name: 'tablet-portrait' },
            { width: 1024, height: 768, name: 'tablet-landscape' },
            { width: 375, height: 812, name: 'mobile-iphone-x' },
            { width: 414, height: 896, name: 'mobile-iphone-11' }
        ];

        for (const viewport of viewports) {
            await page.setViewportSize({ width: viewport.width, height: viewport.height });
            
            // Percy snapshot
            await percySnapshot(page, `Dashboard - ${viewport.name}`);
            
            // Argos screenshot
            await argosScreenshot(page, `dashboard-${viewport.name}`);
            
            // Playwright screenshot for local comparison
            await page.screenshot({
                path: `./screenshots/dashboard-${viewport.name}.png`,
                fullPage: true
            });
        }
    });

    test('Appointment form interactions', async ({ page }) => {
        await page.goto('/appointments/new');
        
        // Initial state
        await percySnapshot(page, 'Appointment Form - Initial');
        
        // Fill customer field
        await page.fill('#customer-search', 'John');
        await page.waitForSelector('.customer-suggestions');
        await percySnapshot(page, 'Appointment Form - Customer Search');
        
        // Select customer
        await page.click('.customer-suggestions li:first-child');
        
        // Select service
        await page.click('#service-select');
        await page.waitForSelector('.service-dropdown');
        await percySnapshot(page, 'Appointment Form - Service Selection');
        
        // Date picker
        await page.click('#appointment-date');
        await page.waitForSelector('.date-picker-popup');
        await percySnapshot(page, 'Appointment Form - Date Picker');
        
        // Time slot selection
        await page.click('.date-picker-popup [data-date="15"]');
        await page.waitForSelector('.time-slots');
        await percySnapshot(page, 'Appointment Form - Time Slots');
        
        // Validation errors
        await page.click('#submit-appointment');
        await page.waitForSelector('.validation-errors');
        await percySnapshot(page, 'Appointment Form - Validation Errors');
    });

    test('Dark mode consistency', async ({ page }) => {
        // Test all major pages in dark mode
        const pages = [
            '/dashboard',
            '/appointments',
            '/customers',
            '/settings',
            '/reports'
        ];

        // Enable dark mode
        await page.goto('/settings');
        await page.click('#theme-toggle');
        await page.waitForSelector('body.dark-mode');

        for (const pagePath of pages) {
            await page.goto(pagePath);
            await page.waitForLoadState('networkidle');
            
            await percySnapshot(page, `Dark Mode - ${pagePath}`);
            
            // Check contrast ratios
            const contrastIssues = await page.evaluate(() => {
                const elements = document.querySelectorAll('*');
                const issues = [];
                
                elements.forEach(el => {
                    const style = window.getComputedStyle(el);
                    const bg = style.backgroundColor;
                    const fg = style.color;
                    
                    // Simple contrast check (would use proper WCAG algorithm in production)
                    if (bg !== 'rgba(0, 0, 0, 0)' && fg !== 'rgba(0, 0, 0, 0)') {
                        const bgLuminance = getLuminance(bg);
                        const fgLuminance = getLuminance(fg);
                        const contrast = (Math.max(bgLuminance, fgLuminance) + 0.05) / 
                                       (Math.min(bgLuminance, fgLuminance) + 0.05);
                        
                        if (contrast < 4.5) {
                            issues.push({
                                element: el.tagName,
                                class: el.className,
                                contrast: contrast
                            });
                        }
                    }
                });
                
                function getLuminance(color) {
                    // Simplified luminance calculation
                    const rgb = color.match(/\d+/g);
                    if (!rgb) return 0;
                    return (0.299 * rgb[0] + 0.587 * rgb[1] + 0.114 * rgb[2]) / 255;
                }
                
                return issues;
            });
            
            expect(contrastIssues.length).toBe(0);
        }
    });

    test('Component visual states', async ({ page }) => {
        await page.goto('/style-guide');
        
        // Button states
        const buttons = await page.$$('.btn-showcase button');
        for (let i = 0; i < buttons.length; i++) {
            const button = buttons[i];
            
            // Normal state
            await percySnapshot(page, `Button ${i} - Normal`);
            
            // Hover state
            await button.hover();
            await percySnapshot(page, `Button ${i} - Hover`);
            
            // Focus state
            await button.focus();
            await percySnapshot(page, `Button ${i} - Focus`);
            
            // Active state
            await page.mouse.down();
            await percySnapshot(page, `Button ${i} - Active`);
            await page.mouse.up();
        }
        
        // Form inputs
        const inputs = await page.$$('input, select, textarea');
        for (const input of inputs) {
            const inputType = await input.getAttribute('type') || 'text';
            
            // Empty state
            await percySnapshot(page, `Input ${inputType} - Empty`);
            
            // Focused state
            await input.focus();
            await percySnapshot(page, `Input ${inputType} - Focused`);
            
            // Filled state
            await input.fill('Test content');
            await percySnapshot(page, `Input ${inputType} - Filled`);
            
            // Error state
            await input.evaluate(el => el.classList.add('error'));
            await percySnapshot(page, `Input ${inputType} - Error`);
        }
    });

    test('Loading states and animations', async ({ page }) => {
        // Intercept API calls to simulate loading
        await page.route('**/api/**', async route => {
            await page.waitForTimeout(2000); // Simulate slow response
            await route.continue();
        });
        
        await page.goto('/appointments');
        
        // Capture loading skeleton
        await percySnapshot(page, 'Appointments - Loading State');
        
        // Wait for content
        await page.waitForSelector('.appointment-list', { timeout: 5000 });
        await percySnapshot(page, 'Appointments - Loaded State');
        
        // Test transition animations
        await page.click('.appointment-card:first-child');
        
        // Capture mid-animation (multiple frames)
        for (let i = 0; i < 5; i++) {
            await page.waitForTimeout(100);
            await page.screenshot({
                path: `./screenshots/animation-frame-${i}.png`
            });
        }
    });

    test('Print styles', async ({ page }) => {
        await page.goto('/appointments');
        
        // Emulate print media
        await page.emulateMedia({ media: 'print' });
        
        await percySnapshot(page, 'Appointments - Print View');
        
        // Generate PDF
        await page.pdf({
            path: './screenshots/appointments-print.pdf',
            format: 'A4'
        });
        
        // Test invoice print
        await page.goto('/invoices/123');
        await page.emulateMedia({ media: 'print' });
        await percySnapshot(page, 'Invoice - Print View');
    });

    test('Accessibility visual indicators', async ({ page }) => {
        await page.goto('/dashboard');
        
        // Tab through interface
        const tabSequence = [];
        for (let i = 0; i < 20; i++) {
            await page.keyboard.press('Tab');
            
            const focusedElement = await page.evaluate(() => {
                const el = document.activeElement;
                return {
                    tag: el.tagName,
                    class: el.className,
                    text: el.textContent?.substring(0, 50),
                    hasVisibleFocus: window.getComputedStyle(el).outline !== 'none'
                };
            });
            
            tabSequence.push(focusedElement);
            
            // Screenshot each focused state
            await page.screenshot({
                path: `./screenshots/focus-state-${i}.png`
            });
        }
        
        // Verify all interactive elements have visible focus
        const missingFocus = tabSequence.filter(el => !el.hasVisibleFocus);
        expect(missingFocus.length).toBe(0);
    });

    test('Cross-browser rendering', async ({ browserName, page }) => {
        // Test critical pages across different browsers
        const criticalPages = [
            '/dashboard',
            '/appointments/new',
            '/customers'
        ];
        
        for (const pagePath of criticalPages) {
            await page.goto(pagePath);
            await page.waitForLoadState('networkidle');
            
            await page.screenshot({
                path: `./screenshots/${browserName}-${pagePath.replace(/\//g, '-')}.png`,
                fullPage: true
            });
            
            // Browser-specific checks
            if (browserName === 'webkit') {
                // Safari-specific rendering issues
                const blurryText = await page.evaluate(() => {
                    const elements = document.querySelectorAll('*');
                    return Array.from(elements).some(el => {
                        const style = window.getComputedStyle(el);
                        return style.webkitFontSmoothing === 'none';
                    });
                });
                expect(blurryText).toBe(false);
            }
        }
    });

    test('Dynamic content visual stability', async ({ page }) => {
        await page.goto('/dashboard');
        
        // Take initial screenshot
        const screenshot1 = await page.screenshot({ fullPage: true });
        
        // Trigger data refresh
        await page.click('#refresh-dashboard');
        await page.waitForLoadState('networkidle');
        
        // Take second screenshot
        const screenshot2 = await page.screenshot({ fullPage: true });
        
        // Use image comparison library
        const pixelmatch = require('pixelmatch');
        const PNG = require('pngjs').PNG;
        
        const img1 = PNG.sync.read(screenshot1);
        const img2 = PNG.sync.read(screenshot2);
        const { width, height } = img1;
        const diff = new PNG({ width, height });
        
        const numDiffPixels = pixelmatch(
            img1.data,
            img2.data,
            diff.data,
            width,
            height,
            { threshold: 0.1 }
        );
        
        // Allow for minor differences (timestamps, etc)
        const diffPercentage = (numDiffPixels / (width * height)) * 100;
        expect(diffPercentage).toBeLessThan(5); // Less than 5% difference
    });
});