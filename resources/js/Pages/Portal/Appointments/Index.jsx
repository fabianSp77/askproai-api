import React, { useState, useEffect } from 'react';
import { 
    Card, 
    Table, 
    Button, 
    Space, 
    Tag, 
    DatePicker, 
    Select, 
    Input, 
    Row, 
    Col, 
    Statistic, 
    Typography, 
    Modal, 
    Form, 
    TimePicker,
    message,
    Tooltip,
    Badge,
    Empty,
    Spin,
    Drawer,
    Descriptions,
    Timeline,
    Alert,
    Popconfirm,
    Grid
} from 'antd';
import { 
    CalendarOutlined, 
    UserOutlined, 
    ClockCircleOutlined, 
    PhoneOutlined,
    EditOutlined,
    DeleteOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    ExclamationCircleOutlined,
    PlusOutlined,
    ReloadOutlined,
    EnvironmentOutlined,
    TeamOutlined,
    DollarOutlined,
    FileTextOutlined,
    MailOutlined,
    WhatsAppOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import axiosInstance from '../../../services/axiosInstance';
import MobileAppointmentList from '../../../components/Portal/Mobile/MobileAppointmentList';
import useResponsive from '../../../hooks/useResponsive';
import { useAppointmentUpdates } from '../../../hooks/useEcho';

dayjs.locale('de');

const { Title, Text } = Typography;
const { RangePicker } = DatePicker;
const { Option } = Select;
const { useBreakpoint } = Grid;

const AppointmentsIndex = () => {
    const [appointments, setAppointments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        status: 'all',
        branch_id: null,
        staff_id: null,
        service_id: null,
        date_range: null,
        search: ''
    });
    const screens = useBreakpoint();
    const { isMobile } = useResponsive();
    const isMobileView = !screens.md || isMobile;
    const [stats, setStats] = useState({
        total: 0,
        today: 0,
        this_week: 0,
        confirmed: 0,
        pending: 0,
        cancelled: 0
    });
    const [branches, setBranches] = useState([]);
    const [staff, setStaff] = useState([]);
    const [services, setServices] = useState([]);
    const [selectedAppointment, setSelectedAppointment] = useState(null);
    const [drawerVisible, setDrawerVisible] = useState(false);
    const [modalVisible, setModalVisible] = useState(false);
    const [form] = Form.useForm();
    const [userPermissions, setUserPermissions] = useState({
        is_admin: false,
        can_delete_business_data: false
    });
    
    // Handle real-time appointment updates
    useAppointmentUpdates((update) => {
        console.log('Appointment update received:', update);
        
        if (update.event === 'created') {
            // New appointment created
            message.success(`Neuer Termin erstellt für ${update.appointment?.customer?.name || 'Kunde'}`);
            // Refresh the appointments list
            fetchAppointments();
        } else if (update.event === 'updated' || !update.event) {
            // Existing appointment updated
            const updatedAppointment = update.appointment;
            if (updatedAppointment) {
                // Update the appointment in the list
                setAppointments(prevAppointments => {
                    const index = prevAppointments.findIndex(a => a.id === updatedAppointment.id);
                    if (index !== -1) {
                        const newAppointments = [...prevAppointments];
                        newAppointments[index] = { ...newAppointments[index], ...updatedAppointment };
                        return newAppointments;
                    } else {
                        // Appointment not in current list, might be new or filtered
                        return prevAppointments;
                    }
                });
                
                // Show notification for status changes
                if (updatedAppointment.status) {
                    message.info(`Termin-Status aktualisiert: ${getStatusText(updatedAppointment.status)}`);
                }
            }
        }
    });

    useEffect(() => {
        fetchUserPermissions();
        fetchAppointments();
        fetchFilterOptions();
    }, [filters]);

    const fetchUserPermissions = async () => {
        try {
            const response = await axiosInstance.get('/user/permissions');
            setUserPermissions({
                is_admin: response.data.user?.is_admin || false,
                can_delete_business_data: response.data.user?.can_delete_business_data || false
            });
        } catch (error) {
            // Silently handle permission errors - user may not have permission
        }
    };

    const fetchAppointments = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            
            if (filters.status && filters.status !== 'all') {
                params.append('status', filters.status);
            }
            if (filters.branch_id) {
                params.append('branch_id', filters.branch_id);
            }
            if (filters.staff_id) {
                params.append('staff_id', filters.staff_id);
            }
            if (filters.service_id) {
                params.append('service_id', filters.service_id);
            }
            if (filters.date_range) {
                params.append('start_date', filters.date_range[0].format('YYYY-MM-DD'));
                params.append('end_date', filters.date_range[1].format('YYYY-MM-DD'));
            }
            if (filters.search) {
                params.append('search', filters.search);
            }

            const response = await axiosInstance.get(`/appointments?${params}`);
            setAppointments(response.data.appointments.data || []);
            setStats(response.data.stats || {});
        } catch (error) {
            message.error('Fehler beim Laden der Termine');
        } finally {
            setLoading(false);
        }
    };

    const fetchFilterOptions = async () => {
        try {
            const response = await axiosInstance.get('/appointments/filters');
            setBranches(response.data.branches || []);
            setStaff(response.data.staff || []);
            setServices(response.data.services || []);
        } catch (error) {
            // Silently handle filter errors - not critical for functionality
        }
    };

    const handleStatusChange = async (appointmentId, newStatus) => {
        try {
            await axiosInstance.post(`/appointments/${appointmentId}/status`, { status: newStatus });
            message.success('Status erfolgreich aktualisiert');
            fetchAppointments();
        } catch (error) {
            message.error('Fehler beim Aktualisieren des Status');
        }
    };

    const handleCreateAppointment = async (values) => {
        try {
            const appointmentData = {
                ...values,
                starts_at: values.datetime[0].format('YYYY-MM-DD HH:mm:ss'),
                ends_at: values.datetime[1].format('YYYY-MM-DD HH:mm:ss'),
            };
            delete appointmentData.datetime;

            await axiosInstance.post('/appointments', appointmentData);
            message.success('Termin erfolgreich erstellt');
            setModalVisible(false);
            form.resetFields();
            fetchAppointments();
        } catch (error) {
            message.error('Fehler beim Erstellen des Termins');
        }
    };

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

    const columns = [
        {
            title: 'Datum & Zeit',
            dataIndex: 'starts_at',
            key: 'starts_at',
            render: (date, record) => (
                <Space direction="vertical" size={0}>
                    <Text strong>{dayjs(date).format('DD.MM.YYYY')}</Text>
                    <Text type="secondary">
                        {dayjs(date).format('HH:mm')} - {dayjs(record.ends_at).format('HH:mm')}
                    </Text>
                </Space>
            ),
            sorter: (a, b) => dayjs(a.starts_at).unix() - dayjs(b.starts_at).unix()
        },
        {
            title: 'Kunde',
            dataIndex: 'customer',
            key: 'customer',
            render: (customer) => (
                <Space direction="vertical" size={0}>
                    <Text strong>{customer?.name || 'N/A'}</Text>
                    <Text type="secondary" copyable>{customer?.phone || 'N/A'}</Text>
                </Space>
            )
        },
        {
            title: 'Service',
            dataIndex: 'service',
            key: 'service',
            render: (service) => (
                <Space direction="vertical" size={0}>
                    <Text>{service?.name || 'N/A'}</Text>
                    <Text type="secondary">{service?.duration || 0} Min.</Text>
                </Space>
            )
        },
        {
            title: 'Mitarbeiter',
            dataIndex: 'staff',
            key: 'staff',
            render: (staff) => (
                <Tag icon={<UserOutlined />}>
                    {staff?.name || 'N/A'}
                </Tag>
            )
        },
        {
            title: 'Filiale',
            dataIndex: 'branch',
            key: 'branch',
            render: (branch) => (
                <Tag icon={<EnvironmentOutlined />}>
                    {branch?.name || 'N/A'}
                </Tag>
            )
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (status) => (
                <Tag color={getStatusColor(status)}>
                    {getStatusText(status)}
                </Tag>
            )
        },
        {
            title: 'Aktionen',
            key: 'actions',
            fixed: 'right',
            render: (_, record) => (
                <Space>
                    <Tooltip title="Details anzeigen">
                        <Button
                            type="text"
                            icon={<FileTextOutlined />}
                            onClick={() => {
                                setSelectedAppointment(record);
                                setDrawerVisible(true);
                            }}
                        />
                    </Tooltip>
                    <Tooltip title="Bearbeiten">
                        <Button
                            type="text"
                            icon={<EditOutlined />}
                            onClick={() => handleEdit(record)}
                        />
                    </Tooltip>
                    <Popconfirm
                        title="Termin stornieren?"
                        onConfirm={() => handleStatusChange(record.id, 'cancelled')}
                        disabled={record.status === 'cancelled' || record.status === 'completed'}
                    >
                        <Tooltip title="Stornieren">
                            <Button
                                type="text"
                                danger
                                icon={<CloseCircleOutlined />}
                                disabled={record.status === 'cancelled' || record.status === 'completed'}
                            />
                        </Tooltip>
                    </Popconfirm>
                    {userPermissions.can_delete_business_data && (
                        <Popconfirm
                            title="Termin wirklich löschen?"
                            description="Diese Aktion kann nicht rückgängig gemacht werden."
                            onConfirm={() => handleDelete(record.id)}
                            okText="Ja, löschen"
                            cancelText="Abbrechen"
                            okButtonProps={{ danger: true }}
                        >
                            <Tooltip title="Löschen (nur Administratoren)">
                                <Button
                                    type="text"
                                    danger
                                    icon={<DeleteOutlined />}
                                />
                            </Tooltip>
                        </Popconfirm>
                    )}
                </Space>
            )
        }
    ];

    const handleEdit = (appointment) => {
        // TODO: Implement edit functionality
        message.info('Bearbeiten-Funktion wird noch implementiert');
    };

    const handleDelete = async (appointmentId) => {
        try {
            await axiosInstance.delete(`/appointments/${appointmentId}`);
            message.success('Termin erfolgreich gelöscht');
            fetchAppointments();
        } catch (error) {
            message.error(error.response?.data?.message || 'Fehler beim Löschen des Termins');
        }
    };

    // Mobile handlers
    const handleCall = (phone) => {
        if (phone) {
            window.location.href = `tel:${phone}`;
        }
    };

    const handleViewDetails = (appointment) => {
        setSelectedAppointment(appointment);
        setDrawerVisible(true);
    };

    // Render mobile view
    if (isMobileView) {
        return (
            <div style={{ padding: isMobile ? 8 : 16 }}>
                {/* Mobile Header */}
                <div style={{ marginBottom: 16 }}>
                    <Title level={4} style={{ margin: 0 }}>
                        <CalendarOutlined /> Termine
                    </Title>
                </div>

                {/* Mobile Stats - Horizontal scrollable */}
                <div style={{ 
                    display: 'flex', 
                    gap: 12, 
                    overflowX: 'auto', 
                    paddingBottom: 16,
                    WebkitOverflowScrolling: 'touch'
                }}>
                    <Card size="small" style={{ minWidth: 120 }}>
                        <Statistic
                            title="Heute"
                            value={stats.today}
                            valueStyle={{ fontSize: 20 }}
                        />
                    </Card>
                    <Card size="small" style={{ minWidth: 120 }}>
                        <Statistic
                            title="Diese Woche"
                            value={stats.this_week}
                            valueStyle={{ fontSize: 20 }}
                        />
                    </Card>
                    <Card size="small" style={{ minWidth: 120 }}>
                        <Statistic
                            title="Bestätigt"
                            value={stats.confirmed}
                            valueStyle={{ fontSize: 20, color: '#52c41a' }}
                        />
                    </Card>
                    <Card size="small" style={{ minWidth: 120 }}>
                        <Statistic
                            title="Ausstehend"
                            value={stats.pending}
                            valueStyle={{ fontSize: 20, color: '#faad14' }}
                        />
                    </Card>
                </div>

                {/* Mobile Filters */}
                <Card size="small" style={{ marginBottom: 16 }}>
                    <Space direction="vertical" style={{ width: '100%' }} size="small">
                        <Input.Search
                            placeholder="Suche..."
                            value={filters.search}
                            onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                            onSearch={fetchAppointments}
                            allowClear
                        />
                        <Select
                            style={{ width: '100%' }}
                            placeholder="Status"
                            value={filters.status}
                            onChange={(value) => setFilters({ ...filters, status: value })}
                        >
                            <Option value="all">Alle Status</Option>
                            <Option value="scheduled">Geplant</Option>
                            <Option value="confirmed">Bestätigt</Option>
                            <Option value="completed">Abgeschlossen</Option>
                            <Option value="cancelled">Storniert</Option>
                        </Select>
                        <DatePicker
                            style={{ width: '100%' }}
                            placeholder="Datum wählen"
                            onChange={(date) => setFilters({ 
                                ...filters, 
                                date_range: date ? [date.startOf('day'), date.endOf('day')] : null 
                            })}
                        />
                    </Space>
                </Card>

                {/* Mobile Appointment List */}
                <MobileAppointmentList
                    appointments={appointments}
                    loading={loading}
                    onRefresh={fetchAppointments}
                    onStatusChange={handleStatusChange}
                    onCall={handleCall}
                    onViewDetails={handleViewDetails}
                />

                {/* Mobile Action Button */}
                <Button
                    type="primary"
                    shape="circle"
                    size="large"
                    icon={<PlusOutlined />}
                    style={{
                        position: 'fixed',
                        bottom: 24,
                        right: 24,
                        width: 56,
                        height: 56,
                        boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
                        zIndex: 100
                    }}
                    onClick={() => setModalVisible(true)}
                />

                {/* Reuse existing Drawer and Modal components */}
                <Drawer
                    title="Termin-Details"
                    placement="bottom"
                    height="90%"
                    onClose={() => setDrawerVisible(false)}
                    open={drawerVisible}
                    bodyStyle={{ paddingTop: 0 }}
                >
                    {selectedAppointment && (
                        <div>
                            <Alert
                                message={getStatusText(selectedAppointment.status)}
                                type={selectedAppointment.status === 'confirmed' ? 'success' : 'info'}
                                style={{ marginBottom: 16 }}
                            />
                            
                            <Descriptions bordered column={1}>
                                <Descriptions.Item label="Datum & Zeit">
                                    {dayjs(selectedAppointment.starts_at).format('DD.MM.YYYY HH:mm')} - 
                                    {dayjs(selectedAppointment.ends_at).format('HH:mm')}
                                </Descriptions.Item>
                                <Descriptions.Item label="Kunde">
                                    {selectedAppointment.customer?.name}
                                </Descriptions.Item>
                                <Descriptions.Item label="Telefon">
                                    <a href={`tel:${selectedAppointment.customer?.phone}`}>
                                        {selectedAppointment.customer?.phone}
                                    </a>
                                </Descriptions.Item>
                                <Descriptions.Item label="Service">
                                    {selectedAppointment.service?.name}
                                </Descriptions.Item>
                                <Descriptions.Item label="Mitarbeiter">
                                    {selectedAppointment.staff?.name}
                                </Descriptions.Item>
                            </Descriptions>

                            <div style={{ marginTop: 24 }}>
                                <Space direction="vertical" style={{ width: '100%' }}>
                                    <Button
                                        type="primary"
                                        icon={<PhoneOutlined />}
                                        onClick={() => handleCall(selectedAppointment.customer?.phone)}
                                        block
                                    >
                                        Kunde anrufen
                                    </Button>
                                    <Button
                                        icon={<MailOutlined />}
                                        onClick={() => message.info('E-Mail-Funktion wird implementiert')}
                                        block
                                    >
                                        E-Mail senden
                                    </Button>
                                </Space>
                            </div>
                        </div>
                    )}
                </Drawer>
            </div>
        );
    }

    // Desktop view (existing code)
    return (
        <div style={{ padding: 24 }}>
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col span={24}>
                    <Title level={2}>
                        <CalendarOutlined /> Termine
                    </Title>
                </Col>
            </Row>

            {/* Statistics */}
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col xs={24} sm={12} md={8} lg={4}>
                    <Card>
                        <Statistic
                            title="Gesamt"
                            value={stats.total}
                            prefix={<CalendarOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8} lg={4}>
                    <Card>
                        <Statistic
                            title="Heute"
                            value={stats.today}
                            prefix={<ClockCircleOutlined />}
                            valueStyle={{ color: '#3f8600' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8} lg={4}>
                    <Card>
                        <Statistic
                            title="Diese Woche"
                            value={stats.this_week}
                            prefix={<CalendarOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8} lg={4}>
                    <Card>
                        <Statistic
                            title="Bestätigt"
                            value={stats.confirmed}
                            prefix={<CheckCircleOutlined />}
                            valueStyle={{ color: '#52c41a' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8} lg={4}>
                    <Card>
                        <Statistic
                            title="Ausstehend"
                            value={stats.pending}
                            prefix={<ExclamationCircleOutlined />}
                            valueStyle={{ color: '#faad14' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8} lg={4}>
                    <Card>
                        <Statistic
                            title="Storniert"
                            value={stats.cancelled}
                            prefix={<CloseCircleOutlined />}
                            valueStyle={{ color: '#ff4d4f' }}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Filters */}
            <Card style={{ marginBottom: 16 }}>
                <Row gutter={[16, 16]} align="middle">
                    <Col xs={24} sm={12} md={6}>
                        <Input.Search
                            placeholder="Suche nach Kunde oder Telefon..."
                            value={filters.search}
                            onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                            onSearch={fetchAppointments}
                            allowClear
                        />
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <RangePicker
                            style={{ width: '100%' }}
                            value={filters.date_range}
                            onChange={(dates) => setFilters({ ...filters, date_range: dates })}
                            format="DD.MM.YYYY"
                            placeholder={['Von', 'Bis']}
                        />
                    </Col>
                    <Col xs={24} sm={12} md={4}>
                        <Select
                            style={{ width: '100%' }}
                            placeholder="Status"
                            value={filters.status}
                            onChange={(value) => setFilters({ ...filters, status: value })}
                        >
                            <Option value="all">Alle Status</Option>
                            <Option value="scheduled">Geplant</Option>
                            <Option value="confirmed">Bestätigt</Option>
                            <Option value="completed">Abgeschlossen</Option>
                            <Option value="cancelled">Storniert</Option>
                            <Option value="no_show">Nicht erschienen</Option>
                        </Select>
                    </Col>
                    <Col xs={24} sm={12} md={4}>
                        <Select
                            style={{ width: '100%' }}
                            placeholder="Filiale"
                            value={filters.branch_id}
                            onChange={(value) => setFilters({ ...filters, branch_id: value })}
                            allowClear
                        >
                            {branches.map(branch => (
                                <Option key={branch.id} value={branch.id}>{branch.name}</Option>
                            ))}
                        </Select>
                    </Col>
                    <Col xs={24} sm={12} md={4}>
                        <Space>
                            <Button
                                icon={<ReloadOutlined />}
                                onClick={fetchAppointments}
                                loading={loading}
                            >
                                Aktualisieren
                            </Button>
                            <Button
                                type="primary"
                                icon={<PlusOutlined />}
                                onClick={() => setModalVisible(true)}
                            >
                                Neuer Termin
                            </Button>
                        </Space>
                    </Col>
                </Row>
            </Card>

            {/* Appointments Table */}
            <Card>
                <Table
                    columns={columns}
                    dataSource={appointments}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        defaultPageSize: 25,
                        showSizeChanger: true,
                        showTotal: (total) => `${total} Termine`,
                    }}
                    locale={{
                        emptyText: <Empty description="Keine Termine gefunden" />
                    }}
                />
            </Card>

            {/* Appointment Details Drawer */}
            <Drawer
                title="Termin-Details"
                placement="right"
                width={600}
                onClose={() => setDrawerVisible(false)}
                open={drawerVisible}
            >
                {selectedAppointment && (
                    <div>
                        <Alert
                            message={getStatusText(selectedAppointment.status)}
                            type={selectedAppointment.status === 'confirmed' ? 'success' : 'info'}
                            style={{ marginBottom: 16 }}
                        />
                        
                        <Descriptions bordered column={1}>
                            <Descriptions.Item label="Datum & Zeit">
                                {dayjs(selectedAppointment.starts_at).format('DD.MM.YYYY HH:mm')} - 
                                {dayjs(selectedAppointment.ends_at).format('HH:mm')}
                            </Descriptions.Item>
                            <Descriptions.Item label="Kunde">
                                {selectedAppointment.customer?.name}
                            </Descriptions.Item>
                            <Descriptions.Item label="Telefon">
                                {selectedAppointment.customer?.phone}
                            </Descriptions.Item>
                            <Descriptions.Item label="E-Mail">
                                {selectedAppointment.customer?.email || 'N/A'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Service">
                                {selectedAppointment.service?.name}
                            </Descriptions.Item>
                            <Descriptions.Item label="Dauer">
                                {selectedAppointment.service?.duration} Minuten
                            </Descriptions.Item>
                            <Descriptions.Item label="Preis">
                                {selectedAppointment.service?.price ? `€${selectedAppointment.service.price}` : 'N/A'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Mitarbeiter">
                                {selectedAppointment.staff?.name}
                            </Descriptions.Item>
                            <Descriptions.Item label="Filiale">
                                {selectedAppointment.branch?.name}
                            </Descriptions.Item>
                            <Descriptions.Item label="Notizen">
                                {selectedAppointment.notes || 'Keine Notizen'}
                            </Descriptions.Item>
                        </Descriptions>

                        <div style={{ marginTop: 24 }}>
                            <Space>
                                <Button
                                    type="primary"
                                    icon={<MailOutlined />}
                                    onClick={() => message.info('E-Mail-Funktion wird implementiert')}
                                >
                                    E-Mail senden
                                </Button>
                                <Button
                                    icon={<WhatsAppOutlined />}
                                    onClick={() => message.info('WhatsApp-Funktion wird implementiert')}
                                >
                                    WhatsApp
                                </Button>
                                <Button
                                    icon={<PhoneOutlined />}
                                    onClick={() => message.info('Anruf-Funktion wird implementiert')}
                                >
                                    Anrufen
                                </Button>
                            </Space>
                        </div>

                        {selectedAppointment.history && selectedAppointment.history.length > 0 && (
                            <div style={{ marginTop: 24 }}>
                                <Title level={5}>Verlauf</Title>
                                <Timeline>
                                    {selectedAppointment.history.map((item, index) => (
                                        <Timeline.Item key={index}>
                                            <Text>{item.action}</Text>
                                            <br />
                                            <Text type="secondary">
                                                {dayjs(item.created_at).format('DD.MM.YYYY HH:mm')}
                                            </Text>
                                        </Timeline.Item>
                                    ))}
                                </Timeline>
                            </div>
                        )}
                    </div>
                )}
            </Drawer>

            {/* Create Appointment Modal */}
            <Modal
                title="Neuen Termin erstellen"
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
                    onFinish={handleCreateAppointment}
                >
                    <Form.Item
                        name="customer_phone"
                        label="Kunden-Telefonnummer"
                        rules={[{ required: true, message: 'Bitte Telefonnummer eingeben' }]}
                    >
                        <Input placeholder="+49..." />
                    </Form.Item>

                    <Form.Item
                        name="customer_name"
                        label="Kundenname"
                        rules={[{ required: true, message: 'Bitte Namen eingeben' }]}
                    >
                        <Input placeholder="Max Mustermann" />
                    </Form.Item>

                    <Form.Item
                        name="service_id"
                        label="Service"
                        rules={[{ required: true, message: 'Bitte Service auswählen' }]}
                    >
                        <Select placeholder="Service auswählen">
                            {services.map(service => (
                                <Option key={service.id} value={service.id}>
                                    {service.name} ({service.duration} Min.)
                                </Option>
                            ))}
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="staff_id"
                        label="Mitarbeiter"
                        rules={[{ required: true, message: 'Bitte Mitarbeiter auswählen' }]}
                    >
                        <Select placeholder="Mitarbeiter auswählen">
                            {staff.map(s => (
                                <Option key={s.id} value={s.id}>{s.name}</Option>
                            ))}
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="branch_id"
                        label="Filiale"
                        rules={[{ required: true, message: 'Bitte Filiale auswählen' }]}
                    >
                        <Select placeholder="Filiale auswählen">
                            {branches.map(branch => (
                                <Option key={branch.id} value={branch.id}>{branch.name}</Option>
                            ))}
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="datetime"
                        label="Datum & Zeit"
                        rules={[{ required: true, message: 'Bitte Datum und Zeit auswählen' }]}
                    >
                        <RangePicker
                            showTime
                            format="DD.MM.YYYY HH:mm"
                            placeholder={['Start', 'Ende']}
                            style={{ width: '100%' }}
                        />
                    </Form.Item>

                    <Form.Item
                        name="notes"
                        label="Notizen"
                    >
                        <Input.TextArea rows={3} placeholder="Optionale Notizen..." />
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                Termin erstellen
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