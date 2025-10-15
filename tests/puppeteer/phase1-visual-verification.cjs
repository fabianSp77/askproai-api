#!/usr/bin/env node

/**
 * Phase 1 Visual Verification - Screenshot Testing
 * Takes screenshots of all Phase 1 features
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://api.askproai.de';
const SCREENSHOTS_DIR = path.join(__dirname, 'screenshots', 'phase1-visual');

if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

function log(message) {
    console.log(`[${new Date().toISOString()}] ${message}`);
}

async function screenshot(page, name, fullPage = true) {
    const filename = `${name}.png`;
    const filepath = path.join(SCREENSHOTS_DIR, filename);
    await page.screenshot({ path: filepath, fullPage });
    log(`✅ Screenshot: ${filename}`);
    return filename;
}

async function main() {
    log('Starting Phase 1 Visual Verification...');

    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080'
        ],
        defaultViewport: { width: 1920, height: 1080 }
    });

    try {
        const page = await browser.newPage();

        // Test 1: Appointments List
        log('\n📊 Capturing Appointments List...');
        try {
            await page.goto(`${BASE_URL}/admin/appointments`, {
                waitUntil: 'networkidle2',
                timeout: 30000
            });
            await new Promise(resolve => setTimeout(resolve, 3000));
            await screenshot(page, '01-appointments-list');
        } catch (e) {
            log(`⚠️ Could not load appointments list: ${e.message}`);
        }

        // Test 2: Create Appointment Form
        log('\n📝 Capturing Create Appointment Form...');
        try {
            await page.goto(`${BASE_URL}/admin/appointments/create`, {
                waitUntil: 'networkidle2',
                timeout: 30000
            });
            await new Promise(resolve => setTimeout(resolve, 3000));
            await screenshot(page, '02-create-appointment-form');

            // Scroll down to see more of the form
            await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
            await new Promise(resolve => setTimeout(resolve, 1000));
            await screenshot(page, '03-create-appointment-form-scrolled');
        } catch (e) {
            log(`⚠️ Could not load create form: ${e.message}`);
        }

        // Test 3: Inspect Page Source
        log('\n🔍 Analyzing Page Source...');
        try {
            const content = await page.content();

            const features = {
                'Customer History Widget': content.includes('customer_history') || content.includes('Kunden-Historie'),
                'Next Available Slot': content.includes('nextAvailableSlot') || content.includes('Nächster freier Slot'),
                'Service Select': content.includes('service_id'),
                'Staff Select': content.includes('staff_id'),
                'DateTime Picker': content.includes('starts_at')
            };

            log('\n📋 Features Found in Page Source:');
            Object.entries(features).forEach(([feature, found]) => {
                log(`  ${found ? '✅' : '❌'} ${feature}`);
            });
        } catch (e) {
            log(`⚠️ Could not analyze source: ${e.message}`);
        }

        // Test 4: Database Check
        log('\n🗄️ Checking Database for Appointments...');
        try {
            const { execSync } = require('child_process');
            const appointmentCount = execSync(
                `mysql -N askproai_db -e "SELECT COUNT(*) FROM appointments;"`,
                { encoding: 'utf-8' }
            ).trim();

            const recentAppointments = execSync(
                `mysql -N askproai_db -e "SELECT id, customer_id, service_id, staff_id, DATE_FORMAT(starts_at, '%Y-%m-%d %H:%i') as time, status FROM appointments ORDER BY created_at DESC LIMIT 5;" | column -t`,
                { encoding: 'utf-8' }
            );

            log(`\n  Total Appointments: ${appointmentCount}`);
            log(`\n  Recent Appointments:\n${recentAppointments}`);
        } catch (e) {
            log(`⚠️ Could not query database: ${e.message}`);
        }

        log('\n✅ Visual verification complete!');
        log(`📁 Screenshots saved to: ${SCREENSHOTS_DIR}`);

    } catch (error) {
        log(`❌ Error: ${error.message}`);
        console.error(error);
    } finally {
        await browser.close();
    }
}

main().catch(console.error);
