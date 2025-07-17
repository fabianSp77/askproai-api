import React, { useState } from 'react';
import { 
    Calendar,
    Clock,
    MapPin,
    User,
    Euro,
    CheckCircle,
    AlertCircle,
    Bell,
    FileText,
    Building,
    CreditCard,
    QrCode,
    ChevronRight,
    X
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { format, parseISO, differenceInMinutes } from 'date-fns';
import { de } from 'date-fns/locale';
import { TouchButton } from '../ui/TouchButton';
import { MobileCard, MobileSection } from './MobileLayout';
import { Badge } from '../ui/badge';

/**
 * Mobile-optimized Appointment Details Component
 */
const MobileAppointmentDetails = ({ call, appointment, onUpdate }) => {
    const [showQRCode, setShowQRCode] = useState(false);
    
    // Get appointment data from either appointment prop or call data
    const appointmentData = appointment || (call.appointment_id ? call.appointment : null);
    
    const getStatusColor = (status) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800',
            confirmed: 'bg-green-100 text-green-800',
            completed: 'bg-blue-100 text-blue-800',
            cancelled: 'bg-red-100 text-red-800',
            no_show: 'bg-gray-100 text-gray-800'
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

    const generateQRCode = () => {
        const qrData = {
            type: 'appointment_checkin',
            appointment_id: appointmentData?.id || call.id,
            customer_id: appointmentData?.customer_id || call.customer_id,
            timestamp: new Date().toISOString()
        };
        return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(JSON.stringify(qrData))}`;
    };

    // If we have full appointment data
    if (appointmentData) {
        const duration = appointmentData.starts_at && appointmentData.ends_at
            ? differenceInMinutes(parseISO(appointmentData.ends_at), parseISO(appointmentData.starts_at))
            : null;

        return (
            <MobileSection title="Gebuchter Termin" className="mt-4">
                {/* Status Badge */}
                <div className="px-4 mb-3">
                    <Badge className={cn("w-full justify-center py-2", getStatusColor(appointmentData.status))}>
                        {getStatusLabel(appointmentData.status)}
                    </Badge>
                </div>

                {/* Main Info Card */}
                <MobileCard className="mx-4 p-4 space-y-4">
                    {/* Date & Time */}
                    <div className="flex items-start gap-3">
                        <Calendar className="h-5 w-5 text-blue-600 mt-0.5" />
                        <div className="flex-1">
                            <p className="font-semibold">
                                {format(parseISO(appointmentData.starts_at), 'EEEE, d. MMMM', { locale: de })}
                            </p>
                            <p className="text-gray-600">
                                {format(parseISO(appointmentData.starts_at), 'HH:mm')} - 
                                {format(parseISO(appointmentData.ends_at), 'HH:mm')} Uhr
                            </p>
                            {duration && (
                                <p className="text-sm text-gray-500">{duration} Minuten</p>
                            )}
                        </div>
                    </div>

                    {/* Service */}
                    <div className="flex items-start gap-3">
                        <FileText className="h-5 w-5 text-gray-400 mt-0.5" />
                        <div className="flex-1">
                            <p className="font-semibold">{appointmentData.service?.name || call.dienstleistung}</p>
                            {appointmentData.service?.description && (
                                <p className="text-sm text-gray-600">{appointmentData.service.description}</p>
                            )}
                        </div>
                    </div>

                    {/* Staff */}
                    {appointmentData.staff && (
                        <div className="flex items-start gap-3">
                            <User className="h-5 w-5 text-gray-400 mt-0.5" />
                            <div className="flex-1">
                                <p className="font-semibold">{appointmentData.staff.name}</p>
                                {appointmentData.staff.title && (
                                    <p className="text-sm text-gray-600">{appointmentData.staff.title}</p>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Branch */}
                    <div className="flex items-start gap-3">
                        <Building className="h-5 w-5 text-gray-400 mt-0.5" />
                        <div className="flex-1">
                            <p className="font-semibold">{appointmentData.branch?.name || call.branch_name}</p>
                            {appointmentData.branch?.address && (
                                <p className="text-sm text-gray-600">{appointmentData.branch.address}</p>
                            )}
                        </div>
                    </div>

                    {/* Price */}
                    {appointmentData.price && (
                        <div className="flex items-start gap-3">
                            <Euro className="h-5 w-5 text-gray-400 mt-0.5" />
                            <div className="flex-1">
                                <p className="font-semibold">{appointmentData.price} €</p>
                                {appointmentData.prepaid && (
                                    <Badge variant="success" className="mt-1">
                                        <CreditCard className="h-3 w-3 mr-1" />
                                        Bezahlt
                                    </Badge>
                                )}
                            </div>
                        </div>
                    )}
                </MobileCard>

                {/* Communication Status */}
                <MobileCard className="mx-4 mt-3 p-4">
                    <h4 className="text-sm font-semibold text-gray-700 mb-3">Kommunikation</h4>
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-gray-600">Bestätigung</span>
                            {appointmentData.confirmation_sent_at ? (
                                <CheckCircle className="h-4 w-4 text-green-600" />
                            ) : (
                                <X className="h-4 w-4 text-gray-400" />
                            )}
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-gray-600">24h Erinnerung</span>
                            {appointmentData.reminder_24h_sent_at ? (
                                <CheckCircle className="h-4 w-4 text-green-600" />
                            ) : (
                                <X className="h-4 w-4 text-gray-400" />
                            )}
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-gray-600">2h Erinnerung</span>
                            {appointmentData.reminder_2h_sent_at ? (
                                <CheckCircle className="h-4 w-4 text-green-600" />
                            ) : (
                                <X className="h-4 w-4 text-gray-400" />
                            )}
                        </div>
                    </div>
                </MobileCard>

                {/* Action Buttons */}
                <div className="px-4 mt-4 space-y-3">
                    {appointmentData.status === 'pending' && (
                        <TouchButton
                            variant="primary"
                            fullWidth
                            icon={CheckCircle}
                        >
                            Termin bestätigen
                        </TouchButton>
                    )}
                    
                    <TouchButton
                        variant="outline"
                        fullWidth
                        icon={Bell}
                    >
                        Erinnerung senden
                    </TouchButton>

                    <TouchButton
                        variant="outline"
                        fullWidth
                        icon={QrCode}
                        onClick={() => setShowQRCode(!showQRCode)}
                    >
                        Check-in QR anzeigen
                    </TouchButton>

                    <TouchButton
                        variant="ghost"
                        fullWidth
                        onClick={() => window.location.href = `/business/appointments/${appointmentData.id}`}
                    >
                        <span className="flex items-center justify-center gap-2">
                            Termin Details
                            <ChevronRight className="h-4 w-4" />
                        </span>
                    </TouchButton>
                </div>

                {/* QR Code Modal */}
                {showQRCode && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                        <div className="bg-white rounded-lg p-6 max-w-sm w-full">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold">Check-in QR Code</h3>
                                <button
                                    onClick={() => setShowQRCode(false)}
                                    className="p-2 hover:bg-gray-100 rounded-lg"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            <img 
                                src={generateQRCode()} 
                                alt="Check-in QR Code"
                                className="w-full"
                            />
                            <p className="text-sm text-gray-600 text-center mt-4">
                                Kunde kann diesen Code beim Check-in scannen
                            </p>
                        </div>
                    </div>
                )}
            </MobileSection>
        );
    }

    // Basic appointment request info from call
    if (call.appointment_requested) {
        return (
            <MobileSection title="Terminanfrage">
                <MobileCard className="mx-4 p-4 border-2 border-blue-200 bg-blue-50">
                    <div className="space-y-3">
                        {call.datum_termin && (
                            <div className="flex items-center gap-2">
                                <Calendar className="h-4 w-4 text-blue-600" />
                                <span className="font-medium">{call.datum_termin}</span>
                                {call.uhrzeit_termin && <span>um {call.uhrzeit_termin}</span>}
                            </div>
                        )}
                        {call.dienstleistung && (
                            <div className="flex items-center gap-2">
                                <FileText className="h-4 w-4 text-blue-600" />
                                <span>{call.dienstleistung}</span>
                            </div>
                        )}
                        {call.appointment_made && (
                            <div className="flex items-center gap-2 text-green-600 font-medium">
                                <CheckCircle className="h-4 w-4" />
                                <span>Termin gebucht</span>
                            </div>
                        )}
                    </div>
                </MobileCard>
            </MobileSection>
        );
    }

    return null;
};

export default MobileAppointmentDetails;