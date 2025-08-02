/**
 * AskProAI UI Tester - Browser Console Tool
 * 
 * Usage: Copy & paste into browser console while on AskProAI admin
 */

window.AskProAITester = {
    // Capture current view with annotations
    captureAnnotated: function() {
        //console.log('📸 Capturing annotated view...');
        
        // Highlight important elements
        const elements = {
            errors: document.querySelectorAll('.text-danger, .alert-danger'),
            forms: document.querySelectorAll('form'),
            tables: document.querySelectorAll('.filament-tables-table'),
            widgets: document.querySelectorAll('[class*="widget"]'),
        };
        
        // Add visual markers
        Object.entries(elements).forEach(([type, nodes]) => {
            nodes.forEach(node => {
                node.style.outline = `3px solid ${this.getColorForType(type)}`;
                node.dataset.uitest = type;
            });
        });
        
        // Generate report
        const report = {
            url: window.location.href,
            timestamp: new Date().toISOString(),
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            elements: {
                errors: elements.errors.length,
                forms: elements.forms.length,
                tables: elements.tables.length,
                widgets: elements.widgets.length,
            },
            performance: performance.timing,
        };
        
        //console.log('📊 UI Test Report:', report);
        
        // Copy to clipboard
        navigator.clipboard.writeText(JSON.stringify(report, null, 2));
        //console.log('✅ Report copied to clipboard!');
        
        return report;
    },
    
    getColorForType: function(type) {
        const colors = {
            errors: '#dc2626',
            forms: '#3b82f6',
            tables: '#10b981',
            widgets: '#f59e0b',
        };
        return colors[type] || '#6b7280';
    },
    
    // Test responsive behavior
    testResponsive: function() {
        const sizes = {
            mobile: { width: 375, height: 667 },
            tablet: { width: 768, height: 1024 },
            desktop: { width: 1920, height: 1080 },
        };
        
        //console.log('📱 Testing responsive layouts...');
        
        Object.entries(sizes).forEach(([device, size]) => {
            window.resizeTo(size.width, size.height);
            setTimeout(() => {
                //console.log(`${device}: ${this.checkLayout()}`);
            }, 1000);
        });
    },
    
    checkLayout: function() {
        const issues = [];
        
        // Check for overflow
        document.querySelectorAll('*').forEach(el => {
            if (el.scrollWidth > el.clientWidth) {
                issues.push(`Horizontal overflow on ${el.tagName}.${el.className}`);
            }
        });
        
        // Check for hidden elements
        document.querySelectorAll('[class*="hidden"], [class*="invisible"]').forEach(el => {
            if (window.getComputedStyle(el).display === 'none') {
                issues.push(`Hidden element: ${el.tagName}.${el.className}`);
            }
        });
        
        return issues.length ? issues : '✅ No layout issues detected';
    },
    
    // Capture form state before submission
    captureFormState: function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                
                //console.log('📝 Form submission captured:', {
                    action: form.action,
                    method: form.method,
                    data: data,
                    timestamp: new Date().toISOString(),
                });
                
                // Allow normal submission
                return true;
            });
        });
        
        //console.log('✅ Form capture enabled');
    }
};

// Auto-initialize
//console.log('🔧 AskProAI UI Tester loaded. Available commands:');
//console.log('- AskProAITester.captureAnnotated()');
//console.log('- AskProAITester.testResponsive()');
//console.log('- AskProAITester.captureFormState()');