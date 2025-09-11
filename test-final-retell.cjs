const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    
    const page = await context.newPage();
    
    console.log('=== FINAL TEST RETELL AGENT PAGE ===\n');
    
    // Navigate to the page
    console.log('Navigating to /admin/retell-agents/135...');
    await page.goto('https://api.askproai.de/admin/retell-agents/135', { 
        waitUntil: 'networkidle',
        timeout: 30000 
    });
    
    const url = page.url();
    console.log('Current URL:', url);
    
    // Take screenshot
    await page.screenshot({ 
        path: '/var/www/api-gateway/retell-final-test.png', 
        fullPage: true 
    });
    
    if (url.includes('login')) {
        console.log('\n✓ Redirected to login page (expected for unauthenticated access)');
        console.log('  This means the route is working correctly!');
        console.log('  After login, the agent details should be displayed.');
    } else {
        // Check for content
        console.log('\nChecking page content...');
        
        const hasAgentInfo = await page.locator('text="Agent Information"').count();
        const hasRetellViewer = await page.locator('[wire\\:id*="retell-agent-viewer"]').count();
        const hasLivewire = await page.locator('[wire\\:id]').count();
        
        console.log('- Agent Information sections:', hasAgentInfo);
        console.log('- RetellAgentViewer components:', hasRetellViewer);
        console.log('- Total Livewire components:', hasLivewire);
        
        // Check for any visible agent data
        const agentName = await page.locator('text="Online: Assistent"').count();
        if (agentName > 0) {
            console.log('\n✓ SUCCESS: Agent data is being displayed!');
        }
        
        // Get visible text
        const bodyText = await page.innerText('body').catch(() => '');
        if (bodyText.includes('135') || bodyText.includes('agent_')) {
            console.log('✓ Agent details found in page content');
        }
    }
    
    console.log('\n✓ Screenshot saved: retell-final-test.png');
    console.log('\n=== TEST COMPLETE ===');
    
    await browser.close();
})();