import React, { useState, useEffect } from 'react';
import { Badge, Button, Dropdown, List, Space, Avatar, Empty } from 'antd';
import { BellOutlined, CheckOutlined, DeleteOutlined } from '@ant-design/icons';
import axios from 'axios';

const AdminNotificationCenter = () => {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    useEffect(() => {
        fetchNotifications();
    }, []);

    const fetchNotifications = async () => {
        setLoading(true);
        try {
            // Placeholder für echte Notifications
            const mockNotifications = [
                {
                    id: 1,
                    title: 'Neuer Mandant registriert',
                    description: 'Example Company GmbH',
                    time: 'vor 5 Minuten',
                    read: false,
                    type: 'info'
                },
                {
                    id: 2,
                    title: 'System Update verfügbar',
                    description: 'Version 2.1.0 bereit zur Installation',
                    time: 'vor 1 Stunde',
                    read: false,
                    type: 'warning'
                }
            ];
            
            setNotifications(mockNotifications);
            setUnreadCount(mockNotifications.filter(n => !n.read).length);
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        }
        setLoading(false);
    };

    const markAsRead = (id) => {
        setNotifications(prev => 
            prev.map(n => n.id === id ? { ...n, read: true } : n)
        );
        setUnreadCount(prev => Math.max(0, prev - 1));
    };

    const markAllAsRead = () => {
        setNotifications(prev => 
            prev.map(n => ({ ...n, read: true }))
        );
        setUnreadCount(0);
    };

    const deleteNotification = (id) => {
        setNotifications(prev => prev.filter(n => n.id !== id));
        const notification = notifications.find(n => n.id === id);
        if (notification && !notification.read) {
            setUnreadCount(prev => Math.max(0, prev - 1));
        }
    };

    const getNotificationIcon = (type) => {
        const colors = {
            info: '#1890ff',
            warning: '#faad14',
            error: '#ff4d4f',
            success: '#52c41a'
        };
        
        return (
            <Avatar 
                style={{ backgroundColor: colors[type] || colors.info }}
                size="small"
            >
                {type?.charAt(0).toUpperCase()}
            </Avatar>
        );
    };

    const menu = {
        items: [{
            key: 'notifications',
            label: (
                <div style={{ width: 350 }}>
                    <div className="flex justify-between items-center mb-3">
                        <h4 className="text-base font-semibold">Benachrichtigungen</h4>
                        {unreadCount > 0 && (
                            <Button 
                                type="link" 
                                size="small"
                                onClick={markAllAsRead}
                            >
                                Alle als gelesen markieren
                            </Button>
                        )}
                    </div>
                    {notifications.length === 0 ? (
                        <Empty 
                            description="Keine Benachrichtigungen" 
                            image={Empty.PRESENTED_IMAGE_SIMPLE}
                        />
                    ) : (
                        <List
                            dataSource={notifications}
                            renderItem={(item) => (
                                <List.Item
                                    className={`cursor-pointer ${!item.read ? 'bg-blue-50' : ''}`}
                                    onClick={() => markAsRead(item.id)}
                                    actions={[
                                        <Button
                                            type="text"
                                            size="small"
                                            icon={<DeleteOutlined />}
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                deleteNotification(item.id);
                                            }}
                                        />
                                    ]}
                                >
                                    <List.Item.Meta
                                        avatar={getNotificationIcon(item.type)}
                                        title={<span className="font-medium">{item.title}</span>}
                                        description={
                                            <div>
                                                <div>{item.description}</div>
                                                <div className="text-xs text-gray-500 mt-1">{item.time}</div>
                                            </div>
                                        }
                                    />
                                </List.Item>
                            )}
                        />
                    )}
                </div>
            )
        }]
    };

    return (
        <Dropdown 
            menu={menu} 
            trigger={['click']}
            placement="bottomRight"
            open={open}
            onOpenChange={setOpen}
        >
            <Badge count={unreadCount} size="small">
                <Button type="text" icon={<BellOutlined />} />
            </Badge>
        </Dropdown>
    );
};

export default AdminNotificationCenter;