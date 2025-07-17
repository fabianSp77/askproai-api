import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../ui/card';
import { Button } from '../../ui/button';
import { Input } from '../../ui/input';
import { Label } from '../../ui/label';
import { Textarea } from '../../ui/textarea';
import { Badge } from '../../ui/badge';
import { Progress } from '../../ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '../../ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../../ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '../../ui/sheet';
import { 
    Target,
    Plus,
    Edit,
    Trash2,
    TrendingUp,
    Calendar,
    BarChart3,
    CheckCircle,
    XCircle,
    Loader2,
    AlertTriangle,
    Info,
    ChevronRight,
    Trophy,
    Zap,
    Users,
    DollarSign,
    Phone
} from 'lucide-react';
import { useGoals } from '../../../hooks/useGoals';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

const GoalConfiguration = () => {
    const { goals, loading, error, createGoal, updateGoal, deleteGoal, fetchGoalTemplates } = useGoals();
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [selectedGoal, setSelectedGoal] = useState(null);
    const [templates, setTemplates] = useState([]);
    const [templateDialogOpen, setTemplateDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [saving, setSaving] = useState(false);
    
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        type: 'calls',
        target_value: '',
        target_period: 'month',
        starts_at: dayjs().format('YYYY-MM-DD'),
        ends_at: dayjs().add(3, 'months').format('YYYY-MM-DD'),
        branch_id: '',
        staff_id: '',
        priority: 'medium',
        notifications_enabled: true
    });

    const goalTypes = [
        { value: 'calls', label: 'Anrufe', icon: Phone },
        { value: 'appointments', label: 'Termine', icon: Calendar },
        { value: 'conversion', label: 'Konversionsrate', icon: TrendingUp },
        { value: 'revenue', label: 'Umsatz', icon: DollarSign },
        { value: 'customers', label: 'Neue Kunden', icon: Users },
        { value: 'data_forwarding_focus', label: 'Datenweiterleitung', icon: Zap }
    ];

    const targetPeriods = [
        { value: 'day', label: 'Täglich' },
        { value: 'week', label: 'Wöchentlich' },
        { value: 'month', label: 'Monatlich' },
        { value: 'quarter', label: 'Quartalsweise' },
        { value: 'year', label: 'Jährlich' }
    ];

    const priorities = [
        { value: 'low', label: 'Niedrig', color: 'secondary' },
        { value: 'medium', label: 'Mittel', color: 'default' },
        { value: 'high', label: 'Hoch', color: 'destructive' }
    ];

    useEffect(() => {
        loadTemplates();
    }, []);

    const loadTemplates = async () => {
        try {
            const templateData = await fetchGoalTemplates();
            setTemplates(templateData);
        } catch (err) {
            // Silently handle template loading error
        }
    };

    const handleCreate = async () => {
        setSaving(true);
        try {
            await createGoal(formData);
            setDialogOpen(false);
            resetForm();
        } catch (err) {
            // Goal creation failed - could show toast notification
        } finally {
            setSaving(false);
        }
    };

    const handleUpdate = async () => {
        if (!selectedGoal) return;
        
        setSaving(true);
        try {
            await updateGoal(selectedGoal.id, formData);
            setDialogOpen(false);
            setEditMode(false);
            setSelectedGoal(null);
            resetForm();
        } catch (err) {
            // Goal update failed - could show toast notification
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async () => {
        if (!selectedGoal) return;
        
        try {
            await deleteGoal(selectedGoal.id);
            setDeleteDialogOpen(false);
            setSelectedGoal(null);
        } catch (err) {
            // Goal deletion failed - could show toast notification
        }
    };

    const openEditDialog = (goal) => {
        setSelectedGoal(goal);
        setFormData({
            name: goal.name,
            description: goal.description || '',
            type: goal.type,
            target_value: goal.target_value.toString(),
            target_period: goal.target_period,
            starts_at: dayjs(goal.starts_at).format('YYYY-MM-DD'),
            ends_at: dayjs(goal.ends_at).format('YYYY-MM-DD'),
            branch_id: goal.branch_id || '',
            staff_id: goal.staff_id || '',
            priority: goal.priority || 'medium',
            notifications_enabled: goal.notifications_enabled ?? true
        });
        setEditMode(true);
        setDialogOpen(true);
    };

    const applyTemplate = (template) => {
        // Extract the first metric as the primary goal
        const primaryMetric = template.metrics && template.metrics.find(m => m.is_primary) || template.metrics?.[0];
        
        setFormData({
            ...formData,
            name: template.name,
            description: template.description,
            type: primaryMetric?.metric_type || 'calls',
            target_value: primaryMetric?.suggested_target?.toString() || '',
            target_period: template.default_duration === 30 ? 'month' : 'quarter',
            priority: template.priority || 'medium',
            template_type: template.type
        });
        setTemplateDialogOpen(false);
    };

    const resetForm = () => {
        setFormData({
            name: '',
            description: '',
            type: 'calls',
            target_value: '',
            target_period: 'month',
            starts_at: dayjs().format('YYYY-MM-DD'),
            ends_at: dayjs().add(3, 'months').format('YYYY-MM-DD'),
            branch_id: '',
            staff_id: '',
            priority: 'medium',
            notifications_enabled: true
        });
    };

    const getGoalIcon = (type) => {
        const goalType = goalTypes.find(t => t.value === type);
        return goalType ? goalType.icon : Target;
    };

    const getProgressColor = (progress) => {
        if (progress >= 90) return 'text-green-600';
        if (progress >= 70) return 'text-yellow-600';
        return 'text-red-600';
    };

    const calculateDaysRemaining = (endDate) => {
        const days = dayjs(endDate).diff(dayjs(), 'days');
        return days > 0 ? days : 0;
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Ziele verwalten</h2>
                    <p className="text-muted-foreground">Erstellen und verwalten Sie Ihre Geschäftsziele</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={() => setTemplateDialogOpen(true)}>
                        <Zap className="h-4 w-4 mr-2" />
                        Vorlagen
                    </Button>
                    <Button onClick={() => { resetForm(); setEditMode(false); setDialogOpen(true); }}>
                        <Plus className="h-4 w-4 mr-2" />
                        Neues Ziel
                    </Button>
                </div>
            </div>

            {/* Active Goals */}
            {loading ? (
                <div className="flex items-center justify-center p-8">
                    <Loader2 className="h-8 w-8 animate-spin" />
                </div>
            ) : goals.length === 0 ? (
                <Card>
                    <CardContent className="p-8 text-center">
                        <Target className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                        <p className="text-muted-foreground mb-4">Noch keine Ziele erstellt</p>
                        <Button onClick={() => setDialogOpen(true)}>
                            <Plus className="h-4 w-4 mr-2" />
                            Erstes Ziel erstellen
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {goals.map((goal) => {
                        const Icon = getGoalIcon(goal.type);
                        const progress = goal.current_value ? (goal.current_value / goal.target_value) * 100 : 0;
                        const daysRemaining = calculateDaysRemaining(goal.ends_at);
                        
                        return (
                            <Card key={goal.id} className="relative overflow-hidden">
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 rounded-lg bg-muted">
                                                <Icon className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <CardTitle className="text-lg">{goal.name}</CardTitle>
                                                <Badge variant={priorities.find(p => p.value === goal.priority)?.color || 'default'} className="mt-1">
                                                    {priorities.find(p => p.value === goal.priority)?.label}
                                                </Badge>
                                            </div>
                                        </div>
                                        <div className="flex gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => openEditDialog(goal)}
                                            >
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => {
                                                    setSelectedGoal(goal);
                                                    setDeleteDialogOpen(true);
                                                }}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {goal.description && (
                                            <p className="text-sm text-muted-foreground">{goal.description}</p>
                                        )}
                                        
                                        <div className="space-y-2">
                                            <div className="flex justify-between text-sm">
                                                <span>Fortschritt</span>
                                                <span className={cn("font-medium", getProgressColor(progress))}>
                                                    {progress.toFixed(0)}%
                                                </span>
                                            </div>
                                            <Progress value={progress} className="h-2" />
                                            <div className="flex justify-between text-xs text-muted-foreground">
                                                <span>{goal.current_value || 0} / {goal.target_value}</span>
                                                <span>{daysRemaining} Tage verbleibend</span>
                                            </div>
                                        </div>

                                        <div className="pt-2 border-t">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">Zeitraum</span>
                                                <span>{targetPeriods.find(p => p.value === goal.target_period)?.label}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-sm mt-1">
                                                <span className="text-muted-foreground">Laufzeit</span>
                                                <span>
                                                    {dayjs(goal.starts_at).format('DD.MM')} - {dayjs(goal.ends_at).format('DD.MM.YYYY')}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            )}

            {/* Create/Edit Goal Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{editMode ? 'Ziel bearbeiten' : 'Neues Ziel erstellen'}</DialogTitle>
                        <DialogDescription>
                            {editMode ? 'Aktualisieren Sie die Zieldetails' : 'Definieren Sie ein neues Geschäftsziel'}
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    placeholder="z.B. 100 Anrufe pro Monat"
                                />
                            </div>
                            <div>
                                <Label htmlFor="type">Typ</Label>
                                <Select
                                    value={formData.type}
                                    onValueChange={(value) => setFormData({ ...formData, type: value })}
                                >
                                    <SelectTrigger id="type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {goalTypes.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                <div className="flex items-center gap-2">
                                                    <type.icon className="h-4 w-4" />
                                                    {type.label}
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="description">Beschreibung</Label>
                            <Textarea
                                id="description"
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                placeholder="Optionale Beschreibung des Ziels"
                                rows={3}
                            />
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="target_value">Zielwert</Label>
                                <Input
                                    id="target_value"
                                    type="number"
                                    value={formData.target_value}
                                    onChange={(e) => setFormData({ ...formData, target_value: e.target.value })}
                                    placeholder="z.B. 100"
                                />
                            </div>
                            <div>
                                <Label htmlFor="target_period">Zeitraum</Label>
                                <Select
                                    value={formData.target_period}
                                    onValueChange={(value) => setFormData({ ...formData, target_period: value })}
                                >
                                    <SelectTrigger id="target_period">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {targetPeriods.map((period) => (
                                            <SelectItem key={period.value} value={period.value}>
                                                {period.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="starts_at">Startdatum</Label>
                                <Input
                                    id="starts_at"
                                    type="date"
                                    value={formData.starts_at}
                                    onChange={(e) => setFormData({ ...formData, starts_at: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label htmlFor="ends_at">Enddatum</Label>
                                <Input
                                    id="ends_at"
                                    type="date"
                                    value={formData.ends_at}
                                    onChange={(e) => setFormData({ ...formData, ends_at: e.target.value })}
                                />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="priority">Priorität</Label>
                            <Select
                                value={formData.priority}
                                onValueChange={(value) => setFormData({ ...formData, priority: value })}
                            >
                                <SelectTrigger id="priority">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {priorities.map((priority) => (
                                        <SelectItem key={priority.value} value={priority.value}>
                                            {priority.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDialogOpen(false)}>
                            Abbrechen
                        </Button>
                        <Button onClick={editMode ? handleUpdate : handleCreate} disabled={saving}>
                            {saving && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            {editMode ? 'Aktualisieren' : 'Erstellen'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Template Dialog */}
            <Dialog open={templateDialogOpen} onOpenChange={setTemplateDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Zielvorlagen</DialogTitle>
                        <DialogDescription>
                            Wählen Sie eine Vorlage für Ihr neues Ziel
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-3">
                        {templates.map((template) => {
                            const Icon = getGoalIcon(template.type);
                            return (
                                <Card 
                                    key={template.id} 
                                    className="cursor-pointer hover:bg-muted/50 transition-colors"
                                    onClick={() => applyTemplate(template)}
                                >
                                    <CardContent className="p-4">
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 rounded-lg bg-muted">
                                                <Icon className="h-5 w-5" />
                                            </div>
                                            <div className="flex-1">
                                                <h4 className="font-medium">{template.name}</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    {template.description}
                                                </p>
                                            </div>
                                            <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ziel löschen</DialogTitle>
                        <DialogDescription>
                            Sind Sie sicher, dass Sie das Ziel "{selectedGoal?.name}" löschen möchten? 
                            Diese Aktion kann nicht rückgängig gemacht werden.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteDialogOpen(false)}>
                            Abbrechen
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Löschen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default GoalConfiguration;