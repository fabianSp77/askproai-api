import React, { useState, useEffect } from 'react';
import {
    Card,
    Form,
    Switch,
    Button,
    Typography,
    Space,
    Divider,
    message,
    Spin,
    Row,
    Col,
    Alert
} from 'antd';
import {
    MailOutlined,
    MobileOutlined,
    BellOutlined,
    SoundOutlined,
    DesktopOutlined,
    CalendarOutlined,
    PhoneOutlined,
    FileTextOutlined,
    TeamOutlined,
    InfoCircleOutlined,
    SaveOutlined
} from '@ant-design/icons';
import notificationService from '../../../services/NotificationService';

const { Title, Text, Paragraph } = Typography;

const NotificationPreferences = ({ csrfToken }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [hasChanges, setHasChanges] = useState(false);

    const notificationCategories = [
        {
            key: 'appointments',
            label: 'Termine',
            icon: <CalendarOutlined />,
            description: 'Benachrichtigungen über neue, geänderte oder stornierte Termine'
        },
        {
            key: 'calls',
            label: 'Anrufe',
            icon: <PhoneOutlined />,
            description: 'Benachrichtigungen über eingehende Anrufe und verpasste Anrufe'
        },
        {
            key: 'invoices',
            label: 'Rechnungen',
            icon: <FileTextOutlined />,
            description: 'Benachrichtigungen über neue Rechnungen und Zahlungen'
        },
        {
            key: 'team',
            label: 'Team',
            icon: <TeamOutlined />,
            description: 'Benachrichtigungen über Team-Aktivitäten und Änderungen'
        },
        {
            key: 'system',
            label: 'System',
            icon: <InfoCircleOutlined />,
            description: 'Wichtige Systembenachrichtigungen und Updates'
        }
    ];

    useEffect(() => {
        loadPreferences();
    }, []);

    const loadPreferences = async () => {
        setLoading(true);
        try {
            const preferences = await notificationService.getPreferences();
            if (preferences) {
                form.setFieldsValue({ preferences });
            }
        } catch (error) {
            message.error('Fehler beim Laden der Einstellungen');
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async (values) => {
        setSaving(true);
        try {
            const success = await notificationService.updatePreferences(values.preferences);
            if (success) {
                message.success('Einstellungen erfolgreich gespeichert');
                setHasChanges(false);
            } else {
                message.error('Fehler beim Speichern der Einstellungen');
            }
        } catch (error) {
            message.error('Fehler beim Speichern der Einstellungen');
        } finally {
            setSaving(false);
        }
    };

    const handleFormChange = () => {
        setHasChanges(true);
    };

    const requestNotificationPermission = async () => {
        const granted = await notificationService.requestPermission();
        if (granted) {
            message.success('Browser-Benachrichtigungen wurden aktiviert');
            form.setFieldValue(['preferences', 'desktop'], true);
        } else {
            message.warning('Browser-Benachrichtigungen wurden verweigert');
            form.setFieldValue(['preferences', 'desktop'], false);
        }
    };

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: 50 }}>
                <Spin size="large" />
            </div>
        );
    }

    return (
        <div style={{ maxWidth: 800, margin: '0 auto' }}>
            <Card>
                <Title level={3}>
                    <BellOutlined /> Benachrichtigungseinstellungen
                </Title>
                <Paragraph type="secondary">
                    Verwalten Sie hier, welche Benachrichtigungen Sie erhalten möchten und auf welchem Weg.
                </Paragraph>

                <Divider />

                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSave}
                    onValuesChange={handleFormChange}
                >
                    {/* General Settings */}
                    <Title level={5}>Allgemeine Einstellungen</Title>
                    
                    <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                        <Col xs={24} sm={12}>
                            <Form.Item
                                name={['preferences', 'sound']}
                                valuePropName="checked"
                            >
                                <Space>
                                    <Switch />
                                    <div>
                                        <Text strong>
                                            <SoundOutlined /> Benachrichtigungston
                                        </Text>
                                        <br />
                                        <Text type="secondary" style={{ fontSize: 12 }}>
                                            Ton bei neuen Benachrichtigungen abspielen
                                        </Text>
                                    </div>
                                </Space>
                            </Form.Item>
                        </Col>
                        
                        <Col xs={24} sm={12}>
                            <Form.Item
                                name={['preferences', 'desktop']}
                                valuePropName="checked"
                            >
                                <Space>
                                    <Switch />
                                    <div>
                                        <Text strong>
                                            <DesktopOutlined /> Browser-Benachrichtigungen
                                        </Text>
                                        <br />
                                        <Text type="secondary" style={{ fontSize: 12 }}>
                                            Desktop-Benachrichtigungen anzeigen
                                        </Text>
                                    </div>
                                </Space>
                            </Form.Item>
                        </Col>
                    </Row>

                    {Notification.permission === 'default' && (
                        <Alert
                            message="Browser-Benachrichtigungen aktivieren"
                            description="Klicken Sie hier, um Browser-Benachrichtigungen zu erlauben"
                            type="info"
                            showIcon
                            action={
                                <Button size="small" onClick={requestNotificationPermission}>
                                    Aktivieren
                                </Button>
                            }
                            style={{ marginBottom: 24 }}
                        />
                    )}

                    <Divider />

                    {/* Category Settings */}
                    <Title level={5}>Benachrichtigungskategorien</Title>
                    <Paragraph type="secondary" style={{ marginBottom: 24 }}>
                        Wählen Sie aus, welche Arten von Benachrichtigungen Sie per E-Mail oder Push-Benachrichtigung erhalten möchten.
                    </Paragraph>

                    {notificationCategories.map((category) => (
                        <Card
                            key={category.key}
                            size="small"
                            style={{ marginBottom: 16 }}
                            bodyStyle={{ padding: 16 }}
                        >
                            <Row gutter={[16, 8]} align="middle">
                                <Col xs={24} sm={12}>
                                    <Space>
                                        {category.icon}
                                        <div>
                                            <Text strong>{category.label}</Text>
                                            <br />
                                            <Text type="secondary" style={{ fontSize: 12 }}>
                                                {category.description}
                                            </Text>
                                        </div>
                                    </Space>
                                </Col>
                                <Col xs={12} sm={6}>
                                    <Form.Item
                                        name={['preferences', 'email', category.key]}
                                        valuePropName="checked"
                                        style={{ marginBottom: 0 }}
                                    >
                                        <Space>
                                            <Switch size="small" />
                                            <Text type="secondary">
                                                <MailOutlined /> E-Mail
                                            </Text>
                                        </Space>
                                    </Form.Item>
                                </Col>
                                <Col xs={12} sm={6}>
                                    <Form.Item
                                        name={['preferences', 'push', category.key]}
                                        valuePropName="checked"
                                        style={{ marginBottom: 0 }}
                                    >
                                        <Space>
                                            <Switch size="small" />
                                            <Text type="secondary">
                                                <MobileOutlined /> Push
                                            </Text>
                                        </Space>
                                    </Form.Item>
                                </Col>
                            </Row>
                        </Card>
                    ))}

                    <Divider />

                    <Form.Item>
                        <Space>
                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={saving}
                                disabled={!hasChanges}
                                icon={<SaveOutlined />}
                            >
                                Einstellungen speichern
                            </Button>
                            <Button
                                onClick={() => {
                                    form.resetFields();
                                    loadPreferences();
                                    setHasChanges(false);
                                }}
                                disabled={!hasChanges}
                            >
                                Zurücksetzen
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Card>
        </div>
    );
};

export default NotificationPreferences;