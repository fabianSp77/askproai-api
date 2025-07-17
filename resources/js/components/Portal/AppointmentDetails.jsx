import React, { useState, useEffect } from 'react';
import { 
    Calendar,
    Clock,
    MapPin,
    User,
    Euro,
    CheckCircle,
    AlertCircle,
    Mail,
    Bell,
    FileText,
    ExternalLink,
    Edit,
    X,
    Phone,
    MessageSquare,
    Building,
    CreditCard,
    CalendarCheck,
    CalendarX,
    ChevronRight,
    History,
    QrCode
} from 'lucide-react';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Card, CardContent } from '../ui/card';
import { cn } from '../../lib/utils';
import { format, parseISO, differenceInMinutes } from 'date-fns';
import { de } from 'date-fns/locale';
import axiosInstance from '../../services/axiosInstance';
import { toast } from 'react-toastify';

/**
 * Enhanced Appointment Details Component
 * Shows comprehensive appointment information within call details
 */
const AppointmentDetails = ({ call, appointment, onUpdate }) => {
    const [loading, setLoading] = useState(false);
    const [appointmentData, setAppointmentData] = useState(appointment);
    const [showQRCode, setShowQRCode] = useState(false);

    // Load appointment details if we have an appointment_id but no data
    useEffect(() => {
        if (call.appointment_id && !appointmentData) {
            loadAppointmentDetails();
        }
    }, [call.appointment_id]);

    const loadAppointmentDetails = async () => {
        try {
            setLoading(true);
            const response = await axiosInstance.get(`/business/api/appointments/${call.appointment_id}`);
            setAppointmentData(response.data.data);
        } catch (error) {
            console.error('Failed to load appointment details:', error);
            toast.error('Termindetails konnten nicht geladen werden');
        } finally {
            setLoading(false);
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800 border-yellow-300',
            confirmed: 'bg-green-100 text-green-800 border-green-300',
            completed: 'bg-blue-100 text-blue-800 border-blue-300',
            cancelled: 'bg-red-100 text-red-800 border-red-300',
            no_show: 'bg-gray-100 text-gray-800 border-gray-300'
        };
        return colors[status] || colors.pending;
    };

    const getStatusLabel = (status) => {
        const labels = {
            pending: 'Unbestätigt',
            confirmed: 'Bestätigt',
            completed: 'Abgeschlossen',
            cancelled: 'Storniert',
            no_show: 'Nicht erschienen'
        };
        return labels[status] || status;
    };

    const handleConfirmAppointment = async () => {
        try {
            setLoading(true);
            await axiosInstance.post(`/business/api/appointments/${appointmentData.id}/status`, {
                status: 'confirmed'
            });
            toast.success('Termin wurde bestätigt');
            setAppointmentData({ ...appointmentData, status: 'confirmed' });
            if (onUpdate) onUpdate();
        } catch (error) {
            console.error('Failed to confirm appointment:', error);
            toast.error('Fehler beim Bestätigen des Termins');
        } finally {
            setLoading(false);
        }
    };

    const handleSendReminder = async () => {
        try {
            setLoading(true);
            await axiosInstance.post(`/business/api/appointments/${appointmentData.id}/reminder`);
            toast.success('Erinnerung wurde gesendet');
        } catch (error) {
            console.error('Failed to send reminder:', error);
            toast.error('Fehler beim Senden der Erinnerung');
        } finally {
            setLoading(false);
        }
    };

    const generateQRCode = () => {
        // Generate QR code data for check-in
        const qrData = {
            type: 'appointment_checkin',
            appointment_id: appointmentData.id,
            customer_id: appointmentData.customer_id,
            timestamp: new Date().toISOString()
        };
        return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(JSON.stringify(qrData))}`;
    };

    // If no appointment data, show booking prompt
    if (!call.appointment_requested && !appointmentData) {
        return null;
    }

    // Show loading state
    if (loading && !appointmentData) {
        return (
            <Card className="animate-pulse">
                <CardContent className="p-6">
                    <div className="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
                    <div className="space-y-3">
                        <div className="h-4 bg-gray-200 rounded w-full"></div>
                        <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                        <div className="h-4 bg-gray-200 rounded w-1/2"></div>
                    </div>
                </CardContent>
            </Card>
        );
    }

    // If we have appointment data, show full details
    if (appointmentData) {
        const duration = appointmentData.starts_at && appointmentData.ends_at
            ? differenceInMinutes(parseISO(appointmentData.ends_at), parseISO(appointmentData.starts_at))
            : null;

        return (
            <Card className="overflow-hidden">
                {/* Header with Status */}
                <div className="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 border-b">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-white rounded-lg shadow-sm">
                                <Calendar className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold">Gebuchter Termin</h3>
                                <p className="text-sm text-gray-600">
                                    Referenz: #{appointmentData.id}
                                </p>
                            </div>
                        </div>
                        <Badge className={cn("border", getStatusColor(appointmentData.status))}>
                            {getStatusLabel(appointmentData.status)}
                        </Badge>
                    </div>
                </div>

                <CardContent className="p-6 space-y-6">
                    {/* Main Appointment Info */}
                    <div className="grid md:grid-cols-2 gap-6">
                        {/* Left Column */}
                        <div className="space-y-4">
                            {/* Date & Time */}
                            <div className="flex items-start gap-3">
                                <Calendar className="h-5 w-5 text-gray-400 mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm text-gray-500">Datum & Uhrzeit</p>
                                    <p className="font-semibold">
                                        {format(parseISO(appointmentData.starts_at), 'EEEE, d. MMMM yyyy', { locale: de })}
                                    </p>
                                    <p className="text-gray-700">
                                        {format(parseISO(appointmentData.starts_at), 'HH:mm')} - 
                                        {format(parseISO(appointmentData.ends_at), 'HH:mm')} Uhr
                                        {duration && <span className="text-gray-500"> ({duration} Min)</span>}
                                    </p>
                                </div>
                            </div>

                            {/* Service */}
                            <div className="flex items-start gap-3">
                                <FileText className="h-5 w-5 text-gray-400 mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm text-gray-500">Dienstleistung</p>
                                    <p className="font-semibold">{appointmentData.service?.name || call.dienstleistung}</p>
                                    {appointmentData.service?.description && (
                                        <p className="text-sm text-gray-600 mt-1">{appointmentData.service.description}</p>
                                    )}
                                </div>
                            </div>

                            {/* Staff */}
                            {appointmentData.staff && (
                                <div className="flex items-start gap-3">
                                    <User className="h-5 w-5 text-gray-400 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="text-sm text-gray-500">Mitarbeiter</p>
                                        <p className="font-semibold">{appointmentData.staff.name}</p>
                                        {appointmentData.staff.title && (
                                            <p className="text-sm text-gray-600">{appointmentData.staff.title}</p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Right Column */}
                        <div className="space-y-4">
                            {/* Branch */}
                            <div className="flex items-start gap-3">
                                <Building className="h-5 w-5 text-gray-400 mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm text-gray-500">Filiale</p>
                                    <p className="font-semibold">{appointmentData.branch?.name || call.branch_name}</p>
                                    {appointmentData.branch?.address && (
                                        <p className="text-sm text-gray-600 mt-1">{appointmentData.branch.address}</p>
                                    )}
                                </div>
                            </div>

                            {/* Price */}
                            {appointmentData.price && (
                                <div className="flex items-start gap-3">
                                    <Euro className="h-5 w-5 text-gray-400 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="text-sm text-gray-500">Preis</p>
                                        <p className="font-semibold">{appointmentData.price} €</p>
                                        {appointmentData.prepaid && (
                                            <Badge className="mt-1" variant="success">
                                                <CreditCard className="h-3 w-3 mr-1" />
                                                Vorauszahlung erhalten
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Customer */}
                            <div className="flex items-start gap-3">
                                <User className="h-5 w-5 text-gray-400 mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm text-gray-500">Kunde</p>
                                    <p className="font-semibold">{appointmentData.customer?.name || call.extracted_name}</p>
                                    <p className="text-sm text-gray-600">{appointmentData.customer?.phone || call.from_number}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Communication Status */}
                    <div className="border-t pt-4">
                        <h4 className="text-sm font-semibold text-gray-700 mb-3">Kommunikationsstatus</h4>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div className="flex items-center gap-2">
                                {appointmentData.confirmation_sent_at ? (
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                ) : (
                                    <X className="h-4 w-4 text-gray-400" />
                                )}
                                <span className="text-sm">Bestätigung gesendet</span>
                            </div>
                            <div className="flex items-center gap-2">
                                {appointmentData.reminder_24h_sent_at ? (
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                ) : (
                                    <X className="h-4 w-4 text-gray-400" />
                                )}
                                <span className="text-sm">24h Erinnerung</span>
                            </div>
                            <div className="flex items-center gap-2">
                                {appointmentData.reminder_2h_sent_at ? (
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                ) : (
                                    <X className="h-4 w-4 text-gray-400" />
                                )}
                                <span className="text-sm">2h Erinnerung</span>
                            </div>
                            <div className="flex items-center gap-2">
                                {appointmentData.customer_confirmed ? (
                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                ) : (
                                    <X className="h-4 w-4 text-gray-400" />
                                )}
                                <span className="text-sm">Kunde bestätigt</span>
                            </div>
                        </div>
                    </div>

                    {/* Notes */}
                    {appointmentData.notes && (
                        <div className="border-t pt-4">
                            <h4 className="text-sm font-semibold text-gray-700 mb-2">Notizen</h4>
                            <p className="text-sm text-gray-600 bg-gray-50 p-3 rounded">{appointmentData.notes}</p>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="border-t pt-4 flex flex-wrap gap-3">
                        {appointmentData.status === 'pending' && (
                            <Button
                                onClick={handleConfirmAppointment}
                                disabled={loading}
                                className="gap-2"
                            >
                                <CheckCircle className="h-4 w-4" />
                                Termin bestätigen
                            </Button>
                        )}
                        
                        <Button
                            variant="outline"
                            onClick={handleSendReminder}
                            disabled={loading}
                            className="gap-2"
                        >
                            <Bell className="h-4 w-4" />
                            Erinnerung senden
                        </Button>

                        <Button
                            variant="outline"
                            onClick={() => window.location.href = `/business/appointments/${appointmentData.id}`}
                            className="gap-2"
                        >
                            <ExternalLink className="h-4 w-4" />
                            Termin öffnen
                        </Button>

                        <Button
                            variant="outline"
                            onClick={() => setShowQRCode(!showQRCode)}
                            className="gap-2"
                        >
                            <QrCode className="h-4 w-4" />
                            Check-in QR
                        </Button>
                    </div>

                    {/* QR Code */}
                    {showQRCode && (
                        <div className="border-t pt-4 text-center">
                            <p className="text-sm text-gray-600 mb-3">QR-Code für Check-in</p>
                            <img 
                                src={generateQRCode()} 
                                alt="Check-in QR Code"
                                className="mx-auto"
                            />
                        </div>
                    )}
                </CardContent>
            </Card>
        );
    }

    // Show basic appointment request info from call
    return (
        <Card>
            <CardContent className="p-6">
                <div className="flex items-center gap-3 mb-4">
                    <Calendar className="h-5 w-5 text-blue-600" />
                    <h3 className="text-lg font-semibold">Terminanfrage</h3>
                </div>
                
                <div className="space-y-3">
                    {call.datum_termin && (
                        <div className="flex items-center gap-2">
                            <CalendarCheck className="h-4 w-4 text-gray-400" />
                            <span className="text-gray-700">
                                Gewünschter Termin: <strong>{call.datum_termin}</strong>
                                {call.uhrzeit_termin && <span> um <strong>{call.uhrzeit_termin}</strong></span>}
                            </span>
                        </div>
                    )}
                    
                    {call.dienstleistung && (
                        <div className="flex items-center gap-2">
                            <FileText className="h-4 w-4 text-gray-400" />
                            <span className="text-gray-700">
                                Service: <strong>{call.dienstleistung}</strong>
                            </span>
                        </div>
                    )}

                    <div className="flex items-center gap-2 mt-4">
                        {call.appointment_made ? (
                            <Badge variant="success" className="gap-1">
                                <CheckCircle className="h-3 w-3" />
                                Termin wurde gebucht
                            </Badge>
                        ) : (
                            <Badge variant="warning" className="gap-1">
                                <AlertCircle className="h-3 w-3" />
                                Termin noch nicht gebucht
                            </Badge>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
};

export default AppointmentDetails;