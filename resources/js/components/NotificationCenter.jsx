import React, { useState, useEffect, useCallback } from 'react';
import {
    Badge,
    Dropdown,
    List,
    Avatar,
    Button,
    Space,
    Typography,
    Empty,
    Spin,
    Tabs,
    Tag,
    Tooltip,
    message,
    Divider
} from 'antd';
import {
    BellOutlined,
    BellFilled,
    CheckOutlined,
    DeleteOutlined,
    CalendarOutlined,
    PhoneOutlined,
    FileTextOutlined,
    TeamOutlined,
    InfoCircleOutlined,
    MessageOutlined,
    ClockCircleOutlined,
    ExclamationCircleOutlined,
    DollarOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    WarningOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/de';
import notificationService from '../services/NotificationService';

dayjs.extend(relativeTime);
dayjs.locale('de');

const { Text, Title } = Typography;
const { TabPane } = Tabs;

const NotificationCenter = ({ csrfToken }) => {
    const [visible, setVisible] = useState(false);
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(false);
    const [unreadCount, setUnreadCount] = useState(0);
    const [categoryCounts, setCategoryCounts] = useState({});
    const [activeTab, setActiveTab] = useState('all');
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 20,
        total: 0
    });

    // Icon mapping
    const getIcon = (type) => {
        const iconMap = {
            'appointment.created': <CalendarOutlined style={{ color: '#1890ff' }} />,
            'appointment.confirmed': <CheckCircleOutlined style={{ color: '#52c41a' }} />,
            'appointment.cancelled': <CloseCircleOutlined style={{ color: '#ff4d4f' }} />,
            'appointment.reminder': <ClockCircleOutlined style={{ color: '#faad14' }} />,
            'call.received': <PhoneOutlined style={{ color: '#52c41a' }} />,
            'call.missed': <PhoneOutlined style={{ color: '#ff4d4f' }} />,
            'invoice.created': <FileTextOutlined style={{ color: '#1890ff' }} />,
            'invoice.paid': <DollarOutlined style={{ color: '#52c41a' }} />,
            'team.member_added': <TeamOutlined style={{ color: '#722ed1' }} />,
            'system.update': <InfoCircleOutlined style={{ color: '#1890ff' }} />,
            'system.alert': <WarningOutlined style={{ color: '#faad14' }} />,
            'feedback.received': <MessageOutlined style={{ color: '#13c2c2' }} />,
            'feedback.responded': <MessageOutlined style={{ color: '#52c41a' }} />
        };
        return iconMap[type] || <BellOutlined />;
    };

    // Priority colors
    const getPriorityColor = (priority) => {
        const colors = {
            low: 'default',
            medium: 'blue',
            high: 'orange',
            urgent: 'red'
        };
        return colors[priority] || 'default';
    };

    // Load notifications
    const fetchNotifications = useCallback(async (category = 'all', page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page,
                category: category === 'all' ? '' : category
            });

            const response = await fetch(`/business/api-optional/notifications?${params}`, {
            credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!response.ok) throw new Error('Failed to fetch notifications');

            const data = await response.json();
            setNotifications(data.notifications.data);
            setUnreadCount(data.unread_count);
            setCategoryCounts(data.category_counts);
            setPagination({
                current: data.notifications.current_page,
                pageSize: data.notifications.per_page,
                total: data.notifications.total
            });
        } catch (error) {
            message.error('Fehler beim Laden der Benachrichtigungen');
        } finally {
            setLoading(false);
        }
    }, [csrfToken]);

    // Initialize notification service
    useEffect(() => {
        const token = csrfToken; // In production, use proper auth token
        
        notificationService.initialize(token, {
            onNotification: (notification) => {
                // Refresh notifications when new one arrives
                if (activeTab === 'all' || notification.category === activeTab) {
                    fetchNotifications(activeTab, pagination.current);
                }
            },
            onUnreadCountChange: (count) => {
                setUnreadCount(count);
            },
            onConnectionChange: (connected) => {
                if (connected) {
                    // Service connected
                } else {
                    // Service disconnected
                }
            }
        });

        // Initial load
        fetchNotifications();

        return () => {
            notificationService.disconnect();
        };
    }, []);

    // Mark as read
    const handleMarkAsRead = async (notification) => {
        if (notification.read_at) return;

        const success = await notificationService.markAsRead(notification.id);
        if (success) {
            setNotifications(prev => 
                prev.map(n => n.id === notification.id 
                    ? { ...n, read_at: new Date().toISOString() } 
                    : n
                )
            );
            setUnreadCount(prev => Math.max(0, prev - 1));
        }
    };

    // Mark all as read
    const handleMarkAllAsRead = async () => {
        const category = activeTab === 'all' ? null : activeTab;
        const success = await notificationService.markAllAsRead(category);
        
        if (success) {
            setNotifications(prev => 
                prev.map(n => ({ ...n, read_at: new Date().toISOString() }))
            );
            fetchNotifications(activeTab, pagination.current);
        }
    };

    // Delete notification
    const handleDelete = async (id, event) => {
        event.stopPropagation();
        
        try {
            const response = await fetch(`/business/api-optional/notifications/${id}`, {
            credentials: 'include',
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (response.ok) {
                setNotifications(prev => prev.filter(n => n.id !== id));
                message.success('Benachrichtigung gelöscht');
            }
        } catch (error) {
            message.error('Fehler beim Löschen');
        }
    };

    // Clear all read notifications
    const handleClearAll = async () => {
        try {
            const response = await fetch('/business/api-optional/notifications/delete-all', {
            credentials: 'include',
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    category: activeTab === 'all' ? null : activeTab
                })
            });

            if (response.ok) {
                fetchNotifications(activeTab, 1);
                message.success('Gelesene Benachrichtigungen gelöscht');
            }
        } catch (error) {
            message.error('Fehler beim Löschen');
        }
    };

    // Render notification item
    const renderNotification = (notification) => {
        const { title, message: msg, action_text } = notification.data;
        const isUnread = !notification.read_at;

        return (
            <List.Item
                style={{
                    backgroundColor: isUnread ? '#f0f8ff' : 'transparent',
                    cursor: 'pointer',
                    padding: '12px 16px',
                    borderBottom: '1px solid #f0f0f0'
                }}
                onClick={() => {
                    handleMarkAsRead(notification);
                    if (notification.action_url) {
                        window.location.href = notification.action_url;
                    }
                }}
                actions={[
                    <Tooltip title="Löschen" key="delete">
                        <Button
                            type="text"
                            size="small"
                            icon={<DeleteOutlined />}
                            onClick={(e) => handleDelete(notification.id, e)}
                        />
                    </Tooltip>
                ]}
            >
                <List.Item.Meta
                    avatar={
                        <Avatar
                            icon={getIcon(notification.type)}
                            style={{ backgroundColor: '#fff', border: '1px solid #f0f0f0' }}
                        />
                    }
                    title={
                        <Space>
                            <Text strong={isUnread}>{title}</Text>
                            {notification.priority !== 'medium' && (
                                <Tag color={getPriorityColor(notification.priority)} style={{ marginLeft: 8 }}>
                                    {notification.priority}
                                </Tag>
                            )}
                        </Space>
                    }
                    description={
                        <div>
                            <Text type="secondary">{msg}</Text>
                            <div style={{ marginTop: 4 }}>
                                <Text type="secondary" style={{ fontSize: 12 }}>
                                    {dayjs(notification.created_at).fromNow()}
                                </Text>
                                {action_text && (
                                    <Button
                                        type="link"
                                        size="small"
                                        style={{ padding: '0 8px' }}
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        {action_text} →
                                    </Button>
                                )}
                            </div>
                        </div>
                    }
                />
            </List.Item>
        );
    };

    const dropdownContent = (
        <div style={{ width: 400, maxHeight: 600, backgroundColor: '#fff' }}>
            <div style={{ 
                padding: '16px', 
                borderBottom: '1px solid #f0f0f0',
                position: 'sticky',
                top: 0,
                backgroundColor: '#fff',
                zIndex: 1
            }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Title level={4} style={{ margin: 0 }}>Benachrichtigungen</Title>
                    <Space>
                        {unreadCount > 0 && (
                            <Button
                                type="text"
                                size="small"
                                icon={<CheckOutlined />}
                                onClick={handleMarkAllAsRead}
                            >
                                Alle als gelesen markieren
                            </Button>
                        )}
                        <Button
                            type="text"
                            size="small"
                            onClick={() => window.location.href = '/business/notifications'}
                        >
                            Alle anzeigen
                        </Button>
                    </Space>
                </div>
            </div>

            <Tabs
                activeKey={activeTab}
                onChange={setActiveTab}
                size="small"
                style={{ padding: '0 16px' }}
                tabBarStyle={{ marginBottom: 0 }}
            >
                <TabPane tab="Alle" key="all" />
                <TabPane 
                    tab={
                        <Badge count={categoryCounts.appointment || 0} size="small">
                            <span>Termine</span>
                        </Badge>
                    } 
                    key="appointment" 
                />
                <TabPane 
                    tab={
                        <Badge count={categoryCounts.call || 0} size="small">
                            <span>Anrufe</span>
                        </Badge>
                    } 
                    key="call" 
                />
                <TabPane 
                    tab={
                        <Badge count={categoryCounts.invoice || 0} size="small">
                            <span>Rechnungen</span>
                        </Badge>
                    } 
                    key="invoice" 
                />
            </Tabs>

            <div style={{ maxHeight: 400, overflow: 'auto' }}>
                {loading ? (
                    <div style={{ textAlign: 'center', padding: 40 }}>
                        <Spin />
                    </div>
                ) : notifications.length > 0 ? (
                    <List
                        dataSource={notifications}
                        renderItem={renderNotification}
                        locale={{ emptyText: <Empty description="Keine Benachrichtigungen" /> }}
                    />
                ) : (
                    <Empty
                        description="Keine Benachrichtigungen"
                        style={{ padding: 40 }}
                    />
                )}
            </div>

            {notifications.filter(n => n.read_at).length > 0 && (
                <div style={{ 
                    padding: '12px 16px', 
                    borderTop: '1px solid #f0f0f0',
                    textAlign: 'center'
                }}>
                    <Button
                        type="text"
                        size="small"
                        danger
                        onClick={handleClearAll}
                    >
                        Gelesene Benachrichtigungen löschen
                    </Button>
                </div>
            )}
        </div>
    );

    return (
        <Dropdown
            menu={{ items: [] }}
            dropdownRender={() => dropdownContent}
            trigger={['click']}
            open={visible}
            onOpenChange={setVisible}
            placement="bottomRight"
        >
            <Badge count={unreadCount} size="small">
                <Button
                    type="text"
                    icon={unreadCount > 0 ? <BellFilled /> : <BellOutlined />}
                    style={{ 
                        fontSize: 20,
                        color: unreadCount > 0 ? '#1890ff' : undefined
                    }}
                />
            </Badge>
        </Dropdown>
    );
};

export default NotificationCenter;