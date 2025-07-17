import React, { useState } from 'react';
import { 
    Descriptions, 
    Tag, 
    Space, 
    Button, 
    Timeline, 
    Card, 
    Divider, 
    Typography, 
    List,
    Avatar,
    Tooltip,
    Modal,
    Form,
    Input,
    Select,
    message,
    Tabs,
    Badge,
    Row,
    Col,
    Statistic,
    Empty,
    Spin
} from 'antd';
import { 
    PhoneOutlined, 
    UserOutlined, 
    ClockCircleOutlined,
    CalendarOutlined,
    BranchesOutlined,
    MessageOutlined,
    DollarOutlined,
    FileTextOutlined,
    TagOutlined,
    AudioOutlined,
    TeamOutlined,
    ExclamationCircleOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    PlayCircleOutlined,
    HistoryOutlined,
    CustomerServiceOutlined,
    BarChartOutlined,
    DownloadOutlined,
    TranslationOutlined,
    GlobalOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import axios from 'axios';

dayjs.extend(duration);

const { Title, Text, Paragraph } = Typography;
const { TextArea } = Input;

const CallDetailView = ({ call, onUpdate, onClose, permissions = {} }) => {
    const [activeTab, setActiveTab] = useState('overview');
    const [editModalVisible, setEditModalVisible] = useState(false);
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [translatedSummary, setTranslatedSummary] = useState(null);
    const [translatingS, setTranslatingS] = useState(false);
    const [showTranslated, setShowTranslated] = useState(false);
    const [showOnlyCustomer, setShowOnlyCustomer] = useState(false);

    if (!call) return null;

    // Format duration
    const formatDuration = (seconds) => {
        if (!seconds) return '-';
        const dur = dayjs.duration(seconds, 'seconds');
        return `${Math.floor(dur.asMinutes())}:${dur.seconds().toString().padStart(2, '0')}`;
    };

    // Translate summary
    const translateSummary = async () => {
        if (!call.summary && !call.call_summary) {
            return;
        }

        setTranslatingS(true);
        try {
            const response = await axios.post('/business/api/translate', {
                text: call.summary || call.call_summary,
                target_lang: 'de',
                source_lang: call.detected_language || 'en'
            });
            
            if (response.data.translated) {
                setTranslatedSummary(response.data.translated);
                setShowTranslated(true);
                message.success('Zusammenfassung wurde übersetzt');
            }
        } catch (error) {
            message.error('Übersetzung fehlgeschlagen');
        } finally {
            setTranslatingS(false);
        }
    };

    // Get status color
    const getStatusColor = (status) => {
        const colors = {
            'completed': 'green',
            'in_progress': 'blue',
            'ended': 'default',
            'scheduled': 'orange',
            'cancelled': 'red',
            'no_show': 'grey',
        };
        return colors[status] || 'default';
    };

    // Handle status update
    const handleStatusUpdate = async (newStatus) => {
        setLoading(true);
        try {
            await onUpdate(call.id, { status: newStatus });
            message.success('Status aktualisiert');
        } catch (error) {
            message.error('Fehler beim Aktualisieren des Status');
        } finally {
            setLoading(false);
        }
    };

    // Handle edit submit
    const handleEditSubmit = async (values) => {
        setLoading(true);
        try {
            await onUpdate(call.id, values);
            message.success('Anrufdetails aktualisiert');
            setEditModalVisible(false);
            form.resetFields();
        } catch (error) {
            message.error('Fehler beim Aktualisieren');
        } finally {
            setLoading(false);
        }
    };

    // Render audio player
    const renderAudioPlayer = () => {
        if (!call.recording_url) {
            return (
                <Card>
                    <Empty description="Keine Aufzeichnung verfügbar" />
                </Card>
            );
        }

        return (
            <Card>
                <Space direction="vertical" style={{ width: '100%' }}>
                    <audio controls style={{ width: '100%' }}>
                        <source src={call.recording_url} type="audio/mpeg" />
                        Ihr Browser unterstützt kein Audio.
                    </audio>
                    {permissions['calls.download_recording'] && (
                        <Button 
                            icon={<DownloadOutlined />}
                            href={call.recording_url}
                            download
                        >
                            Aufzeichnung herunterladen
                        </Button>
                    )}
                </Space>
            </Card>
        );
    };

    // Render transcript
    const renderTranscript = () => {
        if (!call.transcript && !call.transcript_object) {
            return <Text type="secondary">Kein Transkript verfügbar</Text>;
        }

        // Check if we have structured transcript
        if (call.transcript_object && Array.isArray(call.transcript_object)) {
            return (
                <Space direction="vertical" style={{ width: '100%' }}>
                    <div style={{ marginBottom: 16 }}>
                        <Button
                            type={showOnlyCustomer ? "primary" : "default"}
                            icon={<CustomerServiceOutlined />}
                            onClick={() => setShowOnlyCustomer(!showOnlyCustomer)}
                        >
                            {showOnlyCustomer ? 'Alle Aussagen anzeigen' : 'Nur Kundenaussagen anzeigen'}
                        </Button>
                    </div>
                    <Card style={{ maxHeight: 400, overflow: 'auto' }}>
                        {call.transcript_object
                            .filter(entry => !showOnlyCustomer || entry.role === 'user' || entry.speaker === 'customer')
                            .map((entry, index) => (
                                <div key={index} style={{ marginBottom: 12 }}>
                                    <Text strong style={{ 
                                        color: entry.role === 'agent' || entry.speaker === 'agent' ? '#1890ff' : '#52c41a' 
                                    }}>
                                        {entry.role === 'agent' || entry.speaker === 'agent' ? 'Agent' : 'Kunde'}
                                        {entry.timestamp && ` (${entry.timestamp})`}:
                                    </Text>
                                    <Paragraph style={{ marginTop: 4, marginBottom: 0, marginLeft: 16 }}>
                                        {entry.content || entry.text || entry.message}
                                    </Paragraph>
                                </div>
                            ))}
                    </Card>
                </Space>
            );
        }

        // Fallback to simple transcript with basic parsing
        const lines = call.transcript.split('\n');
        const parsedTranscript = lines.map((line, index) => {
            const isAgent = line.toLowerCase().includes('agent:') || 
                           line.toLowerCase().includes('assistant:') ||
                           line.toLowerCase().includes('ai:');
            const isCustomer = line.toLowerCase().includes('customer:') || 
                              line.toLowerCase().includes('caller:') ||
                              line.toLowerCase().includes('user:');
            
            if (showOnlyCustomer && (isAgent || (!isCustomer && !isAgent))) {
                return null;
            }
            
            return (
                <div key={index} style={{ marginBottom: 8 }}>
                    <Text style={{ 
                        color: isAgent ? '#1890ff' : (isCustomer ? '#52c41a' : 'inherit')
                    }}>
                        {line}
                    </Text>
                </div>
            );
        }).filter(Boolean);

        return (
            <Space direction="vertical" style={{ width: '100%' }}>
                <div style={{ marginBottom: 16 }}>
                    <Button
                        type={showOnlyCustomer ? "primary" : "default"}
                        icon={<CustomerServiceOutlined />}
                        onClick={() => setShowOnlyCustomer(!showOnlyCustomer)}
                    >
                        {showOnlyCustomer ? 'Alle Aussagen anzeigen' : 'Nur Kundenaussagen anzeigen'}
                    </Button>
                </div>
                <Card style={{ maxHeight: 400, overflow: 'auto' }}>
                    {parsedTranscript.length > 0 ? parsedTranscript : (
                        <Paragraph>
                            <pre style={{ whiteSpace: 'pre-wrap', fontFamily: 'inherit' }}>
                                {call.transcript}
                            </pre>
                        </Paragraph>
                    )}
            </Card>
        );
    };

    // Render timeline
    const renderTimeline = () => {
        const items = [];

        // Call started
        items.push({
            color: 'green',
            children: (
                <div>
                    <Text strong>Anruf gestartet</Text>
                    <br />
                    <Text type="secondary">{call.created_at}</Text>
                </div>
            ),
        });

        // Status changes
        if (call.status_history) {
            call.status_history.forEach(history => {
                items.push({
                    color: 'blue',
                    children: (
                        <div>
                            <Text strong>Status geändert zu {history.status}</Text>
                            <br />
                            <Text type="secondary">
                                {history.created_at} von {history.user?.name || 'System'}
                            </Text>
                        </div>
                    ),
                });
            });
        }

        // Assignments
        if (call.assignment_history) {
            call.assignment_history.forEach(assignment => {
                items.push({
                    color: 'orange',
                    children: (
                        <div>
                            <Text strong>
                                Zugewiesen an {assignment.assigned_to?.name || 'Unbekannt'}
                            </Text>
                            <br />
                            <Text type="secondary">
                                {assignment.created_at} von {assignment.assigned_by?.name || 'System'}
                            </Text>
                            {assignment.notes && (
                                <>
                                    <br />
                                    <Text italic>{assignment.notes}</Text>
                                </>
                            )}
                        </div>
                    ),
                });
            });
        }

        // Notes
        if (call.notes) {
            call.notes.forEach(note => {
                items.push({
                    dot: <MessageOutlined />,
                    children: (
                        <div>
                            <Text strong>Notiz hinzugefügt</Text>
                            <br />
                            <Text>{note.content}</Text>
                            <br />
                            <Text type="secondary">
                                {note.created_at} von {note.user?.name || 'Unbekannt'}
                            </Text>
                        </div>
                    ),
                });
            });
        }

        // Call ended
        if (call.ended_at) {
            items.push({
                color: 'red',
                children: (
                    <div>
                        <Text strong>Anruf beendet</Text>
                        <br />
                        <Text type="secondary">{call.ended_at}</Text>
                    </div>
                ),
            });
        }

        return <Timeline items={items} />;
    };

    // Tab items
    const tabItems = [
        {
            key: 'overview',
            label: (
                <span>
                    <FileTextOutlined />
                    Übersicht
                </span>
            ),
            children: (
                <Space direction="vertical" style={{ width: '100%' }} size="large">
                    <Descriptions bordered column={1}>
                        <Descriptions.Item label="Anrufer">
                            <Space>
                                <PhoneOutlined />
                                <Text copyable>{call.from_number}</Text>
                                {call.customer && (
                                    <Tag icon={<UserOutlined />}>
                                        {call.customer.name}
                                    </Tag>
                                )}
                            </Space>
                        </Descriptions.Item>
                        <Descriptions.Item label="Empfänger">
                            <Space>
                                <PhoneOutlined />
                                <Text>{call.to_number || '-'}</Text>
                            </Space>
                        </Descriptions.Item>
                        <Descriptions.Item label="Status">
                            <Tag color={getStatusColor(call.status)}>
                                {call.status}
                            </Tag>
                        </Descriptions.Item>
                        <Descriptions.Item label="Filiale">
                            <Space>
                                <BranchesOutlined />
                                {call.branch?.name || '-'}
                            </Space>
                        </Descriptions.Item>
                        <Descriptions.Item label="Datum & Zeit">
                            <Space>
                                <CalendarOutlined />
                                {call.created_at}
                            </Space>
                        </Descriptions.Item>
                        <Descriptions.Item label="Dauer">
                            <Space>
                                <ClockCircleOutlined />
                                {formatDuration(call.duration_sec)}
                            </Space>
                        </Descriptions.Item>
                        {permissions['billing.view'] && (
                            <Descriptions.Item label="Kosten">
                                <Space>
                                    <DollarOutlined />
                                    {call.total_cost ? `${call.total_cost} €` : '-'}
                                </Space>
                            </Descriptions.Item>
                        )}
                        <Descriptions.Item label="Zugewiesen an">
                            <Space>
                                <TeamOutlined />
                                {call.assigned_to?.name || 'Nicht zugewiesen'}
                            </Space>
                        </Descriptions.Item>
                    </Descriptions>

                    {(call.summary || call.call_summary) && (
                        <Card 
                            title={
                                <Space>
                                    <span>Zusammenfassung</span>
                                    {call.detected_language && call.detected_language !== 'de' && (
                                        <Tag color="blue" icon={<GlobalOutlined />}>
                                            {call.detected_language.toUpperCase()}
                                        </Tag>
                                    )}
                                </Space>
                            }
                            size="small"
                            extra={
                                call.detected_language && call.detected_language !== 'de' && (
                                    <Button
                                        size="small"
                                        icon={<TranslationOutlined />}
                                        loading={translatingS}
                                        onClick={translateSummary}
                                    >
                                        {showTranslated ? 'Original anzeigen' : 'Übersetzen'}
                                    </Button>
                                )
                            }
                        >
                            <Spin spinning={translatingS}>
                                <Paragraph>
                                    {showTranslated && translatedSummary 
                                        ? translatedSummary 
                                        : (call.summary || call.call_summary)}
                                </Paragraph>
                                {showTranslated && translatedSummary && (
                                    <div style={{ marginTop: 8 }}>
                                        <Button 
                                            size="small" 
                                            type="link"
                                            onClick={() => setShowTranslated(false)}
                                        >
                                            Original anzeigen
                                        </Button>
                                    </div>
                                )}
                            </Spin>
                        </Card>
                    )}

                    {call.extracted_data && (
                        <Card title="Extrahierte Daten" size="small">
                            <Descriptions size="small" column={1}>
                                {Object.entries(call.extracted_data).map(([key, value]) => (
                                    <Descriptions.Item key={key} label={key}>
                                        {typeof value === 'object' ? JSON.stringify(value) : value}
                                    </Descriptions.Item>
                                ))}
                            </Descriptions>
                        </Card>
                    )}
                </Space>
            ),
        },
        {
            key: 'transcript',
            label: (
                <span>
                    <FileTextOutlined />
                    Transkript
                </span>
            ),
            children: renderTranscript(),
        },
        {
            key: 'timeline',
            label: (
                <span>
                    <HistoryOutlined />
                    Verlauf
                </span>
            ),
            children: renderTimeline(),
        },
        {
            key: 'notes',
            label: (
                <Badge count={call.notes?.length || 0}>
                    <span>
                        <MessageOutlined />
                        Notizen
                    </span>
                </Badge>
            ),
            children: (
                <List
                    dataSource={call.notes || []}
                    renderItem={note => (
                        <List.Item>
                            <List.Item.Meta
                                avatar={<Avatar icon={<UserOutlined />} />}
                                title={
                                    <Space>
                                        <Text strong>{note.user?.name || 'Unbekannt'}</Text>
                                        <Text type="secondary">{note.created_at}</Text>
                                    </Space>
                                }
                                description={note.content}
                            />
                        </List.Item>
                    )}
                    locale={{ emptyText: 'Keine Notizen vorhanden' }}
                />
            ),
        },
    ];

    // Add analytics tab if available
    if (call.analytics) {
        tabItems.push({
            key: 'analytics',
            label: (
                <span>
                    <BarChartOutlined />
                    Analyse
                </span>
            ),
            children: (
                <Row gutter={16}>
                    <Col span={8}>
                        <Card>
                            <Statistic
                                title="Stimmung"
                                value={call.analytics.sentiment || 'Neutral'}
                                prefix={
                                    call.analytics.sentiment === 'positive' ? 
                                        <CheckCircleOutlined style={{ color: '#52c41a' }} /> :
                                    call.analytics.sentiment === 'negative' ?
                                        <CloseCircleOutlined style={{ color: '#ff4d4f' }} /> :
                                        <ExclamationCircleOutlined style={{ color: '#faad14' }} />
                                }
                            />
                        </Card>
                    </Col>
                    <Col span={8}>
                        <Card>
                            <Statistic
                                title="Anrufqualität"
                                value={call.analytics.quality_score || 0}
                                suffix="/ 100"
                            />
                        </Card>
                    </Col>
                    <Col span={8}>
                        <Card>
                            <Statistic
                                title="Kundenzufriedenheit"
                                value={call.analytics.satisfaction_score || 0}
                                suffix="/ 5"
                            />
                        </Card>
                    </Col>
                </Row>
            ),
        });
    }

    // Enhanced audio player component
    const renderEnhancedAudioPlayer = () => {
        if (!call.recording_url) {
            return null;
        }

        return (
            <Card 
                size="small" 
                title={
                    <Space>
                        <AudioOutlined />
                        <Text>Anrufaufzeichnung</Text>
                    </Space>
                }
                style={{ marginBottom: 16 }}
            >
                <Space direction="vertical" style={{ width: '100%' }}>
                    <audio 
                        controls 
                        style={{ 
                            width: '100%',
                            height: '50px',
                            outline: 'none'
                        }}
                    >
                        <source src={call.recording_url} type="audio/mpeg" />
                        <source src={call.recording_url} type="audio/wav" />
                        <source src={call.recording_url} type="audio/ogg" />
                        Ihr Browser unterstützt kein Audio.
                    </audio>
                    <Row justify="space-between" align="middle">
                        <Col>
                            <Space>
                                <Text type="secondary">
                                    <ClockCircleOutlined /> Dauer: {formatDuration(call.duration_sec)}
                                </Text>
                                {call.detected_language && (
                                    <Tag color="blue">
                                        Sprache: {call.detected_language}
                                    </Tag>
                                )}
                            </Space>
                        </Col>
                        <Col>
                            {permissions['calls.download_recording'] && (
                                <Button 
                                    size="small"
                                    icon={<DownloadOutlined />}
                                    href={call.recording_url}
                                    download={`anruf_${call.id}_${dayjs(call.created_at).format('YYYY-MM-DD')}.mp3`}
                                >
                                    Download
                                </Button>
                            )}
                        </Col>
                    </Row>
                </Space>
            </Card>
        );
    };

    return (
        <>
            <Space direction="vertical" style={{ width: '100%' }} size="large">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Title level={4}>
                        <PhoneOutlined /> Anruf #{call.id}
                    </Title>
                    <Space>
                        {permissions['calls.edit'] && (
                            <Button onClick={() => {
                                form.setFieldsValue({
                                    status: call.status,
                                    assigned_to_id: call.assigned_to?.id,
                                    urgency: call.urgency,
                                });
                                setEditModalVisible(true);
                            }}>
                                Bearbeiten
                            </Button>
                        )}
                        <Button onClick={onClose}>
                            Schließen
                        </Button>
                    </Space>
                </div>

                {/* Audio Player above tabs */}
                {renderEnhancedAudioPlayer()}

                <Tabs 
                    activeKey={activeTab} 
                    onChange={setActiveTab}
                    items={tabItems}
                />
            </Space>

            <Modal
                title="Anrufdetails bearbeiten"
                open={editModalVisible}
                onCancel={() => setEditModalVisible(false)}
                onOk={() => form.submit()}
                confirmLoading={loading}
            >
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleEditSubmit}
                >
                    <Form.Item
                        name="status"
                        label="Status"
                        rules={[{ required: true }]}
                    >
                        <Select>
                            <Select.Option value="scheduled">Geplant</Select.Option>
                            <Select.Option value="in_progress">In Bearbeitung</Select.Option>
                            <Select.Option value="completed">Abgeschlossen</Select.Option>
                            <Select.Option value="cancelled">Abgebrochen</Select.Option>
                            <Select.Option value="no_show">Nicht erschienen</Select.Option>
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="assigned_to_id"
                        label="Zugewiesen an"
                    >
                        <Select allowClear placeholder="Mitarbeiter auswählen">
                            {/* Staff options would be loaded here */}
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="urgency"
                        label="Dringlichkeit"
                    >
                        <Select allowClear>
                            <Select.Option value="low">Niedrig</Select.Option>
                            <Select.Option value="medium">Mittel</Select.Option>
                            <Select.Option value="high">Hoch</Select.Option>
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="notes"
                        label="Notiz hinzufügen"
                    >
                        <TextArea rows={3} placeholder="Optional: Notiz zu dieser Änderung" />
                    </Form.Item>
                </Form>
            </Modal>
        </>
    );
};

export default CallDetailView;