import React, { useState } from 'react';
import { Modal, Checkbox, Button, Space, Alert, Divider, Row, Col, Typography } from 'antd';
import { ExportOutlined, FileTextOutlined, FilePdfOutlined, WarningOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;

const ExportDataSelector = ({ 
    visible, 
    onCancel, 
    onExport, 
    exportType = 'csv',
    availableFields,
    userPermissions = {},
    loading = false
}) => {
    const [selectedFields, setSelectedFields] = useState([]);
    const [selectAll, setSelectAll] = useState(false);

    // Group fields by category
    const fieldGroups = {
        basic: {
            label: 'Grunddaten',
            fields: [
                { key: 'date', label: 'Datum', sensitive: false },
                { key: 'time', label: 'Uhrzeit', sensitive: false },
                { key: 'phone_number', label: 'Telefonnummer', sensitive: true },
                { key: 'duration', label: 'Anrufdauer', sensitive: false },
                { key: 'status', label: 'Status', sensitive: false },
            ]
        },
        customer: {
            label: 'Kundendaten',
            fields: [
                { key: 'customer_name', label: 'Kundenname', sensitive: true },
                { key: 'customer_email', label: 'E-Mail', sensitive: true },
                { key: 'customer_company', label: 'Firma', sensitive: true },
                { key: 'customer_number', label: 'Kundennummer', sensitive: true },
            ]
        },
        content: {
            label: 'Gesprächsinhalt',
            fields: [
                { key: 'summary', label: 'Zusammenfassung', sensitive: false },
                { key: 'reason', label: 'Anrufgrund', sensitive: false },
                { key: 'notes', label: 'Notizen', sensitive: false },
                { key: 'transcript', label: 'Transkript', sensitive: true },
            ]
        },
        administrative: {
            label: 'Administrative Daten',
            fields: [
                { key: 'assigned_to', label: 'Zugewiesen an', sensitive: false },
                { key: 'branch', label: 'Filiale', sensitive: false },
                { key: 'urgency', label: 'Dringlichkeit', sensitive: false },
            ]
        },
        financial: {
            label: 'Finanzdaten',
            permission: 'billing.view',
            fields: [
                { key: 'cost', label: 'Kosten', sensitive: true, critical: true },
                { key: 'price_per_minute', label: 'Preis pro Minute', sensitive: true, critical: true },
            ]
        }
    };

    const handleSelectAll = (checked) => {
        setSelectAll(checked);
        if (checked) {
            const allFields = [];
            Object.values(fieldGroups).forEach(group => {
                if (!group.permission || userPermissions[group.permission]) {
                    group.fields.forEach(field => {
                        if (!field.critical || userPermissions['billing.view']) {
                            allFields.push(field.key);
                        }
                    });
                }
            });
            setSelectedFields(allFields);
        } else {
            setSelectedFields([]);
        }
    };

    const handleFieldChange = (fieldKey, checked) => {
        if (checked) {
            setSelectedFields([...selectedFields, fieldKey]);
        } else {
            setSelectedFields(selectedFields.filter(f => f !== fieldKey));
            setSelectAll(false);
        }
    };

    const handleExport = () => {
        if (selectedFields.length === 0) {
            Modal.warning({
                title: 'Keine Felder ausgewählt',
                content: 'Bitte wählen Sie mindestens ein Feld für den Export aus.',
            });
            return;
        }

        onExport(selectedFields);
    };

    const getSensitiveFieldCount = () => {
        let count = 0;
        Object.values(fieldGroups).forEach(group => {
            group.fields.forEach(field => {
                if (selectedFields.includes(field.key) && field.sensitive) {
                    count++;
                }
            });
        });
        return count;
    };

    const sensitiveCount = getSensitiveFieldCount();

    return (
        <Modal
            title={
                <Space>
                    {exportType === 'pdf' ? <FilePdfOutlined /> : <FileTextOutlined />}
                    <span>Daten für {exportType.toUpperCase()} Export auswählen</span>
                </Space>
            }
            open={visible}
            onCancel={onCancel}
            width={700}
            footer={[
                <Button key="cancel" onClick={onCancel}>
                    Abbrechen
                </Button>,
                <Button 
                    key="export" 
                    type="primary" 
                    icon={<ExportOutlined />}
                    onClick={handleExport}
                    loading={loading}
                >
                    {selectedFields.length} Felder exportieren
                </Button>,
            ]}
        >
            {sensitiveCount > 0 && (
                <Alert
                    message="Sensible Daten ausgewählt"
                    description={`Sie haben ${sensitiveCount} Felder mit sensiblen Daten ausgewählt. Diese Aktion wird protokolliert.`}
                    type="warning"
                    icon={<WarningOutlined />}
                    showIcon
                    style={{ marginBottom: 16 }}
                />
            )}

            <Checkbox 
                checked={selectAll} 
                onChange={(e) => handleSelectAll(e.target.checked)}
                style={{ marginBottom: 16 }}
            >
                <strong>Alle verfügbaren Felder auswählen</strong>
            </Checkbox>

            <Divider />

            {Object.entries(fieldGroups).map(([groupKey, group]) => {
                // Check if user has permission for this group
                if (group.permission && !userPermissions[group.permission]) {
                    return null;
                }

                return (
                    <div key={groupKey} style={{ marginBottom: 24 }}>
                        <Title level={5}>{group.label}</Title>
                        <Row gutter={[16, 16]}>
                            {group.fields.map(field => {
                                // Check if user has permission for critical fields
                                if (field.critical && !userPermissions['billing.view']) {
                                    return null;
                                }

                                return (
                                    <Col span={12} key={field.key}>
                                        <Checkbox
                                            checked={selectedFields.includes(field.key)}
                                            onChange={(e) => handleFieldChange(field.key, e.target.checked)}
                                        >
                                            <Space>
                                                <span>{field.label}</span>
                                                {field.sensitive && (
                                                    <Text type="warning" style={{ fontSize: 12 }}>
                                                        (Sensibel)
                                                    </Text>
                                                )}
                                                {field.critical && (
                                                    <Text type="danger" style={{ fontSize: 12 }}>
                                                        (Kritisch)
                                                    </Text>
                                                )}
                                            </Space>
                                        </Checkbox>
                                    </Col>
                                );
                            })}
                        </Row>
                    </div>
                );
            })}

            <Divider />

            <Alert
                message="Datenschutzhinweis"
                description="Alle Exporte werden protokolliert. Stellen Sie sicher, dass Sie die exportierten Daten gemäß den Datenschutzbestimmungen behandeln."
                type="info"
                showIcon
            />
        </Modal>
    );
};

export default ExportDataSelector;