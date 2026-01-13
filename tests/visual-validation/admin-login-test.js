import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

(async () => {
  const browser = await chromium.launch({ headless: true });
  const screenshotsDir = path.join(__dirname, 'screenshots');

  // Create screenshots directory
  if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
  }

  const url = 'https://api.askproai.de/admin/login';

  console.log('Starting visual validation of Admin Login Page...');
  console.log(`URL: ${url}\n`);

  // Test 1: Desktop Light Mode
  console.log('1. Desktop Light Mode (1920x1080)');
  const desktopContext = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    colorScheme: 'light'
  });
  const desktopPage = await desktopContext.newPage();
  await desktopPage.goto(url, { waitUntil: 'networkidle' });
  await desktopPage.waitForTimeout(2000); // Wait for animations
  await desktopPage.screenshot({
    path: path.join(screenshotsDir, '01-desktop-light.png'),
    fullPage: true
  });
  console.log('   ✓ Screenshot saved: 01-desktop-light.png');
  await desktopContext.close();

  // Test 2: Desktop Dark Mode
  console.log('2. Desktop Dark Mode (1920x1080)');
  const darkContext = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    colorScheme: 'dark'
  });
  const darkPage = await darkContext.newPage();
  await darkPage.goto(url, { waitUntil: 'networkidle' });
  await darkPage.waitForTimeout(2000);
  await darkPage.screenshot({
    path: path.join(screenshotsDir, '02-desktop-dark.png'),
    fullPage: true
  });
  console.log('   ✓ Screenshot saved: 02-desktop-dark.png');
  await darkContext.close();

  // Test 3: Tablet Portrait
  console.log('3. Tablet Portrait (768x1024)');
  const tabletContext = await browser.newContext({
    viewport: { width: 768, height: 1024 },
    colorScheme: 'light'
  });
  const tabletPage = await tabletContext.newPage();
  await tabletPage.goto(url, { waitUntil: 'networkidle' });
  await tabletPage.waitForTimeout(2000);
  await tabletPage.screenshot({
    path: path.join(screenshotsDir, '03-tablet-portrait.png'),
    fullPage: true
  });
  console.log('   ✓ Screenshot saved: 03-tablet-portrait.png');
  await tabletContext.close();

  // Test 4: Mobile Portrait
  console.log('4. Mobile Portrait (375x667 - iPhone SE)');
  const mobileContext = await browser.newContext({
    viewport: { width: 375, height: 667 },
    colorScheme: 'light'
  });
  const mobilePage = await mobileContext.newPage();
  await mobilePage.goto(url, { waitUntil: 'networkidle' });
  await mobilePage.waitForTimeout(2000);
  await mobilePage.screenshot({
    path: path.join(screenshotsDir, '04-mobile-portrait.png'),
    fullPage: true
  });
  console.log('   ✓ Screenshot saved: 04-mobile-portrait.png');
  await mobileContext.close();

  // Test 5: Mobile Landscape
  console.log('5. Mobile Landscape (667x375)');
  const mobileLandscapeContext = await browser.newContext({
    viewport: { width: 667, height: 375 },
    colorScheme: 'light'
  });
  const mobileLandscapePage = await mobileLandscapeContext.newPage();
  await mobileLandscapePage.goto(url, { waitUntil: 'networkidle' });
  await mobileLandscapePage.waitForTimeout(2000);
  await mobileLandscapePage.screenshot({
    path: path.join(screenshotsDir, '05-mobile-landscape.png'),
    fullPage: true
  });
  console.log('   ✓ Screenshot saved: 05-mobile-landscape.png');
  await mobileLandscapeContext.close();

  // Test 6: Focus States - Email Field
  console.log('6. Focus State - Email Field');
  const focusContext = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    colorScheme: 'light'
  });
  const focusPage = await focusContext.newPage();
  await focusPage.goto(url, { waitUntil: 'networkidle' });
  await focusPage.waitForTimeout(2000);

  // Focus on email input
  const emailInput = await focusPage.locator('input[type="email"], input[name="email"]').first();
  if (await emailInput.count() > 0) {
    await emailInput.focus();
    await focusPage.waitForTimeout(500);
    await focusPage.screenshot({
      path: path.join(screenshotsDir, '06-focus-email.png'),
      fullPage: true
    });
    console.log('   ✓ Screenshot saved: 06-focus-email.png');
  } else {
    console.log('   ⚠ Email input not found');
  }
  await focusContext.close();

  // Test 7: Focus States - Password Field
  console.log('7. Focus State - Password Field');
  const passwordFocusContext = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    colorScheme: 'light'
  });
  const passwordFocusPage = await passwordFocusContext.newPage();
  await passwordFocusPage.goto(url, { waitUntil: 'networkidle' });
  await passwordFocusPage.waitForTimeout(2000);

  const passwordInput = await passwordFocusPage.locator('input[type="password"], input[name="password"]').first();
  if (await passwordInput.count() > 0) {
    await passwordInput.focus();
    await passwordFocusPage.waitForTimeout(500);
    await passwordFocusPage.screenshot({
      path: path.join(screenshotsDir, '07-focus-password.png'),
      fullPage: true
    });
    console.log('   ✓ Screenshot saved: 07-focus-password.png');
  } else {
    console.log('   ⚠ Password input not found');
  }
  await passwordFocusContext.close();

  // Test 8: Accessibility Analysis
  console.log('8. Accessibility Analysis');
  const a11yContext = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    colorScheme: 'light'
  });
  const a11yPage = await a11yContext.newPage();
  await a11yPage.goto(url, { waitUntil: 'networkidle' });
  await a11yPage.waitForTimeout(2000);

  // Extract accessibility information
  const a11yInfo = await a11yPage.evaluate(() => {
    const results = {
      labels: [],
      ariaLabels: [],
      formElements: [],
      headings: [],
      landmarks: [],
      focusableElements: []
    };

    // Check labels
    document.querySelectorAll('label').forEach(label => {
      results.labels.push({
        text: label.textContent.trim(),
        for: label.getAttribute('for'),
        hasFor: !!label.getAttribute('for')
      });
    });

    // Check ARIA labels
    document.querySelectorAll('[aria-label], [aria-labelledby]').forEach(el => {
      results.ariaLabels.push({
        tagName: el.tagName,
        ariaLabel: el.getAttribute('aria-label'),
        ariaLabelledBy: el.getAttribute('aria-labelledby')
      });
    });

    // Check form elements
    document.querySelectorAll('input, button, select, textarea').forEach(el => {
      results.formElements.push({
        tagName: el.tagName,
        type: el.type,
        name: el.name,
        id: el.id,
        hasLabel: !!document.querySelector(`label[for="${el.id}"]`),
        ariaLabel: el.getAttribute('aria-label'),
        placeholder: el.placeholder
      });
    });

    // Check headings
    document.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(heading => {
      results.headings.push({
        level: heading.tagName,
        text: heading.textContent.trim()
      });
    });

    // Check landmarks
    document.querySelectorAll('[role], main, nav, header, footer, aside').forEach(landmark => {
      results.landmarks.push({
        tagName: landmark.tagName,
        role: landmark.getAttribute('role')
      });
    });

    // Check focusable elements
    const focusable = 'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])';
    document.querySelectorAll(focusable).forEach(el => {
      const style = window.getComputedStyle(el);
      results.focusableElements.push({
        tagName: el.tagName,
        hasFocusStyle: style.outlineWidth !== '0px' || style.outlineColor !== 'rgba(0, 0, 0, 0)',
        tabIndex: el.tabIndex
      });
    });

    return results;
  });

  // Save accessibility report
  fs.writeFileSync(
    path.join(screenshotsDir, 'accessibility-report.json'),
    JSON.stringify(a11yInfo, null, 2)
  );
  console.log('   ✓ Accessibility report saved: accessibility-report.json');

  await a11yContext.close();

  // Test 9: DOM Structure Analysis
  console.log('9. DOM Structure Analysis');
  const domContext = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const domPage = await domContext.newPage();
  await domPage.goto(url, { waitUntil: 'networkidle' });
  await domPage.waitForTimeout(2000);

  const domStructure = await domPage.evaluate(() => {
    return {
      title: document.title,
      bodyClasses: Array.from(document.body.classList),
      hasFilamentBranding: !!document.querySelector('[class*="filament"]'),
      formAction: document.querySelector('form')?.action || 'No form found',
      cssLinks: Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(l => l.href),
      loginButton: {
        exists: !!document.querySelector('button[type="submit"]'),
        text: document.querySelector('button[type="submit"]')?.textContent.trim()
      }
    };
  });

  fs.writeFileSync(
    path.join(screenshotsDir, 'dom-structure.json'),
    JSON.stringify(domStructure, null, 2)
  );
  console.log('   ✓ DOM structure saved: dom-structure.json');

  await domContext.close();

  console.log('\n✅ Visual validation complete!');
  console.log(`📁 All screenshots and reports saved to: ${screenshotsDir}`);

  await browser.close();
})();
