import puppeteer from 'puppeteer';
import fs from 'fs';

async function detailedAudit() {
    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: '/usr/bin/chromium',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const viewports = [
        { name: 'desktop', width: 1920, height: 1080 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'mobile', width: 375, height: 667 }
    ];

    for (const viewport of viewports) {
        console.log(`Testing ${viewport.name} (${viewport.width}x${viewport.height})`);
        
        const page = await browser.newPage();
        await page.setViewport(viewport);

        try {
            // Login
            await page.goto('https://api.askproai.de/emergency-login.php');
            await new Promise(r => setTimeout(r, 2000));
            await page.type('input[type="email"]', 'fabian@askproai.de');
            await page.type('input[type="password"]', 'password');
            await page.click('button[type="submit"]');
            await page.waitForNavigation();

            // Take dashboard screenshot
            await new Promise(r => setTimeout(r, 3000));
            await page.screenshot({ 
                path: `./ui-audit-screenshots/dashboard-${viewport.name}.png`, 
                fullPage: true 
            });

            // Detailed analysis
            const analysis = await page.evaluate(() => {
                const result = {
                    viewport: { width: window.innerWidth, height: window.innerHeight },
                    navigation: {},
                    issues: []
                };

                // Find navigation container
                const navContainers = ['nav', '.sidebar', 'aside', '.navigation'];
                for (const selector of navContainers) {
                    const nav = document.querySelector(selector);
                    if (nav) {
                        const rect = nav.getBoundingClientRect();
                        const styles = getComputedStyle(nav);
                        
                        result.navigation[selector] = {
                            exists: true,
                            position: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
                            styles: {
                                position: styles.position,
                                zIndex: styles.zIndex,
                                display: styles.display,
                                visibility: styles.visibility,
                                overflow: styles.overflow,
                                transform: styles.transform
                            },
                            classes: nav.className,
                            isVisible: rect.width > 0 && rect.height > 0 && styles.visibility !== 'hidden'
                        };

                        // Check for overlapping
                        const allElements = document.querySelectorAll('*');
                        let overlappingElements = [];
                        
                        for (const el of allElements) {
                            if (el === nav) continue;
                            const elRect = el.getBoundingClientRect();
                            
                            // Check if elements overlap
                            if (rect.x < elRect.x + elRect.width &&
                                rect.x + rect.width > elRect.x &&
                                rect.y < elRect.y + elRect.height &&
                                rect.y + rect.height > elRect.y &&
                                elRect.width > 0 && elRect.height > 0) {
                                
                                overlappingElements.push({
                                    tag: el.tagName,
                                    classes: el.className,
                                    position: { x: elRect.x, y: elRect.y, width: elRect.width, height: elRect.height },
                                    zIndex: getComputedStyle(el).zIndex
                                });
                            }
                        }
                        
                        result.navigation[selector].overlapping = overlappingElements.slice(0, 10); // Limit results
                    }
                }

                // Check navigation links
                const navLinks = document.querySelectorAll('nav a, .sidebar a, aside a');
                result.navigationLinks = [];
                
                for (const link of navLinks) {
                    const rect = link.getBoundingClientRect();
                    const styles = getComputedStyle(link);
                    
                    result.navigationLinks.push({
                        text: link.textContent.trim(),
                        href: link.href,
                        position: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
                        isClickable: rect.width > 0 && rect.height > 0 && 
                                   styles.pointerEvents !== 'none' && 
                                   styles.visibility !== 'hidden',
                        styles: {
                            pointerEvents: styles.pointerEvents,
                            visibility: styles.visibility,
                            display: styles.display
                        }
                    });
                }

                // Check for layout issues
                if (result.navigation.nav && !result.navigation.nav.isVisible) {
                    result.issues.push('Navigation is not visible');
                }
                
                const nonClickableLinks = result.navigationLinks.filter(l => !l.isClickable);
                if (nonClickableLinks.length > 0) {
                    result.issues.push(`${nonClickableLinks.length} navigation links are not clickable`);
                }

                return result;
            });

            // Save analysis
            fs.writeFileSync(
                `./ui-audit-screenshots/analysis-${viewport.name}.json`, 
                JSON.stringify(analysis, null, 2)
            );

            console.log(`${viewport.name} analysis:`, {
                hasNavigation: Object.keys(analysis.navigation).length > 0,
                navigationLinks: analysis.navigationLinks.length,
                issues: analysis.issues.length
            });

        } catch (e) {
            console.error(`Error in ${viewport.name}:`, e.message);
        }

        await page.close();
    }

    await browser.close();
    console.log('Detailed audit complete!');
}

detailedAudit();
