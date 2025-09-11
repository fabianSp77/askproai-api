const { chromium } = require('playwright');

async function testAppointmentPage() {
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
        
        // Check if the page loaded
        const pageTitle = await page.textContent('h1, .fi-header-heading');
        console.log(`📋 Page title: ${pageTitle}`);
        
        // Check for table presence
        const tableExists = await page.locator('.fi-ta-table').count() > 0;
        console.log(`📊 Table present: ${tableExists ? '✅ Yes' : '❌ No'}`);
        
        if (tableExists) {
            // Count table rows
            const rowCount = await page.locator('.fi-ta-table tbody tr').count();
            console.log(`📈 Table rows found: ${rowCount}`);
            
            // Check for specific columns
            const columns = await page.locator('.fi-ta-table thead th').allTextContents();
            console.log('📋 Table columns:', columns.filter(c => c.trim()).join(', '));
            
            // Get first few appointment details if available
            if (rowCount > 0) {
                console.log('\n🔍 Sample appointments:');
                const firstRows = await page.locator('.fi-ta-table tbody tr').evaluateAll(rows => {
                    return rows.slice(0, 3).map(row => {
                        const cells = row.querySelectorAll('td');
                        return {
                            id: cells[1]?.textContent?.trim() || 'N/A',
                            customer: cells[2]?.textContent?.trim() || 'N/A',
                            eventType: cells[4]?.textContent?.trim() || 'N/A',
                            date: cells[6]?.textContent?.trim() || 'N/A',
                            status: cells[7]?.textContent?.trim() || 'N/A'
                        };
                    });
                });
                
                firstRows.forEach((row, i) => {
                    console.log(`  ${i + 1}. ID: ${row.id}, Customer: ${row.customer}, Date: ${row.date}, Status: ${row.status}`);
                });
            }
        } else {
            // Check for empty state message
            const emptyMessage = await page.locator('.fi-ta-empty-state').textContent().catch(() => null);
            if (emptyMessage) {
                console.log(`⚠️ Empty state message: ${emptyMessage}`);
            }
            
            // Check for any error messages
            const errorMessage = await page.locator('.fi-notification, .fi-danger').textContent().catch(() => null);
            if (errorMessage) {
                console.log(`❌ Error message: ${errorMessage}`);
            }
        }
        
        // Take a screenshot for debugging
        await page.screenshot({ path: '/tmp/appointment-page-test.png', fullPage: true });
        console.log('\n📸 Screenshot saved to /tmp/appointment-page-test.png');
        
        // Test filtering
        console.log('\n🔍 Testing filters...');
        const filterButton = await page.locator('button:has-text("Filter"), button[title*="Filter"]').first();
        if (await filterButton.count() > 0) {
            await filterButton.click();
            await page.waitForTimeout(500);
            console.log('✅ Filter menu opened');
            
            // Check available filters
            const filterOptions = await page.locator('[role="dialog"] label, .fi-fo-field-wrp label').allTextContents();
            console.log('Available filters:', filterOptions.filter(f => f.trim()).slice(0, 5).join(', '));
        }
        
        console.log('\n✨ Test completed successfully!');
        
    } catch (error) {
        console.error('❌ Error during test:', error.message);
        
        // Take error screenshot
        try {
            await page.screenshot({ path: '/tmp/appointment-page-error.png', fullPage: true });
            console.log('📸 Error screenshot saved to /tmp/appointment-page-error.png');
        } catch {}
    } finally {
        await browser.close();
    }
}

testAppointmentPage().catch(console.error);