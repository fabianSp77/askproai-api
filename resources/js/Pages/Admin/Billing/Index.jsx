import React, { useState, useEffect, useCallback } from 'react';
import { 
    Card, 
    Table, 
    Button, 
    Space, 
    Typography, 
    Tag, 
    Tabs, 
    Input, 
    Select, 
    DatePicker, 
    Modal, 
    Form, 
    InputNumber, 
    message, 
    Tooltip, 
    Row, 
    Col, 
    Statistic, 
    Progress, 
    Switch, 
    Dropdown, 
    Badge,
    Alert,
    Descriptions,
    Divider,
    Popconfirm,
    Checkbox
} from 'antd';
import { 
    DollarOutlined, 
    CreditCardOutlined, 
    FileTextOutlined, 
    HistoryOutlined, 
    WarningOutlined, 
    PlusOutlined, 
    DownloadOutlined, 
    ReloadOutlined, 
    SettingOutlined,
    RiseOutlined,
    FallOutlined,
    BankOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    SyncOutlined,
    MailOutlined,
    FilePdfOutlined,
    EuroOutlined
} from '@ant-design/icons';
import adminAxios from '../../../services/adminAxios';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

const { Title, Text } = Typography;
const { RangePicker } = DatePicker;
const { Option } = Select;
const { TabPane } = Tabs;

