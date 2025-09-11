const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    });
    
    const page = await context.newPage();
    
    console.log('=== TESTING RETELL AGENT PAGE ===\n');
    
    // First, let's try to access the page without authentication
    console.log('1. Attempting to access /admin/retell-agents/135 without auth...');
    await page.goto('https://api.askproai.de/admin/retell-agents/135', { waitUntil: 'networkidle' });
    
    const url1 = page.url();
    console.log('   Current URL:', url1);
    
    if (url1.includes('login')) {
        console.log('   ✓ Redirected to login page as expected\n');
        
        // We need to check if we can see the actual content
        console.log('2. Checking page structure...');
        
        // Try to find login form
        const loginForm = await page.$('form');
        if (loginForm) {
            console.log('   ✓ Login form found\n');
        }
    } else {
        console.log('   ! Page loaded without redirect\n');
        
        // Check what content is visible
        console.log('2. Analyzing page content...');
        
        // Look for specific text
        const hasAgentInfo = await page.locator('text="Agent Information"').count();
        console.log('   "Agent Information" sections found:', hasAgentInfo);
        
        // Check for any data entries
        const textEntries = await page.$$('.fi-in-entry');
        console.log('   Filament entry components found:', textEntries.length);
        
        // Get all visible text
        const bodyText = await page.innerText('body');
        const lines = bodyText.split('\n').filter(line => line.trim());
        
        console.log('\n3. Visible text on page (first 20 lines):');
        lines.slice(0, 20).forEach(line => {
            console.log('   ', line);
        });
        
        // Check for specific Filament components
        console.log('\n4. Checking Filament components:');
        const sections = await page.$$('.fi-section');
        console.log('   Sections found:', sections.length);
        
        const infolists = await page.$$('[wire\\:id]');
        console.log('   Livewire components found:', infolists.length);
        
        // Check if there are any error messages
        const errors = await page.$$('.text-danger, .error, .alert-danger');
        console.log('   Error elements found:', errors.length);
        
        // Get the HTML structure of the main content area
        console.log('\n5. Main content structure:');
        const mainContent = await page.$('main, .fi-main, .fi-page, [role="main"]');
        if (mainContent) {
            const html = await mainContent.innerHTML();
            // Show first 500 chars of HTML
            console.log('   HTML Preview:', html.substring(0, 500) + '...');
        } else {
            console.log('   No main content area found');
        }
    }
    
    // Take a screenshot for reference
    await page.screenshot({ path: '/var/www/api-gateway/retell-agent-page.png', fullPage: true });
    console.log('\n✓ Screenshot saved to retell-agent-page.png');
    
    await browser.close();
    console.log('\n=== TEST COMPLETE ===');
})();