import { useEffect, useRef, useCallback } from 'react';
import echo, { getCompanyChannel, getBranchChannel, getUserChannel } from '../services/echo';
import { useAuth } from './useAuth';

/**
 * Hook for managing Laravel Echo WebSocket connections
 */
export const useEcho = () => {
    const { user } = useAuth();
    const channelsRef = useRef({});
    
    /**
     * Subscribe to a private channel
     */
    const subscribeToChannel = useCallback((channelName, callbacks = {}) => {
        if (channelsRef.current[channelName]) {
            return channelsRef.current[channelName];
        }
        
        const channel = echo.private(channelName);
        
        // Subscribe to events
        Object.entries(callbacks).forEach(([event, callback]) => {
            channel.listen(event, callback);
        });
        
        channelsRef.current[channelName] = channel;
        return channel;
    }, []);
    
    /**
     * Subscribe to company channel
     */
    const subscribeToCompany = useCallback((callbacks = {}) => {
        if (!user?.company_id) return null;
        
        const channel = getCompanyChannel(user.company_id);
        
        // Default company events
        const defaultCallbacks = {
            '.call.status.updated': (data) => {
                console.log('Call status updated:', data);
            },
            '.appointment.updated': (data) => {
                console.log('Appointment updated:', data);
            },
            '.dashboard.stats.updated': (data) => {
                console.log('Dashboard stats updated:', data);
            },
            ...callbacks
        };
        
        Object.entries(defaultCallbacks).forEach(([event, callback]) => {
            channel.listen(event, callback);
        });
        
        return channel;
    }, [user]);
    
    /**
     * Subscribe to user notifications
     */
    const subscribeToNotifications = useCallback((onNotification) => {
        if (!user?.id) return null;
        
        const channel = getUserChannel(user.id);
        
        channel
            .listen('.notification.created', (notification) => {
                if (onNotification) {
                    onNotification(notification);
                }
            });
        
        return channel;
    }, [user]);
    
    /**
     * Join a presence channel
     */
    const joinPresence = useCallback((channelName, callbacks = {}) => {
        const channel = echo.join(channelName);
        
        if (callbacks.here) {
            channel.here(callbacks.here);
        }
        
        if (callbacks.joining) {
            channel.joining(callbacks.joining);
        }
        
        if (callbacks.leaving) {
            channel.leaving(callbacks.leaving);
        }
        
        if (callbacks.error) {
            channel.error(callbacks.error);
        }
        
        channelsRef.current[`presence-${channelName}`] = channel;
        return channel;
    }, []);
    
    /**
     * Leave a specific channel
     */
    const leaveChannel = useCallback((channelName) => {
        if (channelsRef.current[channelName]) {
            echo.leave(channelName);
            delete channelsRef.current[channelName];
        }
    }, []);
    
    /**
     * Leave all channels
     */
    const leaveAllChannels = useCallback(() => {
        Object.keys(channelsRef.current).forEach(channelName => {
            echo.leave(channelName);
        });
        channelsRef.current = {};
    }, []);
    
    /**
     * Send a whisper (client event)
     */
    const whisper = useCallback((channel, eventName, data) => {
        if (channelsRef.current[channel]) {
            channelsRef.current[channel].whisper(eventName, data);
        }
    }, []);
    
    /**
     * Listen for whispers
     */
    const listenForWhisper = useCallback((channel, eventName, callback) => {
        if (channelsRef.current[channel]) {
            channelsRef.current[channel].listenForWhisper(eventName, callback);
        }
    }, []);
    
    // Cleanup on unmount
    useEffect(() => {
        return () => {
            leaveAllChannels();
        };
    }, [leaveAllChannels]);
    
    return {
        echo,
        subscribeToChannel,
        subscribeToCompany,
        subscribeToNotifications,
        joinPresence,
        leaveChannel,
        leaveAllChannels,
        whisper,
        listenForWhisper
    };
};

/**
 * Hook for subscribing to real-time call updates
 */
export const useCallUpdates = (onUpdate) => {
    const { subscribeToCompany } = useEcho();
    
    useEffect(() => {
        const channel = subscribeToCompany({
            '.call.status.updated': (data) => {
                if (onUpdate) {
                    onUpdate(data);
                }
            }
        });
        
        return () => {
            if (channel) {
                channel.stopListening('.call.status.updated');
            }
        };
    }, [subscribeToCompany, onUpdate]);
};

/**
 * Hook for subscribing to real-time appointment updates
 */
export const useAppointmentUpdates = (onUpdate) => {
    const { subscribeToCompany } = useEcho();
    
    useEffect(() => {
        const channel = subscribeToCompany({
            '.appointment.updated': (data) => {
                if (onUpdate) {
                    onUpdate(data);
                }
            },
            '.appointment.created': (data) => {
                if (onUpdate) {
                    onUpdate({ ...data, event: 'created' });
                }
            }
        });
        
        return () => {
            if (channel) {
                channel.stopListening('.appointment.updated');
                channel.stopListening('.appointment.created');
            }
        };
    }, [subscribeToCompany, onUpdate]);
};

/**
 * Hook for subscribing to dashboard statistics updates
 */
export const useDashboardUpdates = (onUpdate) => {
    const { subscribeToCompany } = useEcho();
    
    useEffect(() => {
        const channel = subscribeToCompany({
            '.dashboard.stats.updated': (data) => {
                if (onUpdate) {
                    onUpdate(data);
                }
            }
        });
        
        return () => {
            if (channel) {
                channel.stopListening('.dashboard.stats.updated');
            }
        };
    }, [subscribeToCompany, onUpdate]);
};

/**
 * Hook for presence channel (show who's online)
 */
export const usePresence = (companyId) => {
    const { joinPresence, leaveChannel } = useEcho();
    const [users, setUsers] = useState([]);
    
    useEffect(() => {
        if (!companyId) return;
        
        const channelName = `presence.company.${companyId}`;
        const channel = joinPresence(channelName, {
            here: (users) => {
                setUsers(users);
            },
            joining: (user) => {
                setUsers(prev => [...prev, user]);
            },
            leaving: (user) => {
                setUsers(prev => prev.filter(u => u.id !== user.id));
            },
            error: (error) => {
                console.error('Presence channel error:', error);
            }
        });
        
        return () => {
            leaveChannel(`presence-${channelName}`);
        };
    }, [companyId, joinPresence, leaveChannel]);
    
    return users;
};