const BillingIndex = () => {
    // States
    const [loading, setLoading] = useState(false);
    const [activeTab, setActiveTab] = useState('overview');
    const [overview, setOverview] = useState(null);
    const [balances, setBalances] = useState([]);
    const [invoices, setInvoices] = useState([]);
    const [topups, setTopups] = useState([]);
    const [transactions, setTransactions] = useState([]);
    const [callCharges, setCallCharges] = useState([]);
    const [companies, setCompanies] = useState([]);
    
    // Filters
    const [filters, setFilters] = useState({
        company_id: null,
        search: '',
        status: null,
        date_range: [null, null],
        low_balance_only: false,
        auto_topup: null,
        billable_only: false
    });
    
    // Pagination
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 20,
        total: 0
    });
    
    // Modals
    const [topupModalVisible, setTopupModalVisible] = useState(false);
    const [chargeModalVisible, setChargeModalVisible] = useState(false);
    const [settingsModalVisible, setSettingsModalVisible] = useState(false);
    const [selectedBalance, setSelectedBalance] = useState(null);
    
    // Forms
    const [topupForm] = Form.useForm();
    const [chargeForm] = Form.useForm();
    const [settingsForm] = Form.useForm();

    // Fetch functions
    const fetchOverview = useCallback(async () => {
        try {
            const response = await adminAxios.get('/billing/overview', {
                params: { company_id: filters.company_id }
            });
            setOverview(response.data);
        } catch (error) {
            console.error('Error fetching overview:', error);
        }
    }, [filters.company_id]);

    const fetchBalances = useCallback(async () => {
        setLoading(true);
        try {
            const response = await adminAxios.get('/billing/balances', {
                params: {
                    ...filters,
                    page: pagination.current,
                    per_page: pagination.pageSize
                }
            });
            setBalances(response.data.data);
            setPagination(prev => ({ ...prev, total: response.data.total }));
        } catch (error) {
            message.error('Fehler beim Laden der Guthaben');
        } finally {
            setLoading(false);
        }
    }, [filters, pagination.current, pagination.pageSize]);

    const fetchInvoices = useCallback(async () => {
        setLoading(true);
        try {
            const params = {
                ...filters,
                page: pagination.current,
                per_page: pagination.pageSize
            };
            if (filters.date_range[0] && filters.date_range[1]) {
                params.date_from = filters.date_range[0].format('YYYY-MM-DD');
                params.date_to = filters.date_range[1].format('YYYY-MM-DD');
            }
            const response = await adminAxios.get('/billing/invoices', { params });
            setInvoices(response.data.data);
            setPagination(prev => ({ ...prev, total: response.data.total }));
        } catch (error) {
            message.error('Fehler beim Laden der Rechnungen');
        } finally {
            setLoading(false);
        }
    }, [filters, pagination.current, pagination.pageSize]);

    const fetchTopups = useCallback(async () => {
        setLoading(true);
        try {
            const params = {
                ...filters,
                page: pagination.current,
                per_page: pagination.pageSize
            };
            if (filters.date_range[0] && filters.date_range[1]) {
                params.date_from = filters.date_range[0].format('YYYY-MM-DD');
                params.date_to = filters.date_range[1].format('YYYY-MM-DD');
            }
            const response = await adminAxios.get('/billing/topups', { params });
            setTopups(response.data.data);
            setPagination(prev => ({ ...prev, total: response.data.total }));
        } catch (error) {
            message.error('Fehler beim Laden der Aufladungen');
        } finally {
            setLoading(false);
        }
    }, [filters, pagination.current, pagination.pageSize]);

    const fetchTransactions = useCallback(async () => {
        setLoading(true);
        try {
            const params = {
                ...filters,
                page: pagination.current,
                per_page: 50
            };
            if (filters.date_range[0] && filters.date_range[1]) {
                params.date_from = filters.date_range[0].format('YYYY-MM-DD');
                params.date_to = filters.date_range[1].format('YYYY-MM-DD');
            }
            const response = await adminAxios.get('/billing/transactions', { params });
            setTransactions(response.data.data);
            setPagination(prev => ({ ...prev, total: response.data.total }));
        } catch (error) {
            message.error('Fehler beim Laden der Transaktionen');
        } finally {
            setLoading(false);
        }
    }, [filters, pagination.current]);

    const fetchCallCharges = useCallback(async () => {
        setLoading(true);
        try {
            const params = {
                ...filters,
                page: pagination.current,
                per_page: 50
            };
            if (filters.date_range[0] && filters.date_range[1]) {
                params.date_from = filters.date_range[0].format('YYYY-MM-DD');
                params.date_to = filters.date_range[1].format('YYYY-MM-DD');
            }
            const response = await adminAxios.get('/billing/call-charges', { params });
            setCallCharges(response.data.data);
            setPagination(prev => ({ ...prev, total: response.data.total }));
        } catch (error) {
            message.error('Fehler beim Laden der Anrufgebühren');
        } finally {
            setLoading(false);
        }
    }, [filters, pagination.current]);

    const fetchCompanies = async () => {
        try {
            const response = await adminAxios.get('/companies');
            setCompanies(response.data.data || []);
        } catch (error) {
            console.error('Error fetching companies:', error);
        }
    };

    // Effects
    useEffect(() => {
        fetchOverview();
        fetchCompanies();
    }, []);

    useEffect(() => {
        switch (activeTab) {
            case 'overview':
                fetchOverview();
                break;
            case 'balances':
                fetchBalances();
                break;
            case 'invoices':
                fetchInvoices();
                break;
            case 'topups':
                fetchTopups();
                break;
            case 'transactions':
                fetchTransactions();
                break;
            case 'charges':
                fetchCallCharges();
                break;
        }
    }, [activeTab, filters, pagination.current, pagination.pageSize]);

    // Handlers
    const handleCreateTopup = async (values) => {
        try {
            await adminAxios.post('/billing/topups', values);
            message.success('Aufladung erfolgreich erstellt');
            setTopupModalVisible(false);
            topupForm.resetFields();
            fetchBalances();
            fetchOverview();
        } catch (error) {
            message.error('Fehler beim Erstellen der Aufladung');
        }
    };

    const handleCreateCharge = async (values) => {
        try {
            await adminAxios.post('/billing/charges', values);
            message.success('Belastung erfolgreich erstellt');
            setChargeModalVisible(false);
            chargeForm.resetFields();
            fetchBalances();
            fetchOverview();
        } catch (error) {
            message.error(error.response?.data?.error || 'Fehler beim Erstellen der Belastung');
        }
    };

    const handleUpdateSettings = async (values) => {
        try {
            await adminAxios.put(`/api/admin/billing/balances/${selectedBalance.id}/settings`, values);
            message.success('Einstellungen erfolgreich aktualisiert');
            setSettingsModalVisible(false);
            fetchBalances();
        } catch (error) {
            message.error('Fehler beim Aktualisieren der Einstellungen');
        }
    };

    const handleMarkInvoiceAsPaid = async (invoiceId) => {
        try {
            await adminAxios.post(`/api/admin/billing/invoices/${invoiceId}/mark-paid`);
            message.success('Rechnung als bezahlt markiert');
            fetchInvoices();
        } catch (error) {
            message.error('Fehler beim Markieren der Rechnung');
        }
    };

    const handleResendInvoice = async (invoiceId) => {
        try {
            await adminAxios.post(`/api/admin/billing/invoices/${invoiceId}/resend`);
            message.success('Rechnung erfolgreich erneut gesendet');
            fetchInvoices();
        } catch (error) {
            message.error('Fehler beim Senden der Rechnung');
        }
    };

    const handleExportTransactions = async () => {
        try {
            const params = { ...filters };
            if (filters.date_range[0] && filters.date_range[1]) {
                params.date_from = filters.date_range[0].format('YYYY-MM-DD');
                params.date_to = filters.date_range[1].format('YYYY-MM-DD');
            } else {
                message.warning('Bitte wählen Sie einen Datumsbereich für den Export');
                return;
            }
            
            const response = await adminAxios.get('/billing/export-transactions', { params });
            
            // Create CSV
            const csvContent = [
                response.data.headers.join(','),
                ...response.data.rows.map(row => row.join(','))
            ].join('\n');
            
            // Download
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = response.data.filename;
            a.click();
            window.URL.revokeObjectURL(url);
            
            message.success('Export erfolgreich');
        } catch (error) {
            message.error('Fehler beim Exportieren');
        }
    };

    // Table columns
    const balanceColumns = [
        {
            title: 'Unternehmen',
            dataIndex: ['company', 'name'],
            key: 'company',
            render: (text, record) => (
                <Space direction="vertical" size="small">
                    <Text strong>{text}</Text>
                    <Text type="secondary" style={{ fontSize: '12px' }}>
                        {record.company.email}
                    </Text>
                </Space>
            )
        },
        {
            title: 'Guthaben',
            key: 'balance',
            render: (_, record) => (
                <Space direction="vertical" size="small">
                    <Space>
                        <EuroOutlined />
                        <Text strong>{record.balance.toFixed(2)}</Text>
                        {record.bonus_balance > 0 && (
                            <Tag color="gold">+{record.bonus_balance.toFixed(2)} Bonus</Tag>
                        )}
                    </Space>
                    {record.reserved_balance > 0 && (
                        <Text type="secondary" style={{ fontSize: '12px' }}>
                            Reserviert: €{record.reserved_balance.toFixed(2)}
                        </Text>
                    )}
                </Space>
            )
        },
        {
            title: 'Verfügbar',
            key: 'available',
            render: (_, record) => (
                <Space>
                    <Text strong style={{ color: record.available_balance < 10 ? '#ff4d4f' : '#52c41a' }}>
                        €{record.available_balance.toFixed(2)}
                    </Text>
                    {record.is_low_balance && (
                        <Tag icon={<WarningOutlined />} color="error">Niedrig</Tag>
                    )}
                </Space>
            )
        },
        {
            title: 'Auto-Aufladung',
            key: 'auto_topup',
            render: (_, record) => (
                <Space direction="vertical" size="small">
                    <Switch 
                        checked={record.auto_topup_enabled}
                        checkedChildren="Aktiv"
                        unCheckedChildren="Inaktiv"
                        disabled
                    />
                    {record.auto_topup_enabled && (
                        <Text type="secondary" style={{ fontSize: '12px' }}>
                            Schwelle: €{record.auto_topup_threshold} → €{record.auto_topup_amount}
                        </Text>
                    )}
                </Space>
            )
        },
        {
            title: 'Monatliche Nutzung',
            dataIndex: 'monthly_usage',
            key: 'monthly_usage',
            render: (value) => `€${value.toFixed(2)}`
        },
        {
            title: 'Letzte Aufladung',
            dataIndex: 'last_topup',
            key: 'last_topup',
            render: (date) => date ? dayjs(date).format('DD.MM.YYYY HH:mm') : '-'
        },
        {
            title: 'Aktionen',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    <Button 
                        size="small" 
                        icon={<PlusOutlined />}
                        onClick={() => {
                            topupForm.setFieldsValue({ company_id: record.company.id });
                            setTopupModalVisible(true);
                        }}
                    >
                        Aufladen
                    </Button>
                    <Button
                        size="small"
                        icon={<SettingOutlined />}
                        onClick={() => {
                            setSelectedBalance(record);
                            settingsForm.setFieldsValue({
                                low_balance_threshold: record.low_balance_threshold,
                                auto_topup_enabled: record.auto_topup_enabled,
                                auto_topup_threshold: record.auto_topup_threshold,
                                auto_topup_amount: record.auto_topup_amount,
                                auto_topup_monthly_limit: record.auto_topup_monthly_limit
                            });
                            setSettingsModalVisible(true);
                        }}
                    >
                        Einstellungen
                    </Button>
                </Space>
            )
        }
    ];

    const invoiceColumns = [
        {
            title: 'Rechnungsnr.',
            dataIndex: 'number',
            key: 'number',
            render: (text) => <Text strong>{text}</Text>
        },
        {
            title: 'Unternehmen',
            dataIndex: ['company', 'name'],
            key: 'company'
        },
        {
            title: 'Datum',
            dataIndex: 'invoice_date',
            key: 'invoice_date',
            render: (date) => dayjs(date).format('DD.MM.YYYY')
        },
        {
            title: 'Fällig',
            dataIndex: 'due_date',
            key: 'due_date',
            render: (date, record) => (
                <Space>
                    <Text>{dayjs(date).format('DD.MM.YYYY')}</Text>
                    {record.status !== 'paid' && dayjs(date).isBefore(dayjs()) && (
                        <Tag color="error">Überfällig</Tag>
                    )}
                </Space>
            )
        },
        {
            title: 'Betrag',
            dataIndex: 'total',
            key: 'total',
            render: (amount) => `€${amount.toFixed(2)}`
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (status) => {
                const config = {
                    draft: { color: 'default', text: 'Entwurf' },
                    sent: { color: 'processing', text: 'Gesendet' },
                    paid: { color: 'success', text: 'Bezahlt' },
                    cancelled: { color: 'error', text: 'Storniert' },
                    unpaid: { color: 'warning', text: 'Unbezahlt' }
                };
                return <Tag color={config[status]?.color}>{config[status]?.text || status}</Tag>;
            }
        },
        {
            title: 'Aktionen',
            key: 'actions',
            render: (_, record) => (
                <Dropdown
                    menu={{
                        items: [
                            {
                                key: 'view',
                                label: 'PDF anzeigen',
                                icon: <FilePdfOutlined />
                            },
                            {
                                key: 'download',
                                label: 'PDF herunterladen',
                                icon: <DownloadOutlined />
                            },
                            {
                                type: 'divider'
                            },
                            {
                                key: 'resend',
                                label: 'Erneut senden',
                                icon: <MailOutlined />,
                                onClick: () => handleResendInvoice(record.id),
                                disabled: record.status === 'draft'
                            },
                            {
                                key: 'mark-paid',
                                label: 'Als bezahlt markieren',
                                icon: <CheckCircleOutlined />,
                                onClick: () => handleMarkInvoiceAsPaid(record.id),
                                disabled: record.status === 'paid'
                            }
                        ]
                    }}
                >
                    <Button size="small">Aktionen</Button>
                </Dropdown>
            )
        }
    ];

    const topupColumns = [
        {
            title: 'Datum',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => dayjs(date).format('DD.MM.YYYY HH:mm')
        },
        {
            title: 'Unternehmen',
            dataIndex: ['company', 'name'],
            key: 'company'
        },
        {
            title: 'Betrag',
            dataIndex: 'amount',
            key: 'amount',
            render: (amount) => <Text strong>€{amount.toFixed(2)}</Text>
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (status) => {
                const config = {
                    pending: { color: 'processing', text: 'Ausstehend', icon: <ClockCircleOutlined /> },
                    processing: { color: 'processing', text: 'In Bearbeitung', icon: <SyncOutlined spin /> },
                    succeeded: { color: 'success', text: 'Erfolgreich', icon: <CheckCircleOutlined /> },
                    failed: { color: 'error', text: 'Fehlgeschlagen', icon: <CloseCircleOutlined /> },
                    cancelled: { color: 'default', text: 'Abgebrochen', icon: <CloseCircleOutlined /> }
                };
                const { color, text, icon } = config[status] || {};
                return <Tag color={color} icon={icon}>{text || status}</Tag>;
            }
        },
        {
            title: 'Typ',
            key: 'type',
            render: (_, record) => {
                const isManual = record.metadata?.type === 'manual';
                const isBonus = record.metadata?.is_bonus;
                return (
                    <Space>
                        <Tag color={isManual ? 'blue' : 'green'}>
                            {isManual ? 'Manuell' : 'Automatisch'}
                        </Tag>
                        {isBonus && <Tag color="gold">Bonus</Tag>}
                    </Space>
                );
            }
        },
        {
            title: 'Initiiert von',
            dataIndex: ['initiatedBy', 'email'],
            key: 'initiatedBy',
            render: (email, record) => email || record.metadata?.created_by || '-'
        },
        {
            title: 'Bezahlt am',
            dataIndex: 'paid_at',
            key: 'paid_at',
            render: (date) => date ? dayjs(date).format('DD.MM.YYYY HH:mm') : '-'
        }
    ];

    const transactionColumns = [
        {
            title: 'Datum',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => dayjs(date).format('DD.MM.YYYY HH:mm:ss')
        },
        {
            title: 'Unternehmen',
            dataIndex: ['company', 'name'],
            key: 'company'
        },
        {
            title: 'Typ',
            dataIndex: 'type',
            key: 'type',
            render: (type) => {
                const config = {
                    topup: { color: 'success', text: 'Aufladung', icon: <RiseOutlined /> },
                    charge: { color: 'error', text: 'Belastung', icon: <FallOutlined /> },
                    bonus: { color: 'gold', text: 'Bonus', icon: <BankOutlined /> },
                    reservation: { color: 'processing', text: 'Reservierung' },
                    release: { color: 'default', text: 'Freigabe' }
                };
                const { color, text, icon } = config[type] || {};
                return <Tag color={color} icon={icon}>{text || type}</Tag>;
            }
        },
        {
            title: 'Beschreibung',
            dataIndex: 'description',
            key: 'description',
            ellipsis: true
        },
        {
            title: 'Betrag',
            dataIndex: 'amount',
            key: 'amount',
            render: (amount) => (
                <Text strong style={{ color: amount > 0 ? '#52c41a' : '#ff4d4f' }}>
                    {amount > 0 ? '+' : ''}€{Math.abs(amount).toFixed(2)}
                </Text>
            )
        },
        {
            title: 'Saldo vorher',
            dataIndex: 'balance_before',
            key: 'balance_before',
            render: (amount) => `€${amount.toFixed(2)}`
        },
        {
            title: 'Saldo nachher',
            dataIndex: 'balance_after',
            key: 'balance_after',
            render: (amount) => `€${amount.toFixed(2)}`
        }
    ];

    const callChargeColumns = [
        {
            title: 'Datum',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => dayjs(date).format('DD.MM.YYYY HH:mm')
        },
        {
            title: 'Unternehmen',
            dataIndex: ['company', 'name'],
            key: 'company'
        },
        {
            title: 'Anruf',
            key: 'call',
            render: (_, record) => (
                <Space direction="vertical" size="small">
                    <Text>ID: {record.call?.id || record.call_id}</Text>
                    {record.call?.customer && (
                        <Text type="secondary" style={{ fontSize: '12px' }}>
                            {record.call.customer.name} ({record.call.customer.phone})
                        </Text>
                    )}
                </Space>
            )
        },
        {
            title: 'Dauer',
            dataIndex: 'duration_seconds',
            key: 'duration',
            render: (seconds) => {
                const minutes = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${minutes}:${secs.toString().padStart(2, '0')} Min`;
            }
        },
        {
            title: 'Kosten',
            key: 'costs',
            render: (_, record) => (
                <Space direction="vertical" size="small">
                    <Text>Minuten: €{record.minutes_charge.toFixed(2)}</Text>
                    <Text>KI: €{record.ai_charge.toFixed(2)}</Text>
                    <Text strong>Total: €{record.total_charge.toFixed(2)}</Text>
                </Space>
            )
        },
        {
            title: 'Abrechenbar',
            dataIndex: 'is_billable',
            key: 'is_billable',
            render: (billable) => (
                <Tag color={billable ? 'success' : 'default'}>
                    {billable ? 'Ja' : 'Nein'}
                </Tag>
            )
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (status) => {
                const config = {
                    charged: { color: 'success', text: 'Berechnet' },
                    reserved: { color: 'processing', text: 'Reserviert' },
                    failed: { color: 'error', text: 'Fehlgeschlagen' }
                };
                return <Tag color={config[status]?.color}>{config[status]?.text || status}</Tag>;
            }
        }
    ];

    return (
        <div>
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col span={24}>
                    <Title level={2}>
                        <DollarOutlined /> Abrechnungsverwaltung
                    </Title>
                </Col>
            </Row>

            <Tabs activeKey={activeTab} onChange={setActiveTab}>
                <TabPane tab="Übersicht" key="overview">
                    {overview && (
                        <>
                            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                                <Col xs={24} sm={12} md={6}>
                                    <Card>
                                        <Statistic
                                            title="Gesamtguthaben"
                                            value={overview.total_balance}
                                            precision={2}
                                            prefix="€"
                                            valueStyle={{ color: '#3f8600' }}
                                        />
                                        {overview.total_bonus_balance > 0 && (
                                            <Text type="secondary">
                                                + €{overview.total_bonus_balance.toFixed(2)} Bonus
                                            </Text>
                                        )}
                                    </Card>
                                </Col>
                                <Col xs={24} sm={12} md={6}>
                                    <Card>
                                        <Statistic
                                            title="Umsatz heute"
                                            value={overview.revenue_today}
                                            precision={2}
                                            prefix="€"
                                        />
                                    </Card>
                                </Col>
                                <Col xs={24} sm={12} md={6}>
                                    <Card>
                                        <Statistic
                                            title="Umsatz Monat"
                                            value={overview.revenue_mtd}
                                            precision={2}
                                            prefix="€"
                                        />
                                    </Card>
                                </Col>
                                <Col xs={24} sm={12} md={6}>
                                    <Card>
                                        <Statistic
                                            title="Unbezahlte Rechnungen"
                                            value={overview.unpaid_invoices}
                                            suffix={overview.unpaid_invoices > 0 ? <WarningOutlined style={{ color: '#ff4d4f' }} /> : null}
                                            valueStyle={{ color: overview.unpaid_invoices > 0 ? '#ff4d4f' : '#52c41a' }}
                                        />
                                    </Card>
                                </Col>
                            </Row>
                            
                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={8}>
                                    <Card title="Unternehmen-Status">
                                        <Space direction="vertical" style={{ width: '100%' }}>
                                            <div>
                                                <Text>Aktive Unternehmen</Text>
                                                <Progress 
                                                    percent={Math.round((overview.active_companies / (overview.active_companies + (overview.low_balance_count || 0))) * 100)}
                                                    status="active"
                                                    format={() => overview.active_companies}
                                                />
                                            </div>
                                            <div>
                                                <Text>Niedriges Guthaben</Text>
                                                <Progress 
                                                    percent={overview.low_balance_count > 0 ? 100 : 0}
                                                    status="exception"
                                                    format={() => overview.low_balance_count}
                                                    strokeColor="#ff4d4f"
                                                />
                                            </div>
                                            <div>
                                                <Text>Auto-Aufladung aktiv</Text>
                                                <Progress 
                                                    percent={Math.round((overview.auto_topup_enabled / overview.active_companies) * 100)}
                                                    format={() => overview.auto_topup_enabled}
                                                />
                                            </div>
                                        </Space>
                                    </Card>
                                </Col>
                                <Col xs={24} md={16}>
                                    <Card 
                                        title="Schnellaktionen"
                                        extra={
                                            <Button 
                                                icon={<ReloadOutlined />} 
                                                onClick={fetchOverview}
                                            >
                                                Aktualisieren
                                            </Button>
                                        }
                                    >
                                        <Space size="large" wrap>
                                            <Button 
                                                type="primary"
                                                icon={<PlusOutlined />}
                                                onClick={() => setTopupModalVisible(true)}
                                            >
                                                Manuelle Aufladung
                                            </Button>
                                            <Button 
                                                icon={<CreditCardOutlined />}
                                                onClick={() => setChargeModalVisible(true)}
                                            >
                                                Manuelle Belastung
                                            </Button>
                                            <Button 
                                                icon={<FileTextOutlined />}
                                                onClick={() => setActiveTab('invoices')}
                                            >
                                                Rechnungen anzeigen
                                            </Button>
                                            <Button 
                                                icon={<HistoryOutlined />}
                                                onClick={() => setActiveTab('transactions')}
                                            >
                                                Transaktionen anzeigen
                                            </Button>
                                        </Space>
                                        
                                        {overview.low_balance_count > 0 && (
                                            <Alert
                                                message="Achtung: Niedriges Guthaben"
                                                description={`${overview.low_balance_count} Unternehmen haben ein niedriges Guthaben und sollten aufgeladen werden.`}
                                                type="warning"
                                                showIcon
                                                style={{ marginTop: 16 }}
                                                action={
                                                    <Button 
                                                        size="small" 
                                                        onClick={() => {
                                                            setFilters(prev => ({ ...prev, low_balance_only: true }));
                                                            setActiveTab('balances');
                                                        }}
                                                    >
                                                        Anzeigen
                                                    </Button>
                                                }
                                            />
                                        )}
                                    </Card>
                                </Col>
                            </Row>
                        </>
                    )}
                </TabPane>

                <TabPane 
                    tab={
                        <Badge count={overview?.low_balance_count || 0} offset={[10, 0]}>
                            <span>Guthaben</span>
                        </Badge>
                    } 
                    key="balances"
                >
                    <Card>
                        <Space style={{ marginBottom: 16 }} wrap>
                            <Input.Search
                                placeholder="Suche Unternehmen..."
                                value={filters.search}
                                onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
                                style={{ width: 200 }}
                            />
                            <Select
                                placeholder="Alle Unternehmen"
                                value={filters.company_id}
                                onChange={(value) => setFilters(prev => ({ ...prev, company_id: value }))}
                                style={{ width: 200 }}
                                allowClear
                            >
                                {companies.map(company => (
                                    <Option key={company.id} value={company.id}>
                                        {company.name}
                                    </Option>
                                ))}
                            </Select>
                            <Button
                                type={filters.low_balance_only ? 'primary' : 'default'}
                                danger={filters.low_balance_only}
                                icon={<WarningOutlined />}
                                onClick={() => setFilters(prev => ({ ...prev, low_balance_only: !prev.low_balance_only }))}
                            >
                                Nur niedriges Guthaben
                            </Button>
                            <Select
                                placeholder="Auto-Aufladung"
                                value={filters.auto_topup}
                                onChange={(value) => setFilters(prev => ({ ...prev, auto_topup: value }))}
                                style={{ width: 150 }}
                                allowClear
                            >
                                <Option value={true}>Aktiviert</Option>
                                <Option value={false}>Deaktiviert</Option>
                            </Select>
                            <Button icon={<ReloadOutlined />} onClick={fetchBalances}>Aktualisieren</Button>
                        </Space>

                        <Table
                            columns={balanceColumns}
                            dataSource={balances}
                            loading={loading}
                            rowKey="id"
                            pagination={{
                                ...pagination,
                                showSizeChanger: true,
                                showTotal: (total) => `Gesamt: ${total} Einträge`
                            }}
                            onChange={(newPagination) => setPagination(newPagination)}
                        />
                    </Card>
                </TabPane>

                <TabPane tab="Rechnungen" key="invoices">
                    <Card>
                        <Space style={{ marginBottom: 16 }} wrap>
                            <Input.Search
                                placeholder="Suche Rechnung..."
                                value={filters.search}
                                onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
                                style={{ width: 200 }}
                            />
                            <Select
                                placeholder="Alle Unternehmen"
                                value={filters.company_id}
                                onChange={(value) => setFilters(prev => ({ ...prev, company_id: value }))}
                                style={{ width: 200 }}
                                allowClear
                            >
                                {companies.map(company => (
                                    <Option key={company.id} value={company.id}>
                                        {company.name}
                                    </Option>
                                ))}
                            </Select>
                            <Select
                                placeholder="Status"
                                value={filters.status}
                                onChange={(value) => setFilters(prev => ({ ...prev, status: value }))}
                                style={{ width: 150 }}
                                allowClear
                            >
                                <Option value="draft">Entwurf</Option>
                                <Option value="sent">Gesendet</Option>
                                <Option value="paid">Bezahlt</Option>
                                <Option value="unpaid">Unbezahlt</Option>
                                <Option value="cancelled">Storniert</Option>
                            </Select>
                            <RangePicker
                                value={filters.date_range}
                                onChange={(dates) => setFilters(prev => ({ ...prev, date_range: dates }))}
                                format="DD.MM.YYYY"
                            />
                            <Button icon={<ReloadOutlined />} onClick={fetchInvoices}>Aktualisieren</Button>
                        </Space>

                        <Table
                            columns={invoiceColumns}
                            dataSource={invoices}
                            loading={loading}
                            rowKey="id"
                            pagination={{
                                ...pagination,
                                showSizeChanger: true,
                                showTotal: (total) => `Gesamt: ${total} Rechnungen`
                            }}
                            onChange={(newPagination) => setPagination(newPagination)}
                        />
                    </Card>
                </TabPane>

                <TabPane tab="Aufladungen" key="topups">
                    <Card>
                        <Space style={{ marginBottom: 16 }} wrap>
                            <Select
                                placeholder="Alle Unternehmen"
                                value={filters.company_id}
                                onChange={(value) => setFilters(prev => ({ ...prev, company_id: value }))}
                                style={{ width: 200 }}
                                allowClear
                            >
                                {companies.map(company => (
                                    <Option key={company.id} value={company.id}>
                                        {company.name}
                                    </Option>
                                ))}
                            </Select>
                            <Select
                                placeholder="Status"
                                value={filters.status}
                                onChange={(value) => setFilters(prev => ({ ...prev, status: value }))}
                                style={{ width: 150 }}
                                allowClear
                            >
                                <Option value="pending">Ausstehend</Option>
                                <Option value="processing">In Bearbeitung</Option>
                                <Option value="succeeded">Erfolgreich</Option>
                                <Option value="failed">Fehlgeschlagen</Option>
                                <Option value="cancelled">Abgebrochen</Option>
                            </Select>
                            <RangePicker
                                value={filters.date_range}
                                onChange={(dates) => setFilters(prev => ({ ...prev, date_range: dates }))}
                                format="DD.MM.YYYY"
                            />
                            <Button 
                                type="primary"
                                icon={<PlusOutlined />}
                                onClick={() => setTopupModalVisible(true)}
                            >
                                Neue Aufladung
                            </Button>
                            <Button icon={<ReloadOutlined />} onClick={fetchTopups}>Aktualisieren</Button>
                        </Space>

                        <Table
                            columns={topupColumns}
                            dataSource={topups}
                            loading={loading}
                            rowKey="id"
                            pagination={{
                                ...pagination,
                                showSizeChanger: true,
                                showTotal: (total) => `Gesamt: ${total} Aufladungen`
                            }}
                            onChange={(newPagination) => setPagination(newPagination)}
                        />
                    </Card>
                </TabPane>

                <TabPane tab="Transaktionen" key="transactions">
                    <Card>
                        <Space style={{ marginBottom: 16 }} wrap>
                            <Select
                                placeholder="Alle Unternehmen"
                                value={filters.company_id}
                                onChange={(value) => setFilters(prev => ({ ...prev, company_id: value }))}
                                style={{ width: 200 }}
                                allowClear
                            >
                                {companies.map(company => (
                                    <Option key={company.id} value={company.id}>
                                        {company.name}
                                    </Option>
                                ))}
                            </Select>
                            <Select
                                placeholder="Typ"
                                value={filters.type}
                                onChange={(value) => setFilters(prev => ({ ...prev, type: value }))}
                                style={{ width: 150 }}
                                allowClear
                            >
                                <Option value="topup">Aufladung</Option>
                                <Option value="charge">Belastung</Option>
                                <Option value="bonus">Bonus</Option>
                                <Option value="reservation">Reservierung</Option>
                                <Option value="release">Freigabe</Option>
                            </Select>
                            <RangePicker
                                value={filters.date_range}
                                onChange={(dates) => setFilters(prev => ({ ...prev, date_range: dates }))}
                                format="DD.MM.YYYY"
                            />
                            <Button 
                                icon={<DownloadOutlined />}
                                onClick={handleExportTransactions}
                                disabled={!filters.date_range[0] || !filters.date_range[1]}
                            >
                                Export CSV
                            </Button>
                            <Button icon={<ReloadOutlined />} onClick={fetchTransactions}>Aktualisieren</Button>
                        </Space>

                        <Table
                            columns={transactionColumns}
                            dataSource={transactions}
                            loading={loading}
                            rowKey="id"
                            pagination={{
                                current: pagination.current,
                                pageSize: 50,
                                total: pagination.total,
                                showTotal: (total) => `Gesamt: ${total} Transaktionen`
                            }}
                            onChange={(newPagination) => setPagination(newPagination)}
                            size="small"
                        />
                    </Card>
                </TabPane>

                <TabPane tab="Anrufgebühren" key="charges">
                    <Card>
                        <Space style={{ marginBottom: 16 }} wrap>
                            <Select
                                placeholder="Alle Unternehmen"
                                value={filters.company_id}
                                onChange={(value) => setFilters(prev => ({ ...prev, company_id: value }))}
                                style={{ width: 200 }}
                                allowClear
                            >
                                {companies.map(company => (
                                    <Option key={company.id} value={company.id}>
                                        {company.name}
                                    </Option>
                                ))}
                            </Select>
                            <RangePicker
                                value={filters.date_range}
                                onChange={(dates) => setFilters(prev => ({ ...prev, date_range: dates }))}
                                format="DD.MM.YYYY"
                            />
                            <Button
                                type={filters.billable_only ? 'primary' : 'default'}
                                onClick={() => setFilters(prev => ({ ...prev, billable_only: !prev.billable_only }))}
                            >
                                Nur abrechenbare
                            </Button>
                            <Button icon={<ReloadOutlined />} onClick={fetchCallCharges}>Aktualisieren</Button>
                        </Space>

                        <Table
                            columns={callChargeColumns}
                            dataSource={callCharges}
                            loading={loading}
                            rowKey="id"
                            pagination={{
                                current: pagination.current,
                                pageSize: 50,
                                total: pagination.total,
                                showTotal: (total) => `Gesamt: ${total} Gebühren`
                            }}
                            onChange={(newPagination) => setPagination(newPagination)}
                        />
                    </Card>
                </TabPane>
            </Tabs>

            {/* Modals */}
            <Modal
                title="Manuelle Aufladung erstellen"
                open={topupModalVisible}
                onCancel={() => {
                    setTopupModalVisible(false);
                    topupForm.resetFields();
                }}
                footer={null}
            >
                <Form
                    form={topupForm}
                    layout="vertical"
                    onFinish={handleCreateTopup}
                >
                    <Form.Item
                        name="company_id"
                        label="Unternehmen"
                        rules={[{ required: true, message: 'Bitte wählen Sie ein Unternehmen' }]}
                    >
                        <Select placeholder="Unternehmen auswählen">
                            {companies.map(company => (
                                <Option key={company.id} value={company.id}>
                                    {company.name}
                                </Option>
                            ))}
                        </Select>
                    </Form.Item>
                    <Form.Item
                        name="amount"
                        label="Betrag (€)"
                        rules={[{ required: true, message: 'Bitte geben Sie einen Betrag ein' }]}
                    >
                        <InputNumber
                            min={0.01}
                            step={10}
                            precision={2}
                            style={{ width: '100%' }}
                        />
                    </Form.Item>
                    <Form.Item
                        name="description"
                        label="Beschreibung"
                        rules={[{ required: true, message: 'Bitte geben Sie eine Beschreibung ein' }]}
                    >
                        <Input.TextArea rows={3} />
                    </Form.Item>
                    <Form.Item
                        name="is_bonus"
                        valuePropName="checked"
                    >
                        <Checkbox>Als Bonus-Guthaben hinzufügen</Checkbox>
                    </Form.Item>
                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                Aufladung erstellen
                            </Button>
                            <Button onClick={() => {
                                setTopupModalVisible(false);
                                topupForm.resetFields();
                            }}>
                                Abbrechen
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>

            <Modal
                title="Manuelle Belastung erstellen"
                open={chargeModalVisible}
                onCancel={() => {
                    setChargeModalVisible(false);
                    chargeForm.resetFields();
                }}
                footer={null}
            >
                <Form
                    form={chargeForm}
                    layout="vertical"
                    onFinish={handleCreateCharge}
                >
                    <Form.Item
                        name="company_id"
                        label="Unternehmen"
                        rules={[{ required: true, message: 'Bitte wählen Sie ein Unternehmen' }]}
                    >
                        <Select placeholder="Unternehmen auswählen">
                            {companies.map(company => (
                                <Option key={company.id} value={company.id}>
                                    {company.name}
                                </Option>
                            ))}
                        </Select>
                    </Form.Item>
                    <Form.Item
                        name="amount"
                        label="Betrag (€)"
                        rules={[{ required: true, message: 'Bitte geben Sie einen Betrag ein' }]}
                    >
                        <InputNumber
                            min={0.01}
                            step={1}
                            precision={2}
                            style={{ width: '100%' }}
                        />
                    </Form.Item>
                    <Form.Item
                        name="description"
                        label="Beschreibung"
                        rules={[{ required: true, message: 'Bitte geben Sie eine Beschreibung ein' }]}
                    >
                        <Input.TextArea rows={3} />
                    </Form.Item>
                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                Belastung erstellen
                            </Button>
                            <Button onClick={() => {
                                setChargeModalVisible(false);
                                chargeForm.resetFields();
                            }}>
                                Abbrechen
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>

            <Modal
                title="Guthaben-Einstellungen"
                open={settingsModalVisible}
                onCancel={() => {
                    setSettingsModalVisible(false);
                    setSelectedBalance(null);
                    settingsForm.resetFields();
                }}
                footer={null}
                width={600}
            >
                {selectedBalance && (
                    <>
                        <Descriptions bordered style={{ marginBottom: 16 }}>
                            <Descriptions.Item label="Unternehmen" span={3}>
                                {selectedBalance.company.name}
                            </Descriptions.Item>
                            <Descriptions.Item label="Aktuelles Guthaben">
                                €{selectedBalance.balance.toFixed(2)}
                            </Descriptions.Item>
                            <Descriptions.Item label="Bonus-Guthaben">
                                €{selectedBalance.bonus_balance.toFixed(2)}
                            </Descriptions.Item>
                            <Descriptions.Item label="Verfügbar">
                                €{selectedBalance.available_balance.toFixed(2)}
                            </Descriptions.Item>
                        </Descriptions>

                        <Form
                            form={settingsForm}
                            layout="vertical"
                            onFinish={handleUpdateSettings}
                        >
                            <Divider>Warnungen</Divider>
                            <Form.Item
                                name="low_balance_threshold"
                                label="Schwellenwert für niedriges Guthaben (€)"
                                extra="Bei Unterschreitung wird eine Warnung angezeigt"
                            >
                                <InputNumber
                                    min={0}
                                    step={5}
                                    precision={2}
                                    style={{ width: '100%' }}
                                />
                            </Form.Item>

                            <Divider>Automatische Aufladung</Divider>
                            <Form.Item
                                name="auto_topup_enabled"
                                valuePropName="checked"
                            >
                                <Switch 
                                    checkedChildren="Aktiv" 
                                    unCheckedChildren="Inaktiv"
                                />
                                <Text style={{ marginLeft: 8 }}>Auto-Aufladung aktivieren</Text>
                            </Form.Item>
                            <Form.Item
                                name="auto_topup_threshold"
                                label="Aufladung auslösen bei Guthaben unter (€)"
                                rules={[
                                    ({ getFieldValue }) => ({
                                        validator(_, value) {
                                            if (!getFieldValue('auto_topup_enabled') || value > 0) {
                                                return Promise.resolve();
                                            }
                                            return Promise.reject(new Error('Schwellenwert muss größer als 0 sein'));
                                        },
                                    }),
                                ]}
                            >
                                <InputNumber
                                    min={0}
                                    step={10}
                                    precision={2}
                                    style={{ width: '100%' }}
                                    disabled={!settingsForm.getFieldValue('auto_topup_enabled')}
                                />
                            </Form.Item>
                            <Form.Item
                                name="auto_topup_amount"
                                label="Aufladungsbetrag (€)"
                            >
                                <InputNumber
                                    min={0}
                                    step={50}
                                    precision={2}
                                    style={{ width: '100%' }}
                                    disabled={!settingsForm.getFieldValue('auto_topup_enabled')}
                                />
                            </Form.Item>
                            <Form.Item
                                name="auto_topup_monthly_limit"
                                label="Monatliches Limit für Auto-Aufladungen (€)"
                                extra="0 = Kein Limit"
                            >
                                <InputNumber
                                    min={0}
                                    step={100}
                                    precision={2}
                                    style={{ width: '100%' }}
                                    disabled={!settingsForm.getFieldValue('auto_topup_enabled')}
                                />
                            </Form.Item>
                            <Form.Item
                                name="stripe_payment_method_id"
                                label="Stripe Zahlungsmethode ID"
                                extra="Für automatische Aufladungen"
                            >
                                <Input 
                                    placeholder="pm_..."
                                    disabled={!settingsForm.getFieldValue('auto_topup_enabled')}
                                />
                            </Form.Item>

                            <Form.Item>
                                <Space>
                                    <Button type="primary" htmlType="submit">
                                        Einstellungen speichern
                                    </Button>
                                    <Button onClick={() => {
                                        setSettingsModalVisible(false);
                                        setSelectedBalance(null);
                                        settingsForm.resetFields();
                                    }}>
                                        Abbrechen
                                    </Button>
                                </Space>
                            </Form.Item>
                        </Form>
                    </>
                )}
            </Modal>
        </div>
    );
};

export default BillingIndex;