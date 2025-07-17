import React, { useState, useEffect } from 'react';
import { 
    Card, 
    Table, 
    Button, 
    Space, 
    Tag, 
    Avatar, 
    Input, 
    Row, 
    Col, 
    Typography, 
    Modal, 
    Form, 
    Select,
    message,
    Tooltip,
    Badge,
    Empty,
    Popconfirm,
    Drawer,
    Descriptions,
    Switch,
    Transfer,
    Alert
} from 'antd';
import { 
    UserOutlined, 
    MailOutlined, 
    PhoneOutlined,
    EditOutlined,
    DeleteOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    PlusOutlined,
    ReloadOutlined,
    TeamOutlined,
    LockOutlined,
    SafetyOutlined,
    EnvironmentOutlined,
    CalendarOutlined,
    ClockCircleOutlined,
    UserAddOutlined,
    KeyOutlined
} from '@ant-design/icons';
import axiosInstance from '../../../services/axiosInstance';

const { Title, Text } = Typography;
const { Option } = Select;

const TeamIndex = () => {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [stats, setStats] = useState({
        total: 0,
        active: 0,
        inactive: 0,
        admins: 0
    });
    const [branches, setBranches] = useState([]);
    const [permissions, setPermissions] = useState([]);
    const [selectedUser, setSelectedUser] = useState(null);
    const [drawerVisible, setDrawerVisible] = useState(false);
    const [inviteModalVisible, setInviteModalVisible] = useState(false);
    const [editModalVisible, setEditModalVisible] = useState(false);
    const [permissionModalVisible, setPermissionModalVisible] = useState(false);
    const [selectedPermissions, setSelectedPermissions] = useState([]);
    const [form] = Form.useForm();
    const [editForm] = Form.useForm();

    useEffect(() => {
        fetchUsers();
        fetchFilterOptions();
    }, [search]);

    const fetchUsers = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (search) {
                params.append('search', search);
            }

            const response = await axiosInstance.get(`/team?`);

            if (!response.data) throw new Error('Failed to fetch team members');

            const data = await response.data;
            setUsers(data.users || []);
            setStats(data.stats || {});
        } catch (error) {
            message.error('Fehler beim Laden der Teammitglieder');
        } finally {
            setLoading(false);
        }
    };

    const fetchFilterOptions = async () => {
        try {
            const response = await axiosInstance.get('/team/filters');

            if (!response.data) throw new Error('Failed to fetch filters');

            const data = await response.data;
            setBranches(data.branches || []);
            setPermissions(data.permissions || []);
        } catch (error) {
            // Silently handle filter errors
        }
    };

    const handleInvite = async (values) => {
        try {
            const response = await axiosInstance.get('/team/invite');

            if (!response.data) throw new Error('Failed to send invite');

            message.success('Einladung erfolgreich versendet');
            setInviteModalVisible(false);
            form.resetFields();
            fetchUsers();
        } catch (error) {
            message.error('Fehler beim Versenden der Einladung');
        }
    };

    const handleUpdateUser = async (userId, values) => {
        try {
            const response = await axiosInstance.get(`/team/`);

            if (!response.data) throw new Error('Failed to update user');

            message.success('Benutzer erfolgreich aktualisiert');
            setEditModalVisible(false);
            editForm.resetFields();
            fetchUsers();
        } catch (error) {
            message.error('Fehler beim Aktualisieren des Benutzers');
        }
    };

    const handleToggleStatus = async (userId, active) => {
        try {
            const response = await axiosInstance.put(`/team/${userId}`, { is_active: !active });

            if (!response.data) throw new Error('Failed to toggle status');

            message.success(`Benutzer ${active ? 'aktiviert' : 'deaktiviert'}`);
            fetchUsers();
        } catch (error) {
            message.error('Fehler beim Ändern des Status');
        }
    };

    const handleUpdatePermissions = async (userId) => {
        try {
            const response = await axiosInstance.put(`/team/${userId}`, { permissions });

            if (!response.data) throw new Error('Failed to update permissions');

            message.success('Berechtigungen erfolgreich aktualisiert');
            setPermissionModalVisible(false);
            fetchUsers();
        } catch (error) {
            message.error('Fehler beim Aktualisieren der Berechtigungen');
        }
    };

    const columns = [
        {
            title: 'Benutzer',
            dataIndex: 'name',
            key: 'name',
            render: (name, record) => (
                <Space>
                    <Avatar 
                        src={record.avatar_url} 
                        icon={!record.avatar_url && <UserOutlined />}
                        style={{ backgroundColor: record.is_active ? '#52c41a' : '#d9d9d9' }}
                    />
                    <div>
                        <Text strong>{name}</Text>
                        <br />
                        <Text type="secondary" style={{ fontSize: '12px' }}>{record.email}</Text>
                    </div>
                </Space>
            )
        },
        {
            title: 'Rolle',
            dataIndex: 'role',
            key: 'role',
            render: (role) => {
                const colors = {
                    'admin': 'gold',
                    'manager': 'blue',
                    'employee': 'green'
                };
                return <Tag color={colors[role] || 'default'}>{role}</Tag>;
            }
        },
        {
            title: 'Filiale(n)',
            dataIndex: 'branches',
            key: 'branches',
            render: (branches) => (
                <Space size={[0, 8]} wrap>
                    {branches?.map(branch => (
                        <Tag key={branch.id} icon={<EnvironmentOutlined />}>
                            {branch.name}
                        </Tag>
                    )) || <Text type="secondary">Keine</Text>}
                </Space>
            )
        },
        {
            title: 'Status',
            dataIndex: 'is_active',
            key: 'is_active',
            render: (isActive, record) => (
                <Switch
                    checked={isActive}
                    onChange={(checked) => handleToggleStatus(record.id, checked)}
                    checkedChildren="Aktiv"
                    unCheckedChildren="Inaktiv"
                />
            )
        },
        {
            title: 'Letzter Login',
            dataIndex: 'last_login_at',
            key: 'last_login_at',
            render: (date) => date ? new Date(date).toLocaleDateString('de-DE') : 'Nie',
            sorter: (a, b) => new Date(a.last_login_at) - new Date(b.last_login_at)
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
                            icon={<UserOutlined />}
                            onClick={() => {
                                setSelectedUser(record);
                                setDrawerVisible(true);
                            }}
                        />
                    </Tooltip>
                    <Tooltip title="Bearbeiten">
                        <Button
                            type="text"
                            icon={<EditOutlined />}
                            onClick={() => {
                                setSelectedUser(record);
                                editForm.setFieldsValue({
                                    name: record.name,
                                    email: record.email,
                                    role: record.role,
                                    branch_ids: record.branches?.map(b => b.id) || []
                                });
                                setEditModalVisible(true);
                            }}
                        />
                    </Tooltip>
                    <Tooltip title="Berechtigungen">
                        <Button
                            type="text"
                            icon={<SafetyOutlined />}
                            onClick={() => {
                                setSelectedUser(record);
                                setSelectedPermissions(record.permissions?.map(p => p.id) || []);
                                setPermissionModalVisible(true);
                            }}
                        />
                    </Tooltip>
                    <Popconfirm
                        title="Benutzer löschen?"
                        description="Dieser Vorgang kann nicht rückgängig gemacht werden."
                        onConfirm={() => message.info('Löschfunktion wird implementiert')}
                        disabled={record.role === 'admin'}
                    >
                        <Tooltip title="Löschen">
                            <Button
                                type="text"
                                danger
                                icon={<DeleteOutlined />}
                                disabled={record.role === 'admin'}
                            />
                        </Tooltip>
                    </Popconfirm>
                </Space>
            )
        }
    ];

    return (
        <div style={{ padding: 24 }}>
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col span={24}>
                    <Title level={2}>
                        <TeamOutlined /> Team-Verwaltung
                    </Title>
                </Col>
            </Row>

            {/* Statistics */}
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Space direction="vertical" style={{ width: '100%' }}>
                            <Text type="secondary">Gesamt</Text>
                            <Title level={3} style={{ margin: 0 }}>{stats.total}</Title>
                        </Space>
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Space direction="vertical" style={{ width: '100%' }}>
                            <Text type="secondary">Aktiv</Text>
                            <Title level={3} style={{ margin: 0, color: '#52c41a' }}>{stats.active}</Title>
                        </Space>
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Space direction="vertical" style={{ width: '100%' }}>
                            <Text type="secondary">Inaktiv</Text>
                            <Title level={3} style={{ margin: 0, color: '#ff4d4f' }}>{stats.inactive}</Title>
                        </Space>
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Space direction="vertical" style={{ width: '100%' }}>
                            <Text type="secondary">Administratoren</Text>
                            <Title level={3} style={{ margin: 0, color: '#faad14' }}>{stats.admins}</Title>
                        </Space>
                    </Card>
                </Col>
            </Row>

            {/* Search and Actions */}
            <Card style={{ marginBottom: 16 }}>
                <Row gutter={[16, 16]} align="middle">
                    <Col xs={24} sm={16}>
                        <Input.Search
                            placeholder="Nach Name oder E-Mail suchen..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onSearch={fetchUsers}
                            allowClear
                            size="large"
                        />
                    </Col>
                    <Col xs={24} sm={8} style={{ textAlign: 'right' }}>
                        <Space>
                            <Button
                                icon={<ReloadOutlined />}
                                onClick={fetchUsers}
                                loading={loading}
                            >
                                Aktualisieren
                            </Button>
                            <Button
                                type="primary"
                                icon={<UserAddOutlined />}
                                onClick={() => setInviteModalVisible(true)}
                            >
                                Einladen
                            </Button>
                        </Space>
                    </Col>
                </Row>
            </Card>

            {/* Team Table */}
            <Card>
                <Table
                    columns={columns}
                    dataSource={users}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        defaultPageSize: 10,
                        showSizeChanger: true,
                        showTotal: (total) => `${total} Teammitglieder`,
                    }}
                    locale={{
                        emptyText: <Empty description="Keine Teammitglieder gefunden" />
                    }}
                />
            </Card>

            {/* User Details Drawer */}
            <Drawer
                title="Benutzer-Details"
                placement="right"
                width={600}
                onClose={() => setDrawerVisible(false)}
                visible={drawerVisible}
            >
                {selectedUser && (
                    <div>
                        <div style={{ textAlign: 'center', marginBottom: 24 }}>
                            <Avatar 
                                size={100} 
                                src={selectedUser.avatar_url}
                                icon={!selectedUser.avatar_url && <UserOutlined />}
                            />
                            <Title level={4} style={{ marginTop: 16, marginBottom: 0 }}>
                                {selectedUser.name}
                            </Title>
                            <Text type="secondary">{selectedUser.email}</Text>
                        </div>

                        <Descriptions bordered column={1}>
                            <Descriptions.Item label="Rolle">
                                <Tag color={selectedUser.role === 'admin' ? 'gold' : 'blue'}>
                                    {selectedUser.role}
                                </Tag>
                            </Descriptions.Item>
                            <Descriptions.Item label="Status">
                                <Badge 
                                    status={selectedUser.is_active ? 'success' : 'default'} 
                                    text={selectedUser.is_active ? 'Aktiv' : 'Inaktiv'} 
                                />
                            </Descriptions.Item>
                            <Descriptions.Item label="Telefon">
                                {selectedUser.phone || 'Nicht angegeben'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Filialen">
                                {selectedUser.branches?.map(branch => (
                                    <Tag key={branch.id}>{branch.name}</Tag>
                                )) || 'Keine'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Registriert am">
                                {new Date(selectedUser.created_at).toLocaleDateString('de-DE')}
                            </Descriptions.Item>
                            <Descriptions.Item label="Letzter Login">
                                {selectedUser.last_login_at 
                                    ? new Date(selectedUser.last_login_at).toLocaleString('de-DE')
                                    : 'Nie'}
                            </Descriptions.Item>
                            <Descriptions.Item label="2FA">
                                {selectedUser.two_factor_enabled ? (
                                    <Tag color="green" icon={<LockOutlined />}>Aktiviert</Tag>
                                ) : (
                                    <Tag color="red" icon={<CloseCircleOutlined />}>Deaktiviert</Tag>
                                )}
                            </Descriptions.Item>
                        </Descriptions>

                        <div style={{ marginTop: 24 }}>
                            <Title level={5}>Berechtigungen</Title>
                            <Space size={[0, 8]} wrap>
                                {selectedUser.permissions?.map(perm => (
                                    <Tag key={perm.id} color="blue">{perm.display_name}</Tag>
                                )) || <Text type="secondary">Keine speziellen Berechtigungen</Text>}
                            </Space>
                        </div>

                        <div style={{ marginTop: 24 }}>
                            <Title level={5}>Aktivitäten</Title>
                            <Alert
                                message="Aktivitätsverlauf wird in Kürze implementiert"
                                type="info"
                                showIcon
                            />
                        </div>
                    </div>
                )}
            </Drawer>

            {/* Invite Modal */}
            <Modal
                title="Teammitglied einladen"
                visible={inviteModalVisible}
                onCancel={() => {
                    setInviteModalVisible(false);
                    form.resetFields();
                }}
                footer={null}
                width={600}
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleInvite}
                >
                    <Form.Item
                        name="email"
                        label="E-Mail Adresse"
                        rules={[
                            { required: true, message: 'Bitte E-Mail eingeben' },
                            { type: 'email', message: 'Bitte gültige E-Mail eingeben' }
                        ]}
                    >
                        <Input 
                            prefix={<MailOutlined />}
                            placeholder="max.mustermann@example.com" 
                        />
                    </Form.Item>

                    <Form.Item
                        name="name"
                        label="Name"
                        rules={[{ required: true, message: 'Bitte Namen eingeben' }]}
                    >
                        <Input 
                            prefix={<UserOutlined />}
                            placeholder="Max Mustermann" 
                        />
                    </Form.Item>

                    <Form.Item
                        name="role"
                        label="Rolle"
                        rules={[{ required: true, message: 'Bitte Rolle auswählen' }]}
                    >
                        <Select placeholder="Rolle auswählen">
                            <Option value="employee">Mitarbeiter</Option>
                            <Option value="manager">Manager</Option>
                            <Option value="admin">Administrator</Option>
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="branch_ids"
                        label="Filialen"
                        rules={[{ required: true, message: 'Bitte mindestens eine Filiale auswählen' }]}
                    >
                        <Select 
                            mode="multiple" 
                            placeholder="Filialen auswählen"
                        >
                            {branches.map(branch => (
                                <Option key={branch.id} value={branch.id}>{branch.name}</Option>
                            ))}
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="message"
                        label="Persönliche Nachricht (optional)"
                    >
                        <Input.TextArea 
                            rows={3} 
                            placeholder="Willkommen im Team! Wir freuen uns auf die Zusammenarbeit..." 
                        />
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit" icon={<MailOutlined />}>
                                Einladung senden
                            </Button>
                            <Button onClick={() => {
                                setInviteModalVisible(false);
                                form.resetFields();
                            }}>
                                Abbrechen
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>

            {/* Edit User Modal */}
            <Modal
                title="Benutzer bearbeiten"
                visible={editModalVisible}
                onCancel={() => {
                    setEditModalVisible(false);
                    editForm.resetFields();
                }}
                footer={null}
                width={600}
            >
                <Form
                    form={editForm}
                    layout="vertical"
                    onFinish={(values) => handleUpdateUser(selectedUser?.id, values)}
                >
                    <Form.Item
                        name="name"
                        label="Name"
                        rules={[{ required: true, message: 'Bitte Namen eingeben' }]}
                    >
                        <Input placeholder="Max Mustermann" />
                    </Form.Item>

                    <Form.Item
                        name="email"
                        label="E-Mail"
                        rules={[
                            { required: true, message: 'Bitte E-Mail eingeben' },
                            { type: 'email', message: 'Bitte gültige E-Mail eingeben' }
                        ]}
                    >
                        <Input placeholder="max.mustermann@example.com" />
                    </Form.Item>

                    <Form.Item
                        name="role"
                        label="Rolle"
                        rules={[{ required: true, message: 'Bitte Rolle auswählen' }]}
                    >
                        <Select placeholder="Rolle auswählen">
                            <Option value="employee">Mitarbeiter</Option>
                            <Option value="manager">Manager</Option>
                            <Option value="admin">Administrator</Option>
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="branch_ids"
                        label="Filialen"
                    >
                        <Select mode="multiple" placeholder="Filialen auswählen">
                            {branches.map(branch => (
                                <Option key={branch.id} value={branch.id}>{branch.name}</Option>
                            ))}
                        </Select>
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                Speichern
                            </Button>
                            <Button onClick={() => {
                                setEditModalVisible(false);
                                editForm.resetFields();
                            }}>
                                Abbrechen
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>

            {/* Permissions Modal */}
            <Modal
                title="Berechtigungen verwalten"
                visible={permissionModalVisible}
                onOk={() => handleUpdatePermissions(selectedUser?.id)}
                onCancel={() => setPermissionModalVisible(false)}
                width={600}
            >
                <Transfer
                    dataSource={permissions.map(p => ({
                        key: p.id,
                        title: p.display_name,
                        description: p.description
                    }))}
                    targetKeys={selectedPermissions}
                    onChange={setSelectedPermissions}
                    render={item => item.title}
                    titles={['Verfügbar', 'Zugewiesen']}
                    listStyle={{
                        width: 250,
                        height: 400,
                    }}
                />
            </Modal>
        </div>
    );
};

export default TeamIndex;