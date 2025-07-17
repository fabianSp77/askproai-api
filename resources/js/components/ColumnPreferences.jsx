import React, { useState } from 'react';
import { 
    Modal, 
    Checkbox, 
    Button, 
    Space, 
    List, 
    Typography,
    Divider,
    message 
} from 'antd';
import { 
    SettingOutlined, 
    EyeOutlined, 
    EyeInvisibleOutlined,
    SaveOutlined,
    ReloadOutlined
} from '@ant-design/icons';

const { Title, Text } = Typography;

const ColumnPreferences = ({ 
    visible, 
    onCancel, 
    columns, 
    visibleColumns, 
    onSave,
    loading = false 
}) => {
    const [selectedColumns, setSelectedColumns] = useState(visibleColumns || []);

    // Default columns that should always be visible
    const requiredColumns = ['from_number', 'actions'];

    // Group columns by category
    const columnGroups = {
        basic: {
            label: 'Grundinformationen',
            columns: ['from_number', 'to_number', 'branch', 'status', 'created_at']
        },
        details: {
            label: 'Details',
            columns: ['duration', 'assigned_to', 'notes', 'urgency']
        },
        financial: {
            label: 'Finanzen',
            columns: ['cost', 'price_per_minute'],
            permission: 'billing.view'
        }
    };

    const handleColumnToggle = (columnKey, checked) => {
        if (requiredColumns.includes(columnKey)) {
            message.warning('Diese Spalte kann nicht ausgeblendet werden');
            return;
        }

        if (checked) {
            setSelectedColumns([...selectedColumns, columnKey]);
        } else {
            setSelectedColumns(selectedColumns.filter(key => key !== columnKey));
        }
    };

    const handleSelectAll = () => {
        const allColumnKeys = columns
            .filter(col => !requiredColumns.includes(col.key))
            .map(col => col.key);
        setSelectedColumns([...requiredColumns, ...allColumnKeys]);
    };

    const handleDeselectAll = () => {
        setSelectedColumns(requiredColumns);
    };

    const handleReset = () => {
        // Reset to default columns
        const defaultColumns = ['from_number', 'branch', 'status', 'created_at', 'duration', 'actions'];
        setSelectedColumns(defaultColumns);
    };

    const handleSave = () => {
        if (selectedColumns.length < 2) {
            message.warning('Bitte wählen Sie mindestens 2 Spalten aus');
            return;
        }
        onSave(selectedColumns);
    };

    const getColumnLabel = (column) => {
        return column.title || column.key;
    };

    const isColumnVisible = (columnKey) => {
        return selectedColumns.includes(columnKey);
    };

    const renderColumnGroup = (groupKey, group) => {
        const groupColumns = columns.filter(col => 
            group.columns.includes(col.key)
        );

        if (groupColumns.length === 0) return null;

        return (
            <div key={groupKey} style={{ marginBottom: 24 }}>
                <Title level={5} style={{ marginBottom: 12 }}>
                    {group.label}
                </Title>
                <List
                    dataSource={groupColumns}
                    renderItem={(column) => (
                        <List.Item
                            actions={[
                                <Checkbox
                                    checked={isColumnVisible(column.key)}
                                    onChange={(e) => handleColumnToggle(column.key, e.target.checked)}
                                    disabled={requiredColumns.includes(column.key)}
                                />
                            ]}
                        >
                            <List.Item.Meta
                                avatar={
                                    isColumnVisible(column.key) ? 
                                        <EyeOutlined style={{ color: '#1890ff' }} /> : 
                                        <EyeInvisibleOutlined style={{ color: '#bfbfbf' }} />
                                }
                                title={getColumnLabel(column)}
                                description={
                                    requiredColumns.includes(column.key) ? 
                                        <Text type="secondary" style={{ fontSize: 12 }}>
                                            (Pflichtfeld)
                                        </Text> : null
                                }
                            />
                        </List.Item>
                    )}
                />
            </div>
        );
    };

    return (
        <Modal
            title={
                <Space>
                    <SettingOutlined />
                    <span>Spalten anpassen</span>
                </Space>
            }
            open={visible}
            onCancel={onCancel}
            width={600}
            footer={[
                <Button key="reset" onClick={handleReset}>
                    <ReloadOutlined /> Zurücksetzen
                </Button>,
                <Button key="cancel" onClick={onCancel}>
                    Abbrechen
                </Button>,
                <Button 
                    key="save" 
                    type="primary" 
                    icon={<SaveOutlined />}
                    onClick={handleSave}
                    loading={loading}
                >
                    Speichern
                </Button>,
            ]}
        >
            <div style={{ marginBottom: 16 }}>
                <Space>
                    <Button size="small" onClick={handleSelectAll}>
                        Alle auswählen
                    </Button>
                    <Button size="small" onClick={handleDeselectAll}>
                        Alle abwählen
                    </Button>
                </Space>
                <Text type="secondary" style={{ marginLeft: 16 }}>
                    {selectedColumns.length} von {columns.length} Spalten ausgewählt
                </Text>
            </div>

            <Divider />

            {Object.entries(columnGroups).map(([groupKey, group]) => 
                renderColumnGroup(groupKey, group)
            )}

            {/* Other columns not in groups */}
            {(() => {
                const groupedColumnKeys = Object.values(columnGroups)
                    .flatMap(group => group.columns);
                const otherColumns = columns.filter(col => 
                    !groupedColumnKeys.includes(col.key) && col.key !== 'actions'
                );

                if (otherColumns.length > 0) {
                    return (
                        <div style={{ marginBottom: 24 }}>
                            <Title level={5} style={{ marginBottom: 12 }}>
                                Weitere Spalten
                            </Title>
                            <List
                                dataSource={otherColumns}
                                renderItem={(column) => (
                                    <List.Item
                                        actions={[
                                            <Checkbox
                                                checked={isColumnVisible(column.key)}
                                                onChange={(e) => handleColumnToggle(column.key, e.target.checked)}
                                            />
                                        ]}
                                    >
                                        <List.Item.Meta
                                            avatar={
                                                isColumnVisible(column.key) ? 
                                                    <EyeOutlined style={{ color: '#1890ff' }} /> : 
                                                    <EyeInvisibleOutlined style={{ color: '#bfbfbf' }} />
                                            }
                                            title={getColumnLabel(column)}
                                        />
                                    </List.Item>
                                )}
                            />
                        </div>
                    );
                }
                return null;
            })()}

            <Divider />

            <Text type="secondary">
                <small>
                    Ihre Spalteneinstellungen werden automatisch gespeichert und bei Ihrem nächsten Besuch wiederhergestellt.
                </small>
            </Text>
        </Modal>
    );
};

export default ColumnPreferences;