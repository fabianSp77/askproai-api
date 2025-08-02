import React, { useState, useEffect } from 'react';
import { 
    Card, 
    Table, 
    Button, 
    Space, 
    Tag, 
    Modal, 
    Form, 
    Input, 
    Select, 
    Switch,
    message,
    Tabs,
    Timeline,
    Statistic,
    Row,
    Col,
    Alert,
    Divider,
    Empty,
    Spin,
    Typography,
    Tooltip,
    Badge,
    Descriptions,
    Drawer,
    Tree,
    JsonView
} from 'antd';
import { 
    ApiOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    ExclamationCircleOutlined,
    SendOutlined,
    CodeOutlined,
    HistoryOutlined,
    BarChartOutlined,
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    ReloadOutlined,
    FilterOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import axiosInstance from '../../../services/axiosInstance';

dayjs.locale('de');

const { Title, Text, Paragraph } = Typography;
const { TabPane } = Tabs;
const { Option } = Select;
const { TextArea } = Input;

const EventsIndex = () => {
    const [loading, setLoading] = useState(false);
    const [events, setEvents] = useState([]);
    const [stats, setStats] = useState(null);
    const [subscriptions, setSubscriptions] = useState([]);
    const [webhookLogs, setWebhookLogs] = useState([]);
    const [schemas, setSchemas] = useState({ standard_events: [], custom_events: [] });
    const [activeTab, setActiveTab] = useState('events');
    const [modalVisible, setModalVisible] = useState(false);
    const [testModalVisible, setTestModalVisible] = useState(false);
    const [selectedSubscription, setSelectedSubscription] = useState(null);
    const [drawerVisible, setDrawerVisible] = useState(false);
    const [selectedEvent, setSelectedEvent] = useState(null);
    const [form] = Form.useForm();
    const [testForm] = Form.useForm();
    const [filters, setFilters] = useState({
        event_names: [],
        date_from: null,
        date_to: null
    });

    useEffect(() => {
        fetchData();
    }, [activeTab]);

    const fetchData = () => {
        switch (activeTab) {
            case 'events':
                fetchEvents();
                break;
            case 'stats':
                fetchStats();
                break;
            case 'subscriptions':
                fetchSubscriptions();
                fetchSchemas();
                break;
            case 'logs':
                fetchWebhookLogs();
                break;
        }
    };

    const fetchEvents = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (filters.event_names.length > 0) {
                params.append('event_names', filters.event_names.join(','));
            }
            if (filters.date_from) {
                params.append('date_from', dayjs(filters.date_from).format('YYYY-MM-DD'));
            }
            if (filters.date_to) {
                params.append('date_to', dayjs(filters.date_to).format('YYYY-MM-DD'));
            }

            const response = await axiosInstance.get(`/events?${params}`);
            setEvents(response.data.events || []);
        } catch (error) {
            message.error('Fehler beim Laden der Events');
        } finally {
            setLoading(false);
        }
    };

    const fetchStats = async () => {
        setLoading(true);
        try {
            const response = await axiosInstance.get('/events/stats', {
                params: { period: 'week', group_by: 'event_name' }
            });
            setStats(response.data);
        } catch (error) {
            message.error('Fehler beim Laden der Statistiken');
        } finally {
            setLoading(false);
        }
    };

    const fetchSubscriptions = async () => {
        setLoading(true);
        try {
            const response = await axiosInstance.get('/events/subscriptions');
            setSubscriptions(response.data.subscriptions || []);
        } catch (error) {
            message.error('Fehler beim Laden der Subscriptions');
        } finally {
            setLoading(false);
        }
    };

    const fetchWebhookLogs = async () => {
        setLoading(true);
        try {
            const response = await axiosInstance.get('/events/webhook-logs');
            setWebhookLogs(response.data.logs || []);
        } catch (error) {
            message.error('Fehler beim Laden der Webhook-Logs');
        } finally {
            setLoading(false);
        }
    };

    const fetchSchemas = async () => {
        try {
            const response = await axiosInstance.get('/events/schemas');
            setSchemas(response.data);
        } catch (error) {
            console.error('Error fetching schemas:', error);
        }
    };

    const handleCreateSubscription = async (values) => {
        try {
            await axiosInstance.post('/events/subscriptions', values);
            message.success('Webhook-Subscription erstellt');
            setModalVisible(false);
            form.resetFields();
            fetchSubscriptions();
        } catch (error) {
            message.error('Fehler beim Erstellen der Subscription');
        }
    };

    const handleUpdateSubscription = async (id, updates) => {
        try {
            await axiosInstance.put(`/events/subscriptions/${id}`, updates);
            message.success('Subscription aktualisiert');
            fetchSubscriptions();
        } catch (error) {
            message.error('Fehler beim Aktualisieren');
        }
    };

    const handleDeleteSubscription = async (id) => {
        try {
            await axiosInstance.delete(`/events/subscriptions/${id}`);
            message.success('Subscription gelöscht');
            fetchSubscriptions();
        } catch (error) {
            message.error('Fehler beim Löschen');
        }
    };

    const handleTestWebhook = async (values) => {
        try {
            const response = await axiosInstance.post('/events/test-webhook', values);
            
            if (response.data.success) {
                message.success('Webhook-Test erfolgreich');
            } else {
                message.error(`Webhook-Test fehlgeschlagen: ${response.data.error || 'Unbekannter Fehler'}`);
            }
            
            setTestModalVisible(false);
            testForm.resetFields();
        } catch (error) {
            message.error('Fehler beim Webhook-Test');
        }
    };

    const getEventIcon = (eventName) => {
        if (eventName.includes('appointment')) return '📅';
        if (eventName.includes('call')) return '📞';
        if (eventName.includes('customer')) return '👤';
        if (eventName.includes('invoice')) return '💰';
        return '📌';
    };

    const eventColumns = [
        {
            title: 'Event',
            dataIndex: 'event_name',
            key: 'event_name',
            render: (name) => (
                <Space>
                    <span>{getEventIcon(name)}</span>
                    <Text strong>{name}</Text>
                </Space>
            )
        },
        {
            title: 'Zeitstempel',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => dayjs(date).format('DD.MM.YYYY HH:mm:ss'),
            sorter: (a, b) => dayjs(a.created_at).unix() - dayjs(b.created_at).unix()
        },
        {
            title: 'Benutzer',
            dataIndex: 'user_id',
            key: 'user_id',
            render: (userId) => userId ? `User ${userId}` : '-'
        },
        {
            title: 'Aktionen',
            key: 'actions',
            render: (_, record) => (
                <Button
                    type="link"
                    onClick={() => {
                        setSelectedEvent(record);
                        setDrawerVisible(true);
                    }}
                >
                    Details
                </Button>
            )
        }
    ];

    const subscriptionColumns = [
        {
            title: 'Webhook URL',
            dataIndex: 'webhook_url',
            key: 'webhook_url',
            ellipsis: true
        },
        {
            title: 'Events',
            dataIndex: 'event_names',
            key: 'event_names',
            render: (eventNames) => (
                <Space wrap>
                    {eventNames.map(name => (
                        <Tag key={name} color="blue">{name}</Tag>
                    ))}
                </Space>
            )
        },
        {
            title: 'Status',
            dataIndex: 'active',
            key: 'active',
            render: (active) => (
                <Tag color={active ? 'green' : 'red'}>
                    {active ? 'Aktiv' : 'Inaktiv'}
                </Tag>
            )
        },
        {
            title: 'Fehlversuche',
            dataIndex: 'retry_count',
            key: 'retry_count',
            render: (count) => (
                <Badge count={count} showZero style={{ backgroundColor: count > 0 ? '#ff4d4f' : '#52c41a' }} />
            )
        },
        {
            title: 'Zuletzt ausgelöst',
            dataIndex: 'last_triggered_at',
            key: 'last_triggered_at',
            render: (date) => date ? dayjs(date).fromNow() : 'Nie'
        },
        {
            title: 'Aktionen',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Switch
                        checked={record.active}
                        onChange={(checked) => handleUpdateSubscription(record.id, { active: checked })}
                        checkedChildren="An"
                        unCheckedChildren="Aus"
                    />
                    <Button
                        type="link"
                        icon={<SendOutlined />}
                        onClick={() => {
                            testForm.setFieldsValue({ webhook_url: record.webhook_url });
                            setTestModalVisible(true);
                        }}
                    >
                        Test
                    </Button>
                    <Button
                        type="link"
                        danger
                        icon={<DeleteOutlined />}
                        onClick={() => handleDeleteSubscription(record.id)}
                    >
                        Löschen
                    </Button>
                </Space>
            )
        }
    ];

    const webhookLogColumns = [
        {
            title: 'Event',
            dataIndex: 'event',
            key: 'event',
            render: (event) => <Tag>{event}</Tag>
        },
        {
            title: 'URL',
            dataIndex: 'url',
            key: 'url',
            ellipsis: true
        },
        {
            title: 'Status',
            key: 'status',
            render: (_, record) => (
                <Tag color={record.success ? 'green' : 'red'}>
                    {record.success ? 'Erfolgreich' : 'Fehlgeschlagen'}
                </Tag>
            )
        },
        {
            title: 'HTTP Status',
            dataIndex: 'response_status',
            key: 'response_status',
            render: (status) => status || '-'
        },
        {
            title: 'Fehler',
            dataIndex: 'error',
            key: 'error',
            ellipsis: true
        },
        {
            title: 'Zeitstempel',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => dayjs(date).format('DD.MM.YYYY HH:mm:ss'),
            sorter: (a, b) => dayjs(a.created_at).unix() - dayjs(b.created_at).unix()
        }
    ];

    return (
        <div style={{ padding: 24 }}>
            <Title level={2}>
                <ApiOutlined /> Event System
            </Title>
            <Paragraph>
                Verwalten Sie Event-Subscriptions und Webhooks für Echtzeit-Integrationen.
            </Paragraph>

            <Tabs activeKey={activeTab} onChange={setActiveTab}>
                <TabPane
                    tab={
                        <span>
                            <HistoryOutlined />
                            Event-Historie
                        </span>
                    }
                    key="events"
                >
                    <Card
                        title="Event-Filter"
                        extra={
                            <Button
                                icon={<ReloadOutlined />}
                                onClick={fetchEvents}
                                loading={loading}
                            >
                                Aktualisieren
                            </Button>
                        }
                        style={{ marginBottom: 16 }}
                    >
                        <Space>
                            <Select
                                mode="multiple"
                                placeholder="Event-Typen filtern"
                                style={{ minWidth: 200 }}
                                onChange={(values) => setFilters({ ...filters, event_names: values })}
                                value={filters.event_names}
                            >
                                <Option value="appointment.created">Termin erstellt</Option>
                                <Option value="appointment.updated">Termin aktualisiert</Option>
                                <Option value="appointment.cancelled">Termin storniert</Option>
                                <Option value="call.created">Anruf erstellt</Option>
                                <Option value="call.completed">Anruf beendet</Option>
                                <Option value="customer.created">Kunde erstellt</Option>
                            </Select>
                            <Button type="primary" onClick={fetchEvents}>
                                Filter anwenden
                            </Button>
                        </Space>
                    </Card>

                    <Table
                        columns={eventColumns}
                        dataSource={events}
                        rowKey="id"
                        loading={loading}
                        pagination={{
                            defaultPageSize: 20,
                            showSizeChanger: true
                        }}
                    />
                </TabPane>

                <TabPane
                    tab={
                        <span>
                            <BarChartOutlined />
                            Statistiken
                        </span>
                    }
                    key="stats"
                >
                    {stats && (
                        <div>
                            <Row gutter={16} style={{ marginBottom: 24 }}>
                                <Col span={8}>
                                    <Card>
                                        <Statistic
                                            title="Events diese Woche"
                                            value={stats.total_events}
                                            prefix={<ClockCircleOutlined />}
                                        />
                                    </Card>
                                </Col>
                            </Row>

                            <Card title="Events nach Typ">
                                <Table
                                    columns={[
                                        {
                                            title: 'Event-Typ',
                                            dataIndex: 'event_name',
                                            key: 'event_name',
                                            render: (name) => (
                                                <Space>
                                                    <span>{getEventIcon(name)}</span>
                                                    <Text>{name}</Text>
                                                </Space>
                                            )
                                        },
                                        {
                                            title: 'Anzahl',
                                            dataIndex: 'count',
                                            key: 'count',
                                            render: (count) => <Text strong>{count}</Text>
                                        }
                                    ]}
                                    dataSource={stats.stats}
                                    rowKey="event_name"
                                    pagination={false}
                                />
                            </Card>
                        </div>
                    )}
                </TabPane>

                <TabPane
                    tab={
                        <span>
                            <ApiOutlined />
                            Webhook-Subscriptions
                        </span>
                    }
                    key="subscriptions"
                >
                    <Card
                        title="Webhook-Subscriptions"
                        extra={
                            <Button
                                type="primary"
                                icon={<PlusOutlined />}
                                onClick={() => setModalVisible(true)}
                            >
                                Neue Subscription
                            </Button>
                        }
                    >
                        <Table
                            columns={subscriptionColumns}
                            dataSource={subscriptions}
                            rowKey="id"
                            loading={loading}
                        />
                    </Card>
                </TabPane>

                <TabPane
                    tab={
                        <span>
                            <CodeOutlined />
                            Webhook-Logs
                        </span>
                    }
                    key="logs"
                >
                    <Table
                        columns={webhookLogColumns}
                        dataSource={webhookLogs}
                        rowKey="id"
                        loading={loading}
                        pagination={{
                            defaultPageSize: 50,
                            showSizeChanger: true
                        }}
                    />
                </TabPane>
            </Tabs>

            {/* Create Subscription Modal */}
            <Modal
                title="Neue Webhook-Subscription"
                open={modalVisible}
                onCancel={() => {
                    setModalVisible(false);
                    form.resetFields();
                }}
                footer={null}
                width={600}
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleCreateSubscription}
                >
                    <Form.Item
                        name="webhook_url"
                        label="Webhook URL"
                        rules={[
                            { required: true, message: 'Bitte URL eingeben' },
                            { type: 'url', message: 'Bitte gültige URL eingeben' }
                        ]}
                    >
                        <Input placeholder="https://example.com/webhook" />
                    </Form.Item>

                    <Form.Item
                        name="event_names"
                        label="Events"
                        rules={[{ required: true, message: 'Bitte mindestens ein Event auswählen' }]}
                    >
                        <Select mode="multiple" placeholder="Events auswählen">
                            {schemas.standard_events.map(schema => (
                                <Option key={schema.event_name} value={schema.event_name}>
                                    {schema.event_name}
                                </Option>
                            ))}
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="active"
                        label="Status"
                        valuePropName="checked"
                        initialValue={true}
                    >
                        <Switch checkedChildren="Aktiv" unCheckedChildren="Inaktiv" />
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                Erstellen
                            </Button>
                            <Button onClick={() => {
                                setModalVisible(false);
                                form.resetFields();
                            }}>
                                Abbrechen
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>

            {/* Test Webhook Modal */}
            <Modal
                title="Webhook testen"
                open={testModalVisible}
                onCancel={() => {
                    setTestModalVisible(false);
                    testForm.resetFields();
                }}
                footer={null}
                width={600}
            >
                <Form
                    form={testForm}
                    layout="vertical"
                    onFinish={handleTestWebhook}
                >
                    <Form.Item
                        name="webhook_url"
                        label="Webhook URL"
                        rules={[
                            { required: true, message: 'Bitte URL eingeben' },
                            { type: 'url', message: 'Bitte gültige URL eingeben' }
                        ]}
                    >
                        <Input placeholder="https://example.com/webhook" />
                    </Form.Item>

                    <Form.Item
                        name="event_name"
                        label="Event-Name"
                        rules={[{ required: true, message: 'Bitte Event auswählen' }]}
                    >
                        <Select placeholder="Event auswählen">
                            <Option value="test.webhook">Test Webhook</Option>
                            <Option value="appointment.created">Termin erstellt</Option>
                            <Option value="call.completed">Anruf beendet</Option>
                            <Option value="customer.created">Kunde erstellt</Option>
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="payload"
                        label="Test-Payload (JSON)"
                    >
                        <TextArea
                            rows={6}
                            placeholder='{"test": true, "message": "Test webhook"}'
                        />
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit" icon={<SendOutlined />}>
                                Test senden
                            </Button>
                            <Button onClick={() => {
                                setTestModalVisible(false);
                                testForm.resetFields();
                            }}>
                                Abbrechen
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>

            {/* Event Details Drawer */}
            <Drawer
                title="Event-Details"
                placement="right"
                width={600}
                onClose={() => setDrawerVisible(false)}
                open={drawerVisible}
            >
                {selectedEvent && (
                    <div>
                        <Descriptions bordered column={1}>
                            <Descriptions.Item label="Event-Name">
                                <Space>
                                    <span>{getEventIcon(selectedEvent.event_name)}</span>
                                    <Text strong>{selectedEvent.event_name}</Text>
                                </Space>
                            </Descriptions.Item>
                            <Descriptions.Item label="Zeitstempel">
                                {dayjs(selectedEvent.created_at).format('DD.MM.YYYY HH:mm:ss')}
                            </Descriptions.Item>
                            <Descriptions.Item label="Benutzer">
                                {selectedEvent.user_id ? `User ${selectedEvent.user_id}` : 'System'}
                            </Descriptions.Item>
                        </Descriptions>

                        <Divider>Payload</Divider>
                        <pre style={{ 
                            backgroundColor: '#f5f5f5', 
                            padding: 16, 
                            borderRadius: 4,
                            overflow: 'auto'
                        }}>
                            {JSON.stringify(selectedEvent.payload, null, 2)}
                        </pre>

                        {selectedEvent.metadata && (
                            <>
                                <Divider>Metadata</Divider>
                                <pre style={{ 
                                    backgroundColor: '#f5f5f5', 
                                    padding: 16, 
                                    borderRadius: 4,
                                    overflow: 'auto'
                                }}>
                                    {JSON.stringify(selectedEvent.metadata, null, 2)}
                                </pre>
                            </>
                        )}
                    </div>
                )}
            </Drawer>
        </div>
    );
};

export default EventsIndex;