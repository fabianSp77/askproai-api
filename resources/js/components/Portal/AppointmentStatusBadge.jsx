import React from 'react';
import { 
    Calendar,
    Clock,
    CheckCircle,
    AlertCircle,
    X,
    ChevronRight,
    CalendarCheck,
    CalendarX,
    Bell
} from 'lucide-react';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { cn } from '../../lib/utils';
import { format, isToday, isTomorrow, isPast, parseISO } from 'date-fns';
import { de } from 'date-fns/locale';

/**
 * Appointment Status Badge Component
 * Shows appointment status and quick actions in call lists
 */
const AppointmentStatusBadge = ({ call, onViewAppointment }) => {
    // Check if call has appointment data
    const hasAppointment = call.appointment_id || call.appointment;
    const appointment = call.appointment;
    
    // Helper to format appointment time
    const formatAppointmentTime = (dateString) => {
        if (!dateString) return '';
        
        try {
            const date = parseISO(dateString);
            
            if (isToday(date)) {
                return `Heute ${format(date, 'HH:mm')} Uhr`;
            } else if (isTomorrow(date)) {
                return `Morgen ${format(date, 'HH:mm')} Uhr`;
            } else if (isPast(date)) {
                return format(date, 'dd.MM.yyyy HH:mm') + ' Uhr (vergangen)';
            } else {
                return format(date, 'dd.MM.yyyy HH:mm') + ' Uhr';
            }
        } catch (e) {
            return dateString;
        }
    };

    // Helper to get status color
    const getStatusStyle = (status) => {
        const styles = {
            pending: {
                badge: 'bg-yellow-100 text-yellow-800 border-yellow-300',
                icon: AlertCircle,
                label: 'Unbestätigt'
            },
            confirmed: {
                badge: 'bg-green-100 text-green-800 border-green-300',
                icon: CheckCircle,
                label: 'Bestätigt'
            },
            completed: {
                badge: 'bg-blue-100 text-blue-800 border-blue-300',
                icon: CalendarCheck,
                label: 'Abgeschlossen'
            },
            cancelled: {
                badge: 'bg-red-100 text-red-800 border-red-300',
                icon: CalendarX,
                label: 'Storniert'
            },
            no_show: {
                badge: 'bg-gray-100 text-gray-800 border-gray-300',
                icon: X,
                label: 'Nicht erschienen'
            }
        };
        return styles[status] || styles.pending;
    };

    // If we have a full appointment object
    if (hasAppointment && appointment) {
        const statusInfo = getStatusStyle(appointment.status);
        const Icon = statusInfo.icon;
        const appointmentTime = formatAppointmentTime(appointment.starts_at);
        const isUrgent = appointment.status === 'pending' && appointment.starts_at && isPast(parseISO(appointment.starts_at));
        
        return (
            <div className="space-y-2">
                {/* Status Badge */}
                <Badge 
                    className={cn(
                        "gap-1 border",
                        statusInfo.badge,
                        isUrgent && "animate-pulse"
                    )}
                >
                    <Icon className="h-3 w-3" />
                    {statusInfo.label}
                </Badge>
                
                {/* Appointment Info */}
                <div className="flex items-center gap-2 text-xs">
                    <Clock className="h-3 w-3 text-gray-400" />
                    <span className={cn(
                        "text-gray-600",
                        isUrgent && "text-red-600 font-medium"
                    )}>
                        {appointmentTime}
                    </span>
                </div>
                
                {/* Quick Actions */}
                <div className="flex gap-1">
                    <Button
                        variant="ghost"
                        size="xs"
                        onClick={() => onViewAppointment(appointment.id)}
                        className="text-xs h-6 px-2"
                    >
                        Details
                        <ChevronRight className="h-3 w-3 ml-1" />
                    </Button>
                    
                    {appointment.status === 'pending' && (
                        <Button
                            variant="ghost"
                            size="xs"
                            className="text-xs h-6 px-2 text-yellow-600 hover:text-yellow-700"
                        >
                            <Bell className="h-3 w-3" />
                        </Button>
                    )}
                </div>
            </div>
        );
    }

    // If we only have basic appointment request info
    if (call.appointment_requested || call.datum_termin) {
        const hasDate = call.datum_termin;
        const hasTime = call.uhrzeit_termin;
        const isBooked = call.appointment_made;
        
        return (
            <div className="space-y-1">
                {isBooked ? (
                    <Badge variant="success" className="gap-1">
                        <CheckCircle className="h-3 w-3" />
                        Termin gebucht
                    </Badge>
                ) : (
                    <Badge variant="warning" className="gap-1">
                        <Calendar className="h-3 w-3" />
                        Termin angefragt
                    </Badge>
                )}
                
                {hasDate && (
                    <p className="text-xs text-gray-600">
                        {hasDate} {hasTime && `um ${hasTime}`}
                    </p>
                )}
                
                {call.dienstleistung && (
                    <p className="text-xs text-gray-500 truncate max-w-[200px]">
                        {call.dienstleistung}
                    </p>
                )}
            </div>
        );
    }

    // No appointment info
    return null;
};

// Mini version for compact displays
export const AppointmentStatusBadgeMini = ({ call }) => {
    const hasAppointment = call.appointment_id || call.appointment;
    const appointment = call.appointment;
    
    if (hasAppointment && appointment) {
        const statusInfo = getStatusStyle(appointment.status);
        const Icon = statusInfo.icon;
        
        return (
            <div className="flex items-center gap-1">
                <Icon className={cn(
                    "h-4 w-4",
                    appointment.status === 'confirmed' && "text-green-600",
                    appointment.status === 'pending' && "text-yellow-600",
                    appointment.status === 'cancelled' && "text-red-600"
                )} />
                <span className="text-xs text-gray-600">{statusInfo.label}</span>
            </div>
        );
    }
    
    if (call.appointment_requested) {
        return (
            <div className="flex items-center gap-1">
                <Calendar className="h-4 w-4 text-blue-600" />
                <span className="text-xs text-gray-600">
                    {call.appointment_made ? 'Gebucht' : 'Angefragt'}
                </span>
            </div>
        );
    }
    
    return null;
};

export default AppointmentStatusBadge;