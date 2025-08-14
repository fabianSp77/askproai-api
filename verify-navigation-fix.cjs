const puppeteer = require('puppeteer');
const fs = require('fs');

async function verifyNavigationFix() {
    const browser = await puppeteer.launch({
        headless: false,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
    });
    
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });
    
    try {
        console.log('Navigation Fix Verification Started...');
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        
        // Step 1: Navigate to login
        console.log('Step 1: Navigating to login page...');
        await page.goto('https://api.askproai.de/admin/login', { waitUntil: 'networkidle2' });
        
        await page.screenshot({ 
            path: `/var/www/api-gateway/public/screenshots/01-login-${timestamp}.png`,
            fullPage: true 
        });
        console.log('Login screenshot saved');
        
        // Step 2: Login
        console.log('Step 2: Attempting login...');
        await page.type('input[name="email"]', 'admin@askproai.de');
        await page.type('input[name="password"]', 'password');
        
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }),
            page.click('button[type="submit"]')
        ]);
        
        console.log('Login successful, URL:', page.url());
        
        // Step 3: Dashboard analysis
        console.log('Step 3: Analyzing dashboard layout...');
        await page.waitForSelector('.fi-sidebar', { timeout: 10000 });
        
        await page.screenshot({ 
            path: `/var/www/api-gateway/public/screenshots/02-dashboard-${timestamp}.png`,
            fullPage: true 
        });
        console.log('Dashboard screenshot saved');
        
        // Layout analysis
        const analysis = await page.evaluate(() => {
            const sidebar = document.querySelector('.fi-sidebar');
            const main = document.querySelector('.fi-main');
            const body = document.body;
            
            if (!sidebar || !main) {
                return { error: 'Layout elements not found', sidebarExists: !!sidebar, mainExists: !!main };
            }
            
            const sidebarRect = sidebar.getBoundingClientRect();
            const mainRect = main.getBoundingClientRect();
            const bodyStyles = window.getComputedStyle(body);
            const sidebarStyles = window.getComputedStyle(sidebar);
            
            return {
                layout: {
                    bodyDisplay: bodyStyles.display,
                    bodyGrid: bodyStyles.gridTemplateColumns,
                    sidebarWidth: sidebarStyles.width,
                    sidebarPosition: sidebarStyles.position,
                    sidebarLeft: sidebarStyles.left
                },
                positioning: {
                    sidebarLeft: Math.round(sidebarRect.left),
                    sidebarWidth: Math.round(sidebarRect.width),
                    mainLeft: Math.round(mainRect.left),
                    mainWidth: Math.round(mainRect.width),
                    overlap: sidebarRect.right > mainRect.left && sidebarRect.left < mainRect.right
                },
                visibility: {
                    sidebarVisible: sidebar.offsetParent !== null,
                    sidebarOpacity: sidebarStyles.opacity,
                    sidebarDisplay: sidebarStyles.display
                }
            };
        });
        
        console.log('Layout Analysis:', JSON.stringify(analysis, null, 2));
        
        // Navigation clickability test
        console.log('Step 4: Testing navigation clickability...');
        const navTest = await page.evaluate(() => {
            const links = document.querySelectorAll('.fi-sidebar nav a');
            return Array.from(links).map((link, index) => ({
                index,
                text: link.textContent.trim(),
                href: link.href,
                clickable: window.getComputedStyle(link).pointerEvents !== 'none',
                visible: link.getBoundingClientRect().width > 0 && link.getBoundingClientRect().height > 0,
                pointerEvents: window.getComputedStyle(link).pointerEvents
            }));
        });
        
        console.log('Navigation Links:');
        navTest.forEach(link => {
            const status = link.clickable ? 'CLICKABLE' : 'BLOCKED';
            console.log(`  ${link.text}: ${status} (pointerEvents: ${link.pointerEvents})`);
        });
        
        // Test actual clicking
        console.log('Step 5: Testing actual navigation click...');
        const clickableLink = navTest.find(l => l.clickable && l.visible && l.text !== 'Dashboard');
        let clickSuccess = false;
        
        if (clickableLink) {
            console.log(`Attempting to click: ${clickableLink.text}`);
            try {
                const urlBefore = page.url();
                await page.click(`a[href*="${new URL(clickableLink.href).pathname}"]`);
                await page.waitForTimeout(3000);
                const urlAfter = page.url();
                
                if (urlBefore !== urlAfter) {
                    console.log('Navigation successful:', urlAfter);
                    clickSuccess = true;
                    
                    await page.screenshot({ 
                        path: `/var/www/api-gateway/public/screenshots/03-nav-clicked-${timestamp}.png`,
                        fullPage: true 
                    });
                } else {
                    console.log('Navigation failed - URL did not change');
                }
            } catch (e) {
                console.log('Click error:', e.message);
            }
        } else {
            console.log('No clickable navigation links found');
        }
        
        // Generate final verdict
        const isGridLayout = analysis.layout.bodyDisplay === 'grid' || 
                            (analysis.layout.bodyGrid && analysis.layout.bodyGrid.includes('16rem'));
        const sidebarVisible = analysis.visibility.sidebarVisible;
        const noOverlap = !analysis.positioning.overlap;
        const sidebarOnLeft = analysis.positioning.sidebarLeft <= 20;
        const hasClickableNav = navTest.some(l => l.clickable);
        
        const isFixed = !analysis.error && isGridLayout && sidebarVisible && noOverlap && sidebarOnLeft && hasClickableNav;
        
        const verdict = {
            timestamp: new Date().toISOString(),
            status: isFixed ? 'FIXED' : 'STILL BROKEN',
            tests: {
                gridLayout: { passed: isGridLayout, value: analysis.layout.bodyDisplay + ' / ' + analysis.layout.bodyGrid },
                sidebarVisible: { passed: sidebarVisible, value: analysis.visibility.sidebarDisplay },
                noOverlap: { passed: noOverlap, value: analysis.positioning.overlap ? 'OVERLAPPING' : 'NO OVERLAP' },
                sidebarOnLeft: { passed: sidebarOnLeft, value: analysis.positioning.sidebarLeft + 'px from left' },
                clickableNavigation: { passed: hasClickableNav, value: navTest.filter(l => l.clickable).length + ' clickable links' },
                actualClickTest: { passed: clickSuccess, value: clickSuccess ? 'Navigation working' : 'Navigation failed' }
            },
            details: analysis,
            navigation: navTest,
            screenshots: [
                `01-login-${timestamp}.png`,
                `02-dashboard-${timestamp}.png`,
                `03-nav-clicked-${timestamp}.png`
            ]
        };
        
        // Save detailed report
        fs.writeFileSync(
            `/var/www/api-gateway/public/screenshots/fix-verified-${timestamp}.json`,
            JSON.stringify(verdict, null, 2)
        );
        
        console.log('\nFINAL VERDICT:', verdict.status);
        console.log('Test Results:');
        Object.entries(verdict.tests).forEach(([test, result]) => {
            const icon = result.passed ? 'PASS' : 'FAIL';
            console.log(`  ${icon} ${test}: ${result.value}`);
        });
        console.log(`Full report: fix-verified-${timestamp}.json`);
        
        return verdict;
        
    } finally {
        await browser.close();
    }
}

// Run verification
verifyNavigationFix().then(result => {
    console.log('\n' + '='.repeat(60));
    console.log('NAVIGATION FIX VERIFICATION COMPLETE');
    console.log('Status:', result.status);
    console.log('='.repeat(60));
}).catch(console.error);
