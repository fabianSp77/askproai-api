import React from 'react';
import { 
    Phone, 
    Calendar,
    TrendingUp,
    Clock,
    AlertCircle,
    Users,
    CheckCircle,
    Activity
} from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { cn } from '@/lib/utils';
import { MobileCard, MobileSection } from './MobileLayout';
import { TouchButton } from '../ui/TouchButton';

/**
 * Mobile Dashboard Component
 */
const MobileDashboard = ({ stats, recentCalls, upcomingAppointments }) => {
    const navigate = useNavigate();

    const statCards = [
        {
            title: 'Anrufe heute',
            value: stats?.calls_today || 0,
            icon: Phone,
            color: 'text-blue-600',
            bgColor: 'bg-blue-100'
        },
        {
            title: 'Neue Anrufe',
            value: stats?.new_calls || 0,
            icon: AlertCircle,
            color: 'text-orange-600',
            bgColor: 'bg-orange-100'
        },
        {
            title: 'Termine heute',
            value: stats?.appointments_today || 0,
            icon: Calendar,
            color: 'text-green-600',
            bgColor: 'bg-green-100'
        },
        {
            title: 'Ø Anrufdauer',
            value: formatDuration(stats?.avg_duration || 0),
            icon: Clock,
            color: 'text-purple-600',
            bgColor: 'bg-purple-100'
        }
    ];

    return (
        <div className="min-h-screen bg-gray-50 pb-20">
            {/* Header */}
            <div className="bg-white border-b border-gray-200 p-4">
                <h1 className="text-2xl font-bold">Dashboard</h1>
                <p className="text-sm text-gray-500 mt-1">
                    {new Date().toLocaleDateString('de-DE', { 
                        weekday: 'long', 
                        day: 'numeric', 
                        month: 'long' 
                    })}
                </p>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-2 gap-3 p-4">
                {statCards.map((stat, index) => (
                    <MobileCard key={index} className="p-4">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="text-sm text-gray-500">{stat.title}</p>
                                <p className="text-2xl font-bold mt-1">{stat.value}</p>
                            </div>
                            <div className={cn(
                                "p-2 rounded-lg",
                                stat.bgColor
                            )}>
                                <stat.icon className={cn("h-5 w-5", stat.color)} />
                            </div>
                        </div>
                    </MobileCard>
                ))}
            </div>

            {/* Action Required */}
            {stats?.action_required > 0 && (
                <div className="mx-4 mb-4">
                    <MobileCard 
                        className="p-4 border-2 border-orange-200 bg-orange-50"
                        onClick={() => navigate('/business/calls?filter=action_required')}
                        interactive
                    >
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <AlertCircle className="h-6 w-6 text-orange-600" />
                                <div>
                                    <p className="font-medium text-orange-900">Handlung erforderlich</p>
                                    <p className="text-sm text-orange-700">{stats.action_required} Anrufe benötigen Ihre Aufmerksamkeit</p>
                                </div>
                            </div>
                            <svg className="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </MobileCard>
                </div>
            )}

            {/* Recent Calls */}
            {recentCalls && recentCalls.length > 0 && (
                <MobileSection 
                    title="Letzte Anrufe"
                    action={{
                        label: 'Alle anzeigen',
                        onClick: () => navigate('/business/calls')
                    }}
                >
                    <div className="space-y-2 px-4">
                        {recentCalls.slice(0, 5).map((call) => (
                            <MobileCard 
                                key={call.id}
                                className="p-4"
                                onClick={() => navigate(`/business/calls/${call.id}/v2`)}
                                interactive
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className={cn(
                                            "h-10 w-10 rounded-full flex items-center justify-center",
                                            call.urgency_level === 'urgent' ? 'bg-red-100' : 'bg-gray-100'
                                        )}>
                                            <Phone className={cn(
                                                "h-5 w-5",
                                                call.urgency_level === 'urgent' ? 'text-red-600' : 'text-gray-600'
                                            )} />
                                        </div>
                                        <div>
                                            <p className="font-medium">
                                                {call.extracted_name || call.from_number}
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                {formatTimeAgo(call.created_at)}
                                            </p>
                                        </div>
                                    </div>
                                    <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </MobileCard>
                        ))}
                    </div>
                </MobileSection>
            )}

            {/* Upcoming Appointments */}
            {upcomingAppointments && upcomingAppointments.length > 0 && (
                <MobileSection 
                    title="Nächste Termine"
                    action={{
                        label: 'Alle anzeigen',
                        onClick: () => navigate('/business/appointments')
                    }}
                    className="mb-4"
                >
                    <div className="space-y-2 px-4">
                        {upcomingAppointments.slice(0, 3).map((appointment) => (
                            <MobileCard 
                                key={appointment.id}
                                className="p-4"
                                onClick={() => navigate(`/business/appointments/${appointment.id}`)}
                                interactive
                            >
                                <div className="flex items-start gap-3">
                                    <div className="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <Calendar className="h-5 w-5 text-blue-600" />
                                    </div>
                                    <div className="flex-1">
                                        <p className="font-medium">{appointment.customer_name}</p>
                                        <p className="text-sm text-gray-600">{appointment.service_name}</p>
                                        <p className="text-sm text-gray-500 mt-1">
                                            {formatAppointmentTime(appointment.start_time)}
                                        </p>
                                    </div>
                                </div>
                            </MobileCard>
                        ))}
                    </div>
                </MobileSection>
            )}

            {/* Quick Actions */}
            <div className="fixed bottom-20 right-4 z-40">
                <TouchButton
                    variant="primary"
                    size="lg"
                    onClick={() => navigate('/business/calls')}
                    className="rounded-full shadow-lg h-14 w-14 p-0"
                >
                    <Phone className="h-6 w-6" />
                </TouchButton>
            </div>
        </div>
    );
};

// Helper functions
const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) {
        return `vor ${diffMins} Minuten`;
    } else if (diffHours < 24) {
        return `vor ${diffHours} Stunden`;
    } else if (diffDays === 1) {
        return 'Gestern';
    } else if (diffDays < 7) {
        return `vor ${diffDays} Tagen`;
    } else {
        return date.toLocaleDateString('de-DE');
    }
};

const formatAppointmentTime = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const isToday = date.toDateString() === today.toDateString();
    const isTomorrow = date.toDateString() === tomorrow.toDateString();

    const time = date.toLocaleTimeString('de-DE', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });

    if (isToday) {
        return `Heute um ${time}`;
    } else if (isTomorrow) {
        return `Morgen um ${time}`;
    } else {
        return date.toLocaleDateString('de-DE', { 
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
};

export default MobileDashboard;