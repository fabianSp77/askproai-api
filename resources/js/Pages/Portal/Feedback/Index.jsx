import React, { useState, useEffect } from 'react';
import { 
    Card, 
    Row, 
    Col, 
    Typography, 
    Button, 
    Space,
    Table,
    Tag,
    Select,
    Input,
    Modal,
    Form,
    message,
    Statistic,
    Badge,
    Empty,
    Timeline,
    Avatar,
    Upload,
    Divider,
    Alert,
    Tooltip,
    Drawer,
    List,
    Spin
} from 'antd';
import { 
    BugOutlined,
    BulbOutlined,
    RiseOutlined,
    QuestionCircleOutlined,
    WarningOutlined,
    PlusOutlined,
    SearchOutlined,
    FilterOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    MessageOutlined,
    PaperClipOutlined,
    SendOutlined,
    TeamOutlined,
    InboxOutlined,
    ReloadOutlined,
    ExclamationCircleOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import relativeTime from 'dayjs/plugin/relativeTime';
import axiosInstance from '../../../services/axiosInstance';

dayjs.extend(relativeTime);
dayjs.locale('de');

const { Title, Text, Paragraph } = Typography;
const { Option } = Select;
const { TextArea } = Input;
const { Dragger } = Upload;

const FeedbackIndex = () => {
    const [loading, setLoading] = useState(true);
    const [feedback, setFeedback] = useState([]);
    const [stats, setStats] = useState({});
    const [filters, setFilters] = useState({
        type: 'all',
        status: 'all',
        priority: 'all',
        search: ''
    });
    const [createModalVisible, setCreateModalVisible] = useState(false);
    const [detailDrawerVisible, setDetailDrawerVisible] = useState(false);
    const [selectedFeedback, setSelectedFeedback] = useState(null);
    const [responseLoading, setResponseLoading] = useState(false);
    const [filterOptions, setFilterOptions] = useState({});
    const [form] = Form.useForm();
    const [responseForm] = Form.useForm();
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 10,
        total: 0
    });

    useEffect(() => {
        fetchFilters();
        fetchFeedback();
    }, [filters, pagination.current]);

    const fetchFilters = async () => {
        try {
            const response = await axiosInstance.get('/feedback/filters');

            if (!response.data) throw new Error('Failed to fetch filters');

            const data = await response.data;
            setFilterOptions(data);
        } catch (error) {
            // Silently handle filters error
        }
    };

    const fetchFeedback = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: pagination.current,
                ...filters
            });

            const response = await axiosInstance.get(`/feedback?`);

            if (!response.data) throw new Error('Failed to fetch feedback');

            const data = await response.data;
            setFeedback(data.feedback.data);
            setStats(data.stats);
            setPagination(prev => ({
                ...prev,
                total: data.feedback.total
            }));
        } catch (error) {
            message.error('Fehler beim Laden des Feedbacks');
        } finally {
            setLoading(false);
        }
    };

    const fetchFeedbackDetail = async (id) => {
        try {
            const response = await axiosInstance.get(`/feedback/`);

            if (!response.data) throw new Error('Failed to fetch feedback detail');

            const data = await response.data;
            setSelectedFeedback(data.feedback);
        } catch (error) {
            message.error('Fehler beim Laden der Details');
        }
    };

    const handleCreateFeedback = async (values) => {
        setLoading(true);
        try {
            const formData = new FormData();
            Object.keys(values).forEach(key => {
                if (key !== 'attachments') {
                    formData.append(key, values[key]);
                }
            });

            // Add attachments
            if (values.attachments?.fileList) {
                values.attachments.fileList.forEach(file => {
                    formData.append('attachments[]', file.originFileObj);
                });
            }

            const response = await axiosInstance.get('/feedback');

            if (!response.data) throw new Error('Failed to create feedback');

            message.success('Feedback erfolgreich übermittelt');
            setCreateModalVisible(false);
            form.resetFields();
            fetchFeedback();
        } catch (error) {
            message.error('Fehler beim Übermitteln des Feedbacks');
        } finally {
            setLoading(false);
        }
    };

    const handleResponse = async (values) => {
        setResponseLoading(true);
        try {
            const response = await axiosInstance.get('/feedback/${selectedFeedback.id}/respond');

            if (!response.data) throw new Error('Failed to send response');

            message.success('Antwort erfolgreich gesendet');
            responseForm.resetFields();
            fetchFeedbackDetail(selectedFeedback.id);
            fetchFeedback();
        } catch (error) {
            message.error('Fehler beim Senden der Antwort');
        } finally {
            setResponseLoading(false);
        }
    };

    const handleStatusChange = async (id, status) => {
        try {
            const response = await axiosInstance.put(`/feedback/${id}/status`, { status });

            if (!response.data) throw new Error('Failed to update status');

            message.success('Status erfolgreich aktualisiert');
            fetchFeedback();
            if (selectedFeedback?.id === id) {
                fetchFeedbackDetail(id);
            }
        } catch (error) {
            message.error('Fehler beim Aktualisieren des Status');
        }
    };

    const getTypeIcon = (type) => {
        const icons = {
            bug: <BugOutlined />,
            feature: <BulbOutlined />,
            improvement: <RiseOutlined />,
            question: <QuestionCircleOutlined />,
            complaint: <WarningOutlined />
        };
        return icons[type] || <ExclamationCircleOutlined />;
    };

    const getTypeColor = (type) => {
        const colors = {
            bug: 'red',
            feature: 'blue',
            improvement: 'green',
            question: 'orange',
            complaint: 'purple'
        };
        return colors[type] || 'default';
    };

    const getPriorityColor = (priority) => {
        const colors = {
            low: 'blue',
            medium: 'orange',
            high: 'red',
            urgent: 'purple'
        };
        return colors[priority] || 'default';
    };

    const getStatusColor = (status) => {
        const colors = {
            open: 'red',
            in_progress: 'orange',
            resolved: 'green',
            closed: 'gray'
        };
        return colors[status] || 'default';
    };

    const columns = [
        {
            title: 'ID',
            dataIndex: 'id',
            key: 'id',
            width: 80,
            render: (id) => <Text strong>#{id}</Text>
        },
        {
            title: 'Typ',
            dataIndex: 'type',
            key: 'type',
            render: (type) => (
                <Tag icon={getTypeIcon(type)} color={getTypeColor(type)}>
                    {filterOptions.types?.find(t => t.value === type)?.label || type}
                </Tag>
            )
        },
        {
            title: 'Betreff',
            dataIndex: 'subject',
            key: 'subject',
            render: (subject, record) => (
                <Space direction="vertical" size={0}>
                    <Text strong>{subject}</Text>
                    <Text type="secondary" style={{ fontSize: 12 }}>
                        von {record.user?.name} • {dayjs(record.created_at).fromNow()}
                    </Text>
                </Space>
            )
        },
        {
            title: 'Priorität',
            dataIndex: 'priority',
            key: 'priority',
            render: (priority) => (
                <Tag color={getPriorityColor(priority)}>
                    {filterOptions.priorities?.find(p => p.value === priority)?.label || priority}
                </Tag>
            )
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (status, record) => (
                <Select
                    value={status}
                    size="small"
                    style={{ width: 120 }}
                    onChange={(value) => handleStatusChange(record.id, value)}
                >
                    {filterOptions.statuses?.map(s => (
                        <Option key={s.value} value={s.value}>
                            <Tag color={getStatusColor(s.value)}>{s.label}</Tag>
                        </Option>
                    ))}
                </Select>
            )
        },
        {
            title: 'Antworten',
            key: 'responses',
            render: (_, record) => (
                <Badge count={record.responses?.length || 0} showZero>
                    <MessageOutlined style={{ fontSize: 16 }} />
                </Badge>
            ),
            align: 'center'
        },
        {
            title: 'Aktion',
            key: 'action',
            render: (_, record) => (
                <Button
                    type="link"
                    onClick={() => {
                        fetchFeedbackDetail(record.id);
                        setDetailDrawerVisible(true);
                    }}
                >
                    Details
                </Button>
            )
        }
    ];

    return (
        <div style={{ padding: 24 }}>
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col span={24}>
                    <Row justify="space-between" align="middle">
                        <Col>
                            <Title level={2}>
                                <MessageOutlined /> Feedback & Support
                            </Title>
                        </Col>
                        <Col>
                            <Space>
                                <Button
                                    icon={<ReloadOutlined />}
                                    onClick={fetchFeedback}
                                    loading={loading}
                                >
                                    Aktualisieren
                                </Button>
                                <Button
                                    type="primary"
                                    icon={<PlusOutlined />}
                                    onClick={() => setCreateModalVisible(true)}
                                >
                                    Neues Feedback
                                </Button>
                            </Space>
                        </Col>
                    </Row>
                </Col>
            </Row>

            {/* Statistics */}
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Gesamt"
                            value={stats.total || 0}
                            prefix={<InboxOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Offen"
                            value={stats.open || 0}
                            valueStyle={{ color: '#ff4d4f' }}
                            prefix={<ExclamationCircleOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="In Bearbeitung"
                            value={stats.in_progress || 0}
                            valueStyle={{ color: '#faad14' }}
                            prefix={<ClockCircleOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Ø Antwortzeit"
                            value={stats.avg_response_time || '-'}
                            prefix={<TeamOutlined />}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Filters */}
            <Card style={{ marginBottom: 16 }}>
                <Row gutter={[16, 16]} align="middle">
                    <Col xs={24} sm={12} md={6}>
                        <Select
                            placeholder="Typ"
                            style={{ width: '100%' }}
                            value={filters.type}
                            onChange={(value) => setFilters(prev => ({ ...prev, type: value }))}
                        >
                            <Option value="all">Alle Typen</Option>
                            {filterOptions.types?.map(type => (
                                <Option key={type.value} value={type.value}>
                                    {type.label}
                                </Option>
                            ))}
                        </Select>
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Select
                            placeholder="Status"
                            style={{ width: '100%' }}
                            value={filters.status}
                            onChange={(value) => setFilters(prev => ({ ...prev, status: value }))}
                        >
                            <Option value="all">Alle Status</Option>
                            {filterOptions.statuses?.map(status => (
                                <Option key={status.value} value={status.value}>
                                    {status.label}
                                </Option>
                            ))}
                        </Select>
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Select
                            placeholder="Priorität"
                            style={{ width: '100%' }}
                            value={filters.priority}
                            onChange={(value) => setFilters(prev => ({ ...prev, priority: value }))}
                        >
                            <Option value="all">Alle Prioritäten</Option>
                            {filterOptions.priorities?.map(priority => (
                                <Option key={priority.value} value={priority.value}>
                                    {priority.label}
                                </Option>
                            ))}
                        </Select>
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Input
                            placeholder="Suchen..."
                            prefix={<SearchOutlined />}
                            value={filters.search}
                            onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
                        />
                    </Col>
                </Row>
            </Card>

            {/* Feedback Table */}
            <Card>
                <Table
                    columns={columns}
                    dataSource={feedback}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        ...pagination,
                        showSizeChanger: true,
                        showTotal: (total) => `${total} Einträge`,
                        onChange: (page, pageSize) => {
                            setPagination(prev => ({
                                ...prev,
                                current: page,
                                pageSize
                            }));
                        }
                    }}
                    locale={{
                        emptyText: <Empty description="Kein Feedback vorhanden" />
                    }}
                />
            </Card>

            {/* Create Feedback Modal */}
            <Modal
                title="Neues Feedback"
                visible={createModalVisible}
                onCancel={() => {
                    setCreateModalVisible(false);
                    form.resetFields();
                }}
                footer={null}
                width={600}
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleCreateFeedback}
                >
                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="type"
                                label="Typ"
                                rules={[{ required: true, message: 'Bitte wählen Sie einen Typ' }]}
                            >
                                <Select placeholder="Typ wählen">
                                    {filterOptions.types?.map(type => (
                                        <Option key={type.value} value={type.value}>
                                            {type.label}
                                        </Option>
                                    ))}
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="priority"
                                label="Priorität"
                                rules={[{ required: true, message: 'Bitte wählen Sie eine Priorität' }]}
                            >
                                <Select placeholder="Priorität wählen">
                                    {filterOptions.priorities?.map(priority => (
                                        <Option key={priority.value} value={priority.value}>
                                            {priority.label}
                                        </Option>
                                    ))}
                                </Select>
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item
                        name="subject"
                        label="Betreff"
                        rules={[{ required: true, message: 'Bitte geben Sie einen Betreff ein' }]}
                    >
                        <Input placeholder="Kurze Beschreibung des Themas" />
                    </Form.Item>

                    <Form.Item
                        name="message"
                        label="Nachricht"
                        rules={[{ required: true, message: 'Bitte geben Sie eine Nachricht ein' }]}
                    >
                        <TextArea
                            rows={6}
                            placeholder="Beschreiben Sie Ihr Anliegen ausführlich..."
                        />
                    </Form.Item>

                    <Form.Item
                        name="attachments"
                        label="Anhänge (optional)"
                    >
                        <Dragger
                            multiple
                            beforeUpload={() => false}
                            accept="image/*,.pdf,.doc,.docx,.txt"
                        >
                            <p className="ant-upload-drag-icon">
                                <PaperClipOutlined />
                            </p>
                            <p className="ant-upload-text">
                                Klicken oder ziehen Sie Dateien hierher
                            </p>
                            <p className="ant-upload-hint">
                                Unterstützt: Bilder, PDF, Word, Text (max. 10MB pro Datei)
                            </p>
                        </Dragger>
                    </Form.Item>

                    <Form.Item>
                        <Space style={{ width: '100%', justifyContent: 'flex-end' }}>
                            <Button onClick={() => {
                                setCreateModalVisible(false);
                                form.resetFields();
                            }}>
                                Abbrechen
                            </Button>
                            <Button type="primary" htmlType="submit" loading={loading}>
                                Feedback senden
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>

            {/* Feedback Detail Drawer */}
            <Drawer
                title={`Feedback #${selectedFeedback?.id}`}
                placement="right"
                width={600}
                visible={detailDrawerVisible}
                onClose={() => {
                    setDetailDrawerVisible(false);
                    setSelectedFeedback(null);
                    responseForm.resetFields();
                }}
            >
                {selectedFeedback && (
                    <Spin spinning={!selectedFeedback}>
                        <Space direction="vertical" style={{ width: '100%' }} size="large">
                            {/* Header Info */}
                            <Card size="small">
                                <Row gutter={[16, 8]}>
                                    <Col span={12}>
                                        <Text type="secondary">Typ:</Text><br />
                                        <Tag icon={getTypeIcon(selectedFeedback.type)} color={getTypeColor(selectedFeedback.type)}>
                                            {filterOptions.types?.find(t => t.value === selectedFeedback.type)?.label}
                                        </Tag>
                                    </Col>
                                    <Col span={12}>
                                        <Text type="secondary">Priorität:</Text><br />
                                        <Tag color={getPriorityColor(selectedFeedback.priority)}>
                                            {filterOptions.priorities?.find(p => p.value === selectedFeedback.priority)?.label}
                                        </Tag>
                                    </Col>
                                    <Col span={12}>
                                        <Text type="secondary">Status:</Text><br />
                                        <Tag color={getStatusColor(selectedFeedback.status)}>
                                            {filterOptions.statuses?.find(s => s.value === selectedFeedback.status)?.label}
                                        </Tag>
                                    </Col>
                                    <Col span={12}>
                                        <Text type="secondary">Erstellt:</Text><br />
                                        <Text>{dayjs(selectedFeedback.created_at).format('DD.MM.YYYY HH:mm')}</Text>
                                    </Col>
                                </Row>
                            </Card>

                            {/* Subject & Message */}
                            <div>
                                <Title level={4}>{selectedFeedback.subject}</Title>
                                <Card>
                                    <Space>
                                        <Avatar icon={<UserOutlined />} />
                                        <div>
                                            <Text strong>{selectedFeedback.user?.name}</Text><br />
                                            <Text type="secondary">{dayjs(selectedFeedback.created_at).fromNow()}</Text>
                                        </div>
                                    </Space>
                                    <Paragraph style={{ marginTop: 16 }}>
                                        {selectedFeedback.message}
                                    </Paragraph>
                                    {selectedFeedback.attachments?.length > 0 && (
                                        <div style={{ marginTop: 16 }}>
                                            <Text type="secondary">Anhänge:</Text>
                                            <List
                                                size="small"
                                                dataSource={selectedFeedback.attachments}
                                                renderItem={item => (
                                                    <List.Item>
                                                        <PaperClipOutlined /> {item.name}
                                                    </List.Item>
                                                )}
                                            />
                                        </div>
                                    )}
                                </Card>
                            </div>

                            {/* Responses */}
                            {selectedFeedback.responses?.length > 0 && (
                                <div>
                                    <Title level={5}>Antworten</Title>
                                    <Timeline>
                                        {selectedFeedback.responses.map(response => (
                                            <Timeline.Item key={response.id}>
                                                <Card size="small">
                                                    <Space>
                                                        <Avatar icon={<UserOutlined />} />
                                                        <div>
                                                            <Text strong>{response.user?.name}</Text>
                                                            {response.is_internal && <Tag color="orange">Intern</Tag>}
                                                            <br />
                                                            <Text type="secondary">{dayjs(response.created_at).fromNow()}</Text>
                                                        </div>
                                                    </Space>
                                                    <Paragraph style={{ marginTop: 8, marginBottom: 0 }}>
                                                        {response.message}
                                                    </Paragraph>
                                                </Card>
                                            </Timeline.Item>
                                        ))}
                                    </Timeline>
                                </div>
                            )}

                            {/* Response Form */}
                            <Card title="Antworten">
                                <Form
                                    form={responseForm}
                                    onFinish={handleResponse}
                                    layout="vertical"
                                >
                                    <Form.Item
                                        name="message"
                                        rules={[{ required: true, message: 'Bitte geben Sie eine Antwort ein' }]}
                                    >
                                        <TextArea
                                            rows={4}
                                            placeholder="Ihre Antwort..."
                                        />
                                    </Form.Item>

                                    <Row gutter={16}>
                                        <Col span={12}>
                                            <Form.Item
                                                name="status"
                                                label="Status ändern (optional)"
                                            >
                                                <Select placeholder="Status beibehalten">
                                                    {filterOptions.statuses?.map(status => (
                                                        <Option key={status.value} value={status.value}>
                                                            {status.label}
                                                        </Option>
                                                    ))}
                                                </Select>
                                            </Form.Item>
                                        </Col>
                                        <Col span={12}>
                                            <Form.Item
                                                name="internal_note"
                                                valuePropName="checked"
                                            >
                                                <Space>
                                                    <input type="checkbox" />
                                                    <Text>Als interne Notiz markieren</Text>
                                                </Space>
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Form.Item>
                                        <Button
                                            type="primary"
                                            htmlType="submit"
                                            icon={<SendOutlined />}
                                            loading={responseLoading}
                                            block
                                        >
                                            Antwort senden
                                        </Button>
                                    </Form.Item>
                                </Form>
                            </Card>
                        </Space>
                    </Spin>
                )}
            </Drawer>
        </div>
    );
};

export default FeedbackIndex;