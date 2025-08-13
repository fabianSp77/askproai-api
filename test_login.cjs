const puppeteer = require('puppeteer');

(async () => {
  console.log('Starting Puppeteer tests for AskProAI admin logins...');
  
  const browser = await puppeteer.launch({
    headless: false,
    defaultViewport: { width: 1920, height: 1080 },
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  
  const page = await browser.newPage();
  await page.setViewport({ width: 1920, height: 1080 });
  
  try {
    // Test 1: Filament Normal Login
    console.log('\n=== TEST 1: Filament Normal Login ===');
    await page.goto('https://api.askproai.de/admin/login', { waitUntil: 'networkidle2' });
    await page.screenshot({ path: '/tmp/filament_login_page.png', fullPage: true });
    console.log('Screenshot saved: filament_login_page.png');
    
    // Wait for login form
    await page.waitForSelector('input[type="email"]', { timeout: 10000 });
    
    // Fill in credentials
    await page.type('input[type="email"]', 'fabian@askproai.de');
    await page.type('input[type="password"]', 'password');
    
    // Take screenshot before submitting
    await page.screenshot({ path: '/tmp/filament_before_submit.png', fullPage: true });
    console.log('Screenshot saved: filament_before_submit.png');
    
    // Submit form
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Take screenshot after submit
    await page.screenshot({ path: '/tmp/filament_after_submit.png', fullPage: true });
    console.log('Screenshot saved: filament_after_submit.png');
    
    // Check for errors or success
    const currentUrl = page.url();
    console.log('Current URL after login:', currentUrl);
    
    const errorMessage = await page.evaluate(() => {
      const errors = document.querySelectorAll('.fi-fo-field-wrp-error-message, .alert-danger, .error');
      return errors.length > 0 ? errors[0].textContent : null;
    });
    
    if (errorMessage) {
      console.log('Error found:', errorMessage);
    }
    
  } catch (error) {
    console.error('Error in Filament login test:', error.message);
  }
  
  try {
    // Test 2: Emergency Login
    console.log('\n=== TEST 2: Emergency Login ===');
    await page.goto('https://api.askproai.de/emergency-login.php', { waitUntil: 'networkidle2' });
    await page.screenshot({ path: '/tmp/emergency_login_page.png', fullPage: true });
    console.log('Screenshot saved: emergency_login_page.png');
    
    // Wait for form elements
    await page.waitForSelector('input[name="email"]', { timeout: 10000 });
    
    // Fill in credentials
    await page.type('input[name="email"]', 'fabian@askproai.de');
    await page.type('input[name="password"]', 'password');
    
    // Take screenshot before submitting
    await page.screenshot({ path: '/tmp/emergency_before_submit.png', fullPage: true });
    console.log('Screenshot saved: emergency_before_submit.png');
    
    // Submit form
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
    
    // Take screenshot after submit
    await page.screenshot({ path: '/tmp/emergency_after_submit.png', fullPage: true });
    console.log('Screenshot saved: emergency_after_submit.png');
    
    const emergencyUrl = page.url();
    console.log('Current URL after emergency login:', emergencyUrl);
    
    // Check if we're in admin dashboard
    if (emergencyUrl.includes('/admin')) {
      console.log('✅ Emergency login successful - redirected to admin panel');
      
      // Test 3: Flowbite theme and navigation
      console.log('\n=== TEST 3: Testing Flowbite Theme and Navigation ===');
      
      // Wait for dashboard to load
      await page.waitForTimeout(3000);
      await page.screenshot({ path: '/tmp/admin_dashboard.png', fullPage: true });
      console.log('Screenshot saved: admin_dashboard.png');
      
      // Test navigation menu
      const navItems = await page.evaluate(() => {
        const items = document.querySelectorAll('nav a, .fi-sidebar-nav a');
        return Array.from(items).map(item => ({
          text: item.textContent.trim(),
          href: item.href,
          visible: item.offsetWidth > 0 && item.offsetHeight > 0
        }));
      });
      
      console.log('Navigation items found:', navItems.length);
      navItems.forEach(item => console.log('- ', item.text, item.visible ? '(visible)' : '(hidden)'));
      
      // Try to navigate to different sections
      const testRoutes = ['/admin/companies', '/admin/appointments', '/admin/calls'];
      
      for (const route of testRoutes) {
        try {
          console.log('Testing route: ' + route);
          const fullUrl = 'https://api.askproai.de' + route;
          await page.goto(fullUrl, { waitUntil: 'networkidle2', timeout: 10000 });
          const routeName = route.split('/').pop();
          await page.screenshot({ path: '/tmp/admin_' + routeName + '.png', fullPage: true });
          console.log('Screenshot saved: admin_' + routeName + '.png');
        } catch (routeError) {
          console.log('Error accessing ' + route + ':', routeError.message);
        }
      }
    } else {
      console.log('❌ Emergency login failed or did not redirect to admin');
    }
    
  } catch (error) {
    console.error('Error in emergency login test:', error.message);
  }
  
  console.log('\n=== Test Summary ===');
  console.log('All screenshots saved to /tmp/ directory');
  console.log('Check the screenshots for visual confirmation');
  
  await browser.close();
})();
