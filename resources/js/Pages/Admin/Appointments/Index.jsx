import React, { useState, useEffect, useCallback } from 'react';
import { 
    Table, Card, Input, Button, Space, Tag, Tooltip, Modal, Badge, 
    Typography, Row, Col, Statistic, message, DatePicker, Select, 
    Form, Dropdown, Menu, Divider, Alert, TimePicker, InputNumber
} from 'antd';
import { 
    SearchOutlined, 
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    CalendarOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    ExclamationCircleOutlined,
    UserOutlined,
    TeamOutlined,
    ShopOutlined,
    PhoneOutlined,
    MailOutlined,
    DollarOutlined,
    FileTextOutlined,
    SyncOutlined,
    DownloadOutlined,
    CheckOutlined,
    CloseOutlined,
    WarningOutlined,
    SendOutlined,
    ReloadOutlined
} from '@ant-design/icons';
import adminAxios from '../../../services/adminAxios';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

const { RangePicker } = DatePicker;
const { Search } = Input;
const { Title, Text } = Typography;
const { Option } = Select;
const { TextArea } = Input;

const AppointmentsIndex = () => {
    const [appointments, setAppointments] = useState([]);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedAppointment, setSelectedAppointment] = useState(null);
    const [modalVisible, setModalVisible] = useState(false);
    const [selectedRowKeys, setSelectedRowKeys] = useState([]);
    const [filters, setFilters] = useState({
        status: null,
        date_from: null,
        date_to: null,
        company_id: null
    });
    const [quickFilters, setQuickFilters] = useState({
        today: 0,
        tomorrow: 0,
        this_week: 0,
        past_due: 0,
        created_today: 0
    });
    const [companies, setCompanies] = useState([]);
    const [branches, setBranches] = useState([]);
    const [customers, setCustomers] = useState([]);
    const [staff, setStaff] = useState([]);
    const [services, setServices] = useState([]);
    const [form] = Form.useForm();
    const [stats, setStats] = useState({
        total: 0,
        scheduled: 0,
        confirmed: 0,
        completed: 0,
        cancelled: 0,
        no_show: 0,
        revenue: 0
    });
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 20,
        total: 0
    });

    // Use centralized admin axios instance
    const api = adminAxios;

    // Fetch appointments
    const fetchAppointments = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = {
                page,
                per_page: pagination.pageSize,
                search: searchTerm,
                ...filters
            };
            
            // Remove null values
            Object.keys(params).forEach(key => {
                if (params[key] === null || params[key] === '') {
                    delete params[key];
                }
            });
            
            const response = await api.get('/appointments', { params });
            
            const data = response.data;
            setAppointments(data.data || []);
            setPagination(prev => ({
                ...prev,
                current: data.current_page || 1,
                total: data.total || 0
            }));
            
            // Calculate stats from current page
            calculateStats(data.data || []);
        } catch (error) {
            console.error('Error fetching appointments:', error);
            message.error('Fehler beim Laden der Termine');
        } finally {
            setLoading(false);
        }
    }, [api, pagination.pageSize, searchTerm, filters]);

    // Fetch quick filters
    const fetchQuickFilters = async () => {
        try {
            const response = await api.get('/appointments/quick-filters', {
                params: { company_id: filters.company_id }
            });
            setQuickFilters(response.data);
        } catch (error) {
            console.error('Error fetching quick filters:', error);
        }
    };

    // Fetch companies
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

    // Fetch branches for selected company
    const fetchBranches = async (companyId) => {
        try {
            const response = await api.get('/branches', {
                params: { company_id: companyId, per_page: 100 }
            });
            setBranches(response.data.data || []);
        } catch (error) {
            console.error('Error fetching branches:', error);
        }
    };

    // Fetch customers for selected company
    const fetchCustomers = async (companyId) => {
        try {
            const response = await api.get('/customers', {
                params: { company_id: companyId, per_page: 100 }
            });
            setCustomers(response.data.data || []);
        } catch (error) {
            console.error('Error fetching customers:', error);
        }
    };

    // Fetch staff for selected branch
    const fetchStaff = async (branchId) => {
        try {
            const response = await api.get('/staff', {
                params: { branch_id: branchId, per_page: 100 }
            });
            setStaff(response.data.data || []);
        } catch (error) {
            console.error('Error fetching staff:', error);
        }
    };

    // Fetch services for selected company
    const fetchServices = async (companyId) => {
        try {
            const response = await api.get('/services', {
                params: { company_id: companyId, per_page: 100 }
            });
            setServices(response.data.data || []);
        } catch (error) {
            console.error('Error fetching services:', error);
        }
    };

    // Calculate stats
    const calculateStats = (appointmentData) => {
        const statusCounts = {
            total: appointmentData.length,
            scheduled: 0,
            confirmed: 0,
            completed: 0,
            cancelled: 0,
            no_show: 0,
            revenue: 0
        };

        appointmentData.forEach(appointment => {
            if (appointment.status) {
                statusCounts[appointment.status] = (statusCounts[appointment.status] || 0) + 1;
            }
            if (['completed', 'confirmed'].includes(appointment.status) && appointment.price) {
                statusCounts.revenue += parseFloat(appointment.price);
            }
        });

        setStats(statusCounts);
    };

    // Initial load
    useEffect(() => {
        fetchAppointments();
        fetchCompanies();
        fetchQuickFilters();
    }, []);

    // Refetch when filters change
    useEffect(() => {
        fetchAppointments(1);
        fetchQuickFilters();
    }, [filters]);

    // Handle search
    const handleSearch = (value) => {
        setSearchTerm(value);
        fetchAppointments(1);
    };

    // Handle table change
    const handleTableChange = (newPagination) => {
        fetchAppointments(newPagination.current);
    };

    // Handle filter change
    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
    };

    // Handle date range change
    const handleDateRangeChange = (dates) => {
        if (dates && dates.length === 2) {
            setFilters(prev => ({
                ...prev,
                date_from: dates[0].format('YYYY-MM-DD'),
                date_to: dates[1].format('YYYY-MM-DD')
            }));
        } else {
            setFilters(prev => ({
                ...prev,
                date_from: null,
                date_to: null
            }));
        }
    };

    // Create or update appointment
    const handleSubmit = async (values) => {
        try {
            // Calculate end time based on service duration
            const service = services.find(s => s.id === values.service_id);
            const startsAt = dayjs(`${values.date} ${values.time}`);
            const endsAt = startsAt.add(service?.duration || 60, 'minute');

            const appointmentData = {
                company_id: values.company_id,
                branch_id: values.branch_id,
                customer_id: values.customer_id,
                staff_id: values.staff_id,
                service_id: values.service_id,
                starts_at: startsAt.format('YYYY-MM-DD HH:mm:ss'),
                ends_at: endsAt.format('YYYY-MM-DD HH:mm:ss'),
                status: values.status || 'scheduled',
                price: values.price || service?.price,
                notes: values.notes
            };

            if (selectedAppointment && selectedAppointment.id) {
                await api.put(`/appointments/${selectedAppointment.id}`, appointmentData);
                message.success('Termin erfolgreich aktualisiert');
            } else {
                await api.post('/appointments', appointmentData);
                message.success('Termin erfolgreich erstellt');
            }
            setModalVisible(false);
            form.resetFields();
            fetchAppointments(pagination.current);
        } catch (error) {
            console.error('Error saving appointment:', error);
            message.error('Fehler beim Speichern des Termins');
        }
    };

    // Delete appointment
    const deleteAppointment = async (appointmentId) => {
        Modal.confirm({
            title: 'Termin löschen?',
            content: 'Sind Sie sicher, dass Sie diesen Termin löschen möchten?',
            okText: 'Löschen',
            okType: 'danger',
            cancelText: 'Abbrechen',
            onOk: async () => {
                try {
                    await api.delete(`/appointments/${appointmentId}`);
                    message.success('Termin erfolgreich gelöscht');
                    fetchAppointments(pagination.current);
                } catch (error) {
                    console.error('Error deleting appointment:', error);
                    message.error('Fehler beim Löschen des Termins');
                }
            }
        });
    };

    // Update appointment status
    const updateStatus = async (appointmentId, status) => {
        try {
            const endpoint = `/appointments/${appointmentId}/${status}`;
            await api.post(endpoint);
            message.success(`Termin als ${getStatusLabel(status)} markiert`);
            fetchAppointments(pagination.current);
        } catch (error) {
            console.error('Error updating status:', error);
            message.error('Fehler beim Aktualisieren des Status');
        }
    };

    // Send reminder
    const sendReminder = async (appointmentId) => {
        try {
            await api.post(`/appointments/${appointmentId}/send-reminder`);
            message.success('Erinnerung erfolgreich gesendet');
            fetchAppointments(pagination.current);
        } catch (error) {
            console.error('Error sending reminder:', error);
            message.error('Fehler beim Senden der Erinnerung');
        }
    };

    // Bulk actions
    const handleBulkAction = async (action) => {
        if (selectedRowKeys.length === 0) {
            message.warning('Bitte wählen Sie mindestens einen Termin aus');
            return;
        }

        try {
            const response = await api.post('/appointments/bulk-action', {
                appointment_ids: selectedRowKeys,
                action,
                status: action === 'update_status' ? 'confirmed' : undefined
            });

            message.success(response.data.message);
            setSelectedRowKeys([]);
            fetchAppointments(pagination.current);
        } catch (error) {
            console.error('Error performing bulk action:', error);
            message.error('Fehler bei der Massenaktion');
        }
    };

    // Get status color
    const getStatusColor = (status) => {
        const colors = {
            scheduled: 'blue',
            confirmed: 'green',
            completed: 'default',
            cancelled: 'red',
            no_show: 'orange'
        };
        return colors[status] || 'default';
    };

    // Get status label
    const getStatusLabel = (status) => {
        const labels = {
            scheduled: 'Geplant',
            confirmed: 'Bestätigt',
            completed: 'Abgeschlossen',
            cancelled: 'Abgesagt',
            no_show: 'Nicht erschienen'
        };
        return labels[status] || status;
    };

    // Table columns
    const columns = [
        {
            title: 'Termin',
            key: 'appointment',
            render: (_, record) => (
                <Space direction="vertical" size="small">
                    <Space>
                        <CalendarOutlined />
                        <Text strong>{record.starts_at}</Text>
                        <Text type="secondary">- {record.ends_at}</Text>
                    </Space>
                    {record.service && (
                        <Text type="secondary">{record.service.name}</Text>
                    )}
                </Space>
            )
        },
        {
            title: 'Kunde',
            key: 'customer',
            render: (_, record) => record.customer ? (
                <Space direction="vertical" size="small">
                    <Space>
                        <UserOutlined />
                        <Text>{record.customer.name}</Text>
                    </Space>
                    {record.customer.phone && (
                        <Space>
                            <PhoneOutlined className="text-gray-400" />
                            <Text copyable className="text-xs">{record.customer.phone}</Text>
                        </Space>
                    )}
                    {record.no_show_count > 0 && (
                        <Tooltip title={`${record.no_show_count} mal nicht erschienen`}>
                            <Tag color="orange" icon={<WarningOutlined />}>
                                No-Show: {record.no_show_count}
                            </Tag>
                        </Tooltip>
                    )}
                </Space>
            ) : '-'
        },
        {
            title: 'Mitarbeiter',
            dataIndex: ['staff', 'name'],
            key: 'staff',
            render: (name) => name ? (
                <Space>
                    <TeamOutlined />
                    {name}
                </Space>
            ) : '-'
        },
        {
            title: 'Filiale',
            dataIndex: ['branch', 'name'],
            key: 'branch',
            render: (name) => name ? (
                <Space>
                    <ShopOutlined />
                    {name}
                </Space>
            ) : '-'
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (status) => (
                <Tag color={getStatusColor(status)}>
                    {getStatusLabel(status)}
                </Tag>
            )
        },
        {
            title: 'Preis',
            dataIndex: 'price',
            key: 'price',
            render: (price) => price ? (
                <Space>
                    <DollarOutlined />
                    <Text>{price.toFixed(2)} €</Text>
                </Space>
            ) : '-'
        },
        {
            title: 'Extras',
            key: 'extras',
            render: (_, record) => (
                <Space>
                    {record.reminder_sent && (
                        <Tooltip title="Erinnerung gesendet">
                            <Badge status="success" />
                        </Tooltip>
                    )}
                    {record.cal_event_id && (
                        <Tooltip title="Mit Cal.com synchronisiert">
                            <SyncOutlined className="text-green-500" />
                        </Tooltip>
                    )}
                    {record.notes && (
                        <Tooltip title="Notizen vorhanden">
                            <FileTextOutlined />
                        </Tooltip>
                    )}
                </Space>
            )
        },
        {
            title: 'Aktionen',
            key: 'actions',
            fixed: 'right',
            width: 200,
            render: (_, record) => {
                const menu = (
                    <Menu>
                        <Menu.Item 
                            key="confirm"
                            icon={<CheckCircleOutlined />}
                            onClick={() => updateStatus(record.id, 'confirm')}
                            disabled={record.status === 'confirmed'}
                        >
                            Bestätigen
                        </Menu.Item>
                        <Menu.Item 
                            key="complete"
                            icon={<CheckOutlined />}
                            onClick={() => updateStatus(record.id, 'complete')}
                            disabled={record.status === 'completed'}
                        >
                            Abschließen
                        </Menu.Item>
                        <Menu.Item 
                            key="cancel"
                            icon={<CloseCircleOutlined />}
                            onClick={() => updateStatus(record.id, 'cancel')}
                            disabled={record.status === 'cancelled'}
                        >
                            Absagen
                        </Menu.Item>
                        <Menu.Item 
                            key="no_show"
                            icon={<ExclamationCircleOutlined />}
                            onClick={() => updateStatus(record.id, 'no-show')}
                            disabled={record.status === 'no_show'}
                        >
                            Nicht erschienen
                        </Menu.Item>
                        <Menu.Divider />
                        <Menu.Item 
                            key="reminder"
                            icon={<SendOutlined />}
                            onClick={() => sendReminder(record.id)}
                            disabled={record.reminder_sent}
                        >
                            Erinnerung senden
                        </Menu.Item>
                        <Menu.Item 
                            key="check_in"
                            icon={<CheckCircleOutlined />}
                            onClick={() => api.post(`/appointments/${record.id}/check-in`).then(() => {
                                message.success('Check-in erfolgreich');
                                fetchAppointments(pagination.current);
                            })}
                        >
                            Check-in
                        </Menu.Item>
                        <Menu.Divider />
                        <Menu.Item 
                            key="edit"
                            icon={<EditOutlined />}
                            onClick={() => {
                                setSelectedAppointment(record);
                                form.setFieldsValue({
                                    company_id: record.company?.id,
                                    branch_id: record.branch?.id,
                                    customer_id: record.customer?.id,
                                    staff_id: record.staff?.id,
                                    service_id: record.service?.id,
                                    date: record.starts_at ? dayjs(record.starts_at, 'DD.MM.YYYY HH:mm') : null,
                                    time: record.starts_at ? dayjs(record.starts_at, 'DD.MM.YYYY HH:mm').format('HH:mm') : null,
                                    status: record.status,
                                    price: record.price,
                                    notes: record.notes
                                });
                                setModalVisible(true);
                            }}
                        >
                            Bearbeiten
                        </Menu.Item>
                        <Menu.Item 
                            key="delete"
                            icon={<DeleteOutlined />}
                            danger
                            onClick={() => deleteAppointment(record.id)}
                        >
                            Löschen
                        </Menu.Item>
                    </Menu>
                );

                return (
                    <Dropdown overlay={menu} trigger={['click']}>
                        <Button type="text" icon={<EditOutlined />} />
                    </Dropdown>
                );
            }
        }
    ];

    const rowSelection = {
        selectedRowKeys,
        onChange: setSelectedRowKeys,
    };

    return (
        <div>
            <div className="mb-6">
                <Title level={2}>Termine</Title>
            </div>

            {/* Statistics */}
            <Row gutter={16} className="mb-6">
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Gesamt"
                            value={stats.total}
                            prefix={<CalendarOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Geplant"
                            value={stats.scheduled}
                            valueStyle={{ color: '#1890ff' }}
                            prefix={<ClockCircleOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Bestätigt"
                            value={stats.confirmed}
                            valueStyle={{ color: '#52c41a' }}
                            prefix={<CheckCircleOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Umsatz"
                            value={stats.revenue}
                            precision={2}
                            valueStyle={{ color: '#faad14' }}
                            prefix="€"
                        />
                    </Card>
                </Col>
            </Row>

            {/* Quick Filters */}
            <Card className="mb-4">
                <Space size="large" wrap>
                    <Button 
                        type={filters.date_from === dayjs().format('YYYY-MM-DD') ? 'primary' : 'default'}
                        onClick={() => setFilters(prev => ({
                            ...prev,
                            date_from: dayjs().format('YYYY-MM-DD'),
                            date_to: dayjs().format('YYYY-MM-DD')
                        }))}
                    >
                        Heute ({quickFilters.today})
                    </Button>
                    <Button 
                        onClick={() => setFilters(prev => ({
                            ...prev,
                            date_from: dayjs().add(1, 'day').format('YYYY-MM-DD'),
                            date_to: dayjs().add(1, 'day').format('YYYY-MM-DD')
                        }))}
                    >
                        Morgen ({quickFilters.tomorrow})
                    </Button>
                    <Button 
                        onClick={() => setFilters(prev => ({
                            ...prev,
                            date_from: dayjs().startOf('week').format('YYYY-MM-DD'),
                            date_to: dayjs().endOf('week').format('YYYY-MM-DD')
                        }))}
                    >
                        Diese Woche ({quickFilters.this_week})
                    </Button>
                    <Badge count={quickFilters.past_due} offset={[-5, 5]}>
                        <Button 
                            danger
                            onClick={() => {
                                // Filter for past due appointments
                                message.info('Überfällige Termine werden angezeigt');
                            }}
                        >
                            Überfällig
                        </Button>
                    </Badge>
                </Space>
            </Card>

            {/* Toolbar */}
            <Card className="mb-4">
                <Row gutter={16} align="middle">
                    <Col xs={24} md={6}>
                        <Search
                            placeholder="Suche nach Kunde oder Telefon..."
                            allowClear
                            enterButton={<SearchOutlined />}
                            onSearch={handleSearch}
                        />
                    </Col>
                    <Col xs={24} md={4}>
                        <Select
                            placeholder="Status"
                            allowClear
                            style={{ width: '100%' }}
                            onChange={(value) => handleFilterChange('status', value)}
                            value={filters.status}
                        >
                            <Option value="scheduled">Geplant</Option>
                            <Option value="confirmed">Bestätigt</Option>
                            <Option value="completed">Abgeschlossen</Option>
                            <Option value="cancelled">Abgesagt</Option>
                            <Option value="no_show">Nicht erschienen</Option>
                        </Select>
                    </Col>
                    <Col xs={24} md={6}>
                        <RangePicker 
                            style={{ width: '100%' }}
                            onChange={handleDateRangeChange}
                            format="DD.MM.YYYY"
                        />
                    </Col>
                    <Col xs={24} md={4}>
                        <Select
                            placeholder="Mandant filtern"
                            allowClear
                            style={{ width: '100%' }}
                            onChange={(value) => handleFilterChange('company_id', value)}
                            value={filters.company_id}
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
                    <Col xs={24} md={4} style={{ textAlign: 'right' }}>
                        <Space>
                            {selectedRowKeys.length > 0 && (
                                <Dropdown overlay={
                                    <Menu>
                                        <Menu.Item onClick={() => handleBulkAction('update_status')}>
                                            Status aktualisieren
                                        </Menu.Item>
                                        <Menu.Item onClick={() => handleBulkAction('send_reminders')}>
                                            Erinnerungen senden
                                        </Menu.Item>
                                        <Menu.Item onClick={() => handleBulkAction('export')}>
                                            Exportieren
                                        </Menu.Item>
                                    </Menu>
                                }>
                                    <Button>
                                        Massenaktionen <DownloadOutlined />
                                    </Button>
                                </Dropdown>
                            )}
                            <Button 
                                type="primary" 
                                icon={<PlusOutlined />}
                                onClick={() => {
                                    setSelectedAppointment(null);
                                    form.resetFields();
                                    setModalVisible(true);
                                }}
                            >
                                Neuer Termin
                            </Button>
                        </Space>
                    </Col>
                </Row>
            </Card>

            {/* Table */}
            <Card>
                <Table
                    rowSelection={rowSelection}
                    columns={columns}
                    dataSource={appointments}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        ...pagination,
                        showSizeChanger: true,
                        showTotal: (total, range) => `${range[0]}-${range[1]} von ${total} Terminen`,
                        pageSizeOptions: ['10', '20', '50', '100']
                    }}
                    onChange={handleTableChange}
                    scroll={{ x: 1200 }}
                />
            </Card>

            {/* Create/Edit Modal */}
            <Modal
                title={selectedAppointment ? 'Termin bearbeiten' : 'Neuer Termin'}
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
                    onValuesChange={(changedValues) => {
                        // Handle company change
                        if (changedValues.company_id) {
                            fetchBranches(changedValues.company_id);
                            fetchCustomers(changedValues.company_id);
                            fetchServices(changedValues.company_id);
                            form.setFieldsValue({ branch_id: null, customer_id: null, staff_id: null, service_id: null });
                        }
                        // Handle branch change
                        if (changedValues.branch_id) {
                            fetchStaff(changedValues.branch_id);
                            form.setFieldsValue({ staff_id: null });
                        }
                    }}
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
                                name="branch_id"
                                label="Filiale"
                                rules={[{ required: true, message: 'Bitte Filiale wählen' }]}
                            >
                                <Select
                                    placeholder="Filiale wählen"
                                    disabled={!form.getFieldValue('company_id')}
                                >
                                    {branches.map(branch => (
                                        <Option key={branch.id} value={branch.id}>
                                            {branch.name}
                                        </Option>
                                    ))}
                                </Select>
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="customer_id"
                                label="Kunde"
                                rules={[{ required: true, message: 'Bitte Kunde wählen' }]}
                            >
                                <Select
                                    placeholder="Kunde wählen"
                                    disabled={!form.getFieldValue('company_id')}
                                    showSearch
                                    optionFilterProp="children"
                                >
                                    {customers.map(customer => (
                                        <Option key={customer.id} value={customer.id}>
                                            {customer.name} ({customer.phone})
                                        </Option>
                                    ))}
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="staff_id"
                                label="Mitarbeiter"
                                rules={[{ required: true, message: 'Bitte Mitarbeiter wählen' }]}
                            >
                                <Select
                                    placeholder="Mitarbeiter wählen"
                                    disabled={!form.getFieldValue('branch_id')}
                                >
                                    {staff.map(member => (
                                        <Option key={member.id} value={member.id}>
                                            {member.name}
                                        </Option>
                                    ))}
                                </Select>
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={24}>
                            <Form.Item
                                name="service_id"
                                label="Service"
                                rules={[{ required: true, message: 'Bitte Service wählen' }]}
                            >
                                <Select
                                    placeholder="Service wählen"
                                    disabled={!form.getFieldValue('company_id')}
                                    onChange={(serviceId) => {
                                        const service = services.find(s => s.id === serviceId);
                                        if (service && service.price) {
                                            form.setFieldsValue({ price: service.price });
                                        }
                                    }}
                                >
                                    {services.map(service => (
                                        <Option key={service.id} value={service.id}>
                                            {service.name} ({service.duration} Min.) - {service.price ? `${service.price.toFixed(2)} €` : 'Preis auf Anfrage'}
                                        </Option>
                                    ))}
                                </Select>
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="date"
                                label="Datum"
                                rules={[{ required: true, message: 'Bitte Datum wählen' }]}
                            >
                                <DatePicker 
                                    style={{ width: '100%' }}
                                    format="DD.MM.YYYY"
                                    disabledDate={(current) => current && current < dayjs().startOf('day')}
                                />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="time"
                                label="Uhrzeit"
                                rules={[{ required: true, message: 'Bitte Uhrzeit wählen' }]}
                            >
                                <TimePicker 
                                    style={{ width: '100%' }}
                                    format="HH:mm"
                                    minuteStep={15}
                                />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="status"
                                label="Status"
                                initialValue="scheduled"
                            >
                                <Select>
                                    <Option value="scheduled">Geplant</Option>
                                    <Option value="confirmed">Bestätigt</Option>
                                    <Option value="completed">Abgeschlossen</Option>
                                    <Option value="cancelled">Abgesagt</Option>
                                    <Option value="no_show">Nicht erschienen</Option>
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="price"
                                label="Preis (€)"
                            >
                                <InputNumber 
                                    style={{ width: '100%' }}
                                    min={0}
                                    precision={2}
                                    placeholder="0.00"
                                />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row>
                        <Col span={24}>
                            <Form.Item
                                name="notes"
                                label="Notizen"
                            >
                                <TextArea 
                                    rows={4} 
                                    placeholder="Zusätzliche Informationen zum Termin..."
                                />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                {selectedAppointment ? 'Aktualisieren' : 'Erstellen'}
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
        </div>
    );
};

export default AppointmentsIndex;
