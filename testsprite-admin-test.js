/**
 * TestSprite Test Suite for AskProAI Admin Panel
 * Tests navigation functionality after implementing universal click handler
 */

const TestSprite = require('@testsprite/testsprite-mcp');
const config = require('./testsprite.config.json');

class AdminPanelTest {
  constructor() {
    this.ts = new TestSprite(config.testsprite);
  }

  async runTests() {
    console.log('🧪 Starting TestSprite Admin Panel Tests...\n');
    
    try {
      // Test 1: Check if universal click handler is loaded
      await this.testUniversalClickHandler();
      
      // Test 2: Test navigation links
      await this.testNavigationLinks();
      
      // Test 3: Test mobile menu
      await this.testMobileMenu();
      
      // Test 4: Check for problematic CSS
      await this.testCSS();
      
      // Test 5: Performance test
      await this.testNavigationPerformance();
      
      console.log('\n✅ All tests completed!');
      
    } catch (error) {
      console.error('❌ Test failed:', error);
    }
  }

  async testUniversalClickHandler() {
    console.log('📋 Test 1: Checking Universal Click Handler...');
    
    await this.ts.navigate('/admin');
    
    // Check if universal-click-handler.js is loaded
    const hasHandler = await this.ts.evaluate(() => {
      const scripts = Array.from(document.scripts);
      return scripts.some(s => s.src.includes('universal-click-handler.js'));
    });
    
    if (hasHandler) {
      console.log('  ✅ Universal click handler is loaded');
    } else {
      console.log('  ❌ Universal click handler NOT found');
    }
    
    // Check if console-cleanup.js is loaded
    const hasCleanup = await this.ts.evaluate(() => {
      const scripts = Array.from(document.scripts);
      return scripts.some(s => s.src.includes('console-cleanup.js'));
    });
    
    console.log(`  ${hasCleanup ? '✅' : '❌'} Console cleanup is ${hasCleanup ? '' : 'NOT '}loaded`);
  }

  async testNavigationLinks() {
    console.log('\n📋 Test 2: Testing Navigation Links...');
    
    const links = [
      { selector: 'a[href="/admin/calls"]', name: 'Calls' },
      { selector: 'a[href="/admin/customers"]', name: 'Customers' },
      { selector: 'a[href="/admin/appointments"]', name: 'Appointments' },
    ];
    
    for (const link of links) {
      try {
        // Check if link exists
        const exists = await this.ts.waitForElement(link.selector, 5000);
        if (!exists) {
          console.log(`  ❌ ${link.name} link not found`);
          continue;
        }
        
        // Check if link is clickable
        const isClickable = await this.ts.evaluate((selector) => {
          const el = document.querySelector(selector);
          if (!el) return false;
          
          const computed = window.getComputedStyle(el);
          return computed.pointerEvents !== 'none' && 
                 computed.cursor === 'pointer';
        }, link.selector);
        
        console.log(`  ${isClickable ? '✅' : '❌'} ${link.name} link is ${isClickable ? 'clickable' : 'NOT clickable'}`);
        
        // Try to click
        if (isClickable) {
          await this.ts.click(link.selector);
          await this.ts.waitForNavigation();
          
          const currentUrl = await this.ts.getUrl();
          const success = currentUrl.includes(link.selector.match(/href="([^"]+)"/)[1]);
          
          console.log(`    ${success ? '✅' : '❌'} Navigation ${success ? 'successful' : 'failed'}`);
          
          // Go back
          await this.ts.navigate('/admin');
        }
      } catch (error) {
        console.log(`  ❌ Error testing ${link.name}: ${error.message}`);
      }
    }
  }

  async testMobileMenu() {
    console.log('\n📋 Test 3: Testing Mobile Menu...');
    
    // Set mobile viewport
    await this.ts.setViewport({ width: 375, height: 667 });
    
    // Check if mobile menu toggle exists
    const toggleExists = await this.ts.waitForElement('.fi-topbar-open-sidebar-btn', 5000);
    
    if (toggleExists) {
      console.log('  ✅ Mobile menu toggle found');
      
      // Try to click it
      await this.ts.click('.fi-topbar-open-sidebar-btn');
      await this.ts.wait(500);
      
      // Check if sidebar is visible
      const sidebarVisible = await this.ts.evaluate(() => {
        const sidebar = document.querySelector('.fi-sidebar');
        if (!sidebar) return false;
        
        const computed = window.getComputedStyle(sidebar);
        return computed.display !== 'none' && computed.visibility !== 'hidden';
      });
      
      console.log(`  ${sidebarVisible ? '✅' : '❌'} Sidebar ${sidebarVisible ? 'opens' : 'does NOT open'} on mobile`);
    } else {
      console.log('  ❌ Mobile menu toggle not found');
    }
    
    // Reset viewport
    await this.ts.setViewport({ width: 1920, height: 1080 });
  }

  async testCSS() {
    console.log('\n📋 Test 4: Checking for Problematic CSS...');
    
    // Check for pointer-events: none on pseudo-elements
    const hasProblematicCSS = await this.ts.evaluate(() => {
      // Check in stylesheets
      let found = false;
      for (const sheet of document.styleSheets) {
        try {
          for (const rule of sheet.cssRules) {
            if (rule.cssText && rule.cssText.includes('::before') || rule.cssText.includes('::after')) {
              if (rule.cssText.includes('pointer-events: none')) {
                found = true;
                break;
              }
            }
          }
        } catch (e) {
          // Cross-origin stylesheets
        }
      }
      return found;
    });
    
    console.log(`  ${hasProblematicCSS ? '❌' : '✅'} Pseudo-elements ${hasProblematicCSS ? 'HAVE' : 'do NOT have'} pointer-events: none`);
    
    // Count elements with pointer-events: none
    const blockedCount = await this.ts.evaluate(() => {
      const elements = document.querySelectorAll('*');
      let count = 0;
      elements.forEach(el => {
        if (window.getComputedStyle(el).pointerEvents === 'none') {
          count++;
        }
      });
      return count;
    });
    
    console.log(`  ℹ️  ${blockedCount} elements have pointer-events: none`);
  }

  async testNavigationPerformance() {
    console.log('\n📋 Test 5: Navigation Performance...');
    
    const startTime = Date.now();
    
    // Navigate to calls
    await this.ts.click('a[href="/admin/calls"]');
    await this.ts.waitForNavigation();
    
    const callsLoadTime = Date.now() - startTime;
    console.log(`  ⏱️  Calls page loaded in ${callsLoadTime}ms`);
    
    // Navigate back to dashboard
    const dashStart = Date.now();
    await this.ts.click('a[href="/admin"]');
    await this.ts.waitForNavigation();
    
    const dashLoadTime = Date.now() - dashStart;
    console.log(`  ⏱️  Dashboard loaded in ${dashLoadTime}ms`);
    
    const avgTime = (callsLoadTime + dashLoadTime) / 2;
    console.log(`  ${avgTime < 1000 ? '✅' : '⚠️'} Average load time: ${avgTime}ms ${avgTime < 1000 ? '(good)' : '(slow)'}`);
  }
}

// Run tests if called directly
if (require.main === module) {
  const test = new AdminPanelTest();
  test.runTests().catch(console.error);
}

module.exports = AdminPanelTest;