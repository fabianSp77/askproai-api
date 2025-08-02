describe('Mobile UI Tests', () => {
  const viewports = [
    { name: 'iphone-x', width: 375, height: 812 },
    { name: 'pixel-5', width: 393, height: 851 },
    { name: 'galaxy-s21', width: 390, height: 844 },
    { name: 'ipad-mini', width: 768, height: 1024 },
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

      it('should display mobile menu toggle button', () => {
        cy.get('.fi-sidebar-toggle').should('be.visible');
        cy.get('.fi-main-sidebar').should('not.be.visible');
      });

      it('should open sidebar when toggle is clicked', () => {
        cy.get('.fi-sidebar-toggle').click();
        cy.get('.fi-main-sidebar').should('be.visible');
        cy.get('.fi-sidebar-close-overlay').should('be.visible');
      });

      it('should close sidebar when overlay is clicked', () => {
        cy.get('.fi-sidebar-toggle').click();
        cy.get('.fi-sidebar-close-overlay').click();
        cy.get('.fi-main-sidebar').should('not.be.visible');
      });

      it('should not have horizontal overflow', () => {
        cy.get('.fi-layout').should(($el) => {
          const el = $el[0];
          expect(el.scrollWidth).to.be.lte(el.clientWidth);
        });
      });

      it('should display calls table with horizontal scroll', () => {
        cy.visit('/admin/calls');
        cy.get('.fi-ta-table-wrap').should('have.css', 'overflow-x', 'auto');
      });

      it('should display view action button in calls table', () => {
        cy.visit('/admin/calls');
        cy.get('.fi-ta-view-action').first().should('be.visible');
        cy.get('.fi-ta-view-action').first().should('contain', 'Details');
      });

      it('should have touch-friendly button sizes', () => {
        cy.get('.fi-btn').each(($btn) => {
          cy.wrap($btn).should('have.css', 'min-height').and('match', /44px/);
        });
      });

      it('should take screenshot for visual regression', () => {
        cy.screenshot(`${viewport.name}-dashboard`);
        
        cy.visit('/admin/calls');
        cy.screenshot(`${viewport.name}-calls`);
        
        cy.visit('/admin/invoices');
        cy.screenshot(`${viewport.name}-invoices`);
      });
    });
  });

  describe('Icon Loading Tests', () => {
    it('should display all heroicons', () => {
      cy.visit('/admin');
      
      // Check if SVG icons are loaded
      cy.get('svg[class*="heroicon"]').should('have.length.greaterThan', 0);
      
      // Check specific icons
      cy.get('.fi-sidebar-toggle svg').should('be.visible');
      cy.get('.fi-ta-view-action svg').should('exist');
    });

    it('should display fallback text for missing icons', () => {
      // Intercept icon requests to simulate failure
      cy.intercept('GET', '**/heroicons/**', { statusCode: 404 });
      
      cy.visit('/admin/calls');
      
      // Check if aria-label is displayed as fallback
      cy.get('.fi-icon-btn[aria-label]').each(($btn) => {
        const label = $btn.attr('aria-label');
        if (label && $btn.find('svg').length === 0) {
          cy.wrap($btn).should('contain', label);
        }
      });
    });
  });

  describe('Performance Tests', () => {
    it('should load page within acceptable time on mobile', () => {
      cy.viewport('iphone-x');
      
      const startTime = Date.now();
      cy.visit('/admin/calls');
      cy.get('.fi-ta-table').should('be.visible');
      const loadTime = Date.now() - startTime;
      
      expect(loadTime).to.be.lessThan(3000); // 3 seconds max
    });

    it('should handle rapid navigation without errors', () => {
      cy.viewport('iphone-x');
      
      const pages = ['/admin', '/admin/calls', '/admin/invoices', '/admin/customers'];
      
      pages.forEach((page) => {
        cy.visit(page);
        cy.get('.fi-page').should('be.visible');
      });
    });
  });
});