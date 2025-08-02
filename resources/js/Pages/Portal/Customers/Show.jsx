import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Badge } from '../../../components/ui/badge';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '../../../components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '../../../components/ui/dialog';
import { Label } from '../../../components/ui/label';
import { Input } from '../../../components/ui/input';
import { Textarea } from '../../../components/ui/textarea';
import { 
    ArrowLeft,
    User, 
    Phone, 
    Mail, 
    Calendar,
    Edit,
    Trash2,
    AlertTriangle,
    Building,
    MapPin,
    Clock,
    FileText,
    PhoneCall,
    CalendarDays,
    MessageSquare,
    TrendingUp,
    Activity,
    BarChart3,
    Euro,
    Star,
    CheckCircle,
    XCircle,
    RefreshCw,
    Plus,
    Download
} from 'lucide-react';
import { useAuth } from '../../../hooks/useAuth';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/de';
import axiosInstance from '../../../services/axiosInstance';

dayjs.extend(relativeTime);
dayjs.locale('de');

const CustomerShow = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const { csrfToken } = useAuth();
    const [loading, setLoading] = useState(true);
    const [customer, setCustomer] = useState(null);
    const [timeline, setTimeline] = useState([]);
    const [appointments, setAppointments] = useState([]);
    const [calls, setCalls] = useState([]);
    const [stats, setStats] = useState(null);
    const [error, setError] = useState('');
    const [activeTab, setActiveTab] = useState('overview');
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [showNoteDialog, setShowNoteDialog] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        street: '',
        city: '',
        postal_code: '',
        country: 'DE',
        notes: ''
    });
    const [noteContent, setNoteContent] = useState('');

    useEffect(() => {
        fetchCustomerData();
    }, [id]);

    const fetchCustomerData = async () => {
        setLoading(true);
        setError('');
        
        try {
            // Fetch all customer data in parallel
            const [
                customerResponse,
                timelineResponse,
                appointmentsResponse,
                callsResponse,
                statsResponse
            ] = await Promise.all([
                axiosInstance.get(`/customers/${id}`),
                axiosInstance.get(`/customers/${id}/timeline`),
                axiosInstance.get(`/customers/${id}/appointments`),
                axiosInstance.get(`/customers/${id}/calls`),
                axiosInstance.get(`/customer-journey/stats?customer_id=${id}`)
            ]);

            setCustomer(customerResponse.data.customer || customerResponse.data);
            setTimeline(timelineResponse.data.timeline || []);
            setAppointments(appointmentsResponse.data.appointments || []);
            setCalls(callsResponse.data.calls || []);
            setStats(statsResponse.data.stats || statsResponse.data);

            // Set form data for editing
            const customerData = customerResponse.data.customer || customerResponse.data;
            setFormData({
                name: customerData.name || '',
                email: customerData.email || '',
                phone: customerData.phone || '',
                street: customerData.street || '',
                city: customerData.city || '',
                postal_code: customerData.postal_code || '',
                country: customerData.country || 'DE',
                notes: customerData.notes || ''
            });
        } catch (err) {
            setError('Fehler beim Laden der Kundendaten');
            console.error('Error fetching customer data:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleUpdateCustomer = async () => {
        try {
            await axiosInstance.put(`/customers/${id}`, formData);
            setShowEditDialog(false);
            fetchCustomerData();
            // Show success message
        } catch (error) {
            setError('Fehler beim Aktualisieren des Kunden');
        }
    };

    const handleDeleteCustomer = async () => {
        try {
            await axiosInstance.delete(`/customers/${id}`);
            navigate('/customers');
        } catch (error) {
            setError('Fehler beim Löschen des Kunden');
        }
    };

    const handleAddNote = async () => {
        try {
            await axiosInstance.post(`/customer-journey/customer/${id}/note`, {
                content: noteContent
            });
            setShowNoteDialog(false);
            setNoteContent('');
            fetchCustomerData();
        } catch (error) {
            setError('Fehler beim Hinzufügen der Notiz');
        }
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: { color: 'bg-green-100 text-green-800', label: 'Aktiv' },
            inactive: { color: 'bg-gray-100 text-gray-800', label: 'Inaktiv' },
            vip: { color: 'bg-purple-100 text-purple-800', label: 'VIP' },
            blacklisted: { color: 'bg-red-100 text-red-800', label: 'Gesperrt' }
        };

        const config = statusConfig[status] || statusConfig.active;
        return <Badge className={config.color}>{config.label}</Badge>;
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount || 0);
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-96">
                <RefreshCw className="h-8 w-8 animate-spin text-primary" />
            </div>
        );
    }

    if (error) {
        return (
            <Alert variant="destructive">
                <AlertTriangle className="h-4 w-4" />
                <AlertDescription>{error}</AlertDescription>
            </Alert>
        );
    }

    if (!customer) {
        return (
            <Alert>
                <AlertDescription>Kunde nicht gefunden</AlertDescription>
            </Alert>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => navigate('/customers')}
                    >
                        <ArrowLeft className="h-4 w-4 mr-2" />
                        Zurück
                    </Button>
                    <div>
                        <h1 className="text-3xl font-bold">{customer.name}</h1>
                        <p className="text-muted-foreground">
                            Kunde seit {dayjs(customer.created_at).format('DD.MM.YYYY')}
                        </p>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={() => setShowEditDialog(true)}>
                        <Edit className="h-4 w-4 mr-2" />
                        Bearbeiten
                    </Button>
                    <Button 
                        variant="outline" 
                        className="text-red-600 hover:text-red-700"
                        onClick={() => setShowDeleteDialog(true)}
                    >
                        <Trash2 className="h-4 w-4 mr-2" />
                        Löschen
                    </Button>
                </div>
            </div>

            {/* Quick Stats */}
            <div className="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    Termine gesamt
                                </p>
                                <p className="text-2xl font-bold">
                                    {stats?.total_appointments || 0}
                                </p>
                            </div>
                            <CalendarDays className="h-8 w-8 text-muted-foreground" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    Anrufe gesamt
                                </p>
                                <p className="text-2xl font-bold">
                                    {stats?.total_calls || 0}
                                </p>
                            </div>
                            <PhoneCall className="h-8 w-8 text-muted-foreground" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    Gesamtumsatz
                                </p>
                                <p className="text-2xl font-bold">
                                    {formatCurrency(stats?.total_revenue)}
                                </p>
                            </div>
                            <Euro className="h-8 w-8 text-muted-foreground" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    No-Shows
                                </p>
                                <p className="text-2xl font-bold">
                                    {stats?.no_shows || 0}
                                </p>
                            </div>
                            <XCircle className="h-8 w-8 text-muted-foreground" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Main Content Tabs */}
            <Tabs value={activeTab} onValueChange={setActiveTab}>
                <TabsList className="grid w-full grid-cols-4">
                    <TabsTrigger value="overview">Übersicht</TabsTrigger>
                    <TabsTrigger value="appointments">Termine</TabsTrigger>
                    <TabsTrigger value="calls">Anrufe</TabsTrigger>
                    <TabsTrigger value="timeline">Timeline</TabsTrigger>
                </TabsList>

                <TabsContent value="overview" className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Contact Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Kontaktinformationen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <Mail className="h-4 w-4 text-muted-foreground" />
                                    <span>{customer.email || 'Keine E-Mail'}</span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Phone className="h-4 w-4 text-muted-foreground" />
                                    <span>{customer.phone || 'Kein Telefon'}</span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <MapPin className="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        {customer.street && <div>{customer.street}</div>}
                                        {(customer.postal_code || customer.city) && (
                                            <div>{customer.postal_code} {customer.city}</div>
                                        )}
                                        {(!customer.street && !customer.city) && 'Keine Adresse'}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Customer Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Kundendetails</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Status</span>
                                    {getStatusBadge(customer.status || 'active')}
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Kundennummer</span>
                                    <span className="font-mono">{customer.customer_number || customer.id}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Quelle</span>
                                    <span>{customer.source || 'Direkt'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Filiale</span>
                                    <span>{customer.branch?.name || 'Hauptfiliale'}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Notes */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Notizen</CardTitle>
                            <Button 
                                variant="outline" 
                                size="sm"
                                onClick={() => setShowNoteDialog(true)}
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Notiz hinzufügen
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {customer.notes ? (
                                <p className="text-sm">{customer.notes}</p>
                            ) : (
                                <p className="text-sm text-muted-foreground">Keine Notizen vorhanden</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Activity */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Letzte Aktivitäten</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {timeline.slice(0, 5).map((event, index) => (
                                    <div key={index} className="flex gap-4">
                                        <div className={cn(
                                            "flex h-8 w-8 items-center justify-center rounded-full",
                                            event.type === 'appointment' ? 'bg-blue-100' : 'bg-green-100'
                                        )}>
                                            {event.type === 'appointment' ? (
                                                <Calendar className="h-4 w-4 text-blue-600" />
                                            ) : (
                                                <Phone className="h-4 w-4 text-green-600" />
                                            )}
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium">{event.title}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {dayjs(event.created_at).fromNow()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="appointments" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Terminhistorie</CardTitle>
                            <CardDescription>
                                Alle Termine des Kunden
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Datum</TableHead>
                                        <TableHead>Service</TableHead>
                                        <TableHead>Mitarbeiter</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Preis</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {appointments.length > 0 ? (
                                        appointments.map((appointment) => (
                                            <TableRow key={appointment.id}>
                                                <TableCell>
                                                    {dayjs(appointment.start_time).format('DD.MM.YYYY HH:mm')}
                                                </TableCell>
                                                <TableCell>{appointment.service?.name || 'N/A'}</TableCell>
                                                <TableCell>{appointment.staff?.name || 'N/A'}</TableCell>
                                                <TableCell>
                                                    <Badge variant={
                                                        appointment.status === 'completed' ? 'success' :
                                                        appointment.status === 'cancelled' ? 'destructive' :
                                                        appointment.status === 'no_show' ? 'secondary' :
                                                        'default'
                                                    }>
                                                        {appointment.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{formatCurrency(appointment.price)}</TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center text-muted-foreground">
                                                Keine Termine vorhanden
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="calls" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Anrufhistorie</CardTitle>
                            <CardDescription>
                                Alle Anrufe des Kunden
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Datum</TableHead>
                                        <TableHead>Dauer</TableHead>
                                        <TableHead>Typ</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {calls.length > 0 ? (
                                        calls.map((call) => (
                                            <TableRow key={call.id}>
                                                <TableCell>
                                                    {dayjs(call.created_at).format('DD.MM.YYYY HH:mm')}
                                                </TableCell>
                                                <TableCell>{call.duration} Sek.</TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {call.direction === 'inbound' ? 'Eingehend' : 'Ausgehend'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={
                                                        call.status === 'completed' ? 'success' :
                                                        call.status === 'missed' ? 'destructive' :
                                                        'default'
                                                    }>
                                                        {call.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => navigate(`/calls/${call.id}`)}
                                                    >
                                                        Details
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center text-muted-foreground">
                                                Keine Anrufe vorhanden
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="timeline" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Vollständige Timeline</CardTitle>
                            <CardDescription>
                                Alle Interaktionen mit dem Kunden
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="relative">
                                <div className="absolute left-8 top-0 bottom-0 w-0.5 bg-border"></div>
                                <div className="space-y-6">
                                    {timeline.map((event, index) => (
                                        <div key={index} className="flex gap-4">
                                            <div className={cn(
                                                "relative flex h-8 w-8 items-center justify-center rounded-full",
                                                event.type === 'appointment' ? 'bg-blue-100' :
                                                event.type === 'call' ? 'bg-green-100' :
                                                event.type === 'note' ? 'bg-yellow-100' :
                                                'bg-gray-100'
                                            )}>
                                                {event.type === 'appointment' ? (
                                                    <Calendar className="h-4 w-4 text-blue-600" />
                                                ) : event.type === 'call' ? (
                                                    <Phone className="h-4 w-4 text-green-600" />
                                                ) : event.type === 'note' ? (
                                                    <MessageSquare className="h-4 w-4 text-yellow-600" />
                                                ) : (
                                                    <Activity className="h-4 w-4 text-gray-600" />
                                                )}
                                            </div>
                                            <div className="flex-1 pb-6">
                                                <p className="text-sm font-medium">{event.title}</p>
                                                {event.description && (
                                                    <p className="text-sm text-muted-foreground mt-1">
                                                        {event.description}
                                                    </p>
                                                )}
                                                <p className="text-xs text-muted-foreground mt-1">
                                                    {dayjs(event.created_at).format('DD.MM.YYYY HH:mm')} • 
                                                    {dayjs(event.created_at).fromNow()}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Edit Customer Dialog */}
            <Dialog open={showEditDialog} onOpenChange={setShowEditDialog}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Kunde bearbeiten</DialogTitle>
                        <DialogDescription>
                            Aktualisieren Sie die Kundendaten
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="edit-name">Name *</Label>
                            <Input
                                id="edit-name"
                                value={formData.name}
                                onChange={(e) => setFormData({...formData, name: e.target.value})}
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit-email">E-Mail</Label>
                            <Input
                                id="edit-email"
                                type="email"
                                value={formData.email}
                                onChange={(e) => setFormData({...formData, email: e.target.value})}
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit-phone">Telefon *</Label>
                            <Input
                                id="edit-phone"
                                value={formData.phone}
                                onChange={(e) => setFormData({...formData, phone: e.target.value})}
                            />
                        </div>
                        <div>
                            <Label htmlFor="edit-street">Straße</Label>
                            <Input
                                id="edit-street"
                                value={formData.street}
                                onChange={(e) => setFormData({...formData, street: e.target.value})}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="edit-postal">PLZ</Label>
                                <Input
                                    id="edit-postal"
                                    value={formData.postal_code}
                                    onChange={(e) => setFormData({...formData, postal_code: e.target.value})}
                                />
                            </div>
                            <div>
                                <Label htmlFor="edit-city">Stadt</Label>
                                <Input
                                    id="edit-city"
                                    value={formData.city}
                                    onChange={(e) => setFormData({...formData, city: e.target.value})}
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="edit-notes">Notizen</Label>
                            <Textarea
                                id="edit-notes"
                                value={formData.notes}
                                onChange={(e) => setFormData({...formData, notes: e.target.value})}
                                rows={3}
                            />
                        </div>
                        <div className="flex justify-end gap-3">
                            <Button variant="outline" onClick={() => setShowEditDialog(false)}>
                                Abbrechen
                            </Button>
                            <Button onClick={handleUpdateCustomer}>
                                Änderungen speichern
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Delete Customer Dialog */}
            <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Kunde löschen</DialogTitle>
                        <DialogDescription>
                            Sind Sie sicher, dass Sie diesen Kunden löschen möchten?
                        </DialogDescription>
                    </DialogHeader>
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            Diese Aktion kann nicht rückgängig gemacht werden. Alle zugehörigen Termine 
                            und Anrufe bleiben erhalten, werden aber nicht mehr diesem Kunden zugeordnet.
                        </AlertDescription>
                    </Alert>
                    <div className="flex justify-end gap-3">
                        <Button variant="outline" onClick={() => setShowDeleteDialog(false)}>
                            Abbrechen
                        </Button>
                        <Button variant="destructive" onClick={handleDeleteCustomer}>
                            Kunde löschen
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Add Note Dialog */}
            <Dialog open={showNoteDialog} onOpenChange={setShowNoteDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Notiz hinzufügen</DialogTitle>
                        <DialogDescription>
                            Fügen Sie eine Notiz zu diesem Kunden hinzu
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="note-content">Notiz</Label>
                            <Textarea
                                id="note-content"
                                value={noteContent}
                                onChange={(e) => setNoteContent(e.target.value)}
                                rows={4}
                                placeholder="Geben Sie hier Ihre Notiz ein..."
                            />
                        </div>
                        <div className="flex justify-end gap-3">
                            <Button variant="outline" onClick={() => {
                                setShowNoteDialog(false);
                                setNoteContent('');
                            }}>
                                Abbrechen
                            </Button>
                            <Button onClick={handleAddNote} disabled={!noteContent.trim()}>
                                Notiz speichern
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default CustomerShow;