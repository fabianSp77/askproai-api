const { chromium } = require('playwright');

async function testAppointmentDetail() {
    const browser = await chromium.launch({ 
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    try {
        const context = await browser.newContext({
            viewport: { width: 1920, height: 1080 }
        });
        const page = await context.newPage();
        
        console.log('🌐 Navigating to login page...');
        await page.goto('https://api.askproai.de/admin/login');
        
        // Login
        console.log('🔐 Logging in...');
        await page.fill('input[type="email"]', 'admin@askproai.de');
        await page.fill('input[type="password"]', 'password123');
        await page.click('button[type="submit"]');
        
        // Wait for dashboard
        await page.waitForURL('**/admin', { timeout: 10000 });
        console.log('✅ Logged in successfully');
        
        // Navigate to appointments
        console.log('📅 Navigating to appointments page...');
        await page.goto('https://api.askproai.de/admin/appointments');
        await page.waitForLoadState('networkidle');
        
        // Click on the first appointment's view button
        console.log('🔍 Opening first appointment detail...');
        // First try to find the view action button in the actions column
        const viewButton = await page.locator('.fi-ta-table tbody tr').first().locator('a[wire\\:navigate][href*="/appointments/"]').first();
        
        if (await viewButton.count() > 0) {
            await viewButton.click();
            await page.waitForLoadState('networkidle');
            
            // Get the URL to confirm we're on a detail page
            const currentUrl = page.url();
            console.log(`📍 Current URL: ${currentUrl}`);
            
            // Check if we're on a detail page
            if (currentUrl.includes('/appointments/') && !currentUrl.endsWith('/appointments')) {
                console.log('✅ Successfully navigated to appointment detail page');
                
                // Check for infolist presence
                const infolistExists = await page.locator('.fi-in-component').count() > 0;
                console.log(`📊 Infolist present: ${infolistExists ? '✅ Yes' : '❌ No'}`);
                
                // Check for tabs
                const tabsExist = await page.locator('[role="tablist"]').count() > 0;
                console.log(`📑 Tabs present: ${tabsExist ? '✅ Yes' : '❌ No'}`);
                
                if (tabsExist) {
                    const tabs = await page.locator('[role="tab"]').allTextContents();
                    console.log('📋 Available tabs:', tabs.map(t => t.trim()).filter(t => t).join(', '));
                }
                
                // Get some key information from the detail view
                console.log('\n📋 Appointment Details:');
                
                // Try to get booking ID
                const bookingId = await page.locator('text=/Booking ID|Cal.com ID/i').locator('..').locator('.fi-in-text-entry').textContent().catch(() => 'N/A');
                console.log(`  - Booking ID: ${bookingId.trim()}`);
                
                // Try to get customer name
                const customerName = await page.locator('text=/Customer/i').locator('..').locator('.fi-in-text-entry').first().textContent().catch(() => 'N/A');
                console.log(`  - Customer: ${customerName.trim()}`);
                
                // Try to get status
                const status = await page.locator('text=/Status/i').locator('..').locator('.fi-badge').textContent().catch(() => 'N/A');
                console.log(`  - Status: ${status.trim()}`);
                
                // Try to get start time
                const startTime = await page.locator('text=/Start Time/i').locator('..').locator('.fi-in-text-entry').textContent().catch(() => 'N/A');
                console.log(`  - Start Time: ${startTime.trim()}`);
                
                // Check for action buttons
                const editButton = await page.locator('a:has-text("Edit"), button:has-text("Edit")').count() > 0;
                const deleteButton = await page.locator('a:has-text("Delete"), button:has-text("Delete")').count() > 0;
                console.log(`\n🔧 Actions available:`);
                console.log(`  - Edit: ${editButton ? '✅' : '❌'}`);
                console.log(`  - Delete: ${deleteButton ? '✅' : '❌'}`);
                
                // Switch to Cal.com Integration tab if it exists
                const calcomTab = await page.locator('[role="tab"]:has-text("Cal.com")').first();
                if (await calcomTab.count() > 0) {
                    console.log('\n📑 Switching to Cal.com Integration tab...');
                    await calcomTab.click();
                    await page.waitForTimeout(500);
                    
                    // Check for Cal.com specific fields
                    const meetingUrl = await page.locator('text=/Meeting URL/i').locator('..').locator('a').getAttribute('href').catch(() => null);
                    if (meetingUrl) {
                        console.log(`  - Meeting URL: ${meetingUrl}`);
                    }
                    
                    const eventType = await page.locator('text=/Event Type/i').locator('..').locator('.fi-badge').textContent().catch(() => 'N/A');
                    console.log(`  - Event Type: ${eventType.trim()}`);
                }
                
                // Take a screenshot
                await page.screenshot({ path: '/tmp/appointment-detail-test.png', fullPage: true });
                console.log('\n📸 Screenshot saved to /tmp/appointment-detail-test.png');
                
            } else {
                console.log('❌ Failed to navigate to detail page');
            }
        } else {
            console.log('❌ Could not find view button for first appointment');
        }
        
        console.log('\n✨ Test completed successfully!');
        
    } catch (error) {
        console.error('❌ Error during test:', error.message);
        
        // Take error screenshot
        try {
            await page.screenshot({ path: '/tmp/appointment-detail-error.png', fullPage: true });
            console.log('📸 Error screenshot saved to /tmp/appointment-detail-error.png');
        } catch {}
    } finally {
        await browser.close();
    }
}

testAppointmentDetail().catch(console.error);