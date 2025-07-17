import React, { useState, useEffect } from 'react';
import { Alert, Space } from 'antd';
import { WifiOutlined, CloudSyncOutlined, ExclamationCircleOutlined } from '@ant-design/icons';

const OfflineIndicator = () => {
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [showIndicator, setShowIndicator] = useState(false);
    const [syncPending, setSyncPending] = useState(false);

    useEffect(() => {
        // Network status handlers
        const handleOnline = () => {
            setIsOnline(true);
            setShowIndicator(true);
            
            // Auto-hide after 3 seconds when coming back online
            setTimeout(() => {
                setShowIndicator(false);
            }, 3000);
            
            // Trigger sync if pending
            if (syncPending) {
                triggerSync();
            }
        };
        
        const handleOffline = () => {
            setIsOnline(false);
            setShowIndicator(true);
        };
        
        // Listen to network events
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
        
        // Check initial state
        if (!navigator.onLine) {
            handleOffline();
        }
        
        // Cleanup
        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, [syncPending]);

    // Trigger background sync
    const triggerSync = async () => {
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            try {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('sync-data');
                setSyncPending(false);
            } catch (error) {
                console.error('Background sync failed:', error);
            }
        }
    };

    // Don't show anything if online and indicator is hidden
    if (isOnline && !showIndicator) {
        return null;
    }

    return (
        <div 
            style={{
                position: 'fixed',
                top: 64,
                left: '50%',
                transform: 'translateX(-50%)',
                zIndex: 1000,
                maxWidth: '90%',
                animation: showIndicator ? 'slideDown 0.3s ease-out' : 'slideUp 0.3s ease-out'
            }}
        >
            <Alert
                message={
                    <Space>
                        {isOnline ? (
                            <>
                                <WifiOutlined />
                                <span>Verbindung wiederhergestellt</span>
                                {syncPending && <CloudSyncOutlined spin />}
                            </>
                        ) : (
                            <>
                                <ExclamationCircleOutlined />
                                <span>Offline-Modus</span>
                            </>
                        )}
                    </Space>
                }
                description={
                    isOnline 
                        ? "Daten werden synchronisiert..."
                        : "Sie arbeiten im Offline-Modus. Änderungen werden gespeichert und synchronisiert, sobald die Verbindung wiederhergestellt ist."
                }
                type={isOnline ? "success" : "warning"}
                showIcon={false}
                closable
                onClose={() => setShowIndicator(false)}
            />
        </div>
    );
};

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
    
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        to {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }
    }
`;
document.head.appendChild(style);

export default OfflineIndicator;