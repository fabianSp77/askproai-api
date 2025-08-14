import puppeteer from 'puppeteer';
import fs from 'fs';

const BASE_URL = 'https://api.askproai.de';

// Create screenshots directory
if (!fs.existsSync('./ui-audit-screenshots')) {
    fs.mkdirSync('./ui-audit-screenshots', { recursive: true });
}

console.log('🚀 Starting UI Audit...');

const browser = await puppeteer.launch({
    headless: 'new',
    executablePath: '/usr/bin/chromium',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
});

const page = await browser.newPage();
await page.setViewport({ width: 1920, height: 1080 });

try {
    // Step 1: Login page
    console.log('📋 Loading login page...');
    await page.goto(BASE_URL + '/emergency-login.php', { waitUntil: 'networkidle0' });
    await page.waitForTimeout(2000);
    
    await page.screenshot({ path: './ui-audit-screenshots/01-login.png', fullPage: true });
    console.log('✅ Login screenshot saved');

    // Step 2: Login
    console.log('🔐 Logging in...');
    await page.type('input[name="email"]', 'fabian@askproai.de');
    await page.type('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle0' });
    
    console.log('✅ Login successful, URL:', page.url());

    // Step 3: Dashboard
    await page.waitForTimeout(5000);
    await page.screenshot({ path: './ui-audit-screenshots/02-dashboard.png', fullPage: true });
    console.log('✅ Dashboard screenshot saved');

    // Step 4: Analyze navigation
    const analysis = await page.evaluate(() => {
        const result = {
            navElements: [],
            clickableElements: [],
            overlaps: []
        };
        
        // Find navigation
        document.querySelectorAll('nav, .sidebar, .navigation, aside').forEach((el, i) => {
            const rect = el.getBoundingClientRect();
            const styles = getComputedStyle(el);
            result.navElements.push({
                index: i,
                tag: el.tagName,
                classes: el.className,
                position: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
                zIndex: styles.zIndex,
                position_style: styles.position,
                display: styles.display,
                text: el.textContent.substring(0, 100)
            });
        });
        
        // Find clickable elements
        document.querySelectorAll('a, button').forEach((el, i) => {
            const rect = el.getBoundingClientRect();
            const styles = getComputedStyle(el);
            if (rect.width > 0 && rect.height > 0) {
                result.clickableElements.push({
                    index: i,
                    tag: el.tagName,
                    text: el.textContent.trim().substring(0, 50),
                    href: el.href || '',
                    clickable: styles.pointerEvents !== 'none',
                    visible: styles.visibility !== 'hidden',
                    position: { x: rect.x, y: rect.y, width: rect.width, height: rect.height }
                });
            }
        });
        
        return result;
    });
    
    console.log('🔍 Analysis complete:');
    console.log('  Navigation elements:', analysis.navElements.length);
    console.log('  Clickable elements:', analysis.clickableElements.length);
    
    // Save analysis
    fs.writeFileSync('./ui-audit-screenshots/analysis.json', JSON.stringify(analysis, null, 2));
    console.log('✅ Analysis saved');

    // Step 5: Test navigation clicks
    const navLinks = await page.$$('nav a, .sidebar a, aside a');
    console.log('🔗 Found', navLinks.length, 'navigation links');
    
    for (let i = 0; i < Math.min(navLinks.length, 3); i++) {
        try {
            const text = await navLinks[i].evaluate(el => el.textContent.trim());
            console.log('Testing link:', text);
            
            await navLinks[i].click();
            await page.waitForTimeout(3000);
            
            await page.screenshot({ 
                path: `./ui-audit-screenshots/03-nav-${i}.png`, 
                fullPage: true 
            });
            console.log(`✅ Navigation ${i} screenshot saved`);
        } catch (e) {
            console.log('❌ Error clicking link', i, ':', e.message);
        }
    }

} catch (error) {
    console.error('❌ Error:', error.message);
} finally {
    await browser.close();
    console.log('🎉 Audit complete!');
}
