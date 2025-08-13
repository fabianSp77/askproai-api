import puppeteer from 'puppeteer';
import fs from 'fs';

async function testAskProUI() {
    console.log('🚀 Starting AskProAI UI Test...');
    
    // Create screenshot directory
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const screenshotDir = `./screenshots/ui-test-${timestamp}`;
    fs.mkdirSync(screenshotDir, { recursive: true });
    
    const browser = await puppeteer.launch({ 
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
    });
    
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });
    
    try {
        // Test 1: Homepage
        console.log('📍 Testing Homepage...');
        await page.goto('https://api.askproai.de/', { waitUntil: 'networkidle0' });
        await page.screenshot({ 
            path: `${screenshotDir}/01-homepage.png`, 
            fullPage: true 
        });
        
        // Test 2: Emergency Login
        console.log('📍 Testing Emergency Login...');
        await page.goto('https://api.askproai.de/emergency-login.php', { waitUntil: 'networkidle0' });
        await page.screenshot({ 
            path: `${screenshotDir}/02-emergency-login.png`, 
            fullPage: true 
        });
        
        // Attempt login
        console.log('🔐 Attempting Login...');
        await page.type('input[name="email"]', 'fabian@askproai.de');
        await page.type('input[name="password"]', 'password');
        await page.screenshot({ 
            path: `${screenshotDir}/03-login-filled.png`, 
            fullPage: true 
        });
        
        await page.click('button[type="submit"]');
        await page.waitForNavigation({ waitUntil: 'networkidle0' });
        await page.screenshot({ 
            path: `${screenshotDir}/04-post-login.png`, 
            fullPage: true 
        });
        
        // Test 3: Admin Dashboard
        console.log('📍 Testing Admin Dashboard...');
        await page.goto('https://api.askproai.de/admin', { waitUntil: 'networkidle0' });
        await page.waitForTimeout(3000);
        await page.screenshot({ 
            path: `${screenshotDir}/05-admin-dashboard.png`, 
            fullPage: true 
        });
        
        // Analyze UI components
        const uiAnalysis = await page.evaluate(() => {
            const analysis = {
                hasFilamentDesign: document.querySelector('.fi-page') !== null,
                hasFlowbiteElements: document.querySelectorAll('[class*="blue-"]').length > 0,
                hasSidebar: document.querySelector('.fi-sidebar, aside, [class*="sidebar"]') !== null,
                colorScheme: {
                    background: window.getComputedStyle(document.body).backgroundColor,
                    hasDarkMode: document.documentElement.classList.contains('dark')
                },
                navigation: {
                    hamburgerVisible: document.querySelector('.fi-topbar-open-sidebar-btn') !== null,
                    sidebarToggleFound: document.querySelector('[x-on\\:click*="sidebar"]') !== null
                },
                widgets: document.querySelectorAll('.fi-wi, [wire\\:id]').length,
                modernDesign: {
                    hasRoundedCorners: document.querySelectorAll('.rounded-xl').length > 0,
                    hasShadows: document.querySelectorAll('.shadow-sm').length > 0,
                    hasGradients: document.querySelectorAll('[class*="gradient"]').length > 0
                }
            };
            return analysis;
        });
        
        // Test mobile responsiveness
        console.log('📱 Testing Mobile View...');
        await page.setViewport({ width: 375, height: 667 });
        await page.waitForTimeout(1000);
        await page.screenshot({ 
            path: `${screenshotDir}/06-mobile-view.png`, 
            fullPage: true 
        });
        
        // Test tablet view
        console.log('📱 Testing Tablet View...');
        await page.setViewport({ width: 768, height: 1024 });
        await page.waitForTimeout(1000);
        await page.screenshot({ 
            path: `${screenshotDir}/07-tablet-view.png`, 
            fullPage: true 
        });
        
        // Back to desktop
        await page.setViewport({ width: 1920, height: 1080 });
        
        // Check for navigation issues
        console.log('🧭 Testing Navigation...');
        const navigationTest = await page.evaluate(() => {
            const navItems = document.querySelectorAll('nav a, .sidebar a, aside a');
            const results = [];
            
            navItems.forEach(item => {
                const style = window.getComputedStyle(item);
                results.push({
                    text: item.textContent ? item.textContent.trim() : 'No text',
                    href: item.href || 'No href',
                    isClickable: style.pointerEvents !== 'none' && !item.disabled,
                    classes: item.className
                });
            });
            
            return {
                totalNavItems: results.length,
                clickableItems: results.filter(item => item.isClickable).length,
                nonClickableItems: results.filter(item => !item.isClickable).length,
                items: results
            };
        });
        
        // Final analysis screenshot
        await page.screenshot({ 
            path: `${screenshotDir}/08-final-analysis.png`, 
            fullPage: true 
        });
        
        // Save analysis report
        const report = {
            timestamp: new Date().toISOString(),
            testResults: {
                uiAnalysis,
                navigationTest,
                screenshotCount: 8,
                screenshotDir
            },
            issues: [],
            recommendations: []
        };
        
        // Analyze results and generate recommendations
        if (!uiAnalysis.hasSidebar) {
            report.issues.push('No sidebar navigation found - may cause navigation issues');
        }
        
        if (navigationTest.nonClickableItems > navigationTest.clickableItems) {
            report.issues.push('More non-clickable than clickable navigation items found');
        }
        
        if (!uiAnalysis.hasFlowbiteElements) {
            report.recommendations.push('Consider implementing Flowbite design system for modern UI');
        }
        
        if (uiAnalysis.hasFilamentDesign) {
            report.recommendations.push('Filament 3 detected - ensure all components are properly styled');
        }
        
        fs.writeFileSync(`${screenshotDir}/test-report.json`, JSON.stringify(report, null, 2));
        
        console.log('✅ UI Test completed!');
        console.log('📊 Report saved to: ' + screenshotDir + '/test-report.json');
        console.log('📸 Screenshots saved to: ' + screenshotDir + '/');
        console.log('\n🔍 Quick Results:');
        console.log('   - Filament Design: ' + (uiAnalysis.hasFilamentDesign ? '✅' : '❌'));
        console.log('   - Sidebar Found: ' + (uiAnalysis.hasSidebar ? '✅' : '❌'));
        console.log('   - Modern Design: ' + (uiAnalysis.modernDesign.hasRoundedCorners && uiAnalysis.modernDesign.hasShadows ? '✅' : '❌'));
        console.log('   - Navigation Items: ' + navigationTest.clickableItems + '/' + navigationTest.totalNavItems + ' clickable');
        console.log('   - Widgets Found: ' + uiAnalysis.widgets);
        
        return report;
        
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        await page.screenshot({ path: `${screenshotDir}/error-state.png`, fullPage: true });
        throw error;
    } finally {
        await browser.close();
    }
}

testAskProUI().catch(console.error);
