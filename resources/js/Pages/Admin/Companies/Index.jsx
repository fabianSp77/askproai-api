import React, { useState, useEffect, useCallback } from 'react';
import { Table, Card, Input, Button, Space, Tag, Tooltip, Modal, Badge, Typography, Row, Col, Statistic, message, Drawer, Form, Select, Switch, Tabs, Divider, Alert } from 'antd';
import { 
    SearchOutlined, 
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    EyeOutlined,
    ShopOutlined,
    PhoneOutlined,
    MailOutlined,
    GlobalOutlined,
    KeyOutlined,
    SyncOutlined,
    SettingOutlined,
    DollarOutlined,
    TeamOutlined,
    CalendarOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    WarningOutlined,
    ApiOutlined
} from '@ant-design/icons';
import adminAxios from '../../../services/adminAxios';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

const { Search } = Input;
const { Title, Text } = Typography;
const { TabPane } = Tabs;
const { Option } = Select;

const CompaniesIndex = () => {
    const [companies, setCompanies] = useState([]);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCompany, setSelectedCompany] = useState(null);
    const [modalVisible, setModalVisible] = useState(false);
    const [detailDrawerVisible, setDetailDrawerVisible] = useState(false);
    const [apiKeysVisible, setApiKeysVisible] = useState({});
    const [form] = Form.useForm();
    const [stats, setStats] = useState({
        total: 0,
        active: 0,
        trial: 0,
        premium: 0
    });
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 10,
        total: 0
    });

    // Use centralized admin axios instance
    const api = adminAxios;

    // Fetch companies
    const fetchCompanies = useCallback(async (page = 1, search = '') => {
        setLoading(true);
        try {
            const response = await api.get('/companies', {
                params: {
                    page,
                    per_page: pagination.pageSize,
                    search
                }
            });
            
            const data = response.data;
            setCompanies(data.data || []);
            setPagination(prev => ({
                ...prev,
                current: data.current_page || 1,
                total: data.total || 0
            }));
            
            // Calculate stats
            if (data.data) {
                const activeCount = data.data.filter(c => c.active).length;
                const trialCount = data.data.filter(c => c.subscription_status === 'trial').length;
                const premiumCount = data.data.filter(c => c.subscription_status === 'premium').length;
                
                setStats({
                    total: data.total || 0,
                    active: activeCount,
                    trial: trialCount,
                    premium: premiumCount
                });
            }
        } catch (error) {
            console.error('Error fetching companies:', error);
            message.error('Fehler beim Laden der Mandanten');
        } finally {
            setLoading(false);
        }
    }, [api, pagination.pageSize]);

    // Initial load
    useEffect(() => {
        fetchCompanies();
    }, []);

    // Handle search
    const handleSearch = (value) => {
        setSearchTerm(value);
        fetchCompanies(1, value);
    };

    // Handle table change
    const handleTableChange = (newPagination) => {
        fetchCompanies(newPagination.current, searchTerm);
    };

    // View company details
    const viewCompany = async (company) => {
        try {
            const response = await api.get(`/companies/${company.id}`);
            setSelectedCompany(response.data);
            setDetailDrawerVisible(true);
        } catch (error) {
            message.error('Fehler beim Laden der Details');
        }
    };

    // Create or update company
    const handleSubmit = async (values) => {
        try {
            if (selectedCompany && selectedCompany.id) {
                await api.put(`/companies/${selectedCompany.id}`, values);
                message.success('Mandant erfolgreich aktualisiert');
            } else {
                await api.post('/companies', values);
                message.success('Mandant erfolgreich erstellt');
            }
            setModalVisible(false);
            form.resetFields();
            fetchCompanies(pagination.current, searchTerm);
        } catch (error) {
            console.error('Error saving company:', error);
            message.error('Fehler beim Speichern des Mandanten');
        }
    };

    // Delete company
    const deleteCompany = async (companyId) => {
        Modal.confirm({
            title: 'Mandant löschen?',
            content: 'Sind Sie sicher, dass Sie diesen Mandanten löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
            okText: 'Löschen',
            okType: 'danger',
            cancelText: 'Abbrechen',
            onOk: async () => {
                try {
                    await api.delete(`/companies/${companyId}`);
                    message.success('Mandant erfolgreich gelöscht');
                    fetchCompanies(pagination.current, searchTerm);
                } catch (error) {
                    console.error('Error deleting company:', error);
                    message.error('Fehler beim Löschen des Mandanten');
                }
            }
        });
    };

    // Toggle company status
    const toggleStatus = async (company) => {
        try {
            const endpoint = company.active ? 'deactivate' : 'activate';
            await api.post(`/companies/${company.id}/${endpoint}`);
            message.success(`Mandant ${company.active ? 'deaktiviert' : 'aktiviert'}`);
            fetchCompanies(pagination.current, searchTerm);
        } catch (error) {
            message.error('Fehler beim Ändern des Status');
        }
    };

    // Sync with Cal.com
    const syncCalcom = async (companyId) => {
        try {
            await api.post(`/companies/${companyId}/sync-calcom`);
            message.success('Cal.com Synchronisation gestartet');
        } catch (error) {
            message.error('Fehler bei der Cal.com Synchronisation');
        }
    };

    // Table columns
    const columns = [
        {
            title: 'Mandant',
            dataIndex: 'name',
            key: 'name',
            render: (_, record) => (
                <Space>
                    <ShopOutlined style={{ fontSize: '20px', color: '#1890ff' }} />
                    <div>
                        <div className="font-medium">{record.name}</div>
                        <Text type="secondary" className="text-xs">
                            ID: {record.id} • {record.language?.toUpperCase() || 'DE'}
                        </Text>
                    </div>
                </Space>
            ),
            sorter: true
        },
        {
            title: 'Kontakt',
            key: 'contact',
            render: (_, record) => (
                <Space direction="vertical" size="small">
                    {record.email && (
                        <Space size="small">
                            <MailOutlined className="text-gray-400" />
                            <Text copyable className="text-xs">{record.email}</Text>
                        </Space>
                    )}
                    {record.phone && (
                        <Space size="small">
                            <PhoneOutlined className="text-gray-400" />
                            <Text copyable>{record.phone}</Text>
                        </Space>
                    )}
                </Space>
            )
        },
        {
            title: 'Statistiken',
            key: 'stats',
            render: (_, record) => (
                <Space size="small">
                    <Tooltip title="Filialen">
                        <Badge count={record.branches_count || 0} showZero style={{ backgroundColor: '#52c41a' }}>
                            <ShopOutlined className="text-lg" />
                        </Badge>
                    </Tooltip>
                    <Tooltip title="Kunden">
                        <Badge count={record.customers_count || 0} showZero style={{ backgroundColor: '#1890ff' }}>
                            <TeamOutlined className="text-lg" />
                        </Badge>
                    </Tooltip>
                    <Tooltip title="Termine">
                        <Badge count={record.appointments_count || 0} showZero style={{ backgroundColor: '#faad14' }}>
                            <CalendarOutlined className="text-lg" />
                        </Badge>
                    </Tooltip>
                    <Tooltip title="Anrufe">
                        <Badge count={record.calls_count || 0} showZero style={{ backgroundColor: '#722ed1' }}>
                            <PhoneOutlined className="text-lg" />
                        </Badge>
                    </Tooltip>
                </Space>
            )
        },
        {
            title: 'Status',
            key: 'status',
            render: (_, record) => {
                const tags = [];
                if (record.active) {
                    tags.push(<Tag color="green" key="active">Aktiv</Tag>);
                } else {
                    tags.push(<Tag color="red" key="inactive">Inaktiv</Tag>);
                }
                if (record.subscription_status === 'trial') {
                    tags.push(<Tag color="orange" key="trial">Trial</Tag>);
                } else if (record.subscription_status === 'premium') {
                    tags.push(<Tag color="gold" key="premium">Premium</Tag>);
                }
                return <Space size="small">{tags}</Space>;
            }
        },
        {
            title: 'API Keys',
            key: 'api_keys',
            render: (_, record) => (
                <Space direction="vertical" size="small">
                    {record.retell_api_key && (
                        <Space size="small">
                            <ApiOutlined />
                            <Text className="text-xs">
                                Retell: {apiKeysVisible[record.id + '_retell'] ? record.retell_api_key : '••••••••'}
                                <Button 
                                    type="link" 
                                    size="small"
                                    onClick={() => setApiKeysVisible(prev => ({
                                        ...prev,
                                        [record.id + '_retell']: !prev[record.id + '_retell']
                                    }))}
                                >
                                    {apiKeysVisible[record.id + '_retell'] ? 'Verbergen' : 'Anzeigen'}
                                </Button>
                            </Text>
                        </Space>
                    )}
                    {record.calcom_api_key && (
                        <Space size="small">
                            <CalendarOutlined />
                            <Text className="text-xs">
                                Cal.com: {apiKeysVisible[record.id + '_calcom'] ? record.calcom_api_key : '••••••••'}
                                <Button 
                                    type="link" 
                                    size="small"
                                    onClick={() => setApiKeysVisible(prev => ({
                                        ...prev,
                                        [record.id + '_calcom']: !prev[record.id + '_calcom']
                                    }))}
                                >
                                    {apiKeysVisible[record.id + '_calcom'] ? 'Verbergen' : 'Anzeigen'}
                                </Button>
                            </Text>
                        </Space>
                    )}
                </Space>
            )
        },
        {
            title: 'Erstellt',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => date ? dayjs(date).format('DD.MM.YYYY') : '-',
            sorter: true
        },
        {
            title: 'Aktionen',
            key: 'actions',
            fixed: 'right',
            width: 200,
            render: (_, record) => (
                <Space size="small" wrap>
                    <Tooltip title="Details anzeigen">
                        <Button 
                            type="text" 
                            icon={<EyeOutlined />} 
                            onClick={() => viewCompany(record)}
                        />
                    </Tooltip>
                    <Tooltip title="Bearbeiten">
                        <Button 
                            type="text" 
                            icon={<EditOutlined />} 
                            onClick={() => {
                                setSelectedCompany(record);
                                form.setFieldsValue(record);
                                setModalVisible(true);
                            }}
                        />
                    </Tooltip>
                    <Tooltip title="Cal.com Sync">
                        <Button 
                            type="text" 
                            icon={<SyncOutlined />} 
                            onClick={() => syncCalcom(record.id)}
                        />
                    </Tooltip>
                    <Tooltip title={record.active ? 'Deaktivieren' : 'Aktivieren'}>
                        <Button 
                            type="text" 
                            icon={record.active ? <CloseCircleOutlined /> : <CheckCircleOutlined />}
                            onClick={() => toggleStatus(record)}
                            danger={record.active}
                        />
                    </Tooltip>
                    <Tooltip title="Löschen">
                        <Button 
                            type="text" 
                            danger
                            icon={<DeleteOutlined />} 
                            onClick={() => deleteCompany(record.id)}
                        />
                    </Tooltip>
                </Space>
            )
        }
    ];

    return (
        <div>
            <div className="mb-6">
                <Title level={2}>Mandanten</Title>
            </div>

            {/* Statistics */}
            <Row gutter={16} className="mb-6">
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Gesamt"
                            value={stats.total}
                            prefix={<ShopOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Aktiv"
                            value={stats.active}
                            valueStyle={{ color: '#3f8600' }}
                            prefix={<CheckCircleOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Trial"
                            value={stats.trial}
                            valueStyle={{ color: '#faad14' }}
                            prefix={<WarningOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Premium"
                            value={stats.premium}
                            valueStyle={{ color: '#fadb14' }}
                            prefix={<DollarOutlined />}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Toolbar */}
            <Card className="mb-4">
                <Row gutter={16} align="middle">
                    <Col flex="auto">
                        <Search
                            placeholder="Suche nach Name, E-Mail oder Telefon..."
                            allowClear
                            enterButton={<SearchOutlined />}
                            size="large"
                            onSearch={handleSearch}
                            style={{ maxWidth: 400 }}
                        />
                    </Col>
                    <Col>
                        <Button 
                            type="primary" 
                            icon={<PlusOutlined />}
                            size="large"
                            onClick={() => {
                                setSelectedCompany(null);
                                form.resetFields();
                                setModalVisible(true);
                            }}
                        >
                            Neuer Mandant
                        </Button>
                    </Col>
                </Row>
            </Card>

            {/* Table */}
            <Card>
                <Table
                    columns={columns}
                    dataSource={companies}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        ...pagination,
                        showSizeChanger: true,
                        showTotal: (total, range) => `${range[0]}-${range[1]} von ${total} Mandanten`,
                        pageSizeOptions: ['10', '20', '50', '100']
                    }}
                    onChange={handleTableChange}
                    scroll={{ x: 1200 }}
                />
            </Card>

            {/* Create/Edit Modal */}
            <Modal
                title={selectedCompany ? 'Mandant bearbeiten' : 'Neuer Mandant'}
                visible={modalVisible}
                onCancel={() => {
                    setModalVisible(false);
                    form.resetFields();
                }}
                footer={null}
                width={800}
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                >
                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="name"
                                label="Name"
                                rules={[{ required: true, message: 'Bitte Name eingeben' }]}
                            >
                                <Input placeholder="Firma GmbH" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="email"
                                label="E-Mail"
                                rules={[
                                    { required: true, message: 'Bitte E-Mail eingeben' },
                                    { type: 'email', message: 'Bitte gültige E-Mail eingeben' }
                                ]}
                            >
                                <Input placeholder="kontakt@firma.de" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="phone"
                                label="Telefon"
                                rules={[{ required: true, message: 'Bitte Telefon eingeben' }]}
                            >
                                <Input placeholder="+49 123 456789" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="timezone"
                                label="Zeitzone"
                                rules={[{ required: true, message: 'Bitte Zeitzone wählen' }]}
                                initialValue="Europe/Berlin"
                            >
                                <Select>
                                    <Option value="Europe/Berlin">Europe/Berlin</Option>
                                    <Option value="Europe/Vienna">Europe/Vienna</Option>
                                    <Option value="Europe/Zurich">Europe/Zurich</Option>
                                </Select>
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={24}>
                            <Form.Item
                                name="address"
                                label="Adresse"
                            >
                                <Input placeholder="Musterstraße 123" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={8}>
                            <Form.Item
                                name="postal_code"
                                label="PLZ"
                            >
                                <Input placeholder="12345" />
                            </Form.Item>
                        </Col>
                        <Col span={8}>
                            <Form.Item
                                name="city"
                                label="Stadt"
                            >
                                <Input placeholder="Berlin" />
                            </Form.Item>
                        </Col>
                        <Col span={8}>
                            <Form.Item
                                name="country"
                                label="Land"
                                initialValue="DE"
                            >
                                <Select>
                                    <Option value="DE">Deutschland</Option>
                                    <Option value="AT">Österreich</Option>
                                    <Option value="CH">Schweiz</Option>
                                </Select>
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="language"
                                label="Sprache"
                                rules={[{ required: true, message: 'Bitte Sprache wählen' }]}
                                initialValue="de"
                            >
                                <Select>
                                    <Option value="de">Deutsch</Option>
                                    <Option value="en">Englisch</Option>
                                    <Option value="fr">Französisch</Option>
                                    <Option value="es">Spanisch</Option>
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="active"
                                label="Status"
                                valuePropName="checked"
                                initialValue={true}
                            >
                                <Switch checkedChildren="Aktiv" unCheckedChildren="Inaktiv" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Divider>API Keys</Divider>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="retell_api_key"
                                label="Retell.ai API Key"
                            >
                                <Input.Password placeholder="Optional" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="calcom_api_key"
                                label="Cal.com API Key"
                            >
                                <Input.Password placeholder="Optional" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                {selectedCompany ? 'Aktualisieren' : 'Erstellen'}
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

            {/* Detail Drawer */}
            <Drawer
                title="Mandanten Details"
                placement="right"
                width={800}
                onClose={() => setDetailDrawerVisible(false)}
                visible={detailDrawerVisible}
            >
                {selectedCompany && (
                    <div>
                        <Tabs defaultActiveKey="1">
                            <TabPane tab="Übersicht" key="1">
                                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                                    <Card title="Grunddaten">
                                        <Row gutter={[16, 16]}>
                                            <Col span={12}>
                                                <Text type="secondary">Name</Text>
                                                <div>{selectedCompany.name}</div>
                                            </Col>
                                            <Col span={12}>
                                                <Text type="secondary">E-Mail</Text>
                                                <div>{selectedCompany.email}</div>
                                            </Col>
                                            <Col span={12}>
                                                <Text type="secondary">Telefon</Text>
                                                <div>{selectedCompany.phone}</div>
                                            </Col>
                                            <Col span={12}>
                                                <Text type="secondary">Status</Text>
                                                <div>
                                                    <Tag color={selectedCompany.active ? 'green' : 'red'}>
                                                        {selectedCompany.active ? 'Aktiv' : 'Inaktiv'}
                                                    </Tag>
                                                </div>
                                            </Col>
                                        </Row>
                                    </Card>

                                    <Card title="Statistiken">
                                        <Row gutter={[16, 16]}>
                                            <Col span={12}>
                                                <Statistic
                                                    title="Umsatz gesamt"
                                                    value={selectedCompany.stats?.total_revenue || 0}
                                                    prefix="€"
                                                    precision={2}
                                                />
                                            </Col>
                                            <Col span={12}>
                                                <Statistic
                                                    title="Termine heute"
                                                    value={selectedCompany.stats?.appointments_today || 0}
                                                />
                                            </Col>
                                            <Col span={12}>
                                                <Statistic
                                                    title="Anrufe heute"
                                                    value={selectedCompany.stats?.calls_today || 0}
                                                />
                                            </Col>
                                            <Col span={12}>
                                                <Statistic
                                                    title="Aktive Mitarbeiter"
                                                    value={selectedCompany.stats?.active_staff || 0}
                                                />
                                            </Col>
                                        </Row>
                                    </Card>
                                </Space>
                            </TabPane>

                            <TabPane tab="Filialen" key="2">
                                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                                    {selectedCompany.branches?.map(branch => (
                                        <Card key={branch.id} size="small">
                                            <Row gutter={[16, 8]}>
                                                <Col span={12}>
                                                    <Text strong>{branch.name}</Text>
                                                </Col>
                                                <Col span={12}>
                                                    <Space>
                                                        <PhoneOutlined />
                                                        <Text>{branch.phone}</Text>
                                                    </Space>
                                                </Col>
                                                <Col span={24}>
                                                    <Text type="secondary">
                                                        {branch.address}, {branch.postal_code} {branch.city}
                                                    </Text>
                                                </Col>
                                            </Row>
                                        </Card>
                                    ))}
                                    {(!selectedCompany.branches || selectedCompany.branches.length === 0) && (
                                        <Alert message="Keine Filialen vorhanden" type="info" />
                                    )}
                                </Space>
                            </TabPane>

                            <TabPane tab="API Integration" key="3">
                                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                                    <Card title="Retell.ai">
                                        <Space direction="vertical" style={{ width: '100%' }}>
                                            <div>
                                                <Text type="secondary">API Key</Text>
                                                <div>
                                                    {selectedCompany.retell_api_key ? (
                                                        <Tag color="green">Konfiguriert</Tag>
                                                    ) : (
                                                        <Tag color="red">Nicht konfiguriert</Tag>
                                                    )}
                                                </div>
                                            </div>
                                            <div>
                                                <Text type="secondary">Agent ID</Text>
                                                <div>{selectedCompany.retell_agent_id || '-'}</div>
                                            </div>
                                        </Space>
                                    </Card>

                                    <Card title="Cal.com">
                                        <Space direction="vertical" style={{ width: '100%' }}>
                                            <div>
                                                <Text type="secondary">API Key</Text>
                                                <div>
                                                    {selectedCompany.calcom_api_key ? (
                                                        <Tag color="green">Konfiguriert</Tag>
                                                    ) : (
                                                        <Tag color="red">Nicht konfiguriert</Tag>
                                                    )}
                                                </div>
                                            </div>
                                            <div>
                                                <Text type="secondary">Team Slug</Text>
                                                <div>{selectedCompany.calcom_team_slug || '-'}</div>
                                            </div>
                                            <Button 
                                                type="primary" 
                                                icon={<SyncOutlined />}
                                                onClick={() => syncCalcom(selectedCompany.id)}
                                            >
                                                Jetzt synchronisieren
                                            </Button>
                                        </Space>
                                    </Card>
                                </Space>
                            </TabPane>
                        </Tabs>
                    </div>
                )}
            </Drawer>
        </div>
    );
};

export default CompaniesIndex;