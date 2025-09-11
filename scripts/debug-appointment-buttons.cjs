const { chromium } = require('playwright');

async function debugAppointmentButtons() {
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
        
        // Debug: Get all links in the first row
        console.log('\n🔍 Analyzing first appointment row...');
        const firstRow = await page.locator('.fi-ta-table tbody tr').first();
        
        // Get all links in the row
        const links = await firstRow.locator('a').evaluateAll(links => 
            links.map(link => ({
                href: link.href,
                text: link.textContent?.trim(),
                title: link.title,
                classes: link.className
            }))
        );
        
        console.log('Found links in first row:');
        links.forEach((link, i) => {
            console.log(`  ${i + 1}. Text: "${link.text}", Href: ${link.href}`);
        });
        
        // Get all buttons in the row
        const buttons = await firstRow.locator('button').evaluateAll(buttons => 
            buttons.map(btn => ({
                text: btn.textContent?.trim(),
                title: btn.title,
                classes: btn.className,
                hasIcon: btn.querySelector('svg') !== null
            }))
        );
        
        console.log('\nFound buttons in first row:');
        buttons.forEach((btn, i) => {
            console.log(`  ${i + 1}. Text: "${btn.text}", Title: "${btn.title}", Has Icon: ${btn.hasIcon}`);
        });
        
        // Check for SVG icons that might be clickable
        const svgIcons = await firstRow.locator('svg').evaluateAll(svgs => 
            svgs.map(svg => ({
                classes: svg.getAttribute('class'),
                parent: svg.parentElement?.tagName,
                parentHref: svg.parentElement?.getAttribute('href')
            }))
        );
        
        console.log('\nFound SVG icons in first row:');
        svgIcons.forEach((svg, i) => {
            if (svg.classes?.includes('heroicon')) {
                console.log(`  ${i + 1}. Classes: ${svg.classes}, Parent: ${svg.parent}, Parent Href: ${svg.parentHref}`);
            }
        });
        
        // Try to navigate using the first appointment ID
        const appointmentId = await firstRow.locator('td').nth(1).textContent();
        if (appointmentId) {
            const directUrl = `https://api.askproai.de/admin/appointments/${appointmentId.trim()}`;
            console.log(`\n📍 Trying direct navigation to: ${directUrl}`);
            
            await page.goto(directUrl);
            await page.waitForLoadState('networkidle');
            
            // Check if we're on the detail page
            const currentUrl = page.url();
            console.log(`📍 Current URL after navigation: ${currentUrl}`);
            
            // Check page content
            const pageTitle = await page.locator('h1, .fi-header-heading').textContent().catch(() => 'No title');
            console.log(`📋 Page title: ${pageTitle}`);
            
            // Check for infolist
            const hasInfolist = await page.locator('.fi-in-component').count() > 0;
            console.log(`📊 Has infolist: ${hasInfolist ? '✅ Yes' : '❌ No'}`);
            
            // Take screenshot
            await page.screenshot({ path: '/tmp/appointment-detail-debug.png', fullPage: true });
            console.log('\n📸 Screenshot saved to /tmp/appointment-detail-debug.png');
        }
        
        console.log('\n✨ Debug completed!');
        
    } catch (error) {
        console.error('❌ Error during debug:', error.message);
    } finally {
        await browser.close();
    }
}

debugAppointmentButtons().catch(console.error);