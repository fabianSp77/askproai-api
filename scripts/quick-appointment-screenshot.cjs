const { chromium } = require('playwright');

async function quickScreenshot() {
    const browser = await chromium.launch({ 
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    try {
        const context = await browser.newContext({
            viewport: { width: 1920, height: 1080 }
        });
        const page = await context.newPage();
        
        // Login
        await page.goto('https://api.askproai.de/admin/login');
        await page.fill('input[type="email"]', 'admin@askproai.de');
        await page.fill('input[type="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin', { timeout: 10000 });
        
        // Go directly to appointment detail
        console.log('📅 Navigating to appointment 353...');
        await page.goto('https://api.askproai.de/admin/appointments/353');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000); // Give extra time for content to load
        
        // Take screenshot
        await page.screenshot({ path: '/tmp/appointment-detail-current.png', fullPage: true });
        console.log('📸 Screenshot saved to /tmp/appointment-detail-current.png');
        
        // Quick check for content
        const hasContent = await page.locator('.fi-in-text-entry, .fi-in-component, .fi-section').count();
        console.log(`📊 Content elements found: ${hasContent}`);
        
        // Check what's visible
        const visibleText = await page.locator('main').textContent();
        console.log(`📝 Page content preview: ${visibleText.substring(0, 200)}...`);
        
    } catch (error) {
        console.error('❌ Error:', error.message);
    } finally {
        await browser.close();
    }
}

quickScreenshot().catch(console.error);