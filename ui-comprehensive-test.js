import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';

const SCREENSHOT_DIR = './screenshots/ui-test-20250813-232918';
const BASE_URL = 'https://api.askproai.de';

// Ensure screenshot directory exists
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function saveScreenshot(page, name, options = {}) {
    const timestamp = new Date().getTime();
    const filename = `${name}-${timestamp}.png`;
    const filepath = path.join(SCREENSHOT_DIR, filename);
    await page.screenshot({ 
        path: filepath, 
        fullPage: true,
        ...options 
    });
    console.log(`📸 Screenshot saved: ${filename}`);
    return filepath;
}

async function analyzeUIComponents(page) {
    return await page.evaluate(() => {
        const components = {
            colors: [],
            typography: [],
            cards: [],
            buttons: [],
            navigation: [],
            errors: []
        };
        
        // Analyze color scheme
        const computedStyle = window.getComputedStyle(document.body);
        components.colors.push({
            background: computedStyle.backgroundColor,
            color: computedStyle.color
        });
        
        // Check for Flowbite/Sky-blue theme elements
        const skyBlueElements = document.querySelectorAll('[class*="sky"], [class*="blue-500"], [class*="blue-600"]');
        components.colors.push({
            skyBlueElements: skyBlueElements.length,
            hasSkyTheme: skyBlueElements.length > 0
        });
        
        // Analyze cards
        const cards = document.querySelectorAll('.bg-white, .bg-gray-50, [class*="rounded"], [class*="shadow"]');
        components.cards = Array.from(cards).slice(0, 5).map(card => ({
            classes: card.className,
            hasRoundedCorners: card.className.includes('rounded'),
            hasShadow: card.className.includes('shadow')
        }));
        
        // Analyze buttons
        const buttons = document.querySelectorAll('button, .btn, [role="button"]');
        components.buttons = Array.from(buttons).slice(0, 5).map(btn => ({
            text: btn.textContent ? btn.textContent.trim().substring(0, 30) : '',
            classes: btn.className,
            hasGradient: btn.className.includes('gradient'),
            isFlowbite: btn.className.includes('text-white') && btn.className.includes('bg-blue')
        }));
        
        // Analyze navigation
        const navElements = document.querySelectorAll('nav, .sidebar, [class*="nav"], aside');
        components.navigation = Array.from(navElements).map(nav => ({
            classes: nav.className,
            isVisible: window.getComputedStyle(nav).display !== 'none',
            hasItems: nav.querySelectorAll('a, [role="menuitem"]').length
        }));
        
        return components;
    });
}

async function testNavigation(page) {
    console.log('🧭 Testing navigation...');
    
    const navigationResults = await page.evaluate(() => {
        const results = {
            sidebarVisible: false,
            menuItems: [],
            clickableItems: 0,
            nonClickableItems: 0,
            issue479: false
        };
        
        // Check for sidebar
        const sidebar = document.querySelector('.sidebar, aside, [class*="sidebar"]');
        if (sidebar) {
            results.sidebarVisible = window.getComputedStyle(sidebar).display !== 'none';
        }
        
        // Check menu items
        const menuItems = document.querySelectorAll('nav a, .sidebar a, aside a, [role="menuitem"]');
        menuItems.forEach(item => {
            const style = window.getComputedStyle(item);
            const isClickable = style.pointerEvents !== 'none' && !item.disabled;
            
            results.menuItems.push({
                text: item.textContent ? item.textContent.trim() : '',
                href: item.href || '',
                isClickable: isClickable,
                pointerEvents: style.pointerEvents,
                classes: item.className
            });
            
            if (isClickable) {
                results.clickableItems++;
            } else {
                results.nonClickableItems++;
            }
        });
        
        // Check for issue #479 - only emergency menu clickable
        const emergencyItems = results.menuItems.filter(item => 
            item.text.toLowerCase().includes('emergency') || 
            item.href.includes('emergency')
        );
        const regularItems = results.menuItems.filter(item => 
            !item.text.toLowerCase().includes('emergency') && 
            !item.href.includes('emergency')
        );
        
        results.issue479 = emergencyItems.some(item => item.isClickable) && 
                          regularItems.every(item => !item.isClickable);
        
        return results;
    });
    
    return navigationResults;
}

async function testResponsive(page) {
    console.log('📱 Testing responsive design...');
    
    const viewports = [
        { name: 'desktop', width: 1920, height: 1080 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'mobile', width: 375, height: 667 }
    ];
    
    const responsiveResults = [];
    
    for (const viewport of viewports) {
        console.log(`Testing ${viewport.name} (${viewport.width}x${viewport.height})`);
        await page.setViewport(viewport);
        await page.waitForTimeout(1000);
        
        const screenshot = await saveScreenshot(page, `responsive-${viewport.name}`);
        
        const analysis = await page.evaluate(() => {
            return {
                hasHamburgerMenu: !!document.querySelector('.hamburger, [class*="hamburger"], .mobile-menu-toggle'),
                sidebarVisible: !!document.querySelector('.sidebar:not([style*="display: none"])'),
                hasOverflow: document.body.scrollWidth > window.innerWidth,
                hasHorizontalScroll: window.innerWidth < document.body.offsetWidth
            };
        });
        
        responsiveResults.push({
            viewport: viewport.name,
            dimensions: `${viewport.width}x${viewport.height}`,
            screenshot,
            analysis
        });
    }
    
    return responsiveResults;
}

