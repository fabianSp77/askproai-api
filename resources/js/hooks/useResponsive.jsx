import { useState, useEffect } from 'react';

/**
 * Custom hook for responsive design
 * Provides breakpoint detection and device type
 */
export const useResponsive = () => {
    const [windowSize, setWindowSize] = useState({
        width: typeof window !== 'undefined' ? window.innerWidth : 1200,
        height: typeof window !== 'undefined' ? window.innerHeight : 800,
    });

    const [device, setDevice] = useState({
        isMobile: false,
        isTablet: false,
        isDesktop: false,
        isLargeDesktop: false,
    });

    useEffect(() => {
        const handleResize = () => {
            const width = window.innerWidth;
            const height = window.innerHeight;
            
            setWindowSize({ width, height });
            
            // Breakpoints aligned with Tailwind CSS
            setDevice({
                isMobile: width < 640,        // < sm
                isTablet: width >= 640 && width < 1024,  // sm to lg
                isDesktop: width >= 1024 && width < 1280, // lg to xl
                isLargeDesktop: width >= 1280, // xl+
            });
        };

        // Initial check
        handleResize();

        // Add event listener
        window.addEventListener('resize', handleResize);
        
        // Cleanup
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    return {
        ...windowSize,
        ...device,
        // Convenience methods
        isMobileOrTablet: device.isMobile || device.isTablet,
        isDesktopOrLarger: device.isDesktop || device.isLargeDesktop,
        isTouch: typeof window !== 'undefined' && ('ontouchstart' in window || navigator.maxTouchPoints > 0),
        // Breakpoint utilities
        breakpoint: {
            xs: windowSize.width < 640,
            sm: windowSize.width >= 640,
            md: windowSize.width >= 768,
            lg: windowSize.width >= 1024,
            xl: windowSize.width >= 1280,
            '2xl': windowSize.width >= 1536,
        }
    };
};

// Breakpoint constants for consistency
export const BREAKPOINTS = {
    xs: 0,
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    '2xl': 1536,
};

export default useResponsive;