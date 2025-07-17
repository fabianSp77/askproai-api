import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Badge } from '../../../components/ui/badge';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '../../../components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '../../../components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../../../components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '../../../components/ui/sheet';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '../../../components/ui/dropdown-menu';
import { 
    Calendar, 
    User, 
    Clock, 
    Phone,
    Edit,
    Trash2,
    CheckCircle,
    XCircle,
    AlertCircle,
    Plus,
    RefreshCw,
    MapPin,
    Users,
    DollarSign,
    FileText,
    Mail,
    MoreHorizontal,
    Loader2,
    CalendarDays,
    Filter,
    Search,
    Building,
    Briefcase,
    ChevronRight,
    Download,
    Eye
} from 'lucide-react';
import { useAuth } from '../../../hooks/useAuth';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import axiosInstance from '../../../services/axiosInstance';

dayjs.locale('de');

const AppointmentsIndex = () => {
    const { } = useAuth();
    const [appointments, setAppointments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        status: 'all',
        branch_id: '',
        staff_id: '',
        service_id: '',
        date_range: null,
        search: ''
    });
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
    const [customers, setCustomers] = useState([]);
    const [selectedAppointment, setSelectedAppointment] = useState(null);
    const [sheetOpen, setSheetOpen] = useState(false);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [saving, setSaving] = useState(false);
    
    // Form data for new appointment
    const [appointmentData, setAppointmentData] = useState({
        customer_id: '',
        branch_id: '',
        staff_id: '',
        service_id: '',
        date: '',
        start_time: '',
        end_time: '',
        notes: ''
    });

    useEffect(() => {
        fetchAppointments();
        fetchFilterOptions();
    }, [filters]);

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
            if (filters.search) {
                params.append('search', filters.search);
            }

            const response = await axiosInstance.get(`/appointments?`);

            if (!response.data) throw new Error('Failed to fetch appointments');

            const data = await response.data;
            setAppointments(data.appointments?.data || []);
            setStats(data.stats || {
                total: 0,
                today: 0,
                this_week: 0,
                confirmed: 0,
                pending: 0,
                cancelled: 0
            });
        } catch (error) {
            // Silently handle error - will show empty state
        } finally {
            setLoading(false);
        }
    };

    const fetchFilterOptions = async () => {
        try {
            const response = await axiosInstance.get('/appointments/filters');

            if (!response.data) throw new Error('Failed to fetch filters');

            const data = await response.data;
            setBranches(data.branches || []);
            setStaff(data.staff || []);
            setServices(data.services || []);
            setCustomers(data.customers || []);
        } catch (error) {
            // Silently handle filters error
        }
    };

    const handleStatusChange = async (appointmentId, newStatus) => {
        try {
            const response = await axiosInstance.post(`/appointments/${appointmentId}/status`, { status: newStatus });

            if (!response.data) throw new Error('Failed to update status');

            fetchAppointments();
        } catch (error) {
            // Status update failed - could show toast notification
        }
    };

    const handleCreateAppointment = async () => {
        setSaving(true);
        try {
            const appointmentPayload = {
                customer_id: appointmentData.customer_id,
                branch_id: appointmentData.branch_id,
                staff_id: appointmentData.staff_id,
                service_id: appointmentData.service_id,
                starts_at: `${appointmentData.date} ${appointmentData.start_time}:00`,
                ends_at: `${appointmentData.date} ${appointmentData.end_time}:00`,
                notes: appointmentData.notes
            };

            const response = await axiosInstance.get('/appointments');

            if (!response.data) throw new Error('Failed to create appointment');

            setDialogOpen(false);
            setAppointmentData({
                customer_id: '',
                branch_id: '',
                staff_id: '',
                service_id: '',
                date: '',
                start_time: '',
                end_time: '',
                notes: ''
            });
            fetchAppointments();
        } catch (error) {
            // Creation failed - could show toast notification
        } finally {
            setSaving(false);
        }
    };

    const handleDeleteAppointment = async (appointmentId) => {
        try {
            const response = await axiosInstance.get(`/appointments/`);

            if (!response.data) throw new Error('Failed to delete appointment');

            setDeleteDialogOpen(false);
            fetchAppointments();
        } catch (error) {
            // Deletion failed - could show toast notification
        }
    };

    const openAppointmentDetails = (appointment) => {
        setSelectedAppointment(appointment);
        setSheetOpen(true);
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'confirmed':
                return <Badge className="bg-green-100 text-green-800">Bestätigt</Badge>;
            case 'pending':
                return <Badge className="bg-yellow-100 text-yellow-800">Ausstehend</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Abgesagt</Badge>;
            case 'completed':
                return <Badge className="bg-blue-100 text-blue-800">Abgeschlossen</Badge>;
            case 'no_show':
                return <Badge variant="secondary">Nicht erschienen</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    const getTimeDisplay = (appointment) => {
        const start = dayjs(appointment.starts_at);
        const end = dayjs(appointment.ends_at);
        return `${start.format('HH:mm')} - ${end.format('HH:mm')}`;
    };

    return (
        <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Termine</h1>
                        <p className="text-muted-foreground">Verwalten Sie Ihre Kundentermine</p>
                    </div>
                    <Button onClick={() => setDialogOpen(true)}>
                        <Plus className="h-4 w-4 mr-2" />
                        Neuer Termin
                    </Button>
                </div>

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Heute</CardTitle>
                            <CalendarDays className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.today}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Diese Woche</CardTitle>
                            <CalendarDays className="h-4 w-4 text-purple-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.this_week}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Bestätigt</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.confirmed}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Ausstehend</CardTitle>
                            <AlertCircle className="h-4 w-4 text-yellow-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.pending}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Abgesagt</CardTitle>
                            <XCircle className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.cancelled}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Suchen Sie nach Kunde, Service oder Mitarbeiter..."
                                        value={filters.search}
                                        onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                                        className="pl-8"
                                    />
                                </div>
                            </div>
                            <Select
                                value={filters.status}
                                onValueChange={(value) => setFilters({ ...filters, status: value })}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Status</SelectItem>
                                    <SelectItem value="pending">Ausstehend</SelectItem>
                                    <SelectItem value="confirmed">Bestätigt</SelectItem>
                                    <SelectItem value="completed">Abgeschlossen</SelectItem>
                                    <SelectItem value="cancelled">Abgesagt</SelectItem>
                                    <SelectItem value="no_show">Nicht erschienen</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.branch_id}
                                onValueChange={(value) => setFilters({ ...filters, branch_id: value })}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Filiale" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Alle Filialen</SelectItem>
                                    {branches.map((branch) => (
                                        <SelectItem key={branch.id} value={branch.id}>
                                            {branch.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button 
                                variant="outline" 
                                size="icon"
                                onClick={fetchAppointments}
                            >
                                <RefreshCw className="h-4 w-4" />
                            </Button>
                        </div>
                    </CardHeader>
                </Card>

                {/* Calendar View / List View Tabs */}
                <Tabs defaultValue="list" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="list">Listenansicht</TabsTrigger>
                        <TabsTrigger value="calendar">Kalenderansicht</TabsTrigger>
                    </TabsList>

                    <TabsContent value="list">
                        <Card>
                            <CardContent className="p-0">
                                {loading ? (
                                    <div className="flex items-center justify-center p-8">
                                        <Loader2 className="h-8 w-8 animate-spin" />
                                    </div>
                                ) : appointments.length === 0 ? (
                                    <div className="text-center p-8">
                                        <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                        <p className="text-muted-foreground">Keine Termine gefunden</p>
                                    </div>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Datum & Zeit</TableHead>
                                                <TableHead>Kunde</TableHead>
                                                <TableHead>Service</TableHead>
                                                <TableHead>Mitarbeiter</TableHead>
                                                <TableHead>Filiale</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead className="text-right">Aktionen</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {appointments.map((appointment) => (
                                                <TableRow 
                                                    key={appointment.id}
                                                    className="cursor-pointer"
                                                    onClick={() => openAppointmentDetails(appointment)}
                                                >
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">
                                                                {dayjs(appointment.starts_at).format('DD.MM.YYYY')}
                                                            </div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {getTimeDisplay(appointment)}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <User className="h-4 w-4 text-muted-foreground" />
                                                            <div>
                                                                <div className="font-medium">
                                                                    {appointment.customer?.name || 'N/A'}
                                                                </div>
                                                                {appointment.customer?.phone && (
                                                                    <div className="text-sm text-muted-foreground">
                                                                        {appointment.customer.phone}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <Briefcase className="h-4 w-4 text-muted-foreground" />
                                                            {appointment.service?.name || 'N/A'}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {appointment.staff?.name || 'N/A'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <Building className="h-4 w-4 text-muted-foreground" />
                                                            {appointment.branch?.name || 'N/A'}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {getStatusBadge(appointment.status)}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild onClick={(e) => e.stopPropagation()}>
                                                                <Button variant="ghost" size="icon">
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                <DropdownMenuLabel>Aktionen</DropdownMenuLabel>
                                                                <DropdownMenuItem onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    openAppointmentDetails(appointment);
                                                                }}>
                                                                    <Eye className="h-4 w-4 mr-2" />
                                                                    Details anzeigen
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem 
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        handleStatusChange(appointment.id, 'confirmed');
                                                                    }}
                                                                    disabled={appointment.status === 'confirmed'}
                                                                >
                                                                    <CheckCircle className="h-4 w-4 mr-2" />
                                                                    Bestätigen
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem 
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        handleStatusChange(appointment.id, 'cancelled');
                                                                    }}
                                                                    disabled={appointment.status === 'cancelled'}
                                                                >
                                                                    <XCircle className="h-4 w-4 mr-2" />
                                                                    Absagen
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem 
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        handleStatusChange(appointment.id, 'completed');
                                                                    }}
                                                                    disabled={appointment.status === 'completed'}
                                                                >
                                                                    <CheckCircle className="h-4 w-4 mr-2" />
                                                                    Als abgeschlossen markieren
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem 
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        setSelectedAppointment(appointment);
                                                                        setDeleteDialogOpen(true);
                                                                    }}
                                                                    className="text-red-600"
                                                                >
                                                                    <Trash2 className="h-4 w-4 mr-2" />
                                                                    Löschen
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="calendar">
                        <Card>
                            <CardContent className="p-6">
                                <Alert>
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        Die Kalenderansicht wird in Kürze verfügbar sein.
                                    </AlertDescription>
                                </Alert>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

                {/* Appointment Details Sheet */}
                <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle>Termindetails</SheetTitle>
                            <SheetDescription>
                                Vollständige Informationen zum Termin
                            </SheetDescription>
                        </SheetHeader>
                        {selectedAppointment && (
                            <div className="mt-6 space-y-6">
                                <div className="space-y-4">
                                    <div>
                                        <Label className="text-muted-foreground">Status</Label>
                                        <div className="mt-1">{getStatusBadge(selectedAppointment.status)}</div>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Datum & Zeit</Label>
                                        <p className="text-lg font-medium">
                                            {dayjs(selectedAppointment.starts_at).format('dddd, DD. MMMM YYYY')}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {getTimeDisplay(selectedAppointment)}
                                        </p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Kunde</Label>
                                        <div className="flex items-center gap-2 mt-1">
                                            <User className="h-4 w-4" />
                                            <div>
                                                <p className="font-medium">{selectedAppointment.customer?.name}</p>
                                                {selectedAppointment.customer?.email && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {selectedAppointment.customer.email}
                                                    </p>
                                                )}
                                                {selectedAppointment.customer?.phone && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {selectedAppointment.customer.phone}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Service</Label>
                                        <p className="flex items-center gap-2 mt-1">
                                            <Briefcase className="h-4 w-4" />
                                            {selectedAppointment.service?.name}
                                        </p>
                                        {selectedAppointment.service?.duration && (
                                            <p className="text-sm text-muted-foreground">
                                                Dauer: {selectedAppointment.service.duration} Minuten
                                            </p>
                                        )}
                                        {selectedAppointment.service?.price && (
                                            <p className="text-sm text-muted-foreground">
                                                Preis: {selectedAppointment.service.price}€
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Mitarbeiter</Label>
                                        <p className="flex items-center gap-2 mt-1">
                                            <User className="h-4 w-4" />
                                            {selectedAppointment.staff?.name}
                                        </p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Filiale</Label>
                                        <p className="flex items-center gap-2 mt-1">
                                            <Building className="h-4 w-4" />
                                            {selectedAppointment.branch?.name}
                                        </p>
                                        {selectedAppointment.branch?.address && (
                                            <p className="text-sm text-muted-foreground">
                                                {selectedAppointment.branch.address}
                                            </p>
                                        )}
                                    </div>
                                    {selectedAppointment.notes && (
                                        <div>
                                            <Label className="text-muted-foreground">Notizen</Label>
                                            <p className="mt-1">{selectedAppointment.notes}</p>
                                        </div>
                                    )}
                                    <div>
                                        <Label className="text-muted-foreground">Erstellt am</Label>
                                        <p>{dayjs(selectedAppointment.created_at).format('DD.MM.YYYY HH:mm')}</p>
                                    </div>
                                </div>

                                <div className="flex gap-2">
                                    <Button
                                        onClick={() => handleStatusChange(selectedAppointment.id, 'confirmed')}
                                        disabled={selectedAppointment.status === 'confirmed'}
                                        className="flex-1"
                                    >
                                        <CheckCircle className="h-4 w-4 mr-2" />
                                        Bestätigen
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={() => {
                                            setDeleteDialogOpen(true);
                                        }}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </SheetContent>
                </Sheet>

                {/* Create Appointment Dialog */}
                <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Neuen Termin erstellen</DialogTitle>
                            <DialogDescription>
                                Fügen Sie einen neuen Termin zum Kalender hinzu
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="customer">Kunde</Label>
                                    <Select
                                        value={appointmentData.customer_id}
                                        onValueChange={(value) => setAppointmentData({ ...appointmentData, customer_id: value })}
                                    >
                                        <SelectTrigger id="customer">
                                            <SelectValue placeholder="Kunde wählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {customers.map((customer) => (
                                                <SelectItem key={customer.id} value={customer.id}>
                                                    {customer.name} - {customer.phone}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="service">Service</Label>
                                    <Select
                                        value={appointmentData.service_id}
                                        onValueChange={(value) => setAppointmentData({ ...appointmentData, service_id: value })}
                                    >
                                        <SelectTrigger id="service">
                                            <SelectValue placeholder="Service wählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {services.map((service) => (
                                                <SelectItem key={service.id} value={service.id}>
                                                    {service.name} ({service.duration} Min)
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="staff">Mitarbeiter</Label>
                                    <Select
                                        value={appointmentData.staff_id}
                                        onValueChange={(value) => setAppointmentData({ ...appointmentData, staff_id: value })}
                                    >
                                        <SelectTrigger id="staff">
                                            <SelectValue placeholder="Mitarbeiter wählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {staff.map((s) => (
                                                <SelectItem key={s.id} value={s.id}>
                                                    {s.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="branch">Filiale</Label>
                                    <Select
                                        value={appointmentData.branch_id}
                                        onValueChange={(value) => setAppointmentData({ ...appointmentData, branch_id: value })}
                                    >
                                        <SelectTrigger id="branch">
                                            <SelectValue placeholder="Filiale wählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {branches.map((branch) => (
                                                <SelectItem key={branch.id} value={branch.id}>
                                                    {branch.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="date">Datum</Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        value={appointmentData.date}
                                        onChange={(e) => setAppointmentData({ ...appointmentData, date: e.target.value })}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <Label htmlFor="start-time">Startzeit</Label>
                                        <Input
                                            id="start-time"
                                            type="time"
                                            value={appointmentData.start_time}
                                            onChange={(e) => setAppointmentData({ ...appointmentData, start_time: e.target.value })}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="end-time">Endzeit</Label>
                                        <Input
                                            id="end-time"
                                            type="time"
                                            value={appointmentData.end_time}
                                            onChange={(e) => setAppointmentData({ ...appointmentData, end_time: e.target.value })}
                                        />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="notes">Notizen</Label>
                                <Input
                                    id="notes"
                                    placeholder="Optionale Notizen zum Termin"
                                    value={appointmentData.notes}
                                    onChange={(e) => setAppointmentData({ ...appointmentData, notes: e.target.value })}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setDialogOpen(false)}>
                                Abbrechen
                            </Button>
                            <Button onClick={handleCreateAppointment} disabled={saving}>
                                {saving && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                Termin erstellen
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Delete Confirmation Dialog */}
                <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Termin löschen</DialogTitle>
                            <DialogDescription>
                                Sind Sie sicher, dass Sie diesen Termin löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setDeleteDialogOpen(false)}>
                                Abbrechen
                            </Button>
                            <Button 
                                variant="destructive" 
                                onClick={() => handleDeleteAppointment(selectedAppointment.id)}
                            >
                                Löschen
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
    );
};

export default AppointmentsIndex;