import React, { useState } from 'react';
import { 
    Phone, 
    Clock, 
    Calendar,
    ChevronLeft,
    MapPin,
    User,
    FileText,
    Volume2,
    Download,
    Mail,
    AlertCircle,
    CheckCircle,
    PhoneIncoming,
    PhoneOutgoing
} from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { de } from 'date-fns/locale';
import { cn } from '@/lib/utils';
import { useNavigate } from 'react-router-dom';
import { TouchButton } from '../ui/TouchButton';
import { MobileCard, MobileSection } from './MobileLayout';
import { SwipeableCard } from './SwipeableCard';
import AudioPlayer from '../ui/AudioPlayer';
import MobileAppointmentDetails from './MobileAppointmentDetails';

/**
 * Mobile-optimized Call Detail View
 */
const MobileCallDetail = ({ call, onBack }) => {
    const navigate = useNavigate();
    const [isPlaying, setIsPlaying] = useState(false);
    const [showTranscript, setShowTranscript] = useState(false);
    
    const urgencyColors = {
        urgent: 'border-red-500 bg-red-50',
        high: 'border-orange-500 bg-orange-50',
        normal: 'border-green-500 bg-green-50',
        low: 'border-gray-400 bg-gray-50'
    };

    const urgencyLabels = {
        urgent: 'Dringend',
        high: 'Hoch',
        normal: 'Normal',
        low: 'Niedrig'
    };

    const getCallIcon = () => {
        if (call.direction === 'inbound') return PhoneIncoming;
        if (call.direction === 'outbound') return PhoneOutgoing;
        return Phone;
    };

    const Icon = getCallIcon();
    const urgencyLevel = call.urgency_level?.toLowerCase() || 'normal';

    const handleEmailSummary = () => {
        navigate(`/business/calls/${call.id}/email`);
    };

    const handleExportCSV = async () => {
        try {
            const response = await axiosInstance.post('/business/api/calls/export', {
                call_ids: [call.id]
            });
            
            if (response.data.download_url) {
                window.location.href = response.data.download_url;
            }
        } catch (error) {
            console.error('Export failed:', error);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 pb-20">
            {/* Header with Back Button */}
            <div className="sticky top-0 z-40 bg-white border-b border-gray-200">
                <div className="flex items-center p-4">
                    <button
                        onClick={onBack}
                        className="p-2 -ml-2 rounded-lg active:bg-gray-100"
                    >
                        <ChevronLeft className="h-6 w-6" />
                    </button>
                    <div className="flex-1 ml-3">
                        <h1 className="text-lg font-semibold">{call.extracted_name || call.from_number}</h1>
                        <p className="text-sm text-gray-500">
                            {formatDistanceToNow(new Date(call.created_at), { 
                                addSuffix: true, 
                                locale: de 
                            })}
                        </p>
                    </div>
                    <div className={cn(
                        "h-10 w-10 rounded-full flex items-center justify-center",
                        urgencyColors[urgencyLevel]
                    )}>
                        <Icon className="h-5 w-5 text-gray-700" />
                    </div>
                </div>
            </div>

            {/* Urgency Alert */}
            {urgencyLevel === 'urgent' && (
                <div className="bg-red-50 border-b border-red-200 px-4 py-3">
                    <div className="flex items-center gap-2 text-red-800">
                        <AlertCircle className="h-5 w-5" />
                        <span className="font-medium">Dringender Anruf - Bitte zeitnah bearbeiten</span>
                    </div>
                </div>
            )}

            {/* Customer Request */}
            {call.customer_request && (
                <MobileSection title="Kundenanfrage" className="mt-4">
                    <MobileCard className="mx-4 p-4">
                        <p className="text-gray-700">{call.customer_request}</p>
                    </MobileCard>
                </MobileSection>
            )}

            {/* Summary */}
            {call.ai_summary && (
                <MobileSection title="Zusammenfassung">
                    <MobileCard className="mx-4 p-4">
                        <p className="text-gray-700">{call.ai_summary}</p>
                    </MobileCard>
                </MobileSection>
            )}

            {/* Audio Player */}
            {call.recording_url && (
                <MobileSection title="Aufzeichnung">
                    <MobileCard className="mx-4 p-4">
                        <AudioPlayer 
                            url={call.recording_url}
                            duration={call.duration_sec}
                            onPlayChange={setIsPlaying}
                        />
                    </MobileCard>
                </MobileSection>
            )}

            {/* Call Details */}
            <MobileSection title="Anrufdetails">
                <MobileCard className="mx-4 p-4 space-y-3">
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-500">Telefonnummer</span>
                        <span className="font-medium">{call.from_number}</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-500">Datum & Zeit</span>
                        <span className="font-medium">
                            {new Date(call.created_at).toLocaleDateString('de-DE', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}
                        </span>
                    </div>
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-500">Dauer</span>
                        <span className="font-medium">{formatDuration(call.duration_sec)}</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-500">Dringlichkeit</span>
                        <span className={cn(
                            "px-2 py-1 rounded text-sm font-medium",
                            urgencyLevel === 'urgent' && "bg-red-100 text-red-700",
                            urgencyLevel === 'high' && "bg-orange-100 text-orange-700",
                            urgencyLevel === 'normal' && "bg-green-100 text-green-700",
                            urgencyLevel === 'low' && "bg-gray-100 text-gray-700"
                        )}>
                            {urgencyLabels[urgencyLevel]}
                        </span>
                    </div>
                    {call.branch_name && (
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-gray-500">Filiale</span>
                            <span className="font-medium">{call.branch_name}</span>
                        </div>
                    )}
                </MobileCard>
            </MobileSection>

            {/* Enhanced Appointment Details */}
            <MobileAppointmentDetails 
                call={call} 
                appointment={call.appointment}
                onUpdate={() => {
                    // Refresh call data if needed
                    console.log('Appointment updated');
                }}
            />

            {/* Transcript */}
            {call.transcript && (
                <MobileSection 
                    title="Transkript"
                    action={{
                        label: showTranscript ? 'Verbergen' : 'Anzeigen',
                        onClick: () => setShowTranscript(!showTranscript)
                    }}
                >
                    {showTranscript && (
                        <MobileCard className="mx-4 p-4">
                            <div className="text-sm text-gray-700 whitespace-pre-wrap">
                                {call.transcript}
                            </div>
                        </MobileCard>
                    )}
                </MobileSection>
            )}

            {/* Action Buttons */}
            <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 safe-area-bottom">
                <div className="grid grid-cols-2 gap-3">
                    <TouchButton
                        variant="outline"
                        onClick={handleExportCSV}
                        icon={Download}
                        fullWidth
                    >
                        CSV Export
                    </TouchButton>
                    <TouchButton
                        variant="primary"
                        onClick={handleEmailSummary}
                        icon={Mail}
                        fullWidth
                    >
                        Email senden
                    </TouchButton>
                </div>
            </div>
        </div>
    );
};

// Helper function to format duration
const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

export default MobileCallDetail;