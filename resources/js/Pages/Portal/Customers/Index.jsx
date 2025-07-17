import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Input } from '../../../components/ui/input';
import { Badge } from '../../../components/ui/badge';
import { Alert, AlertDescription } from '../../../components/ui/alert';
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
    DialogTrigger,
} from '../../../components/ui/dialog';
import { Label } from '../../../components/ui/label';
import { Textarea } from '../../../components/ui/textarea';
import { 
    Users, 
    Search, 
    Plus, 
    Phone, 
    Mail, 
    Calendar,
    Edit,
    Trash2,
    AlertTriangle,
    UserPlus,
    Download,
    RefreshCw,
    ChevronLeft,
    ChevronRight,
    Building,
    MapPin,
    Clock,
    FileText,
    PhoneCall,
    CalendarDays,
    MoreVertical
} from 'lucide-react';
import { useAuth } from '../../../hooks/useAuth';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import axiosInstance from '../../../services/axiosInstance';

dayjs.locale('de');

const CustomersIndex = () => {
    const [loading, setLoading] = useState(true);
    const [customers, setCustomers] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCustomer, setSelectedCustomer] = useState(null);
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [error, setError] = useState(null);
    const [stats, setStats] = useState({
        total_customers: 0,
        new_this_month: 0,
        active_customers: 0,
        total_revenue: 0
    });
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 25,
        total: 0
    });
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        company_name: '',
        address: {
            street: '',
            city: '',
            postal_code: ''
        },
        tags: [],
        notes: ''
    });
    const [formErrors, setFormErrors] = useState({});
    const [userPermissions, setUserPermissions] = useState({
        is_admin: false,
        can_delete_business_data: false
    });

    useEffect(() => {
        fetchUserPermissions();
        fetchCustomers();
    }, [pagination.currentPage, searchTerm]);

    const fetchUserPermissions = async () => {
        try {
            const response = await axiosInstance.get('/user/permissions');
            setUserPermissions({
                is_admin: response.data.user?.is_admin || false,
                can_delete_business_data: response.data.user?.can_delete_business_data || false
            });
        } catch (error) {
            // Silently handle permission errors
        }
    };

    const fetchCustomers = async () => {
        try {
            setLoading(true);
            const params = new URLSearchParams({
                page: pagination.currentPage,
                per_page: pagination.perPage,
                search: searchTerm
            });

            const response = await axiosInstance.get(`/customers?${params}`);
            setCustomers(response.data.customers.data);
            setPagination({
                currentPage: response.data.customers.current_page,
                lastPage: response.data.customers.last_page,
                perPage: response.data.customers.per_page,
                total: response.data.customers.total
            });
            setStats(response.data.stats);
        } catch (error) {
            setError('Fehler beim Laden der Kundendaten');
        } finally {
            setLoading(false);
        }
    };

    const handleCreateCustomer = async () => {
        try {
            setFormErrors({});
            const response = await axiosInstance.post('/customers', formData);
            setShowCreateDialog(false);
            resetForm();
            fetchCustomers();
        } catch (error) {
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            } else {
                setError('Fehler beim Erstellen des Kunden');
            }
        }
    };

    const handleUpdateCustomer = async () => {
        try {
            setFormErrors({});
            const response = await axiosInstance.put(`/customers/${selectedCustomer.id}`, formData);
            setShowEditDialog(false);
            resetForm();
            fetchCustomers();
        } catch (error) {
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            } else {
                setError('Fehler beim Aktualisieren des Kunden');
            }
        }
    };

    const handleDeleteCustomer = async () => {
        try {
            await axiosInstance.delete(`/customers/${selectedCustomer.id}`);
            setShowDeleteDialog(false);
            setSelectedCustomer(null);
            fetchCustomers();
        } catch (error) {
            setError('Fehler beim Löschen des Kunden');
            const errorMessage = error.response?.data?.message || 'Fehler beim Löschen des Kunden';
            alert(errorMessage);
        }
    };

    const handleExport = async () => {
        try {
            const params = new URLSearchParams();
            if (searchTerm) {
                params.append('search', searchTerm);
            }
            
            window.location.href = `/business/api/customers/export-csv?${params.toString()}`;
        } catch (error) {
            setError('Fehler beim Exportieren der Kundendaten');
        }
    };

    const resetForm = () => {
        setFormData({
            name: '',
            email: '',
            phone: '',
            company_name: '',
            address: {
                street: '',
                city: '',
                postal_code: ''
            },
            tags: [],
            notes: ''
        });
        setFormErrors({});
    };

    const openEditDialog = (customer) => {
        setSelectedCustomer(customer);
        setFormData({
            name: customer.name || '',
            email: customer.email || '',
            phone: customer.phone || '',
            company_name: customer.company_name || '',
            address: customer.address || {
                street: '',
                city: '',
                postal_code: ''
            },
            tags: customer.tags || [],
            notes: customer.notes || ''
        });
        setShowEditDialog(true);
    };


    // Loading skeleton
    const LoadingSkeleton = () => (
        <div className="space-y-4">
            {[...Array(5)].map((_, i) => (
                <div key={i} className="animate-pulse">
                    <div className="h-16 bg-gray-200 rounded"></div>
                </div>
            ))}
        </div>
    );

    return (
        <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold flex items-center gap-2">
                            <Users className="h-8 w-8" />
                            Kunden
                        </h1>
                        <p className="text-muted-foreground mt-1">
                            Verwalten Sie Ihre Kundendaten und Kontaktinformationen
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Button
                            variant="outline"
                            onClick={fetchCustomers}
                            disabled={loading}
                        >
                            <RefreshCw className={cn('h-4 w-4 mr-2', loading && 'animate-spin')} />
                            Aktualisieren
                        </Button>
                        <Button
                            variant="outline"
                            onClick={handleExport}
                        >
                            <Download className="h-4 w-4 mr-2" />
                            Exportieren
                        </Button>
                        <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                            <DialogTrigger asChild>
                                <Button onClick={() => {
                                    resetForm();
                                    setShowCreateDialog(true);
                                }}>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Neuer Kunde
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-[500px]">
                                <DialogHeader>
                                    <DialogTitle>Neuen Kunden anlegen</DialogTitle>
                                    <DialogDescription>
                                        Erfassen Sie die Kundendaten für einen neuen Kunden.
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name *</Label>
                                        <Input
                                            id="name"
                                            value={formData.name}
                                            onChange={(e) => setFormData({...formData, name: e.target.value})}
                                            placeholder="Max Mustermann"
                                            className={formErrors.name ? 'border-red-500' : ''}
                                        />
                                        {formErrors.name && (
                                            <p className="text-sm text-red-500">{formErrors.name[0]}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Telefon *</Label>
                                        <Input
                                            id="phone"
                                            value={formData.phone}
                                            onChange={(e) => setFormData({...formData, phone: e.target.value})}
                                            placeholder="+49 123 456789"
                                            className={formErrors.phone ? 'border-red-500' : ''}
                                        />
                                        {formErrors.phone && (
                                            <p className="text-sm text-red-500">{formErrors.phone[0]}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">E-Mail</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={formData.email}
                                            onChange={(e) => setFormData({...formData, email: e.target.value})}
                                            placeholder="max@example.com"
                                            className={formErrors.email ? 'border-red-500' : ''}
                                        />
                                        {formErrors.email && (
                                            <p className="text-sm text-red-500">{formErrors.email[0]}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="company_name">Firma</Label>
                                        <Input
                                            id="company_name"
                                            value={formData.company_name}
                                            onChange={(e) => setFormData({...formData, company_name: e.target.value})}
                                            placeholder="Beispiel GmbH"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="street">Straße</Label>
                                        <Input
                                            id="street"
                                            value={formData.address.street}
                                            onChange={(e) => setFormData({...formData, address: {...formData.address, street: e.target.value}})}
                                            placeholder="Musterstraße 123"
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="postal_code">PLZ</Label>
                                            <Input
                                                id="postal_code"
                                                value={formData.address.postal_code}
                                                onChange={(e) => setFormData({...formData, address: {...formData.address, postal_code: e.target.value}})}
                                                placeholder="12345"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="city">Stadt</Label>
                                            <Input
                                                id="city"
                                                value={formData.address.city}
                                                onChange={(e) => setFormData({...formData, address: {...formData.address, city: e.target.value}})}
                                                placeholder="Berlin"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">Notizen</Label>
                                        <Textarea
                                            id="notes"
                                            value={formData.notes}
                                            onChange={(e) => setFormData({...formData, notes: e.target.value})}
                                            placeholder="Zusätzliche Informationen..."
                                            rows={3}
                                        />
                                    </div>
                                </div>
                                <div className="flex justify-end gap-3">
                                    <Button variant="outline" onClick={() => setShowCreateDialog(false)}>
                                        Abbrechen
                                    </Button>
                                    <Button onClick={handleCreateCustomer}>
                                        Kunde anlegen
                                    </Button>
                                </div>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                {/* Error Alert */}
                {error && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_customers}</div>
                            <p className="text-xs text-muted-foreground">Kunden insgesamt</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Neu</CardTitle>
                            <UserPlus className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.new_this_month}</div>
                            <p className="text-xs text-muted-foreground">Diesen Monat</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Aktiv</CardTitle>
                            <PhoneCall className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_customers}</div>
                            <p className="text-xs text-muted-foreground">Letzte 30 Tage</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Umsatz</CardTitle>
                            <CalendarDays className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(stats.total_revenue || 0)}
                            </div>
                            <p className="text-xs text-muted-foreground">Gesamtumsatz</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Search and Filters */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    type="text"
                                    placeholder="Suche nach Name, Telefon oder E-Mail..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {/* Customers Table */}
                <Card>
                    <CardContent className="p-0">
                        {loading ? (
                            <div className="p-6">
                                <LoadingSkeleton />
                            </div>
                        ) : customers.length === 0 ? (
                            <div className="p-12 text-center">
                                <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    Keine Kunden gefunden
                                </h3>
                                <p className="text-gray-500">
                                    {searchTerm ? 'Versuchen Sie eine andere Suche.' : 'Legen Sie Ihren ersten Kunden an.'}
                                </p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kunde</TableHead>
                                        <TableHead>Kontakt</TableHead>
                                        <TableHead>Firma</TableHead>
                                        <TableHead>Termine</TableHead>
                                        <TableHead>Umsatz</TableHead>
                                        <TableHead>Erstellt</TableHead>
                                        <TableHead className="text-right">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {customers.map((customer) => (
                                        <TableRow key={customer.id}>
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{customer.name}</div>
                                                    {customer.tags && customer.tags.length > 0 && (
                                                        <div className="flex gap-1 mt-1">
                                                            {customer.tags.map((tag, idx) => (
                                                                <Badge key={idx} variant="secondary" className="text-xs">
                                                                    {tag}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    {customer.phone && (
                                                        <div className="flex items-center gap-2 text-sm">
                                                            <Phone className="h-3 w-3" />
                                                            {customer.phone}
                                                        </div>
                                                    )}
                                                    {customer.email && (
                                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                            <Mail className="h-3 w-3" />
                                                            {customer.email}
                                                        </div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {customer.company_name ? (
                                                    <div className="flex items-center gap-2 text-sm">
                                                        <Building className="h-3 w-3 text-muted-foreground" />
                                                        {customer.company_name}
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-3 w-3 text-muted-foreground" />
                                                    <span className="text-sm">{customer.appointments_count || 0}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm font-medium">
                                                    {new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(customer.total_revenue || 0)}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm text-muted-foreground">
                                                    {customer.created_at}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openEditDialog(customer)}
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    {userPermissions.can_delete_business_data && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => {
                                                                setSelectedCustomer(customer);
                                                                setShowDeleteDialog(true);
                                                            }}
                                                            className="text-red-600 hover:text-red-700"
                                                            title="Löschen (nur Administratoren)"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}

                        {/* Pagination */}
                        {!loading && customers.length > 0 && (
                            <div className="flex items-center justify-between px-6 py-4 border-t">
                                <div className="text-sm text-muted-foreground">
                                    Zeige {((pagination.currentPage - 1) * pagination.perPage) + 1} bis{' '}
                                    {Math.min(pagination.currentPage * pagination.perPage, pagination.total)} von{' '}
                                    {pagination.total} Kunden
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setPagination({...pagination, currentPage: pagination.currentPage - 1})}
                                        disabled={pagination.currentPage === 1}
                                    >
                                        <ChevronLeft className="h-4 w-4 mr-1" />
                                        Zurück
                                    </Button>
                                    <div className="flex items-center gap-1">
                                        {[...Array(Math.min(5, pagination.lastPage))].map((_, i) => {
                                            const page = i + 1;
                                            return (
                                                <Button
                                                    key={page}
                                                    variant={page === pagination.currentPage ? 'default' : 'outline'}
                                                    size="sm"
                                                    onClick={() => setPagination({...pagination, currentPage: page})}
                                                    className="w-10"
                                                >
                                                    {page}
                                                </Button>
                                            );
                                        })}
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setPagination({...pagination, currentPage: pagination.currentPage + 1})}
                                        disabled={pagination.currentPage === pagination.lastPage}
                                    >
                                        Weiter
                                        <ChevronRight className="h-4 w-4 ml-1" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Edit Dialog */}
                <Dialog open={showEditDialog} onOpenChange={setShowEditDialog}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>Kunde bearbeiten</DialogTitle>
                            <DialogDescription>
                                Bearbeiten Sie die Kundendaten.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-name">Name *</Label>
                                <Input
                                    id="edit-name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({...formData, name: e.target.value})}
                                    placeholder="Max Mustermann"
                                    className={formErrors.name ? 'border-red-500' : ''}
                                />
                                {formErrors.name && (
                                    <p className="text-sm text-red-500">{formErrors.name[0]}</p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-phone">Telefon *</Label>
                                <Input
                                    id="edit-phone"
                                    value={formData.phone}
                                    onChange={(e) => setFormData({...formData, phone: e.target.value})}
                                    placeholder="+49 123 456789"
                                    className={formErrors.phone ? 'border-red-500' : ''}
                                />
                                {formErrors.phone && (
                                    <p className="text-sm text-red-500">{formErrors.phone[0]}</p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-email">E-Mail</Label>
                                <Input
                                    id="edit-email"
                                    type="email"
                                    value={formData.email}
                                    onChange={(e) => setFormData({...formData, email: e.target.value})}
                                    placeholder="max@example.com"
                                    className={formErrors.email ? 'border-red-500' : ''}
                                />
                                {formErrors.email && (
                                    <p className="text-sm text-red-500">{formErrors.email[0]}</p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-company_name">Firma</Label>
                                <Input
                                    id="edit-company_name"
                                    value={formData.company_name}
                                    onChange={(e) => setFormData({...formData, company_name: e.target.value})}
                                    placeholder="Beispiel GmbH"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-street">Straße</Label>
                                <Input
                                    id="edit-street"
                                    value={formData.address.street}
                                    onChange={(e) => setFormData({...formData, address: {...formData.address, street: e.target.value}})}
                                    placeholder="Musterstraße 123"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-postal_code">PLZ</Label>
                                    <Input
                                        id="edit-postal_code"
                                        value={formData.address.postal_code}
                                        onChange={(e) => setFormData({...formData, address: {...formData.address, postal_code: e.target.value}})}
                                        placeholder="12345"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="edit-city">Stadt</Label>
                                    <Input
                                        id="edit-city"
                                        value={formData.address.city}
                                        onChange={(e) => setFormData({...formData, address: {...formData.address, city: e.target.value}})}
                                        placeholder="Berlin"
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-notes">Notizen</Label>
                                <Textarea
                                    id="edit-notes"
                                    value={formData.notes}
                                    onChange={(e) => setFormData({...formData, notes: e.target.value})}
                                    placeholder="Zusätzliche Informationen..."
                                    rows={3}
                                />
                            </div>
                        </div>
                        <div className="flex justify-end gap-3">
                            <Button variant="outline" onClick={() => setShowEditDialog(false)}>
                                Abbrechen
                            </Button>
                            <Button onClick={handleUpdateCustomer}>
                                Änderungen speichern
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>

                {/* Delete Confirmation Dialog */}
                <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Kunde löschen</DialogTitle>
                            <DialogDescription>
                                Sind Sie sicher, dass Sie diesen Kunden löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                            </DialogDescription>
                        </DialogHeader>
                        {selectedCustomer && (
                            <div className="py-4">
                                <Alert>
                                    <AlertTriangle className="h-4 w-4" />
                                    <AlertDescription>
                                        Sie löschen den Kunden <strong>{selectedCustomer.name}</strong>.
                                        {selectedCustomer.appointments_count > 0 && (
                                            <span> Dieser Kunde hat {selectedCustomer.appointments_count} Termine.</span>
                                        )}
                                    </AlertDescription>
                                </Alert>
                            </div>
                        )}
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
            </div>
    );
};

export default CustomersIndex;