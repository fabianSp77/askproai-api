import puppeteer from 'puppeteer';
import fs from 'fs';

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

async function audit() {
    if (!fs.existsSync('./ui-audit-screenshots')) {
        fs.mkdirSync('./ui-audit-screenshots');
    }

    console.log('Starting audit...');
    
    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: '/usr/bin/chromium',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Login page
        await page.goto('https://api.askproai.de/emergency-login.php');
        await sleep(2000);
        await page.screenshot({ path: './ui-audit-screenshots/01-login.png', fullPage: true });
        console.log('Login screenshot saved');

        // Login
        await page.type('input[type="email"]', 'fabian@askproai.de');
        await page.type('input[type="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForNavigation();
        
        console.log('Logged in to:', page.url());

        // Dashboard
        await sleep(3000);
        await page.screenshot({ path: './ui-audit-screenshots/02-dashboard.png', fullPage: true });
        console.log('Dashboard screenshot saved');

        // Analyze
        const data = await page.evaluate(() => {
            const nav = document.querySelector('nav, .sidebar, aside');
            const links = Array.from(document.querySelectorAll('nav a, .sidebar a, aside a'));
            
            return {
                hasNav: !!nav,
                navText: nav ? nav.textContent.substring(0, 200) : '',
                linkCount: links.length,
                links: links.map(l => ({
                    text: l.textContent.trim(),
                    href: l.href
                })).slice(0, 5)
            };
        });
        
        console.log('Navigation analysis:', data);
        fs.writeFileSync('./ui-audit-screenshots/data.json', JSON.stringify(data, null, 2));

    } catch (e) {
        console.error('Error:', e.message);
    }
    
    await browser.close();
    console.log('Done!');
}

audit();
