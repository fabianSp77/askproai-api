import React, { useState, useMemo } from 'react';
import { 
    Calendar,
    ChevronLeft,
    ChevronRight,
    Clock,
    User,
    Building,
    FileText,
    Plus,
    Filter,
    Eye,
    AlertCircle
} from 'lucide-react';
import { Button } from '../ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { cn } from '../../lib/utils';
import { 
    format, 
    startOfWeek, 
    endOfWeek, 
    eachDayOfInterval,
    addWeeks,
    subWeeks,
    isSameDay,
    isToday,
    parseISO,
    startOfDay,
    endOfDay,
    addMonths,
    subMonths,
    startOfMonth,
    endOfMonth,
    isSameMonth
} from 'date-fns';
import { de } from 'date-fns/locale';

/**
 * Appointment Calendar Component
 * Displays appointments in a calendar view
 */
const AppointmentCalendar = ({ 
    appointments = [], 
    onAppointmentClick,
    onDateClick,
    onCreateAppointment,
    branches = [],
    staff = [],
    services = []
}) => {
    const [currentDate, setCurrentDate] = useState(new Date());
    const [viewMode, setViewMode] = useState('week'); // week | month
    const [selectedBranch, setSelectedBranch] = useState('all');
    const [selectedStaff, setSelectedStaff] = useState('all');
    const [selectedService, setSelectedService] = useState('all');

    // Get date range based on view mode
    const dateRange = useMemo(() => {
        if (viewMode === 'week') {
            const start = startOfWeek(currentDate, { locale: de, weekStartsOn: 1 });
            const end = endOfWeek(currentDate, { locale: de, weekStartsOn: 1 });
            return eachDayOfInterval({ start, end });
        } else {
            const start = startOfMonth(currentDate);
            const end = endOfMonth(currentDate);
            return eachDayOfInterval({ start, end });
        }
    }, [currentDate, viewMode]);

    // Filter appointments
    const filteredAppointments = useMemo(() => {
        return appointments.filter(appointment => {
            if (selectedBranch !== 'all' && appointment.branch_id !== selectedBranch) return false;
            if (selectedStaff !== 'all' && appointment.staff_id !== selectedStaff) return false;
            if (selectedService !== 'all' && appointment.service_id !== selectedService) return false;
            return true;
        });
    }, [appointments, selectedBranch, selectedStaff, selectedService]);

    // Group appointments by date
    const appointmentsByDate = useMemo(() => {
        const grouped = {};
        
        filteredAppointments.forEach(appointment => {
            const date = format(parseISO(appointment.starts_at), 'yyyy-MM-dd');
            if (!grouped[date]) {
                grouped[date] = [];
            }
            grouped[date].push(appointment);
        });

        // Sort appointments within each day
        Object.keys(grouped).forEach(date => {
            grouped[date].sort((a, b) => 
                new Date(a.starts_at) - new Date(b.starts_at)
            );
        });

        return grouped;
    }, [filteredAppointments]);

    // Navigation handlers
    const navigatePrevious = () => {
        if (viewMode === 'week') {
            setCurrentDate(subWeeks(currentDate, 1));
        } else {
            setCurrentDate(subMonths(currentDate, 1));
        }
    };

    const navigateNext = () => {
        if (viewMode === 'week') {
            setCurrentDate(addWeeks(currentDate, 1));
        } else {
            setCurrentDate(addMonths(currentDate, 1));
        }
    };

    const navigateToday = () => {
        setCurrentDate(new Date());
    };

    // Helper to get appointment color based on status
    const getAppointmentColor = (appointment) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800 border-yellow-300',
            confirmed: 'bg-green-100 text-green-800 border-green-300',
            completed: 'bg-blue-100 text-blue-800 border-blue-300',
            cancelled: 'bg-red-100 text-red-800 border-red-300',
            no_show: 'bg-gray-100 text-gray-800 border-gray-300'
        };
        return colors[appointment.status] || colors.pending;
    };

    // Render appointment card
    const renderAppointment = (appointment) => {
        const startTime = format(parseISO(appointment.starts_at), 'HH:mm');
        const hasConflict = checkForConflicts(appointment);
        
        return (
            <div
                key={appointment.id}
                onClick={() => onAppointmentClick(appointment)}
                className={cn(
                    "p-2 rounded border cursor-pointer transition-all hover:shadow-md",
                    getAppointmentColor(appointment),
                    hasConflict && "ring-2 ring-red-500 ring-offset-1"
                )}
            >
                <div className="flex items-center justify-between mb-1">
                    <span className="text-xs font-semibold">{startTime}</span>
                    {hasConflict && (
                        <AlertCircle className="h-3 w-3 text-red-600" />
                    )}
                </div>
                <p className="text-xs font-medium truncate">
                    {appointment.customer?.name || 'Unbekannt'}
                </p>
                <p className="text-xs opacity-75 truncate">
                    {appointment.service?.name || appointment.dienstleistung}
                </p>
                {appointment.staff && (
                    <p className="text-xs opacity-60 truncate mt-1">
                        {appointment.staff.name}
                    </p>
                )}
            </div>
        );
    };

    // Check for appointment conflicts
    const checkForConflicts = (appointment) => {
        const sameDateTime = filteredAppointments.filter(apt => 
            apt.id !== appointment.id &&
            apt.staff_id === appointment.staff_id &&
            apt.starts_at === appointment.starts_at
        );
        return sameDateTime.length > 0;
    };

    // Week view component
    const WeekView = () => (
        <div className="grid grid-cols-7 gap-2">
            {dateRange.map((date, index) => {
                const dateKey = format(date, 'yyyy-MM-dd');
                const dayAppointments = appointmentsByDate[dateKey] || [];
                const isCurrentDay = isToday(date);
                
                return (
                    <div
                        key={index}
                        className={cn(
                            "border rounded-lg p-2 min-h-[400px]",
                            isCurrentDay && "bg-blue-50 border-blue-300"
                        )}
                    >
                        <div className="mb-2">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-semibold">
                                    {format(date, 'EEE', { locale: de })}
                                </span>
                                <Badge 
                                    variant={isCurrentDay ? "default" : "outline"}
                                    className="text-xs"
                                >
                                    {format(date, 'd')}
                                </Badge>
                            </div>
                            {dayAppointments.length > 0 && (
                                <span className="text-xs text-gray-500">
                                    {dayAppointments.length} Termine
                                </span>
                            )}
                        </div>

                        <div className="space-y-2 overflow-y-auto max-h-[350px]">
                            {dayAppointments.map(appointment => 
                                renderAppointment(appointment)
                            )}
                        </div>

                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full mt-2"
                            onClick={() => onDateClick(date)}
                        >
                            <Plus className="h-3 w-3" />
                        </Button>
                    </div>
                );
            })}
        </div>
    );

    // Month view component
    const MonthView = () => {
        const firstDayOfMonth = startOfMonth(currentDate);
        const startDate = startOfWeek(firstDayOfMonth, { locale: de, weekStartsOn: 1 });
        const weeks = [];
        let currentWeek = [];
        
        // Generate 6 weeks (42 days) for consistent grid
        for (let i = 0; i < 42; i++) {
            const date = new Date(startDate);
            date.setDate(date.getDate() + i);
            currentWeek.push(date);
            
            if (currentWeek.length === 7) {
                weeks.push(currentWeek);
                currentWeek = [];
            }
        }

        return (
            <div className="grid grid-cols-7 gap-1">
                {/* Week day headers */}
                {['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'].map(day => (
                    <div key={day} className="text-center text-sm font-semibold p-2">
                        {day}
                    </div>
                ))}
                
                {/* Calendar days */}
                {weeks.flat().map((date, index) => {
                    const dateKey = format(date, 'yyyy-MM-dd');
                    const dayAppointments = appointmentsByDate[dateKey] || [];
                    const isCurrentMonth = isSameMonth(date, currentDate);
                    const isCurrentDay = isToday(date);
                    
                    return (
                        <div
                            key={index}
                            onClick={() => onDateClick(date)}
                            className={cn(
                                "border rounded p-2 min-h-[100px] cursor-pointer hover:bg-gray-50",
                                !isCurrentMonth && "opacity-30",
                                isCurrentDay && "bg-blue-50 border-blue-300"
                            )}
                        >
                            <div className="flex items-center justify-between mb-1">
                                <span className="text-sm font-medium">
                                    {format(date, 'd')}
                                </span>
                                {dayAppointments.length > 0 && (
                                    <Badge variant="secondary" className="text-xs h-5 px-1">
                                        {dayAppointments.length}
                                    </Badge>
                                )}
                            </div>
                            
                            {/* Show first 3 appointments */}
                            <div className="space-y-1">
                                {dayAppointments.slice(0, 3).map((apt, i) => (
                                    <div
                                        key={i}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            onAppointmentClick(apt);
                                        }}
                                        className={cn(
                                            "text-xs p-1 rounded truncate cursor-pointer",
                                            getAppointmentColor(apt)
                                        )}
                                    >
                                        {format(parseISO(apt.starts_at), 'HH:mm')} - {apt.customer?.name}
                                    </div>
                                ))}
                                {dayAppointments.length > 3 && (
                                    <div className="text-xs text-gray-500 text-center">
                                        +{dayAppointments.length - 3} mehr
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        );
    };

    return (
        <Card className="w-full">
            <CardHeader>
                <div className="flex items-center justify-between mb-4">
                    <CardTitle className="flex items-center gap-2">
                        <Calendar className="h-5 w-5" />
                        Terminkalender
                    </CardTitle>
                    
                    <div className="flex items-center gap-2">
                        {/* View Mode Selector */}
                        <Select value={viewMode} onValueChange={setViewMode}>
                            <SelectTrigger className="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="week">Woche</SelectItem>
                                <SelectItem value="month">Monat</SelectItem>
                            </SelectContent>
                        </Select>

                        {/* Create Appointment Button */}
                        <Button onClick={onCreateAppointment} size="sm">
                            <Plus className="h-4 w-4 mr-1" />
                            Neuer Termin
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-2 mb-4">
                    {branches.length > 0 && (
                        <Select value={selectedBranch} onValueChange={setSelectedBranch}>
                            <SelectTrigger className="w-40">
                                <Building className="h-4 w-4 mr-2" />
                                <SelectValue placeholder="Alle Filialen" />
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
                    )}

                    {staff.length > 0 && (
                        <Select value={selectedStaff} onValueChange={setSelectedStaff}>
                            <SelectTrigger className="w-40">
                                <User className="h-4 w-4 mr-2" />
                                <SelectValue placeholder="Alle Mitarbeiter" />
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
                    )}

                    {services.length > 0 && (
                        <Select value={selectedService} onValueChange={setSelectedService}>
                            <SelectTrigger className="w-40">
                                <FileText className="h-4 w-4 mr-2" />
                                <SelectValue placeholder="Alle Services" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Alle Services</SelectItem>
                                {services.map(service => (
                                    <SelectItem key={service.id} value={service.id}>
                                        {service.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                </div>

                {/* Navigation */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={navigatePrevious}
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={navigateToday}
                        >
                            Heute
                        </Button>
                        
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={navigateNext}
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>

                    <h3 className="text-lg font-semibold">
                        {viewMode === 'week' 
                            ? `${format(dateRange[0], 'd. MMM', { locale: de })} - ${format(dateRange[6], 'd. MMM yyyy', { locale: de })}`
                            : format(currentDate, 'MMMM yyyy', { locale: de })
                        }
                    </h3>

                    <div className="text-sm text-gray-500">
                        {filteredAppointments.length} Termine
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                {viewMode === 'week' ? <WeekView /> : <MonthView />}
            </CardContent>
        </Card>
    );
};

export default AppointmentCalendar;