import React, { useState, useEffect, useCallback } from 'react';
import { Table, Card, Input, Button, Space, Tag, Tooltip, Modal, Badge, Typography, Row, Col, Statistic, message, Form, Select, Switch, Tabs, TimePicker } from 'antd';
import { 
    SearchOutlined, 
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    EyeOutlined,
    ShopOutlined,
    PhoneOutlined,
    MailOutlined,
    EnvironmentOutlined,
    TeamOutlined,
    CalendarOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined
} from '@ant-design/icons';
import adminAxios from '../../../services/adminAxios';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

const { Search } = Input;
const { Title, Text } = Typography;
const { TabPane } = Tabs;
const { Option } = Select;

const BranchesIndex = () => {
    const [branches, setBranches] = useState([]);
    const [companies, setCompanies] = useState([]);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedBranch, setSelectedBranch] = useState(null);
    const [modalVisible, setModalVisible] = useState(false);
    const [workingHoursModalVisible, setWorkingHoursModalVisible] = useState(false);
    const [selectedCompanyId, setSelectedCompanyId] = useState(null);
    const [form] = Form.useForm();
    const [workingHoursForm] = Form.useForm();
    const [stats, setStats] = useState({
        total: 0,
        active: 0,
        staff_total: 0,
        appointments_today: 0
    });
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 20,
        total: 0
    });

    // Use centralized admin axios instance
    const api = adminAxios;

    // Fetch branches
    const fetchBranches = useCallback(async (page = 1, search = '', companyId = null) => {
        setLoading(true);
        try {
            const params = {
                page,
                per_page: pagination.pageSize,
                search
            };
            
            if (companyId) {
                params.company_id = companyId;
            }
            
            const response = await api.get('/branches', { params });
            
            const data = response.data;
            setBranches(data.data || []);
            setPagination(prev => ({
                ...prev,
                current: data.current_page || 1,
                total: data.total || 0
            }));
            
            // Calculate stats
            if (data.data) {
                const activeCount = data.data.filter(b => b.active !== false).length;
                const staffTotal = data.data.reduce((sum, b) => sum + (b.staff_count || 0), 0);
                
                setStats({
                    total: data.total || 0,
                    active: activeCount,
                    staff_total: staffTotal,
                    appointments_today: data.appointments_today || 0
                });
            }
        } catch (error) {
            console.error('Error fetching branches:', error);
            message.error('Fehler beim Laden der Filialen');
        } finally {
            setLoading(false);
        }
    }, [api, pagination.pageSize]);

    // Fetch companies for dropdown
    const fetchCompanies = async () => {
        try {
            const response = await api.get('/companies', {
                params: { per_page: 100 }
            });
            setCompanies(response.data.data || []);
        } catch (error) {
            console.error('Error fetching companies:', error);
        }
    };

    // Initial load
    useEffect(() => {
        fetchBranches();
        fetchCompanies();
    }, []);

    // Handle search
    const handleSearch = (value) => {
        setSearchTerm(value);
        fetchBranches(1, value, selectedCompanyId);
    };

    // Handle company filter
    const handleCompanyFilter = (companyId) => {
        setSelectedCompanyId(companyId);
        fetchBranches(1, searchTerm, companyId);
    };

    // Handle table change
    const handleTableChange = (newPagination) => {
        fetchBranches(newPagination.current, searchTerm, selectedCompanyId);
    };

    // Create or update branch
    const handleSubmit = async (values) => {
        try {
            if (selectedBranch && selectedBranch.id) {
                await api.put(`/branches/${selectedBranch.id}`, values);
                message.success('Filiale erfolgreich aktualisiert');
            } else {
                await api.post('/branches', values);
                message.success('Filiale erfolgreich erstellt');
            }
            setModalVisible(false);
            form.resetFields();
            fetchBranches(pagination.current, searchTerm, selectedCompanyId);
        } catch (error) {
            console.error('Error saving branch:', error);
            message.error('Fehler beim Speichern der Filiale');
        }
    };

    // Delete branch
    const deleteBranch = async (branchId) => {
        Modal.confirm({
            title: 'Filiale löschen?',
            content: 'Sind Sie sicher, dass Sie diese Filiale löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
            okText: 'Löschen',
            okType: 'danger',
            cancelText: 'Abbrechen',
            onOk: async () => {
                try {
                    await api.delete(`/branches/${branchId}`);
                    message.success('Filiale erfolgreich gelöscht');
                    fetchBranches(pagination.current, searchTerm, selectedCompanyId);
                } catch (error) {
                    console.error('Error deleting branch:', error);
                    message.error('Fehler beim Löschen der Filiale');
                }
            }
        });
    };

    // Update working hours
    const handleWorkingHoursSubmit = async (values) => {
        try {
            await api.post(`/branches/${selectedBranch.id}/working-hours`, { 
                working_hours: values 
            });
            message.success('Öffnungszeiten erfolgreich aktualisiert');
            setWorkingHoursModalVisible(false);
            workingHoursForm.resetFields();
        } catch (error) {
            console.error('Error updating working hours:', error);
            message.error('Fehler beim Aktualisieren der Öffnungszeiten');
        }
    };

    // Table columns
    const columns = [
        {
            title: 'Filiale',
            dataIndex: 'name',
            key: 'name',
            render: (_, record) => (
                <Space>
                    <ShopOutlined style={{ fontSize: '20px', color: '#1890ff' }} />
                    <div>
                        <div className="font-medium">{record.name}</div>
                        <Text type="secondary" className="text-xs">
                            {record.company?.name || 'Unbekannter Mandant'}
                        </Text>
                    </div>
                </Space>
            )
        },
        {
            title: 'Adresse',
            key: 'address',
            render: (_, record) => (
                <Space size="small">
                    <EnvironmentOutlined className="text-gray-400" />
                    <div>
                        <div>{record.address || '-'}</div>
                        <Text type="secondary" className="text-xs">
                            {record.postal_code} {record.city}
                        </Text>
                    </div>
                </Space>
            )
        },
        {
            title: 'Kontakt',
            key: 'contact',
            render: (_, record) => (
                <Space direction="vertical" size="small">
                    {record.phone && (
                        <Space size="small">
                            <PhoneOutlined className="text-gray-400" />
                            <Text copyable>{record.phone}</Text>
                        </Space>
                    )}
                    {record.email && (
                        <Space size="small">
                            <MailOutlined className="text-gray-400" />
                            <Text copyable className="text-xs">{record.email}</Text>
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
                    <Tooltip title="Mitarbeiter">
                        <Badge count={record.staff_count || 0} showZero style={{ backgroundColor: '#52c41a' }}>
                            <TeamOutlined className="text-lg" />
                        </Badge>
                    </Tooltip>
                    <Tooltip title="Termine heute">
                        <Badge count={record.appointments_count || 0} showZero style={{ backgroundColor: '#1890ff' }}>
                            <CalendarOutlined className="text-lg" />
                        </Badge>
                    </Tooltip>
                </Space>
            )
        },
        {
            title: 'Status',
            key: 'status',
            render: (_, record) => (
                <Tag color={record.active !== false ? 'green' : 'red'}>
                    {record.active !== false ? 'Aktiv' : 'Inaktiv'}
                </Tag>
            )
        },
        {
            title: 'Erstellt',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => date ? dayjs(date).format('DD.MM.YYYY') : '-'
        },
        {
            title: 'Aktionen',
            key: 'actions',
            fixed: 'right',
            width: 200,
            render: (_, record) => (
                <Space size="small" wrap>
                    <Tooltip title="Öffnungszeiten">
                        <Button 
                            type="text" 
                            icon={<ClockCircleOutlined />} 
                            onClick={() => {
                                setSelectedBranch(record);
                                if (record.working_hours) {
                                    workingHoursForm.setFieldsValue(record.working_hours);
                                }
                                setWorkingHoursModalVisible(true);
                            }}
                        />
                    </Tooltip>
                    <Tooltip title="Bearbeiten">
                        <Button 
                            type="text" 
                            icon={<EditOutlined />} 
                            onClick={() => {
                                setSelectedBranch(record);
                                form.setFieldsValue({
                                    ...record,
                                    company_id: record.company_id || record.company?.id
                                });
                                setModalVisible(true);
                            }}
                        />
                    </Tooltip>
                    <Tooltip title="Löschen">
                        <Button 
                            type="text" 
                            danger
                            icon={<DeleteOutlined />} 
                            onClick={() => deleteBranch(record.id)}
                        />
                    </Tooltip>
                </Space>
            )
        }
    ];

    // Working hours configuration
    const weekDays = [
        { key: 'monday', label: 'Montag' },
        { key: 'tuesday', label: 'Dienstag' },
        { key: 'wednesday', label: 'Mittwoch' },
        { key: 'thursday', label: 'Donnerstag' },
        { key: 'friday', label: 'Freitag' },
        { key: 'saturday', label: 'Samstag' },
        { key: 'sunday', label: 'Sonntag' }
    ];

    return (
        <div>
            <div className="mb-6">
                <Title level={2}>Filialen</Title>
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
                            title="Mitarbeiter gesamt"
                            value={stats.staff_total}
                            prefix={<TeamOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Termine heute"
                            value={stats.appointments_today}
                            prefix={<CalendarOutlined />}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Toolbar */}
            <Card className="mb-4">
                <Row gutter={16} align="middle">
                    <Col xs={24} md={8}>
                        <Search
                            placeholder="Suche nach Name, Adresse oder Stadt..."
                            allowClear
                            enterButton={<SearchOutlined />}
                            size="large"
                            onSearch={handleSearch}
                        />
                    </Col>
                    <Col xs={24} md={8}>
                        <Select
                            placeholder="Mandant filtern"
                            allowClear
                            style={{ width: '100%' }}
                            size="large"
                            onChange={handleCompanyFilter}
                            showSearch
                            optionFilterProp="children"
                        >
                            {companies.map(company => (
                                <Option key={company.id} value={company.id}>
                                    {company.name}
                                </Option>
                            ))}
                        </Select>
                    </Col>
                    <Col xs={24} md={8} style={{ textAlign: 'right' }}>
                        <Button 
                            type="primary" 
                            icon={<PlusOutlined />}
                            size="large"
                            onClick={() => {
                                setSelectedBranch(null);
                                form.resetFields();
                                setModalVisible(true);
                            }}
                        >
                            Neue Filiale
                        </Button>
                    </Col>
                </Row>
            </Card>

            {/* Table */}
            <Card>
                <Table
                    columns={columns}
                    dataSource={branches}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        ...pagination,
                        showSizeChanger: true,
                        showTotal: (total, range) => `${range[0]}-${range[1]} von ${total} Filialen`,
                        pageSizeOptions: ['10', '20', '50', '100']
                    }}
                    onChange={handleTableChange}
                    scroll={{ x: 1200 }}
                />
            </Card>

            {/* Create/Edit Modal */}
            <Modal
                title={selectedBranch ? 'Filiale bearbeiten' : 'Neue Filiale'}
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
                                name="company_id"
                                label="Mandant"
                                rules={[{ required: true, message: 'Bitte Mandant wählen' }]}
                            >
                                <Select
                                    placeholder="Mandant wählen"
                                    showSearch
                                    optionFilterProp="children"
                                >
                                    {companies.map(company => (
                                        <Option key={company.id} value={company.id}>
                                            {company.name}
                                        </Option>
                                    ))}
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="name"
                                label="Name"
                                rules={[{ required: true, message: 'Bitte Name eingeben' }]}
                            >
                                <Input placeholder="Hauptfiliale" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="phone"
                                label="Telefon"
                            >
                                <Input placeholder="+49 123 456789" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="email"
                                label="E-Mail"
                            >
                                <Input placeholder="filiale@firma.de" />
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

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                {selectedBranch ? 'Aktualisieren' : 'Erstellen'}
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

            {/* Working Hours Modal */}
            <Modal
                title="Öffnungszeiten bearbeiten"
                visible={workingHoursModalVisible}
                onCancel={() => {
                    setWorkingHoursModalVisible(false);
                    workingHoursForm.resetFields();
                }}
                footer={null}
                width={600}
            >
                <Form
                    form={workingHoursForm}
                    layout="vertical"
                    onFinish={handleWorkingHoursSubmit}
                >
                    {weekDays.map(day => (
                        <Row key={day.key} gutter={16} align="middle" className="mb-4">
                            <Col span={6}>
                                <Text strong>{day.label}</Text>
                            </Col>
                            <Col span={6}>
                                <Form.Item
                                    name={[day.key, 'is_open']}
                                    valuePropName="checked"
                                    className="mb-0"
                                >
                                    <Switch checkedChildren="Geöffnet" unCheckedChildren="Geschlossen" />
                                </Form.Item>
                            </Col>
                            <Col span={6}>
                                <Form.Item
                                    name={[day.key, 'open_time']}
                                    className="mb-0"
                                    dependencies={[[day.key, 'is_open']]}
                                >
                                    <TimePicker 
                                        format="HH:mm" 
                                        placeholder="Öffnung"
                                        disabled={!workingHoursForm.getFieldValue([day.key, 'is_open'])}
                                    />
                                </Form.Item>
                            </Col>
                            <Col span={6}>
                                <Form.Item
                                    name={[day.key, 'close_time']}
                                    className="mb-0"
                                    dependencies={[[day.key, 'is_open']]}
                                >
                                    <TimePicker 
                                        format="HH:mm" 
                                        placeholder="Schließung"
                                        disabled={!workingHoursForm.getFieldValue([day.key, 'is_open'])}
                                    />
                                </Form.Item>
                            </Col>
                        </Row>
                    ))}

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                Speichern
                            </Button>
                            <Button onClick={() => {
                                setWorkingHoursModalVisible(false);
                                workingHoursForm.resetFields();
                            }}>
                                Abbrechen
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
};

export default BranchesIndex;