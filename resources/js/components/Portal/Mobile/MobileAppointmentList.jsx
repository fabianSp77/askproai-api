import React, { useState, useCallback } from 'react';
import { 
    Card, 
    List, 
    Tag, 
    Space, 
    Button, 
    Dropdown, 
    Modal, 
    Empty,
    Spin,
    Badge,
    Typography,
    Divider,
    message
} from 'antd';
import { 
    CalendarOutlined, 
    ClockCircleOutlined, 
    UserOutlined, 
    PhoneOutlined,
    EnvironmentOutlined,
    EllipsisOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    ExclamationCircleOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { useVirtualizer } from '@tanstack/react-virtual';
// Swipeable functionality temporarily removed due to React 19 compatibility

const { Text, Title } = Typography;

const MobileAppointmentList = ({ 
    appointments = [], 
    loading = false, 
    onRefresh, 
    onStatusChange,
    onCall,
    onViewDetails 
}) => {
    const [refreshing, setRefreshing] = useState(false);
    const parentRef = React.useRef();

    // Virtual scrolling for performance
    const virtualizer = useVirtualizer({
        count: appointments.length,
        getScrollElement: () => parentRef.current,
        estimateSize: () => 120, // Estimated height of each item
        overscan: 5
    });

    // Pull to refresh handler
    const handleRefresh = useCallback(async () => {
        setRefreshing(true);
        try {
            await onRefresh?.();
        } finally {
            setRefreshing(false);
        }
    }, [onRefresh]);

    // Status color mapping
    const getStatusColor = (status) => {
        const colors = {
            'scheduled': 'blue',
            'confirmed': 'green',
            'completed': 'default',
            'cancelled': 'red',
            'no_show': 'orange'
        };
        return colors[status] || 'default';
    };

    // Status text mapping
    const getStatusText = (status) => {
        const texts = {
            'scheduled': 'Geplant',
            'confirmed': 'Bestätigt',
            'completed': 'Abgeschlossen',
            'cancelled': 'Storniert',
            'no_show': 'Nicht erschienen'
        };
        return texts[status] || status;
    };

    // Touch handling for swipe gestures
    const [touchStart, setTouchStart] = useState(null);
    const [touchEnd, setTouchEnd] = useState(null);
    const minSwipeDistance = 50;

    const onTouchStart = (e) => {
        setTouchEnd(null);
        setTouchStart(e.targetTouches[0].clientX);
    };

    const onTouchMove = (e) => setTouchEnd(e.targetTouches[0].clientX);

    const onTouchEnd = (appointment) => {
        if (!touchStart || !touchEnd) return;
        const distance = touchStart - touchEnd;
        const isLeftSwipe = distance > minSwipeDistance;
        const isRightSwipe = distance < -minSwipeDistance;
        
        if (isLeftSwipe) {
            // Left swipe - confirm appointment
            onStatusChange?.(appointment.id, 'confirmed');
        } else if (isRightSwipe) {
            // Right swipe - call customer
            onCall?.(appointment.customer?.phone);
        }
    };

    // Action menu items
    const getActionItems = (appointment) => [
        {
            key: 'confirm',
            label: 'Bestätigen',
            icon: <CheckCircleOutlined />,
            onClick: () => onStatusChange?.(appointment.id, 'confirmed'),
            disabled: appointment.status === 'confirmed' || appointment.status === 'completed'
        },
        {
            key: 'cancel',
            label: 'Stornieren',
            icon: <CloseCircleOutlined />,
            danger: true,
            onClick: () => {
                Modal.confirm({
                    title: 'Termin stornieren?',
                    content: 'Möchten Sie diesen Termin wirklich stornieren?',
                    okText: 'Ja, stornieren',
                    cancelText: 'Abbrechen',
                    onOk: () => onStatusChange?.(appointment.id, 'cancelled')
                });
            },
            disabled: appointment.status === 'cancelled' || appointment.status === 'completed'
        },
        {
            key: 'call',
            label: 'Anrufen',
            icon: <PhoneOutlined />,
            onClick: () => onCall?.(appointment.customer?.phone)
        },
        { type: 'divider' },
        {
            key: 'details',
            label: 'Details anzeigen',
            onClick: () => onViewDetails?.(appointment)
        }
    ];

    if (loading && appointments.length === 0) {
        return (
            <div style={{ 
                display: 'flex', 
                justifyContent: 'center', 
                alignItems: 'center', 
                height: '50vh' 
            }}>
                <Spin size="large" />
            </div>
        );
    }

    if (!loading && appointments.length === 0) {
        return (
            <Empty 
                description="Keine Termine gefunden"
                style={{ marginTop: 50 }}
            />
        );
    }

    return (
        <div
            ref={parentRef}
            style={{
                height: 'calc(100vh - 200px)',
                overflow: 'auto',
                WebkitOverflowScrolling: 'touch'
            }}
        >
            {/* Pull to refresh indicator */}
            {refreshing && (
                <div style={{ 
                    textAlign: 'center', 
                    padding: 20,
                    position: 'sticky',
                    top: 0,
                    background: '#f0f2f5',
                    zIndex: 10
                }}>
                    <Spin />
                    <Text style={{ marginLeft: 10 }}>Aktualisiere...</Text>
                </div>
            )}

            {/* Virtual list */}
            <div
                style={{
                    height: `${virtualizer.getTotalSize()}px`,
                    width: '100%',
                    position: 'relative',
                }}
            >
                {virtualizer.getVirtualItems().map(virtualItem => {
                    const appointment = appointments[virtualItem.index];
                    const isToday = dayjs(appointment.starts_at).isSame(dayjs(), 'day');
                    const isPast = dayjs(appointment.starts_at).isBefore(dayjs());
                    
                    return (
                        <div
                            key={virtualItem.key}
                            style={{
                                position: 'absolute',
                                top: 0,
                                left: 0,
                                width: '100%',
                                height: `${virtualItem.size}px`,
                                transform: `translateY(${virtualItem.start}px)`,
                            }}
                        >
                            <div
                                onTouchStart={onTouchStart}
                                onTouchMove={onTouchMove}
                                onTouchEnd={() => onTouchEnd(appointment)}
                            >
                                <Card
                                    size="small"
                                    style={{ 
                                        margin: '8px',
                                        opacity: isPast && appointment.status !== 'completed' ? 0.7 : 1
                                    }}
                                    bodyStyle={{ padding: 12 }}
                                >
                                    {/* Header with date and status */}
                                    <div style={{ 
                                        display: 'flex', 
                                        justifyContent: 'space-between',
                                        alignItems: 'center',
                                        marginBottom: 8
                                    }}>
                                        <Space>
                                            <Badge 
                                                dot={isToday} 
                                                color="blue"
                                            >
                                                <CalendarOutlined />
                                            </Badge>
                                            <Text strong>
                                                {dayjs(appointment.starts_at).format('DD.MM.YYYY')}
                                            </Text>
                                            <Text type="secondary">
                                                {dayjs(appointment.starts_at).format('HH:mm')}
                                            </Text>
                                        </Space>
                                        <Tag color={getStatusColor(appointment.status)}>
                                            {getStatusText(appointment.status)}
                                        </Tag>
                                    </div>

                                    {/* Customer info */}
                                    <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                        <Space>
                                            <UserOutlined />
                                            <Text strong>{appointment.customer?.name || 'N/A'}</Text>
                                        </Space>
                                        
                                        {appointment.customer?.phone && (
                                            <Space>
                                                <PhoneOutlined />
                                                <Text copyable>{appointment.customer.phone}</Text>
                                            </Space>
                                        )}

                                        {/* Service and location */}
                                        <div style={{ 
                                            display: 'flex', 
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            marginTop: 8
                                        }}>
                                            <Text type="secondary">
                                                {appointment.service?.name || 'N/A'} 
                                                {appointment.service?.duration && ` (${appointment.service.duration} Min.)`}
                                            </Text>
                                            
                                            <Dropdown
                                                menu={{ 
                                                    items: getActionItems(appointment)
                                                        .filter(item => !item.disabled)
                                                }}
                                                trigger={['click']}
                                            >
                                                <Button 
                                                    type="text" 
                                                    icon={<EllipsisOutlined />}
                                                    size="small"
                                                />
                                            </Dropdown>
                                        </div>

                                        {appointment.branch && (
                                            <Space size={4}>
                                                <EnvironmentOutlined style={{ fontSize: 12 }} />
                                                <Text type="secondary" style={{ fontSize: 12 }}>
                                                    {appointment.branch.name}
                                                </Text>
                                            </Space>
                                        )}
                                    </Space>
                                </Card>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default MobileAppointmentList;