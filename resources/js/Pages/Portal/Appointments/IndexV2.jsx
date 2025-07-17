import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Badge } from '../../../components/ui/badge';
import { Input } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../../components/ui/dialog';
import { Label } from '../../../components/ui/label';
import { Textarea } from '../../../components/ui/textarea';
import { useAuth } from '../../../hooks/useAuth';
import { toast } from 'react-toastify';
import axiosInstance from '../../../services/axiosInstance';
import AppointmentCalendar from '../../../components/Portal/AppointmentCalendar';
import AppointmentDetails from '../../../components/Portal/AppointmentDetails';
import { 
    Calendar,
    Clock,
    User,
    Building,
    Phone,
    Mail,
    MapPin,
    FileText,
    Plus,
    RefreshCw,
    Search,
    Filter,
    CheckCircle,
    XCircle,
    AlertCircle,
    Edit,
    Trash2,
    Eye,
    ChevronLeft,
    ChevronRight,
    CalendarCheck,
    CalendarX,
    DollarSign,
    Users,
    BarChart3,
    TrendingUp,
    MessageSquare,
    Send,
    PhoneCall
} from 'lucide-react';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import relativeTime from 'dayjs/plugin/relativeTime';
import { useIsMobile } from '../../../hooks/useMediaQuery';
import MobileAppointmentDetails from '../../../components/Mobile/MobileAppointmentDetails';
import { cn } from '../../../lib/utils';

dayjs.locale('de');
dayjs.extend(relativeTime);

