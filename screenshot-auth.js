import puppeteer from 'puppeteer';

(async () => {
    const args = process.argv.slice(2);
    const email = args[0] || 'fabian@askproai.de';
    const password = args[1] || 'Qwe421as1!1';
    const targetPath = args[2] || '/admin/company-integration-portal';
    
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const page = await browser.newPage();
    
    // Set viewport
    await page.setViewport({
        width: 1920,
        height: 1080,
        deviceScaleFactor: 2
    });
    
    // Navigate to login page
    await page.goto('https://api.askproai.de/admin/login', {
        waitUntil: 'networkidle0'
    });
    
    // Fill in login form
    await page.type('input[type="email"]', email);
    await page.type('input[type="password"]', password);
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Wait for navigation
    await page.waitForNavigation({
        waitUntil: 'networkidle0'
    });
    
    // Navigate to target page
    await page.goto('https://api.askproai.de' + targetPath, {
        waitUntil: 'networkidle0'
    });
    
    // Take screenshot
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `authenticated_${targetPath.replace(/\//g, '_')}_${timestamp}.png`;
    
    await page.screenshot({
        path: `./storage/app/screenshots/${filename}`,
        fullPage: true
    });
    
    console.log(`Screenshot saved: ./storage/app/screenshots/${filename}`);
    
    await browser.close();
})();