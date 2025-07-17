import React, { useState, useEffect, useCallback } from 'react';
import { Table, Card, Input, Button, Space, Tag, Tooltip, Modal, Drawer, Badge, Avatar, Typography, Row, Col, Statistic, message } from 'antd';
import { 
    SearchOutlined, 
    UserAddOutlined, 
    PhoneOutlined, 
    MailOutlined,
    CalendarOutlined,
    EditOutlined,
    DeleteOutlined,
    EyeOutlined,
    HistoryOutlined,
    SyncOutlined,
    FilterOutlined,
    ExportOutlined,
    UserOutlined
} from '@ant-design/icons';
import adminAxios from '../../../services/adminAxios';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import CustomerDetailView from '../../../components/admin/CustomerDetailView';

dayjs.locale('de');

const { Search } = Input;
const { Title, Text } = Typography;

const CustomersIndex = () => {
    const [customers, setCustomers] = useState([]);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCustomer, setSelectedCustomer] = useState(null);
    const [showDetail, setShowDetail] = useState(false);
    const [stats, setStats] = useState({
        total: 0,
        active: 0,
        new_this_month: 0,
        portal_users: 0
    });
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 20,
        total: 0
    });

    // Use centralized admin axios instance
    const api = adminAxios;

    // Fetch customers
    const fetchCustomers = useCallback(async (page = 1, search = '') => {
        setLoading(true);
        try {
            const response = await api.get('/customers', {
                params: {
                    page,
                    per_page: pagination.pageSize,
                    search
                }
            });
            
            const data = response.data;
            setCustomers(data.data || []);
            setPagination(prev => ({
                ...prev,
                current: data.current_page || 1,
                total: data.total || 0
            }));
            
            // Update stats if provided
            if (data.stats) {
                setStats(data.stats);
            }
        } catch (error) {
            console.error('Error fetching customers:', error);
            message.error('Fehler beim Laden der Kunden');
        } finally {
            setLoading(false);
        }
    }, [api, pagination.pageSize]);

    // Initial load
    useEffect(() => {
        fetchCustomers();
    }, []);

    // Handle search
    const handleSearch = (value) => {
        setSearchTerm(value);
        fetchCustomers(1, value);
    };

    // Handle table change
    const handleTableChange = (newPagination) => {
        fetchCustomers(newPagination.current, searchTerm);
    };

    // View customer details
    const viewCustomer = (customer) => {
        setSelectedCustomer(customer);
        setShowDetail(true);
    };

    // Delete customer
    const deleteCustomer = async (customerId) => {
        Modal.confirm({
            title: 'Kunde löschen?',
            content: 'Sind Sie sicher, dass Sie diesen Kunden löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
            okText: 'Löschen',
            okType: 'danger',
            cancelText: 'Abbrechen',
            onOk: async () => {
                try {
                    await api.delete(`/customers/${customerId}`);
                    message.success('Kunde erfolgreich gelöscht');
                    fetchCustomers(pagination.current, searchTerm);
                } catch (error) {
                    console.error('Error deleting customer:', error);
                    message.error('Fehler beim Löschen des Kunden');
                }
            }
        });
    };

    // Table columns
    const columns = [
        {
            title: 'Kunde',
            dataIndex: 'name',
            key: 'name',
            render: (_, record) => (
                <Space>
                    <Avatar icon={<UserOutlined />} style={{ backgroundColor: '#1890ff' }}>
                        {record.name?.charAt(0) || record.first_name?.charAt(0) || 'K'}
                    </Avatar>
                    <div>
                        <div className="font-medium">
                            {record.name || `${record.first_name || ''} ${record.last_name || ''}`.trim() || 'Unbekannt'}
                        </div>
                        <Text type="secondary" className="text-xs">
                            ID: {record.id}
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
            title: 'Termine',
            dataIndex: 'appointments_count',
            key: 'appointments',
            render: (count) => (
                <Badge count={count || 0} showZero style={{ backgroundColor: '#52c41a' }} />
            ),
            sorter: true
        },
        {
            title: 'Status',
            key: 'status',
            render: (_, record) => {
                const tags = [];
                if (record.is_active !== false) {
                    tags.push(<Tag color="green" key="active">Aktiv</Tag>);
                }
                if (record.portal_user_id) {
                    tags.push(<Tag color="blue" key="portal">Portal</Tag>);
                }
                if (record.is_vip) {
                    tags.push(<Tag color="gold" key="vip">VIP</Tag>);
                }
                return <Space size="small">{tags}</Space>;
            }
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
            width: 120,
            render: (_, record) => (
                <Space size="small">
                    <Tooltip title="Details anzeigen">
                        <Button 
                            type="text" 
                            icon={<EyeOutlined />} 
                            onClick={() => viewCustomer(record)}
                        />
                    </Tooltip>
                    <Tooltip title="Bearbeiten">
                        <Button 
                            type="text" 
                            icon={<EditOutlined />} 
                            onClick={() => {
                                // TODO: Implement edit
                                message.info('Bearbeiten wird implementiert');
                            }}
                        />
                    </Tooltip>
                    <Tooltip title="Löschen">
                        <Button 
                            type="text" 
                            danger
                            icon={<DeleteOutlined />} 
                            onClick={() => deleteCustomer(record.id)}
                        />
                    </Tooltip>
                </Space>
            )
        }
    ];

    // Render detail view if customer is selected
    if (showDetail && selectedCustomer) {
        return (
            <CustomerDetailView
                customerId={selectedCustomer.id}
                onBack={() => {
                    setShowDetail(false);
                    setSelectedCustomer(null);
                    fetchCustomers(pagination.current, searchTerm);
                }}
                api={api}
            />
        );
    }

    return (
        <div>
            <div className="mb-6">
                <Title level={2}>Kunden</Title>
            </div>

            {/* Statistics */}
            <Row gutter={16} className="mb-6">
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Gesamt"
                            value={stats.total}
                            prefix={<TeamOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Aktiv"
                            value={stats.active}
                            valueStyle={{ color: '#3f8600' }}
                            prefix={<UserOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Neu (30 Tage)"
                            value={stats.new_this_month}
                            valueStyle={{ color: '#1890ff' }}
                            prefix={<UserAddOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Portal-Nutzer"
                            value={stats.portal_users}
                            prefix={<SolutionOutlined />}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Toolbar */}
            <Card className="mb-4">
                <Row gutter={16} align="middle">
                    <Col flex="auto">
                        <Search
                            placeholder="Suche nach Name, Telefon oder E-Mail..."
                            allowClear
                            enterButton={<SearchOutlined />}
                            size="large"
                            onSearch={handleSearch}
                            style={{ maxWidth: 400 }}
                        />
                    </Col>
                    <Col>
                        <Space>
                            <Button icon={<FilterOutlined />}>Filter</Button>
                            <Button icon={<ExportOutlined />}>Export</Button>
                            <Button 
                                type="primary" 
                                icon={<UserAddOutlined />}
                                onClick={() => message.info('Neuer Kunde wird implementiert')}
                            >
                                Neuer Kunde
                            </Button>
                        </Space>
                    </Col>
                </Row>
            </Card>

            {/* Table */}
            <Card>
                <Table
                    columns={columns}
                    dataSource={customers}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        ...pagination,
                        showSizeChanger: true,
                        showTotal: (total, range) => `${range[0]}-${range[1]} von ${total} Kunden`,
                        pageSizeOptions: ['10', '20', '50', '100']
                    }}
                    onChange={handleTableChange}
                    scroll={{ x: 800 }}
                />
            </Card>
        </div>
    );
};

export default CustomersIndex;
