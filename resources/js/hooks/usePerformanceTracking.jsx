import React from 'react';
import performanceMonitor from '../utils/performanceMonitor';

// React hook for performance tracking
export const usePerformanceTracking = (componentName) => {
    const renderStartTime = React.useRef(performance.now());
    
    // Track render time after component mounts
    React.useEffect(() => {
        const renderTime = performance.now() - renderStartTime.current;
        performanceMonitor.trackComponentRender(componentName, renderTime);
    }, [componentName]);
    
    return performanceMonitor;
};