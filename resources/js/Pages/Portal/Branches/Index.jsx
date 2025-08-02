import React, { useState, useEffect } from 'react';
import { 
    Card, 
    Table, 
    Button, 
    Space, 
    Tag, 
    Input, 
    Row, 
    Col, 
    Typography, 
    Modal, 
    Form, 
    message,
    Tooltip,
    Badge,
    Empty,
    Popconfirm,
    Drawer,
    Descriptions,
    Switch,
    TimePicker,
    Select,
    Alert,
    Statistic,
    Tabs
} from 'antd';
import { 
    ShopOutlined, 
    PhoneOutlined,
    EditOutlined,
    DeleteOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    PlusOutlined,
    ReloadOutlined,
    EnvironmentOutlined,
    ClockCircleOutlined,
    TeamOutlined,
    AppstoreOutlined,
    MailOutlined,
    GlobalOutlined,
    CalendarOutlined,
    DollarOutlined,
    BarChartOutlined
} from '@ant-design/icons';
import axiosInstance from '../../../services/axiosInstance';
import dayjs from 'dayjs';

const { Title, Text } = Typography;
const { Option } = Select;
const { TabPane } = Tabs;

const BranchesIndex = () => {
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [selectedBranch, setSelectedBranch] = useState(null);
    const [drawerVisible, setDrawerVisible] = useState(false);
    const [modalVisible, setModalVisible] = useState(false);
    const [workingHoursModalVisible, setWorkingHoursModalVisible] = useState(false);
    const [branchStaff, setBranchStaff] = useState([]);
    const [branchServices, setBranchServices] = useState([]);
    const [workingHours, setWorkingHours] = useState([]);
    const [form] = Form.useForm();
    const [editForm] = Form.useForm();
    const [activeTab, setActiveTab] = useState('details');

    const daysOfWeek = [
        { value: 1, label: 'Montag' },
        { value: 2, label: 'Dienstag' },
        { value: 3, label: 'Mittwoch' },
        { value: 4, label: 'Donnerstag' },
        { value: 5, label: 'Freitag' },
        { value: 6, label: 'Samstag' },
        { value: 0, label: 'Sonntag' }
    ];

    useEffect(() => {
        fetchBranches();
    }, [search]);

    const fetchBranches = async () => {
        setLoading(true);
        try {
            const response = await axiosInstance.get('/branches');
            setBranches(response.data.branches || []);
        } catch (error) {
            message.error('Fehler beim Laden der Filialen');
        } finally {
            setLoading(false);
        }
    };

    const fetchBranchDetails = async (branchId) => {
        try {
            const [staffResponse, servicesResponse, hoursResponse] = await Promise.all([
                axiosInstance.get(`/branches/${branchId}/staff`),
                axiosInstance.get(`/branches/${branchId}/services`),
                axiosInstance.get(`/branches/${branchId}/working-hours`)
            ]);

            setBranchStaff(staffResponse.data.staff || []);
            setBranchServices(servicesResponse.data.services || []);
            setWorkingHours(hoursResponse.data.working_hours || []);
        } catch (error) {
            message.error('Fehler beim Laden der Filialdetails');
        }
    };

    const handleCreateBranch = async (values) => {
        try {
            await axiosInstance.post('/branches', {
                ...values,
                create_default_hours: true
            });
            message.success('Filiale erfolgreich erstellt');
            setModalVisible(false);
            form.resetFields();
            fetchBranches();
        } catch (error) {
            message.error('Fehler beim Erstellen der Filiale');
        }
    };

    const handleUpdateBranch = async (branchId, values) => {
        try {
            await axiosInstance.put(`/branches/${branchId}`, values);
            message.success('Filiale erfolgreich aktualisiert');
            fetchBranches();
            if (selectedBranch?.id === branchId) {
                const response = await axiosInstance.get(`/branches/${branchId}`);
                setSelectedBranch(response.data.branch);
            }
        } catch (error) {
            message.error('Fehler beim Aktualisieren der Filiale');
        }
    };

    const handleDeleteBranch = async (branchId) => {
        try {
            await axiosInstance.delete(`/branches/${branchId}`);
            message.success('Filiale erfolgreich gelöscht');
            if (selectedBranch?.id === branchId) {
                setDrawerVisible(false);
            }
            fetchBranches();
        } catch (error) {
            message.error(error.response?.data?.error || 'Fehler beim Löschen der Filiale');
        }
    };

    const handleUpdateWorkingHours = async () => {
        try {
            const formattedHours = workingHours.map(hour => ({
                day_of_week: hour.day_of_week,
                start_time: hour.is_closed ? null : hour.start_time,
                end_time: hour.is_closed ? null : hour.end_time,
                is_closed: hour.is_closed
            }));

            await axiosInstance.put(`/branches/${selectedBranch.id}/working-hours`, {
                hours: formattedHours
            });
            
            message.success('Öffnungszeiten erfolgreich aktualisiert');
            setWorkingHoursModalVisible(false);
        } catch (error) {
            message.error('Fehler beim Aktualisieren der Öffnungszeiten');
        }
    };

    const columns = [
        {
            title: 'Filiale',
            dataIndex: 'name',
            key: 'name',
            render: (name, record) => (
                <Space>
                    <ShopOutlined />
                    <div>
                        <Text strong>{name}</Text>
                        <br />
                        <Text type="secondary" style={{ fontSize: '12px' }}>
                            {record.city ? `${record.city}` : 'Keine Adresse'}
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
                    {record.phone_number && (
                        <Space>
                            <PhoneOutlined />
                            <Text>{record.phone_number}</Text>
                        </Space>
                    )}
                    {record.email && (
                        <Space>
                            <MailOutlined />
                            <Text>{record.email}</Text>
                        </Space>
                    )}
                </Space>
            )
        },
        {
            title: 'Mitarbeiter',
            dataIndex: 'staff_count',
            key: 'staff_count',
            render: (count) => (
                <Badge count={count} showZero>
                    <TeamOutlined style={{ fontSize: '20px' }} />
                </Badge>
            )
        },
        {
            title: 'Services',
            dataIndex: 'services_count',
            key: 'services_count',
            render: (count) => (
                <Badge count={count} showZero>
                    <AppstoreOutlined style={{ fontSize: '20px' }} />
                </Badge>
            )
        },
        {
            title: 'Status',
            dataIndex: 'is_active',
            key: 'is_active',
            render: (isActive, record) => (
                <Switch
                    checked={isActive}
                    onChange={(checked) => handleUpdateBranch(record.id, { is_active: checked })}
                    checkedChildren="Aktiv"
                    unCheckedChildren="Inaktiv"
                />
            )
        },
        {
            title: 'Aktionen',
            key: 'actions',
            fixed: 'right',
            render: (_, record) => (
                <Space>
                    <Tooltip title="Details">
                        <Button
                            type="text"
                            icon={<ShopOutlined />}
                            onClick={() => {
                                setSelectedBranch(record);
                                setDrawerVisible(true);
                                fetchBranchDetails(record.id);
                            }}
                        />
                    </Tooltip>
                    <Popconfirm
                        title="Filiale löschen?"
                        description="Dieser Vorgang kann nicht rückgängig gemacht werden."
                        onConfirm={() => handleDeleteBranch(record.id)}
                    >
                        <Tooltip title="Löschen">
                            <Button
                                type="text"
                                danger
                                icon={<DeleteOutlined />}
                            />
                        </Tooltip>
                    </Popconfirm>
                </Space>
            )
        }
    ];

    const renderWorkingHoursForm = () => {
        const defaultHours = daysOfWeek.map(day => {
            const existing = workingHours.find(h => h.day_of_week === day.value);
            return existing || {
                day_of_week: day.value,
                start_time: '09:00',
                end_time: '18:00',
                is_closed: day.value === 0 // Sunday closed by default
            };
        });

        return (
            <Form layout="vertical">
                {defaultHours.map((hour, index) => {
                    const day = daysOfWeek.find(d => d.value === hour.day_of_week);
                    return (
                        <Form.Item key={hour.day_of_week} label={day?.label}>
                            <Space>
                                <Switch
                                    checked={!hour.is_closed}
                                    onChange={(checked) => {
                                        const newHours = [...workingHours];
                                        if (newHours[index]) {
                                            newHours[index].is_closed = !checked;
                                        } else {
                                            newHours.push({ ...hour, is_closed: !checked });
                                        }
                                        setWorkingHours(newHours);
                                    }}
                                    checkedChildren="Geöffnet"
                                    unCheckedChildren="Geschlossen"
                                />
                                {!hour.is_closed && (
                                    <>
                                        <TimePicker
                                            format="HH:mm"
                                            value={hour.start_time ? dayjs(hour.start_time, 'HH:mm') : null}
                                            onChange={(time) => {
                                                const newHours = [...workingHours];
                                                if (newHours[index]) {
                                                    newHours[index].start_time = time?.format('HH:mm');
                                                } else {
                                                    newHours.push({ ...hour, start_time: time?.format('HH:mm') });
                                                }
                                                setWorkingHours(newHours);
                                            }}
                                        />
                                        <span>bis</span>
                                        <TimePicker
                                            format="HH:mm"
                                            value={hour.end_time ? dayjs(hour.end_time, 'HH:mm') : null}
                                            onChange={(time) => {
                                                const newHours = [...workingHours];
                                                if (newHours[index]) {
                                                    newHours[index].end_time = time?.format('HH:mm');
                                                } else {
                                                    newHours.push({ ...hour, end_time: time?.format('HH:mm') });
                                                }
                                                setWorkingHours(newHours);
                                            }}
                                        />
                                    </>
                                )}
                            </Space>
                        </Form.Item>
                    );
                })}
            </Form>
        );
    };

    return (
        <div style={{ padding: 24 }}>
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col span={24}>
                    <Title level={2}>
                        <ShopOutlined /> Filialverwaltung
                    </Title>
                </Col>
            </Row>

            {/* Statistics */}
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col xs={24} sm={8}>
                    <Card>
                        <Statistic 
                            title="Filialen gesamt" 
                            value={branches.length} 
                            prefix={<ShopOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={8}>
                    <Card>
                        <Statistic 
                            title="Aktive Filialen" 
                            value={branches.filter(b => b.is_active).length} 
                            prefix={<CheckCircleOutlined />}
                            valueStyle={{ color: '#52c41a' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={8}>
                    <Card>
                        <Statistic 
                            title="Mitarbeiter gesamt" 
                            value={branches.reduce((sum, b) => sum + (b.staff_count || 0), 0)} 
                            prefix={<TeamOutlined />}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Search and Actions */}
            <Card style={{ marginBottom: 16 }}>
                <Row gutter={[16, 16]} align="middle">
                    <Col xs={24} sm={16}>
                        <Input.Search
                            placeholder="Nach Filialname oder Stadt suchen..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onSearch={fetchBranches}
                            allowClear
                            size="large"
                        />
                    </Col>
                    <Col xs={24} sm={8} style={{ textAlign: 'right' }}>
                        <Space>
                            <Button
                                icon={<ReloadOutlined />}
                                onClick={fetchBranches}
                                loading={loading}
                            >
                                Aktualisieren
                            </Button>
                            <Button
                                type="primary"
                                icon={<PlusOutlined />}
                                onClick={() => setModalVisible(true)}
                            >
                                Neue Filiale
                            </Button>
                        </Space>
                    </Col>
                </Row>
            </Card>

            {/* Branches Table */}
            <Card>
                <Table
                    columns={columns}
                    dataSource={branches}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        defaultPageSize: 10,
                        showSizeChanger: true,
                        showTotal: (total) => `${total} Filialen`,
                    }}
                    locale={{
                        emptyText: <Empty description="Keine Filialen gefunden" />
                    }}
                />
            </Card>

            {/* Branch Details Drawer */}
            <Drawer
                title="Filialdetails"
                placement="right"
                width={800}
                onClose={() => setDrawerVisible(false)}
                visible={drawerVisible}
            >
                {selectedBranch && (
                    <div>
                        <Tabs activeKey={activeTab} onChange={setActiveTab}>
                            <TabPane tab="Details" key="details">
                                <Descriptions bordered column={1}>
                                    <Descriptions.Item label="Name">
                                        {selectedBranch.name}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Status">
                                        <Badge 
                                            status={selectedBranch.is_active ? 'success' : 'default'} 
                                            text={selectedBranch.is_active ? 'Aktiv' : 'Inaktiv'} 
                                        />
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Adresse">
                                        {selectedBranch.address && (
                                            <div>
                                                {selectedBranch.address}<br />
                                                {selectedBranch.postal_code} {selectedBranch.city}<br />
                                                {selectedBranch.country}
                                            </div>
                                        )}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Telefon">
                                        {selectedBranch.phone_number || 'Nicht angegeben'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="E-Mail">
                                        {selectedBranch.email || 'Nicht angegeben'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Zeitzone">
                                        {selectedBranch.timezone || 'Europe/Berlin'}
                                    </Descriptions.Item>
                                </Descriptions>

                                <div style={{ marginTop: 24 }}>
                                    <Space>
                                        <Button 
                                            icon={<EditOutlined />}
                                            onClick={() => {
                                                editForm.setFieldsValue({
                                                    name: selectedBranch.name,
                                                    address: selectedBranch.address,
                                                    city: selectedBranch.city,
                                                    postal_code: selectedBranch.postal_code,
                                                    country: selectedBranch.country,
                                                    phone_number: selectedBranch.phone_number,
                                                    email: selectedBranch.email,
                                                    timezone: selectedBranch.timezone
                                                });
                                                // Show edit modal
                                            }}
                                        >
                                            Bearbeiten
                                        </Button>
                                        <Button 
                                            icon={<ClockCircleOutlined />}
                                            onClick={() => setWorkingHoursModalVisible(true)}
                                        >
                                            Öffnungszeiten
                                        </Button>
                                    </Space>
                                </div>
                            </TabPane>

                            <TabPane 
                                tab={
                                    <span>
                                        <TeamOutlined />
                                        Mitarbeiter ({branchStaff.length})
                                    </span>
                                } 
                                key="staff"
                            >
                                <Table
                                    dataSource={branchStaff}
                                    rowKey="id"
                                    pagination={false}
                                    columns={[
                                        {
                                            title: 'Name',
                                            dataIndex: 'name',
                                            key: 'name'
                                        },
                                        {
                                            title: 'Position',
                                            dataIndex: 'position',
                                            key: 'position'
                                        },
                                        {
                                            title: 'Email',
                                            dataIndex: 'email',
                                            key: 'email'
                                        },
                                        {
                                            title: 'Status',
                                            dataIndex: 'is_active',
                                            key: 'is_active',
                                            render: (isActive) => (
                                                <Tag color={isActive ? 'green' : 'default'}>
                                                    {isActive ? 'Aktiv' : 'Inaktiv'}
                                                </Tag>
                                            )
                                        }
                                    ]}
                                />
                            </TabPane>

                            <TabPane 
                                tab={
                                    <span>
                                        <AppstoreOutlined />
                                        Services ({branchServices.length})
                                    </span>
                                } 
                                key="services"
                            >
                                <Table
                                    dataSource={branchServices}
                                    rowKey="id"
                                    pagination={false}
                                    columns={[
                                        {
                                            title: 'Service',
                                            dataIndex: 'name',
                                            key: 'name'
                                        },
                                        {
                                            title: 'Dauer',
                                            dataIndex: 'duration',
                                            key: 'duration',
                                            render: (duration) => `${duration} Min.`
                                        },
                                        {
                                            title: 'Preis',
                                            dataIndex: 'price',
                                            key: 'price',
                                            render: (price) => `€${price}`
                                        },
                                        {
                                            title: 'Status',
                                            dataIndex: 'is_active',
                                            key: 'is_active',
                                            render: (isActive) => (
                                                <Tag color={isActive ? 'green' : 'default'}>
                                                    {isActive ? 'Aktiv' : 'Inaktiv'}
                                                </Tag>
                                            )
                                        }
                                    ]}
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
                                <Row gutter={[16, 16]}>
                                    <Col span={12}>
                                        <Statistic 
                                            title="Termine heute" 
                                            value={selectedBranch.appointments_today || 0} 
                                            prefix={<CalendarOutlined />}
                                        />
                                    </Col>
                                    <Col span={12}>
                                        <Statistic 
                                            title="Anrufe heute" 
                                            value={selectedBranch.calls_today || 0} 
                                            prefix={<PhoneOutlined />}
                                        />
                                    </Col>
                                    <Col span={24}>
                                        <Statistic 
                                            title="Umsatz diesen Monat" 
                                            value={selectedBranch.revenue_this_month || 0} 
                                            prefix="€"
                                            precision={2}
                                        />
                                    </Col>
                                </Row>
                            </TabPane>
                        </Tabs>
                    </div>
                )}
            </Drawer>

            {/* Create Branch Modal */}
            <Modal
                title="Neue Filiale erstellen"
                visible={modalVisible}
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
                    onFinish={handleCreateBranch}
                >
                    <Form.Item
                        name="name"
                        label="Filialname"
                        rules={[{ required: true, message: 'Bitte Filialnamen eingeben' }]}
                    >
                        <Input placeholder="Hauptfiliale" />
                    </Form.Item>

                    <Form.Item
                        name="address"
                        label="Straße"
                    >
                        <Input placeholder="Musterstraße 123" />
                    </Form.Item>

                    <Row gutter={16}>
                        <Col span={8}>
                            <Form.Item
                                name="postal_code"
                                label="PLZ"
                            >
                                <Input placeholder="12345" />
                            </Form.Item>
                        </Col>
                        <Col span={16}>
                            <Form.Item
                                name="city"
                                label="Stadt"
                            >
                                <Input placeholder="Berlin" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item
                        name="country"
                        label="Land"
                        initialValue="DE"
                    >
                        <Input placeholder="DE" />
                    </Form.Item>

                    <Form.Item
                        name="phone_number"
                        label="Telefon"
                    >
                        <Input placeholder="+49 123 456789" />
                    </Form.Item>

                    <Form.Item
                        name="email"
                        label="E-Mail"
                    >
                        <Input type="email" placeholder="filiale@example.com" />
                    </Form.Item>

                    <Form.Item
                        name="timezone"
                        label="Zeitzone"
                        initialValue="Europe/Berlin"
                    >
                        <Select>
                            <Option value="Europe/Berlin">Europe/Berlin</Option>
                            <Option value="Europe/Vienna">Europe/Vienna</Option>
                            <Option value="Europe/Zurich">Europe/Zurich</Option>
                        </Select>
                    </Form.Item>

                    <Alert
                        message="Standard-Öffnungszeiten werden automatisch erstellt"
                        description="Mo-Fr: 09:00 - 18:00, Sa: 09:00 - 14:00, So: Geschlossen"
                        type="info"
                        showIcon
                        style={{ marginBottom: 16 }}
                    />

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                Filiale erstellen
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
                onOk={handleUpdateWorkingHours}
                onCancel={() => setWorkingHoursModalVisible(false)}
                width={600}
            >
                {renderWorkingHoursForm()}
            </Modal>
        </div>
    );
};

export default BranchesIndex;