import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '../ui/card';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../ui/dialog';
import { Badge } from '../ui/badge';
import { Alert, AlertDescription } from '../ui/alert';
import { useGoals } from '../../hooks/useGoals';
import { useAuth } from '../../hooks/useAuth';
import { 
    Target, 
    TrendingUp, 
    DollarSign, 
    Plus, 
    Trash2, 
    Calendar,
    AlertCircle,
    Loader2,
    ChevronRight,
    Percent,
    Hash,
    Euro,
    Zap,
    Phone,
    Users
} from 'lucide-react';

export default function GoalConfiguration({ onSuccess, editGoal = null }) {
    const { csrfToken } = useAuth();
    const { createGoal, updateGoal, loading, fetchGoalTemplates } = useGoals();
    
    const [open, setOpen] = useState(false);
    const [selectedTemplate, setSelectedTemplate] = useState(null);
    const [templates, setTemplates] = useState([]);
    const [templatesLoading, setTemplatesLoading] = useState(true);
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        type: '',
        start_date: '',
        end_date: '',
        target_value: '',
        metrics: []
    });
    const [errors, setErrors] = useState({});

    useEffect(() => {
        loadTemplates();
    }, []);

    useEffect(() => {
        if (editGoal) {
            setFormData({
                name: editGoal.name || '',
                description: editGoal.description || '',
                type: editGoal.type || '',
                start_date: editGoal.start_date || '',
                end_date: editGoal.end_date || '',
                target_value: editGoal.target_value || '',
                metrics: editGoal.metrics || []
            });
            setOpen(true);
        }
    }, [editGoal]);

    const loadTemplates = async () => {
        try {
            setTemplatesLoading(true);
            const templateData = await fetchGoalTemplates();
            setTemplates(templateData);
        } catch (err) {
            // Silently handle template loading error
        } finally {
            setTemplatesLoading(false);
        }
    };

    const handleTemplateSelect = (template) => {
        setSelectedTemplate(template);
        setFormData({
            ...formData,
            name: template.name,
            description: template.description,
            type: template.type || template.id,
            template_type: template.type,
            metrics: template.metrics.map((metric, index) => ({
                id: `metric_${index}`,
                name: metric.metric_name || metric.name,
                type: metric.metric_type || metric.type,
                unit: metric.target_unit || metric.unit,
                target_value: metric.suggested_target || '',
                weight: metric.weight || 1.0
            }))
        });
        setOpen(true);
    };

    const handleMetricChange = (metricId, field, value) => {
        setFormData({
            ...formData,
            metrics: formData.metrics.map(metric => 
                metric.id === metricId 
                    ? { ...metric, [field]: value }
                    : metric
            )
        });
    };

    const addMetric = () => {
        const newMetric = {
            id: `metric_${Date.now()}`,
            name: '',
            type: 'count',
            unit: '',
            target_value: '',
            weight: 1.0
        };
        setFormData({
            ...formData,
            metrics: [...formData.metrics, newMetric]
        });
    };

    const removeMetric = (metricId) => {
        setFormData({
            ...formData,
            metrics: formData.metrics.filter(m => m.id !== metricId)
        });
    };

    const validateForm = () => {
        const newErrors = {};
        
        if (!formData.name.trim()) {
            newErrors.name = 'Name ist erforderlich';
        }
        
        if (!formData.start_date) {
            newErrors.start_date = 'Startdatum ist erforderlich';
        }
        
        // Enddatum ist optional für fortlaufende Ziele
        if (formData.start_date && formData.end_date && formData.start_date > formData.end_date) {
            newErrors.end_date = 'Enddatum muss nach dem Startdatum liegen';
        }
        
        if (formData.metrics.length === 0) {
            newErrors.metrics = 'Mindestens eine Metrik ist erforderlich';
        }
        
        formData.metrics.forEach((metric, index) => {
            if (!metric.name.trim()) {
                newErrors[`metric_${metric.id}_name`] = 'Metrikname ist erforderlich';
            }
            if (!metric.target_value || metric.target_value <= 0) {
                newErrors[`metric_${metric.id}_target`] = 'Zielwert muss größer als 0 sein';
            }
        });
        
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async () => {
        if (!validateForm()) return;
        
        try {
            const goalData = {
                ...formData,
                target_value: parseFloat(formData.target_value) || 0,
                metrics: formData.metrics.map(metric => ({
                    name: metric.name,
                    type: metric.type,
                    unit: metric.unit,
                    target_value: parseFloat(metric.target_value),
                    weight: parseFloat(metric.weight)
                }))
            };
            
            if (editGoal) {
                await updateGoal(editGoal.id, goalData);
            } else {
                await createGoal(goalData);
            }
            
            setOpen(false);
            setFormData({
                name: '',
                description: '',
                type: '',
                start_date: '',
                end_date: '',
                target_value: '',
                metrics: []
            });
            setSelectedTemplate(null);
            
            if (onSuccess) {
                onSuccess();
            }
        } catch (error) {
            // Goal save failed - could show toast notification
        }
    };

    const getMetricIcon = (type) => {
        switch (type) {
            case 'percentage':
                return <Percent className="h-4 w-4" />;
            case 'currency':
                return <Euro className="h-4 w-4" />;
            default:
                return <Hash className="h-4 w-4" />;
        }
    };

    const getTemplateIcon = (templateType) => {
        switch (templateType) {
            case 'max_appointments':
                return Target;
            case 'data_collection':
                return Users;
            case 'revenue_optimization':
                return DollarSign;
            case 'data_forwarding_focus':
                return Zap;
            default:
                return Target;
        }
    };

    return (
        <>
            {templatesLoading ? (
                <div className="flex items-center justify-center p-8">
                    <Loader2 className="h-8 w-8 animate-spin" />
                </div>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {templates.map((template) => {
                        const Icon = getTemplateIcon(template.type || template.id);
                        return (
                            <Card 
                                key={template.id}
                                className="cursor-pointer hover:border-primary transition-colors"
                                onClick={() => handleTemplateSelect(template)}
                            >
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Icon className="h-5 w-5" />
                                        {template.name}
                                    </CardTitle>
                                    <CardDescription>
                                        {template.description}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {template.metrics.map((metric, index) => (
                                            <div key={index} className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <ChevronRight className="h-3 w-3" />
                                                {metric.metric_name || metric.name}
                                            </div>
                                        ))}
                                    </div>
                                    <Button 
                                        className="w-full mt-4" 
                                        variant="outline"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleTemplateSelect(template);
                                        }}
                                    >
                                        Auswählen
                                    </Button>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            )}

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editGoal ? 'Ziel bearbeiten' : 'Neues Ziel erstellen'}
                        </DialogTitle>
                        <DialogDescription>
                            Definieren Sie Ihre Ziele und verfolgen Sie Ihren Fortschritt
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                placeholder="z.B. Q1 Umsatzziel"
                            />
                            {errors.name && (
                                <p className="text-sm text-red-500">{errors.name}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Beschreibung</Label>
                            <Input
                                id="description"
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                placeholder="Beschreiben Sie Ihr Ziel..."
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="start_date">Startdatum</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={formData.start_date}
                                    onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                />
                                {errors.start_date && (
                                    <p className="text-sm text-red-500">{errors.start_date}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="end_date">Enddatum</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={formData.end_date}
                                    onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                />
                                {errors.end_date && (
                                    <p className="text-sm text-red-500">{errors.end_date}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label>Metriken</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={addMetric}
                                >
                                    <Plus className="h-4 w-4 mr-1" />
                                    Metrik hinzufügen
                                </Button>
                            </div>
                            
                            {errors.metrics && (
                                <Alert variant="destructive">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>{errors.metrics}</AlertDescription>
                                </Alert>
                            )}

                            <div className="space-y-3">
                                {formData.metrics.map((metric) => (
                                    <Card key={metric.id} className="p-4">
                                        <div className="space-y-3">
                                            <div className="flex gap-2">
                                                <div className="flex-1 space-y-2">
                                                    <Label>Metrikname</Label>
                                                    <Input
                                                        value={metric.name}
                                                        onChange={(e) => handleMetricChange(metric.id, 'name', e.target.value)}
                                                        placeholder="z.B. Anzahl Termine"
                                                    />
                                                    {errors[`metric_${metric.id}_name`] && (
                                                        <p className="text-sm text-red-500">{errors[`metric_${metric.id}_name`]}</p>
                                                    )}
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => removeMetric(metric.id)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>

                                            <div className="grid grid-cols-3 gap-2">
                                                <div className="space-y-2">
                                                    <Label>Typ</Label>
                                                    <Select
                                                        value={metric.type}
                                                        onValueChange={(value) => handleMetricChange(metric.id, 'type', value)}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="count">
                                                                <div className="flex items-center gap-2">
                                                                    <Hash className="h-4 w-4" />
                                                                    Anzahl
                                                                </div>
                                                            </SelectItem>
                                                            <SelectItem value="percentage">
                                                                <div className="flex items-center gap-2">
                                                                    <Percent className="h-4 w-4" />
                                                                    Prozent
                                                                </div>
                                                            </SelectItem>
                                                            <SelectItem value="currency">
                                                                <div className="flex items-center gap-2">
                                                                    <Euro className="h-4 w-4" />
                                                                    Währung
                                                                </div>
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>

                                                <div className="space-y-2">
                                                    <Label>Zielwert</Label>
                                                    <Input
                                                        type="number"
                                                        value={metric.target_value}
                                                        onChange={(e) => handleMetricChange(metric.id, 'target_value', e.target.value)}
                                                        placeholder="100"
                                                    />
                                                    {errors[`metric_${metric.id}_target`] && (
                                                        <p className="text-sm text-red-500">{errors[`metric_${metric.id}_target`]}</p>
                                                    )}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label>Gewichtung</Label>
                                                    <Input
                                                        type="number"
                                                        step="0.1"
                                                        value={metric.weight}
                                                        onChange={(e) => handleMetricChange(metric.id, 'weight', e.target.value)}
                                                        placeholder="1.0"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)}>
                            Abbrechen
                        </Button>
                        <Button onClick={handleSubmit} disabled={loading}>
                            {loading ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Speichern...
                                </>
                            ) : (
                                editGoal ? 'Aktualisieren' : 'Erstellen'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}