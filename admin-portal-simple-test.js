import puppeteer from 'puppeteer';
import fs from 'fs';

async function runSimpleAdminPortalTest() {
    console.log('🚀 AskProAI Admin Portal - Manual Investigation Test');
    
    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: '/usr/bin/chromium',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });
    
    const screenshotDir = '/var/www/api-gateway/admin-portal-test-screenshots';

    try {
        console.log('1. Testing Login Page Structure...');
        
        await page.goto('https://api.askproai.de/admin/login', { waitUntil: 'networkidle2' });
        
        // Analyze the login page structure
        const pageStructure = await page.evaluate(() => {
            const form = document.querySelector('form');
            const inputs = Array.from(document.querySelectorAll('input')).map(input => ({
                type: input.type,
                name: input.name,
                id: input.id,
                placeholder: input.placeholder,
                required: input.required
            }));
            
            const buttons = Array.from(document.querySelectorAll('button')).map(btn => ({
                type: btn.type,
                textContent: btn.textContent.trim(),
                classes: btn.className
            }));
            
            return {
                hasForm: !!form,
                formAction: form?.action,
                formMethod: form?.method,
                inputs: inputs,
                buttons: buttons,
                title: document.title,
                bodyClasses: document.body.className
            };
        });
        
        console.log('Login Page Analysis:', JSON.stringify(pageStructure, null, 2));
        
        // Try direct form submission after filling
        await page.type('input[type="email"]', 'admin@askproai.de');
        await page.type('input[type="password"]', 'password');
        
        await page.screenshot({ path: `${screenshotDir}/manual-01-filled.png`, fullPage: true });
        
        // Try Enter key instead of button click
        console.log('2. Attempting login with Enter key...');
        await page.focus('input[type="password"]');
        await page.keyboard.press('Enter');
        
        // Wait and see what happens
        await page.waitForTimeout(5000);
        
        const currentUrl = page.url();
        console.log(`After Enter key: ${currentUrl}`);
        
        await page.screenshot({ path: `${screenshotDir}/manual-02-after-enter.png`, fullPage: true });
        
        // If still on login page, try clicking any submit element
        if (currentUrl.includes('/login')) {
            console.log('3. Still on login page, trying to find submit button...');
            
            const submitElements = await page.evaluate(() => {
                const elements = [];
                
                // Find all possible submit elements
                document.querySelectorAll('button, input[type="submit"], *[onclick]').forEach(el => {
                    if (el.offsetWidth > 0 && el.offsetHeight > 0) {
                        elements.push({
                            tag: el.tagName,
                            type: el.type,
                            text: el.textContent?.trim() || el.value,
                            classes: el.className,
                            hasClick: !!el.onclick
                        });
                    }
                });
                
                return elements;
            });
            
            console.log('Found submit elements:', JSON.stringify(submitElements, null, 2));
            
            // Try clicking first button
            const buttons = await page.$$('button');
            if (buttons.length > 0) {
                await buttons[0].click();
                await page.waitForTimeout(3000);
                
                const newUrl = page.url();
                console.log(`After button click: ${newUrl}`);
                await page.screenshot({ path: `${screenshotDir}/manual-03-after-button.png`, fullPage: true });
            }
        }
        
        // Check if we're now authenticated
        const finalUrl = page.url();
        const isLoggedIn = !finalUrl.includes('/login');
        
        console.log(`Final URL: ${finalUrl}`);
        console.log(`Login successful: ${isLoggedIn}`);
        
        if (isLoggedIn) {
            console.log('4. ✅ Login successful! Testing dashboard...');
            
            await page.waitForTimeout(3000);
            await page.screenshot({ path: `${screenshotDir}/manual-04-dashboard.png`, fullPage: true });
            
            // Analyze dashboard content
            const dashboardContent = await page.evaluate(() => {
                const widgets = document.querySelectorAll('[class*="widget"], [class*="card"], .fi-section, [class*="stat"]');
                const tables = document.querySelectorAll('table');
                const navigation = document.querySelectorAll('nav a, aside a, [class*="nav"] a, .fi-sidebar-nav a');
                
                return {
                    title: document.title,
                    widgetCount: widgets.length,
                    tableCount: tables.length,
                    navLinkCount: navigation.length,
                    navLinks: Array.from(navigation).slice(0, 10).map(link => ({
                        text: link.textContent.trim(),
                        href: link.href
                    }))
                };
            });
            
            console.log('Dashboard Analysis:', JSON.stringify(dashboardContent, null, 2));
            
            // Test navigation by visiting a few pages
            if (dashboardContent.navLinks.length > 0) {
                console.log('5. Testing navigation pages...');
                
                for (let i = 0; i < Math.min(dashboardContent.navLinks.length, 5); i++) {
                    const link = dashboardContent.navLinks[i];
                    try {
                        console.log(`Visiting: ${link.text} (${link.href})`);
                        await page.goto(link.href, { waitUntil: 'networkidle2', timeout: 10000 });
                        
                        const pageTitle = await page.title();
                        const hasTable = await page.$('table') !== null;
                        const hasForm = await page.$('form') !== null;
                        
                        await page.screenshot({ 
                            path: `${screenshotDir}/manual-page-${i+1}-${link.text.replace(/[^a-zA-Z0-9]/g, '-').toLowerCase()}.png`, 
                            fullPage: true 
                        });
                        
                        console.log(`  - Title: ${pageTitle}`);
                        console.log(`  - Has Table: ${hasTable}`);
                        console.log(`  - Has Form: ${hasForm}`);
                        
                    } catch (error) {
                        console.log(`  - Error visiting ${link.text}: ${error.message}`);
                    }
                }
            }
        } else {
            console.log('❌ Login failed - investigating authentication requirements...');
            
            // Check for error messages
            const errorMessages = await page.evaluate(() => {
                const errors = document.querySelectorAll('[class*="error"], [class*="alert"], [class*="message"]');
                return Array.from(errors).map(el => el.textContent.trim());
            });
            
            console.log('Error messages found:', errorMessages);
            
            // Check if there are additional fields or requirements
            const additionalElements = await page.evaluate(() => {
                const captcha = document.querySelector('[class*="captcha"], [class*="recaptcha"]');
                const twofa = document.querySelector('[class*="2fa"], [class*="otp"], [class*="code"]');
                const additionalInputs = document.querySelectorAll('input[type="text"]:not([type="email"]), input[type="number"]');
                
                return {
                    hasCaptcha: !!captcha,
                    hasTwoFA: !!twofa,
                    additionalInputCount: additionalInputs.length,
                    additionalInputs: Array.from(additionalInputs).map(input => ({
                        name: input.name,
                        placeholder: input.placeholder,
                        required: input.required
                    }))
                };
            });
            
            console.log('Additional requirements:', JSON.stringify(additionalElements, null, 2));
        }
        
    } catch (error) {
        console.error('Test error:', error.message);
        await page.screenshot({ path: `${screenshotDir}/manual-error.png`, fullPage: true });
    }

    await browser.close();
    
    console.log('✅ Manual investigation complete!');
    console.log('📁 Check screenshots in:', screenshotDir);
}

runSimpleAdminPortalTest().catch(console.error);