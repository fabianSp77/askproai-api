describe('Content Overflow Tests', () => {
  const viewports = [
    { name: 'desktop-hd', width: 1920, height: 1080 },
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'laptop', width: 1366, height: 768 },
    { name: 'tablet', width: 1024, height: 768 },
    { name: 'mobile', width: 375, height: 812 }
  ];

  beforeEach(() => {
    // Login as admin
    cy.visit('/admin/login');
    cy.get('input[name="email"]').type('fabian@askproai.de');
    cy.get('input[name="password"]').type('GibsonLesPaul335!');
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/admin');
  });

  viewports.forEach((viewport) => {
    describe(`${viewport.name} (${viewport.width}x${viewport.height})`, () => {
      beforeEach(() => {
        cy.viewport(viewport.width, viewport.height);
      });

      it('should not have horizontal overflow on dashboard', () => {
        cy.visit('/admin');
        
        // Check main layout container
        cy.get('.fi-layout').should(($el) => {
          const el = $el[0];
          expect(el.scrollWidth).to.be.lte(el.clientWidth + 1); // +1 for rounding
        });

        // Check main content
        cy.get('.fi-main').should(($el) => {
          const el = $el[0];
          expect(el.scrollWidth).to.be.lte(el.clientWidth + 1);
        });

        // Check body for overflow
        cy.get('body').should(($body) => {
          const body = $body[0];
          expect(body.scrollWidth).to.be.lte(body.clientWidth + 1);
        });
      });

      it('should not cut off content on the right side', () => {
        cy.visit('/admin');
        
        // Check if all widgets are fully visible
        cy.get('.fi-wi-stats-overview-card').each(($card) => {
          cy.wrap($card).should('be.visible');
          
          // Get card boundaries
          const rect = $card[0].getBoundingClientRect();
          const viewportWidth = Cypress.config('viewportWidth');
          
          // Card should not extend beyond viewport
          expect(rect.right).to.be.lte(viewportWidth);
        });
      });

      it('should handle wide tables with horizontal scroll', () => {
        cy.visit('/admin/calls');
        
        // Table wrapper should have overflow-x auto
        cy.get('.fi-ta-table-wrap').should('have.css', 'overflow-x').and('match', /auto|scroll/);
        
        // Table itself can be wider than viewport
        cy.get('.fi-ta-table').should('exist');
        
        // But the wrapper should not overflow the viewport
        cy.get('.fi-ta-table-wrap').should(($wrap) => {
          const wrap = $wrap[0];
          const rect = wrap.getBoundingClientRect();
          expect(rect.right).to.be.lte(viewport.width);
        });
      });

      it('should not have overflow on multiple pages', () => {
        const pages = [
          '/admin',
          '/admin/calls', 
          '/admin/customers',
          '/admin/appointments',
          '/admin/invoices'
        ];

        pages.forEach((page) => {
          cy.visit(page);
          
          // Wait for page to load
          cy.get('.fi-page').should('be.visible');
          
          // Check for horizontal overflow
          cy.get('.fi-layout').should(($el) => {
            const el = $el[0];
            expect(el.scrollWidth).to.be.lte(el.clientWidth + 1);
          });
        });
      });

      it('should take screenshot for visual regression', () => {
        // Dashboard
        cy.visit('/admin');
        cy.wait(1000); // Wait for any animations
        cy.screenshot(`${viewport.name}-dashboard-overflow-fix`);
        
        // Calls page with table
        cy.visit('/admin/calls');
        cy.wait(1000);
        cy.screenshot(`${viewport.name}-calls-overflow-fix`);
        
        // Page with complex grid layout
        cy.visit('/admin/a-i-call-center');
        cy.wait(1000);
        cy.screenshot(`${viewport.name}-ai-center-overflow-fix`);
      });
    });
  });

  describe('Debug Helper', () => {
    it('should identify elements causing overflow', () => {
      cy.visit('/admin');
      
      // Find all elements that might cause overflow
      cy.window().then((win) => {
        const doc = win.document;
        const body = doc.body;
        const html = doc.documentElement;
        
        const elements = [];
        const all = doc.querySelectorAll('*');
        
        all.forEach((el) => {
          const rect = el.getBoundingClientRect();
          if (rect.right > win.innerWidth || rect.width > win.innerWidth) {
            elements.push({
              element: el,
              tagName: el.tagName,
              className: el.className,
              id: el.id,
              width: rect.width,
              right: rect.right,
              overflow: rect.right - win.innerWidth
            });
          }
        });
        
        if (elements.length > 0) {
          console.log('Elements causing overflow:', elements);
          cy.log(`Found ${elements.length} elements causing overflow`);
          
          // Log details of each overflowing element
          elements.forEach((item, index) => {
            cy.log(`${index + 1}. ${item.tagName}${item.id ? '#' + item.id : ''}${item.className ? '.' + item.className.split(' ').join('.') : ''} - Overflow: ${item.overflow}px`);
          });
        } else {
          cy.log('No elements causing overflow found ✓');
        }
      });
    });
  });
});