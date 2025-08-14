import puppeteer from 'puppeteer';
import fs from 'fs';

async function testNavigation() {
    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: '/usr/bin/chromium',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Login
        await page.goto('https://api.askproai.de/emergency-login.php');
        await page.type('input[type="email"]', 'fabian@askproai.de');
        await page.type('input[type="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForNavigation();

        console.log('Current URL:', page.url());

        // Wait for page to load
        await new Promise(r => setTimeout(r, 5000));

        // Look for navigation menu items specifically
        const detailedNav = await page.evaluate(() => {
            const result = {
                allNavElements: [],
                menuItems: [],
                sidebarState: {},
                possibleMenus: []
            };

            // Check for common navigation patterns
            const selectors = [
                'nav', '.navigation', '.sidebar', '.menu', '.nav-menu',
                'aside', '.admin-nav', '.side-nav', 'ul li a', 
                '[role="navigation"]', '.fi-sidebar-nav', '.fi-sidebar'
            ];

            selectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach((el, i) => {
                    const rect = el.getBoundingClientRect();
                    const styles = getComputedStyle(el);
                    
                    if (rect.width > 0 && rect.height > 0) {
                        result.allNavElements.push({
                            selector: selector,
                            index: i,
                            tag: el.tagName,
                            classes: el.className,
                            text: el.textContent ? el.textContent.trim().substring(0, 200) : '',
                            position: {
                                x: Math.round(rect.x),
                                y: Math.round(rect.y), 
                                width: Math.round(rect.width),
                                height: Math.round(rect.height)
                            },
                            styles: {
                                position: styles.position,
                                display: styles.display,
                                visibility: styles.visibility,
                                zIndex: styles.zIndex,
                                transform: styles.transform
                            }
                        });
                    }
                });
            });

            // Look specifically for menu items
            const menuSelectors = [
                '.fi-sidebar-nav a', '.fi-sidebar-nav li', 
                'nav ul li', 'aside ul li', '.menu-item',
                '.nav-item', '[role="menuitem"]'
            ];

            menuSelectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach((el, i) => {
                    const rect = el.getBoundingClientRect();
                    const styles = getComputedStyle(el);
                    
                    result.menuItems.push({
                        selector: selector,
                        index: i,
                        tag: el.tagName,
                        text: el.textContent ? el.textContent.trim() : '',
                        href: el.href || '',
                        position: {
                            x: Math.round(rect.x),
                            y: Math.round(rect.y),
                            width: Math.round(rect.width),
                            height: Math.round(rect.height)
                        },
                        isVisible: rect.width > 0 && rect.height > 0,
                        isClickable: styles.pointerEvents !== 'none' && 
                                   styles.visibility !== 'hidden' &&
                                   styles.display !== 'none',
                        styles: {
                            pointerEvents: styles.pointerEvents,
                            visibility: styles.visibility,
                            display: styles.display
                        }
                    });
                });
            });

            // Check sidebar state
            const sidebar = document.querySelector('.fi-sidebar, aside, .sidebar');
            if (sidebar) {
                const rect = sidebar.getBoundingClientRect();
                const styles = getComputedStyle(sidebar);
                
                result.sidebarState = {
                    exists: true,
                    classes: sidebar.className,
                    position: {
                        x: rect.x,
                        y: rect.y,
                        width: rect.width,
                        height: rect.height
                    },
                    styles: {
                        position: styles.position,
                        transform: styles.transform,
                        visibility: styles.visibility,
                        display: styles.display,
                        zIndex: styles.zIndex
                    },
                    isOpen: sidebar.className.includes('open') || !sidebar.className.includes('closed'),
                    isVisible: rect.width > 0 && rect.height > 0
                };
            }

            return result;
        });

        console.log('Navigation Analysis:');
        console.log('- Total nav elements:', detailedNav.allNavElements.length);
        console.log('- Menu items found:', detailedNav.menuItems.length);
        console.log('- Sidebar exists:', detailedNav.sidebarState.exists);
        console.log('- Sidebar visible:', detailedNav.sidebarState.isVisible);

        // Save detailed analysis
        fs.writeFileSync('./ui-audit-screenshots/detailed-navigation.json', JSON.stringify(detailedNav, null, 2));

        // Take a screenshot focusing on the navigation area
        await page.screenshot({ 
            path: './ui-audit-screenshots/navigation-focus.png',
            clip: { x: 0, y: 0, width: 400, height: 800 }
        });

        // Try to find the hamburger menu button or sidebar toggle
        const toggleButtons = await page.$$('button[class*="sidebar"], button[class*="menu"], [aria-label*="menu"]');
        console.log('Found', toggleButtons.length, 'potential menu toggle buttons');

        if (toggleButtons.length > 0) {
            for (let i = 0; i < toggleButtons.length; i++) {
                try {
                    const buttonInfo = await toggleButtons[i].evaluate(el => ({
                        text: el.textContent.trim(),
                        classes: el.className,
                        ariaLabel: el.getAttribute('aria-label')
                    }));
                    
                    console.log(`Toggle button ${i}:`, buttonInfo);
                    
                    // Try clicking it
                    await toggleButtons[i].click();
                    await new Promise(r => setTimeout(r, 1000));
                    
                    // Take screenshot after toggle
                    await page.screenshot({ 
                        path: `./ui-audit-screenshots/after-toggle-${i}.png`,
                        fullPage: true
                    });
                } catch (e) {
                    console.log(`Error with toggle button ${i}:`, e.message);
                }
            }
        }

    } catch (e) {
        console.error('Navigation test error:', e.message);
    }

    await browser.close();
    console.log('Navigation test complete!');
}

testNavigation();
