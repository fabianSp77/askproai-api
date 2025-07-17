import React, { useState, useEffect } from 'react';
import { Bell } from 'lucide-react';
import { Button } from './ui/button';
import { Badge } from './ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from './ui/dropdown-menu';
import { cn } from '../lib/utils';

const NotificationCenterModern = ({ csrfToken }) => {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchNotifications();
        // Set up polling for new notifications
        const interval = setInterval(fetchNotifications, 30000); // Check every 30 seconds
        return () => clearInterval(interval);
    }, []);

    const fetchNotifications = async () => {
        try {
            const response = await fetch('/business/api-optional/notifications', {
            credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (response.ok) {
                const data = await response.json();
                setNotifications(Array.isArray(data.notifications) ? data.notifications : []);
                setUnreadCount(data.unread_count || 0);
            }
        } catch (error) {
            // Silently handle notification fetch error
        }
    };

    const markAsRead = async (notificationId) => {
        try {
            await fetch(`/business/api-optional/notifications/${notificationId}/read`, {
            credentials: 'include',
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            fetchNotifications();
        } catch (error) {
            // Silently handle marking as read error
        }
    };

    const markAllAsRead = async () => {
        try {
            await fetch('/business/api-optional/notifications/read-all', {
            credentials: 'include',
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            fetchNotifications();
        } catch (error) {
            // Silently handle marking all as read error
        }
    };

    const getNotificationIcon = (type) => {
        // You can customize icons based on notification type
        return '🔔';
    };

    const formatTime = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'Gerade eben';
        if (diffMins < 60) return `vor ${diffMins} Minuten`;
        if (diffMins < 1440) return `vor ${Math.floor(diffMins / 60)} Stunden`;
        return `vor ${Math.floor(diffMins / 1440)} Tagen`;
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative">
                    <Bell className="h-5 w-5" />
                    {unreadCount > 0 && (
                        <span className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel className="flex justify-between items-center">
                    <span>Benachrichtigungen</span>
                    {unreadCount > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={markAllAsRead}
                            className="text-xs"
                        >
                            Alle als gelesen markieren
                        </Button>
                    )}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                
                <div className="max-h-96 overflow-y-auto">
                    {notifications.length === 0 ? (
                        <div className="px-4 py-8 text-center text-muted-foreground">
                            Keine neuen Benachrichtigungen
                        </div>
                    ) : (
                        (notifications || []).map((notification) => (
                            <DropdownMenuItem
                                key={notification.id}
                                className={cn(
                                    "flex flex-col items-start p-4 cursor-pointer",
                                    !notification.read_at && "bg-muted/50"
                                )}
                                onClick={() => {
                                    if (!notification.read_at) {
                                        markAsRead(notification.id);
                                    }
                                    if (notification.action_url) {
                                        window.location.href = notification.action_url;
                                    }
                                }}
                            >
                                <div className="flex items-start gap-3 w-full">
                                    <span className="text-lg">{getNotificationIcon(notification.type)}</span>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">{notification.title}</p>
                                        <p className="text-sm text-muted-foreground mt-1">
                                            {notification.message}
                                        </p>
                                        <p className="text-xs text-muted-foreground mt-1">
                                            {formatTime(notification.created_at)}
                                        </p>
                                    </div>
                                    {!notification.read_at && (
                                        <div className="h-2 w-2 rounded-full bg-blue-500" />
                                    )}
                                </div>
                            </DropdownMenuItem>
                        ))
                    )}
                </div>
                
                {notifications.length > 0 && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="justify-center">
                            <a href="/business/notifications" className="text-sm text-primary">
                                Alle Benachrichtigungen anzeigen
                            </a>
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
};

export default NotificationCenterModern;