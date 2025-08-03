/**
 * Business Portal React App
 * Simple implementation to prevent build errors
 */

import React from 'react';
import ReactDOM from 'react-dom/client';

// Simple Portal Component
const BusinessPortal = () => {
    return (
        <div className="business-portal-react">
            {/* This is a placeholder - the actual portal uses Blade templates */}
            <div className="hidden">
                React component loaded successfully
            </div>
        </div>
    );
};

// Only mount if there's a React root element
const rootElement = document.getElementById('business-portal-root');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(<BusinessPortal />);
} else {
    console.log('[Business Portal] No React root element found - using Blade templates');
}

// Export for potential use
export default BusinessPortal;