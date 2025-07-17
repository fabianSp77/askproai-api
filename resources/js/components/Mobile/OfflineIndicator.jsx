import React, { useState, useEffect } from 'react';
import { WifiOff, Wifi } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Offline Indicator Component
 * Shows when the app is offline and when it comes back online
 */
const OfflineIndicator = () => {
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [showBanner, setShowBanner] = useState(false);
    const [wasOffline, setWasOffline] = useState(false);

    useEffect(() => {
        const handleOnline = () => {
            setIsOnline(true);
            if (wasOffline) {
                setShowBanner(true);
                // Hide banner after 3 seconds
                setTimeout(() => {
                    setShowBanner(false);
                    setWasOffline(false);
                }, 3000);
            }
        };

        const handleOffline = () => {
            setIsOnline(false);
            setWasOffline(true);
            setShowBanner(true);
        };

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        // Check initial state
        if (!navigator.onLine) {
            handleOffline();
        }

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, [wasOffline]);

    if (!showBanner) return null;

    return (
        <div className={cn(
            "fixed top-0 left-0 right-0 z-50 transition-all duration-300 transform",
            showBanner ? "translate-y-0" : "-translate-y-full"
        )}>
            <div className={cn(
                "flex items-center justify-center p-3 text-white",
                isOnline ? "bg-green-600" : "bg-red-600"
            )}>
                <div className="flex items-center gap-2">
                    {isOnline ? (
                        <>
                            <Wifi className="h-4 w-4" />
                            <span className="text-sm font-medium">
                                Verbindung wiederhergestellt
                            </span>
                        </>
                    ) : (
                        <>
                            <WifiOff className="h-4 w-4" />
                            <span className="text-sm font-medium">
                                Keine Internetverbindung
                            </span>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

/**
 * Offline Content Component
 * Shows cached content when offline
 */
export const OfflineContent = ({ children, fallback }) => {
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    if (!isOnline && fallback) {
        return fallback;
    }

    return children;
};

/**
 * Offline Ready Component
 * Wrapper that adds offline capabilities to components
 */
export const OfflineReady = ({ children, cacheKey, cacheData }) => {
    const [cachedData, setCachedData] = useState(null);
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    useEffect(() => {
        // Load cached data
        if (cacheKey) {
            const cached = localStorage.getItem(`offline_${cacheKey}`);
            if (cached) {
                try {
                    setCachedData(JSON.parse(cached));
                } catch (e) {
                    // Failed to parse cached data
                }
            }
        }

        // Cache new data
        if (cacheKey && cacheData) {
            try {
                localStorage.setItem(`offline_${cacheKey}`, JSON.stringify(cacheData));
            } catch (e) {
                // Failed to cache data - storage might be full
            }
        }
    }, [cacheKey, cacheData]);

    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    // Inject offline state and cached data into children
    return React.cloneElement(children, {
        isOnline,
        cachedData,
        offlineMode: !isOnline
    });
};

export default OfflineIndicator;