async function runComprehensiveTest() {
    console.log('🚀 Starting comprehensive UI/UX test...');
    
    const browser = await puppeteer.launch({ 
        headless: false,
        defaultViewport: { width: 1920, height: 1080 },
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const results = {
        timestamp: new Date().toISOString(),
        testResults: {},
        screenshots: [],
        issues: [],
        recommendations: []
    };
    
    try {
        const page = await browser.newPage();
        
        page.on('console', msg => {
            console.log(`🖥️  Console ${msg.type()}: ${msg.text()}`);
        });
        
        page.on('pageerror', err => {
            console.error(`❌ Page error: ${err.message}`);
            results.issues.push(`Page error: ${err.message}`);
        });
        
        // Test 1: Homepage
        console.log('📋 Test 1: Homepage');
        await page.goto(BASE_URL, { waitUntil: 'networkidle0' });
        await page.waitForTimeout(2000);
        results.screenshots.push(await saveScreenshot(page, 'homepage'));
        
        const homepageAnalysis = await analyzeUIComponents(page);
        results.testResults.homepage = homepageAnalysis;
        
        // Test 2: Emergency Login
        console.log('📋 Test 2: Emergency Login');
        await page.goto(`${BASE_URL}/emergency-login.php`, { waitUntil: 'networkidle0' });
        await page.waitForTimeout(2000);
        results.screenshots.push(await saveScreenshot(page, 'emergency-login'));
        
        const loginPageAnalysis = await analyzeUIComponents(page);
        results.testResults.emergencyLogin = loginPageAnalysis;
        
        // Attempt login
        console.log('🔐 Attempting login...');
        try {
            await page.waitForSelector('input[type="email"], input[name="email"]', { timeout: 5000 });
            await page.type('input[type="email"], input[name="email"]', 'fabian@askproai.de');
            await page.type('input[type="password"], input[name="password"]', 'password');
            
            results.screenshots.push(await saveScreenshot(page, 'login-filled'));
            
            await page.click('button[type="submit"], input[type="submit"], .btn-login');
            await page.waitForTimeout(3000);
            
            results.screenshots.push(await saveScreenshot(page, 'post-login'));
            
        } catch (error) {
            console.log(`⚠️  Login form interaction failed: ${error.message}`);
            results.issues.push(`Login form interaction failed: ${error.message}`);
        }
        
        // Test 3: Admin Dashboard
        console.log('📋 Test 3: Admin Dashboard');
        try {
            await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle0' });
            await page.waitForTimeout(3000);
            results.screenshots.push(await saveScreenshot(page, 'admin-dashboard'));
            
            const dashboardAnalysis = await analyzeUIComponents(page);
            results.testResults.adminDashboard = dashboardAnalysis;
            
            // Test navigation
            const navigationResults = await testNavigation(page);
            results.testResults.navigation = navigationResults;
            
            if (navigationResults.issue479) {
                results.issues.push('CONFIRMED: Issue #479 - Only emergency menu items are clickable');
            }
            
        } catch (error) {
            console.log(`⚠️  Admin dashboard test failed: ${error.message}`);
            results.issues.push(`Admin dashboard access failed: ${error.message}`);
        }
        
        // Test 4: Responsive Design
        console.log('📋 Test 4: Responsive Design');
        const responsiveResults = await testResponsive(page);
        results.testResults.responsive = responsiveResults;
        
        // Reset viewport for final tests
        await page.setViewport({ width: 1920, height: 1080 });
        
        // Test 5: Performance Analysis
        console.log('📋 Test 5: Performance Analysis');
        const performanceMetrics = await page.metrics();
        results.testResults.performance = performanceMetrics;
        
        // Final comprehensive screenshot
        results.screenshots.push(await saveScreenshot(page, 'final-state'));
        
    } catch (error) {
        console.error(`❌ Test failed: ${error.message}`);
        results.issues.push(`Test execution error: ${error.message}`);
    } finally {
        await browser.close();
    }
    
    // Generate recommendations
    if (results.testResults.navigation && results.testResults.navigation.issue479) {
        results.recommendations.push('HIGH PRIORITY: Fix navigation issue #479 - enable all menu items');
    }
    
    if (results.testResults.adminDashboard && results.testResults.adminDashboard.colors && results.testResults.adminDashboard.colors.some(c => !c.hasSkyTheme)) {
        results.recommendations.push('MEDIUM: Verify Flowbite sky-blue theme is properly applied');
    }
    
    // Save results
    const reportPath = path.join(SCREENSHOT_DIR, 'test-results.json');
    fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
    
    console.log('✅ Comprehensive UI test completed!');
    console.log(`📊 Results saved to: ${reportPath}`);
    console.log(`📸 Screenshots saved to: ${SCREENSHOT_DIR}`);
    
    return results;
}

// Run the test
runComprehensiveTest().catch(console.error);