const AppointmentsIndexV2 = () => {
    const navigate = useNavigate();
    const { csrfToken } = useAuth();
    const isMobile = useIsMobile();
    const [appointments, setAppointments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [activeView, setActiveView] = useState('calendar'); // calendar | list
    const [filters, setFilters] = useState({
        status: 'all',
        branch_id: null,
        staff_id: null,
        service_id: null,
        date_range: null,
        search: ''
    });
    const [stats, setStats] = useState({
        total: 0,
        today: 0,
        this_week: 0,
        confirmed: 0,
        pending: 0,
        cancelled: 0,
        revenue_today: 0,
        revenue_week: 0
    });
    const [branches, setBranches] = useState([]);
    const [staff, setStaff] = useState([]);
    const [services, setServices] = useState([]);
    const [selectedAppointment, setSelectedAppointment] = useState(null);
    const [showDetailsDialog, setShowDetailsDialog] = useState(false);
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0
    });
    const [searchTerm, setSearchTerm] = useState('');
    const [showFilters, setShowFilters] = useState(false);

    useEffect(() => {
        fetchAppointments();
        fetchFilterOptions();
    }, [filters, pagination.current_page]);

    const fetchAppointments = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: pagination.current_page,
                per_page: pagination.per_page
            });
            
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
            if (searchTerm) {
                params.append('search', searchTerm);
            }

            const response = await axiosInstance.get(`/business/api/appointments?${params}`);
            const data = response.data;
            
            setAppointments(data.appointments.data || []);
            setStats(data.stats || {});
            setPagination({
                current_page: data.appointments.current_page,
                last_page: data.appointments.last_page,
                per_page: data.appointments.per_page,
                total: data.appointments.total
            });
        } catch (error) {
            toast.error('Fehler beim Laden der Termine');
        } finally {
            setLoading(false);
        }
    };

    const fetchFilterOptions = async () => {
        try {
            const response = await axiosInstance.get('/business/api/appointments/filters');
            const data = response.data;
            setBranches(data.branches || []);
            setStaff(data.staff || []);
            setServices(data.services || []);
        } catch (error) {
            // Silently handle filters error
        }
    };

    const handleStatusChange = async (appointmentId, newStatus) => {
        try {
            await axiosInstance.post(`/business/api/appointments/${appointmentId}/status`, {
                status: newStatus
            });
            toast.success('Status erfolgreich aktualisiert');
            fetchAppointments();
        } catch (error) {
            toast.error('Fehler beim Aktualisieren des Status');
        }
    };

    const handleAppointmentClick = (appointment) => {
        setSelectedAppointment(appointment);
        setShowDetailsDialog(true);
    };

    const handleDateClick = (date) => {
        setShowCreateDialog(true);
        // TODO: Pre-fill date in create dialog
    };

    const handleCreateAppointment = () => {
        setShowCreateDialog(true);
    };

    const getStatusColor = (status) => {
        const colors = {
            'pending': 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'confirmed': 'bg-green-100 text-green-800 border-green-300',
            'completed': 'bg-blue-100 text-blue-800 border-blue-300',
            'cancelled': 'bg-red-100 text-red-800 border-red-300',
            'no_show': 'bg-gray-100 text-gray-800 border-gray-300'
        };
        return colors[status] || colors.pending;
    };

    const getStatusText = (status) => {
        const texts = {
            'pending': 'Unbestätigt',
            'confirmed': 'Bestätigt',
            'completed': 'Abgeschlossen',
            'cancelled': 'Storniert',
            'no_show': 'Nicht erschienen'
        };
        return texts[status] || status;
    };

    const getStatusIcon = (status) => {
        switch(status) {
            case 'confirmed':
                return <CheckCircle className="h-4 w-4" />;
            case 'cancelled':
                return <XCircle className="h-4 w-4" />;
            case 'completed':
                return <CalendarCheck className="h-4 w-4" />;
            case 'no_show':
                return <CalendarX className="h-4 w-4" />;
            default:
                return <AlertCircle className="h-4 w-4" />;
        }
    };

    // Mobile View
    if (isMobile) {
        return (
            <div className="min-h-screen bg-gray-50">
                {/* Mobile Header */}
                <div className="bg-white p-4 shadow-sm sticky top-0 z-10">
                    <div className="flex items-center justify-between mb-3">
                        <h1 className="text-xl font-bold">Termine</h1>
                        <Button size="sm" onClick={handleCreateAppointment}>
                            <Plus className="h-4 w-4" />
                        </Button>
                    </div>
                    
                    {/* Mobile Stats */}
                    <div className="grid grid-cols-3 gap-2 text-center">
                        <div className="bg-gray-50 p-2 rounded">
                            <div className="text-xs text-gray-500">Heute</div>
                            <div className="text-lg font-bold">{stats.today}</div>
                        </div>
                        <div className="bg-green-50 p-2 rounded">
                            <div className="text-xs text-gray-500">Bestätigt</div>
                            <div className="text-lg font-bold text-green-600">{stats.confirmed}</div>
                        </div>
                        <div className="bg-yellow-50 p-2 rounded">
                            <div className="text-xs text-gray-500">Offen</div>
                            <div className="text-lg font-bold text-yellow-600">{stats.pending}</div>
                        </div>
                    </div>
                </div>

                {/* Mobile Appointment List */}
                <div className="p-4 space-y-3">
                    {loading ? (
                        <div className="text-center py-8">
                            <RefreshCw className="h-6 w-6 animate-spin mx-auto text-gray-400" />
                            <p className="mt-2 text-gray-500">Lade Termine...</p>
                        </div>
                    ) : appointments.length === 0 ? (
                        <div className="text-center py-8">
                            <Calendar className="h-12 w-12 mx-auto text-gray-400" />
                            <p className="mt-2 text-gray-500">Keine Termine gefunden</p>
                        </div>
                    ) : (
                        appointments.map((appointment) => (
                            <Card
                                key={appointment.id}
                                className="cursor-pointer hover:shadow-lg transition-shadow"
                                onClick={() => handleAppointmentClick(appointment)}
                            >
                                <CardContent className="p-4">
                                    <div className="flex justify-between items-start mb-2">
                                        <div>
                                            <p className="font-semibold">{appointment.customer?.name || 'Unbekannt'}</p>
                                            <p className="text-sm text-gray-500">{appointment.service?.name}</p>
                                        </div>
                                        <Badge className={cn("gap-1", getStatusColor(appointment.status))}>
                                            {getStatusIcon(appointment.status)}
                                            {getStatusText(appointment.status)}
                                        </Badge>
                                    </div>
                                    <div className="flex items-center gap-4 text-sm text-gray-600">
                                        <div className="flex items-center gap-1">
                                            <Calendar className="h-3 w-3" />
                                            {dayjs(appointment.starts_at).format('DD.MM.YYYY')}
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <Clock className="h-3 w-3" />
                                            {dayjs(appointment.starts_at).format('HH:mm')}
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <User className="h-3 w-3" />
                                            {appointment.staff?.name}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>

                {/* Mobile Details Dialog */}
                {selectedAppointment && (
                    <MobileAppointmentDetails
                        appointment={selectedAppointment}
                        open={showDetailsDialog}
                        onOpenChange={setShowDetailsDialog}
                        onUpdate={fetchAppointments}
                    />
                )}
            </div>
        );
    }

    // Desktop View
    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold flex items-center gap-2">
                        <Calendar className="h-8 w-8" />
                        Termine
                    </h1>
                    <p className="text-muted-foreground mt-1">
                        Verwalten Sie alle Termine und Buchungen
                    </p>
                </div>
                <div className="flex gap-3">
                    <Button
                        variant="outline"
                        onClick={fetchAppointments}
                        disabled={loading}
                    >
                        <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Aktualisieren
                    </Button>
                    <Button onClick={handleCreateAppointment}>
                        <Plus className="h-4 w-4 mr-2" />
                        Neuer Termin
                    </Button>
                </div>
            </div>

            {/* Stats Cards */}
            <div className="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Termine heute
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <Calendar className="h-5 w-5 text-muted-foreground" />
                            <span className="text-2xl font-bold">{stats.today || 0}</span>
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            {stats.revenue_today > 0 && `€${stats.revenue_today.toFixed(2)} Umsatz`}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Diese Woche
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <TrendingUp className="h-5 w-5 text-blue-500" />
                            <span className="text-2xl font-bold">{stats.this_week || 0}</span>
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            {stats.revenue_week > 0 && `€${stats.revenue_week.toFixed(2)} Umsatz`}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Bestätigt
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <CheckCircle className="h-5 w-5 text-green-500" />
                            <span className="text-2xl font-bold text-green-600">{stats.confirmed || 0}</span>
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Bestätigte Termine
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Ausstehend
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5 text-yellow-500" />
                            <span className="text-2xl font-bold text-yellow-600">{stats.pending || 0}</span>
                        </div>
                        <p className="text-xs text-muted-foreground mt-1">
                            Benötigen Bestätigung
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* View Tabs */}
            <Tabs value={activeView} onValueChange={setActiveView} className="w-full">
                <TabsList className="grid w-full grid-cols-2 max-w-[400px]">
                    <TabsTrigger value="calendar" className="flex items-center gap-2">
                        <Calendar className="h-4 w-4" />
                        Kalender
                    </TabsTrigger>
                    <TabsTrigger value="list" className="flex items-center gap-2">
                        <BarChart3 className="h-4 w-4" />
                        Liste
                    </TabsTrigger>
                </TabsList>

                {/* Calendar View */}
                <TabsContent value="calendar" className="mt-6">
                    <AppointmentCalendar
                        appointments={appointments}
                        onAppointmentClick={handleAppointmentClick}
                        onDateClick={handleDateClick}
                        onCreateAppointment={handleCreateAppointment}
                        branches={branches}
                        staff={staff}
                        services={services}
                    />
                </TabsContent>

                {/* List View */}
                <TabsContent value="list" className="mt-6">
                    {/* Filters */}
                    <Card className="mb-6">
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <CardTitle className="text-lg">Filter & Suche</CardTitle>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowFilters(!showFilters)}
                                >
                                    <Filter className="h-4 w-4 mr-2" />
                                    {showFilters ? 'Filter ausblenden' : 'Filter anzeigen'}
                                </Button>
                            </div>
                        </CardHeader>
                        {showFilters && (
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-4">
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                        <Input
                                            placeholder="Suche nach Name oder Telefon..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                    <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Alle Status</SelectItem>
                                            <SelectItem value="pending">Unbestätigt</SelectItem>
                                            <SelectItem value="confirmed">Bestätigt</SelectItem>
                                            <SelectItem value="completed">Abgeschlossen</SelectItem>
                                            <SelectItem value="cancelled">Storniert</SelectItem>
                                            <SelectItem value="no_show">Nicht erschienen</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Select value={filters.branch_id || 'all'} onValueChange={(value) => setFilters({...filters, branch_id: value === 'all' ? null : value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Filiale" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Alle Filialen</SelectItem>
                                            {branches.map(branch => (
                                                <SelectItem key={branch.id} value={branch.id}>
                                                    {branch.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Select value={filters.staff_id || 'all'} onValueChange={(value) => setFilters({...filters, staff_id: value === 'all' ? null : value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Mitarbeiter" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Alle Mitarbeiter</SelectItem>
                                            {staff.map(member => (
                                                <SelectItem key={member.id} value={member.id}>
                                                    {member.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        )}
                    </Card>

                    {/* Appointments Table */}
                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Datum & Zeit</TableHead>
                                        <TableHead>Kunde</TableHead>
                                        <TableHead>Service</TableHead>
                                        <TableHead>Mitarbeiter</TableHead>
                                        <TableHead>Filiale</TableHead>
                                        <TableHead>Preis</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {loading ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center py-8">
                                                <RefreshCw className="h-6 w-6 animate-spin mx-auto text-gray-400" />
                                                <p className="mt-2 text-gray-500">Lade Termine...</p>
                                            </TableCell>
                                        </TableRow>
                                    ) : appointments.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center py-8">
                                                <Calendar className="h-12 w-12 mx-auto text-gray-400" />
                                                <p className="mt-2 text-gray-500">Keine Termine gefunden</p>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        appointments.map((appointment) => (
                                            <TableRow key={appointment.id} className="hover:bg-gray-50">
                                                <TableCell>
                                                    <div>
                                                        <p className="font-medium">{dayjs(appointment.starts_at).format('DD.MM.YYYY')}</p>
                                                        <p className="text-sm text-gray-500">
                                                            {dayjs(appointment.starts_at).format('HH:mm')} - {dayjs(appointment.ends_at).format('HH:mm')}
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <p className="font-medium">{appointment.customer?.name || 'Unbekannt'}</p>
                                                        <p className="text-sm text-gray-500 flex items-center gap-1">
                                                            <Phone className="h-3 w-3" />
                                                            {appointment.customer?.phone}
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <p>{appointment.service?.name}</p>
                                                        <p className="text-sm text-gray-500">{appointment.service?.duration} Min.</p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-1">
                                                        <User className="h-4 w-4 text-gray-400" />
                                                        <span>{appointment.staff?.name}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-1">
                                                        <Building className="h-4 w-4 text-gray-400" />
                                                        <span>{appointment.branch?.name}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {appointment.service?.price && (
                                                        <div className="flex items-center gap-1">
                                                            <DollarSign className="h-4 w-4 text-gray-400" />
                                                            <span>€{appointment.service.price}</span>
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge className={cn("gap-1", getStatusColor(appointment.status))}>
                                                        {getStatusIcon(appointment.status)}
                                                        {getStatusText(appointment.status)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleAppointmentClick(appointment)}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                        {appointment.status === 'pending' && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleStatusChange(appointment.id, 'confirmed')}
                                                                className="text-green-600 hover:text-green-700"
                                                            >
                                                                <CheckCircle className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                        {appointment.status !== 'cancelled' && appointment.status !== 'completed' && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleStatusChange(appointment.id, 'cancelled')}
                                                                className="text-red-600 hover:text-red-700"
                                                            >
                                                                <XCircle className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            {/* Pagination */}
                            {pagination && pagination.last_page > 1 && (
                                <div className="flex items-center justify-between px-4 py-3 border-t">
                                    <div className="text-sm text-gray-700">
                                        Zeige {appointments.length} von {pagination.total} Einträgen
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setPagination({...pagination, current_page: pagination.current_page - 1})}
                                            disabled={pagination.current_page === 1}
                                        >
                                            <ChevronLeft className="h-4 w-4" />
                                            Zurück
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setPagination({...pagination, current_page: pagination.current_page + 1})}
                                            disabled={pagination.current_page === pagination.last_page}
                                        >
                                            Weiter
                                            <ChevronRight className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Appointment Details Dialog */}
            <Dialog open={showDetailsDialog} onOpenChange={setShowDetailsDialog}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Termin-Details</DialogTitle>
                    </DialogHeader>
                    {selectedAppointment && (
                        <AppointmentDetails
                            appointment={selectedAppointment}
                            onUpdate={fetchAppointments}
                            onClose={() => setShowDetailsDialog(false)}
                        />
                    )}
                </DialogContent>
            </Dialog>

            {/* Create Appointment Dialog */}
            <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Neuen Termin erstellen</DialogTitle>
                        <DialogDescription>
                            Erstellen Sie einen neuen Termin für einen Kunden
                        </DialogDescription>
                    </DialogHeader>
                    <form className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="customer_phone">Telefonnummer</Label>
                            <Input id="customer_phone" placeholder="+49..." />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="customer_name">Kundenname</Label>
                            <Input id="customer_name" placeholder="Max Mustermann" />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="service">Service</Label>
                            <Select>
                                <SelectTrigger>
                                    <SelectValue placeholder="Service auswählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    {services.map(service => (
                                        <SelectItem key={service.id} value={service.id}>
                                            {service.name} ({service.duration} Min.)
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="staff">Mitarbeiter</Label>
                            <Select>
                                <SelectTrigger>
                                    <SelectValue placeholder="Mitarbeiter auswählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    {staff.map(member => (
                                        <SelectItem key={member.id} value={member.id}>
                                            {member.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="notes">Notizen</Label>
                            <Textarea id="notes" placeholder="Optionale Notizen..." />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setShowCreateDialog(false)}>
                                Abbrechen
                            </Button>
                            <Button type="submit">
                                Termin erstellen
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default AppointmentsIndexV2;