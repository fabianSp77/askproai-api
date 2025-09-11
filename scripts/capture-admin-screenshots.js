#!/usr/bin/env node

import puppeteer from 'puppeteer';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function captureScreenshots() {
    console.log('Starting screenshot capture...');
    
    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ],
        executablePath: '/usr/bin/chromium' // ARM64 compatible
    });

    try {
        const page = await browser.newPage();
        
        // Desktop viewport
        await page.setViewport({ width: 1920, height: 1080 });
        console.log('Navigating to admin panel (Desktop)...');
        await page.goto('https://api.askproai.de/admin/login', {
            waitUntil: 'networkidle2',
            timeout: 30000
        });
        
        // Wait for content to load
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Capture desktop screenshot
        const desktopPath = '/var/www/api-gateway/public/screenshots/admin-desktop-' + Date.now() + '.png';
        await page.screenshot({ 
            path: desktopPath,
            fullPage: false 
        });
        console.log('Desktop screenshot saved:', desktopPath);
        
        // Tablet viewport
        await page.setViewport({ width: 768, height: 1024 });
        await page.reload({ waitUntil: 'networkidle2' });
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        const tabletPath = '/var/www/api-gateway/public/screenshots/admin-tablet-' + Date.now() + '.png';
        await page.screenshot({ 
            path: tabletPath,
            fullPage: false 
        });
        console.log('Tablet screenshot saved:', tabletPath);
        
        // Mobile viewport
        await page.setViewport({ width: 375, height: 812 });
        await page.reload({ waitUntil: 'networkidle2' });
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        const mobilePath = '/var/www/api-gateway/public/screenshots/admin-mobile-' + Date.now() + '.png';
        await page.screenshot({ 
            path: mobilePath,
            fullPage: false 
        });
        console.log('Mobile screenshot saved:', mobilePath);
        
        // Check if we're on login page or dashboard
        const pageTitle = await page.title();
        const pageUrl = page.url();
        console.log('Page title:', pageTitle);
        console.log('Current URL:', pageUrl);
        
        // Try to check for menu elements
        const hasSidebar = await page.evaluate(() => {
            return document.querySelector('.fi-sidebar') !== null ||
                   document.querySelector('.stripe-sidebar') !== null;
        });
        
        const hasMainContent = await page.evaluate(() => {
            return document.querySelector('.fi-main-ctn') !== null ||
                   document.querySelector('.stripe-main-content') !== null;
        });
        
        console.log('Has sidebar:', hasSidebar);
        console.log('Has main content:', hasMainContent);
        
        // Check for errors
        const hasError = await page.evaluate(() => {
            return document.body.innerText.includes('ErrorException') ||
                   document.body.innerText.includes('filemtime');
        });
        
        if (hasError) {
            console.log('⚠️  ERROR: Laravel error detected on page!');
            console.log('The admin panel is showing an error instead of the login/dashboard.');
        }
        
        console.log('\n✅ Screenshots captured successfully!');
        console.log('View them at: https://api.askproai.de/screenshots/');
        
    } catch (error) {
        console.error('Error capturing screenshots:', error);
    } finally {
        await browser.close();
    }
}

// Run the capture
captureScreenshots().catch(console.error);