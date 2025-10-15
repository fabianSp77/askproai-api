/**
 * Final UI Validation with Screenshots
 *
 * Tests all appointment pages and creates comprehensive screenshots
 * for visual analysis by agents
 *
 * Date: 2025-10-11
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots', `final-review-${new Date().toISOString().split('T')[0]}`);

// Test appointments
const APPOINTMENTS = [675, 654, 632];

// Create directory
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function takeScreenshot(page, name, options = {}) {
    const filepath = path.join(SCREENSHOT_DIR, `${name}.png`);
    await page.screenshot({
        path: filepath,
        fullPage: options.fullPage !== false,
        ...options
    });
    console.log(`📸 ${name}.png`);
    return filepath;
}

async function captureAppointment(browser, appointmentId) {
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        console.log(`\n📋 Capturing Appointment #${appointmentId}...`);

        // Navigate (will redirect to login for unauthenticated)
        await page.goto(`${BASE_URL}/admin/appointments/${appointmentId}`, {
            waitUntil: 'networkidle2',
            timeout: 10000
        });

        await page.waitForTimeout(2000);

        // Full page screenshot
        await takeScreenshot(page, `apt${appointmentId}_01_full_page`);

        // Try to scroll to timeline widget (if page loaded)
        const currentUrl = page.url();
        if (!currentUrl.includes('/login')) {
            // Scroll to timeline
            await page.evaluate(() => {
                const timeline = document.querySelector('[class*="Termin"]');
                if (timeline) {
                    timeline.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
            await page.waitForTimeout(1000);
            await takeScreenshot(page, `apt${appointmentId}_02_timeline_widget`);

            // Scroll to top for tabs
            await page.evaluate(() => window.scrollTo(0, 0));
            await page.waitForTimeout(500);
            await takeScreenshot(page, `apt${appointmentId}_03_top_sections`);

            // Try to find and click Änderungs-Audit tab
            try {
                const tabButton = await page.$('button:has-text("Audit"), button:has-text("Änderung")');
                if (tabButton) {
                    await tabButton.click();
                    await page.waitForTimeout(1000);
                    await takeScreenshot(page, `apt${appointmentId}_04_modifications_tab`);
                }
            } catch (e) {
                console.log('  ⚠️  Could not find/click Modifications tab');
            }

        } else {
            console.log('  ℹ️  Redirected to login (expected for unauthenticated)');
        }

        console.log(`✅ Appointment #${appointmentId} captured`);

    } catch (error) {
        console.error(`❌ Error capturing #${appointmentId}:`, error.message);
        await takeScreenshot(page, `apt${appointmentId}_error`);
    } finally {
        await page.close();
    }
}

async function main() {
    console.log('🚀 Starting Final UI Validation');
    console.log('📁 Screenshots will be saved to:', SCREENSHOT_DIR);
    console.log('─'.repeat(70));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--window-size=1920,1080'
        ]
    });

    try {
        for (const aptId of APPOINTMENTS) {
            await captureAppointment(browser, aptId);
        }

        console.log('\n' + '═'.repeat(70));
        console.log('✅ All screenshots captured successfully');
        console.log('📁 Location:', SCREENSHOT_DIR);
        console.log('📊 Total screenshots:', fs.readdirSync(SCREENSHOT_DIR).length);

        // Create index file
        const screenshots = fs.readdirSync(SCREENSHOT_DIR).sort();
        const indexContent = `# Final UI Validation Screenshots - ${new Date().toISOString()}

## Captured Screenshots

${screenshots.map(f => `- ${f}`).join('\n')}

## Test Appointments
- #675: Current data (reschedule + cancel)
- #654: Legacy data (NULL fields)
- #632: Previous bug fix

## Analysis
Use these screenshots for visual review of:
- Timeline widget ("Termin-Lebenslauf")
- Modifications tab ("Änderungs-Audit")
- Labels and descriptions
- German language compliance
- No duplicates
- Clean UI layout
`;

        fs.writeFileSync(
            path.join(SCREENSHOT_DIR, 'INDEX.md'),
            indexContent
        );

        console.log('📝 INDEX.md created');

    } finally {
        await browser.close();
    }
}

main()
    .then(() => {
        console.log('\n🎉 Screenshot capture complete');
        process.exit(0);
    })
    .catch(error => {
        console.error('\n💥 Fatal error:', error);
        process.exit(1);
    });
