const { chromium } = require('playwright');

async function testAppointmentDetailCorrect() {
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
        
        // Navigate directly to appointment 353 (which has calcom_v2_booking_id 7246782)
        console.log('📅 Navigating to appointment detail page (ID: 353)...');
        await page.goto('https://api.askproai.de/admin/appointments/353');
        await page.waitForLoadState('networkidle');
        
        // Check if we're on the detail page
        const currentUrl = page.url();
        console.log(`📍 Current URL: ${currentUrl}`);
        
        // Check page content
        const pageTitle = await page.locator('h1, .fi-header-heading').textContent().catch(() => 'No title');
        console.log(`📋 Page title: ${pageTitle.trim()}`);
        
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
        
        // Get appointment details
        console.log('\n📋 Appointment Details:');
        
        // Try different selectors for the booking ID
        const bookingIdSelectors = [
            '.fi-in-text-entry:has-text("7246782")',
            'span:has-text("7246782")',
            'text=7246782'
        ];
        
        for (const selector of bookingIdSelectors) {
            const bookingId = await page.locator(selector).first().textContent().catch(() => null);
            if (bookingId) {
                console.log(`  - Booking ID: ${bookingId.trim()}`);
                break;
            }
        }
        
        // Try to get customer name
        const customerSelectors = [
            'text=/Customer/i >> .. >> .fi-in-text-entry',
            'text=/Max Müller/i',
            '.fi-in-text-entry:has-text("Max Müller")'
        ];
        
        for (const selector of customerSelectors) {
            const customerName = await page.locator(selector).first().textContent().catch(() => null);
            if (customerName) {
                console.log(`  - Customer: ${customerName.trim()}`);
                break;
            }
        }
        
        // Check for status badge
        const statusBadge = await page.locator('.fi-badge:has-text("accepted")').first().textContent().catch(() => null);
        if (statusBadge) {
            console.log(`  - Status: ${statusBadge.trim()}`);
        }
        
        // Check for meeting URL
        const meetingUrl = await page.locator('a[href*="cal.com/video"]').first().getAttribute('href').catch(() => null);
        if (meetingUrl) {
            console.log(`  - Meeting URL: ${meetingUrl}`);
        }
        
        // Check for action buttons
        const editButton = await page.locator('a:has-text("Edit"), button:has-text("Edit"), a[href*="/edit"]').count() > 0;
        const deleteButton = await page.locator('button:has-text("Delete"), button[wire\\:click*="delete"]').count() > 0;
        console.log(`\n🔧 Actions available:`);
        console.log(`  - Edit: ${editButton ? '✅' : '❌'}`);
        console.log(`  - Delete: ${deleteButton ? '✅' : '❌'}`);
        
        // Try switching tabs
        const calcomTab = await page.locator('[role="tab"]:has-text("Cal.com")').first();
        if (await calcomTab.count() > 0) {
            console.log('\n📑 Switching to Cal.com Integration tab...');
            await calcomTab.click();
            await page.waitForTimeout(500);
            console.log('✅ Switched to Cal.com tab');
        }
        
        // Take a screenshot
        await page.screenshot({ path: '/tmp/appointment-detail-working.png', fullPage: true });
        console.log('\n📸 Screenshot saved to /tmp/appointment-detail-working.png');
        
        console.log('\n✨ Test completed successfully!');
        
    } catch (error) {
        console.error('❌ Error during test:', error.message);
        
        // Take error screenshot
        try {
            const page = await browser.pages().then(pages => pages[0]);
            if (page) {
                await page.screenshot({ path: '/tmp/appointment-detail-error2.png', fullPage: true });
                console.log('📸 Error screenshot saved to /tmp/appointment-detail-error2.png');
            }
        } catch {}
    } finally {
        await browser.close();
    }
}

testAppointmentDetailCorrect().catch(console.error);