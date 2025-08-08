import React from 'react';
import { createRoot } from 'react-dom/client';
import MCPConfiguration from './components/Admin/MCPConfiguration';

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountComponent);
} else {
    mountComponent();
}

function mountComponent() {
    const container = document.getElementById('mcp-configuration-root');
    
    if (container) {
        console.log('Mounting MCP Configuration component...');
        
        // Get mount data from data attribute
        const mountDataStr = container.getAttribute('data-mount-data');
        let mountData = {};
        
        try {
            if (mountDataStr) {
                mountData = JSON.parse(mountDataStr);
            }
        } catch (e) {
            console.warn('Failed to parse mount data:', e);
        }
        
        // Clear loading state
        container.innerHTML = '';
        
        // Create React root and render component
        const root = createRoot(container);
        root.render(<MCPConfiguration {...mountData} />);
        
        console.log('MCP Configuration component mounted successfully');
    } else {
        console.error('MCP Configuration container not found');
    }
}

// Export for debugging
window.MCPConfigurationDebug = {
    remount: mountComponent,
    container: () => document.getElementById('mcp-configuration-root')
};