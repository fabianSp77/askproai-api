import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Badge } from '../../../components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Separator } from '../../../components/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '../../../components/ui/dialog';
import { toast } from 'react-toastify';
import EmailComposerWithPreview from '../../../components/Portal/EmailComposerWithPreview';
import AppointmentDetails from '../../../components/Portal/AppointmentDetails';
import { 
    Phone,
    ArrowLeft,
    Clock,
    Calendar,
    User,
    Building,
    FileText,
    MessageSquare,
    Download,
    AlertTriangle,
    CheckCircle,
    XCircle,
    PhoneIncoming,
    PhoneOutgoing,
    MapPin,
    Globe,
    Mic,
    Volume2,
    Mail,
    Euro,
    Building2,
    AlertCircle,
    Send,
    UserCheck,
    Activity,
    ChevronRight
} from 'lucide-react';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';
import axiosInstance from '../../../services/axiosInstance';
import { useAuth } from '../../../hooks/useAuth';

dayjs.locale('de');
dayjs.extend(duration);
dayjs.extend(relativeTime);

const CallShowV2 = () => {
    const params = useParams();
    const callId = params.id;
    const navigate = useNavigate();
    const { csrfToken } = useAuth();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [call, setCall] = useState(null);
    const [activeTab, setActiveTab] = useState('details');
    const [translatedSummary, setTranslatedSummary] = useState(null);
    const [isTranslating, setIsTranslating] = useState(false);
    const audioRef = React.useRef(null);
    const [audioDuration, setAudioDuration] = useState(0);
    const [audioCurrentTime, setAudioCurrentTime] = useState(0);
    const [isPlaying, setIsPlaying] = useState(false);
    const [volume, setVolume] = useState(1);
    const [navigation, setNavigation] = useState(null);
    const [loadingNavigation, setLoadingNavigation] = useState(true);
    const [activities, setActivities] = useState([]);
    const [loadingActivities, setLoadingActivities] = useState(true);
    const [showEmailDialog, setShowEmailDialog] = useState(false);
    
    // Icon mapping for activities
    const activityIcons = {
        'Phone': Phone,
        'PhoneOff': XCircle,
        'Activity': Activity,
        'UserCheck': UserCheck,
        'Send': Send,
        'MessageSquare': MessageSquare,
        'CheckCircle': CheckCircle,
        'Mic': Mic,
        'FileText': FileText
    };

    useEffect(() => {
        fetchCallDetails();
        fetchNavigation();
        fetchActivities();
    }, [callId]);

    // Automatisch Zusammenfassung übersetzen, wenn vorhanden
    useEffect(() => {
        if (call?.summary && !translatedSummary && !isTranslating) {
            translateSummary(call.summary);
        }
    }, [call?.summary]);

    const fetchCallDetails = async () => {
        if (!callId) {
            setError('Keine Anruf-ID angegeben');
            setLoading(false);
            return;
        }
        
        try {
            setLoading(true);
            setError(null);

            const response = await axiosInstance.get(`/calls/${callId}`);
            // Extract the call object from the response
            if (response.data.call) {
                setCall(response.data.call);
            } else {
                setCall(response.data); // Fallback to entire response
            }
        } catch (err) {
            setError(err.response?.data?.message || err.message);
        } finally {
            setLoading(false);
        }
    };

    const fetchNavigation = async () => {
        if (!callId) return;
        
        try {
            setLoadingNavigation(true);
            
            // Get current filters from URL params/localStorage to maintain context
            const urlParams = new URLSearchParams(window.location.search);
            const filters = {
                search: urlParams.get('search') || '',
                status: urlParams.get('status') || 'all',
                branch_id: urlParams.get('branch_id') || '',
                date: urlParams.get('date') || '',
            };

            const queryString = new URLSearchParams(filters).toString();
            const response = await axiosInstance.get(`/calls/${callId}/navigation?${queryString}`);
            setNavigation(response.data);
        } catch (err) {
            // Silently ignore navigation errors
        } finally {
            setLoadingNavigation(false);
        }
    };

    const fetchActivities = async () => {
        if (!callId) return;
        
        try {
            setLoadingActivities(true);
            
            const response = await axiosInstance.get(`/calls/${callId}/timeline`);
            setActivities(response.data.activities || []);
        } catch (err) {
            setActivities([]);
        } finally {
            setLoadingActivities(false);
        }
    };

    // Keyboard navigation
    useEffect(() => {
        const handleKeyPress = (e) => {
            // Only handle if no input/textarea is focused
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            if (e.key === 'ArrowLeft' && navigation?.previous) {
                navigate(`/calls/${navigation.previous.id}/v2${window.location.search}`);
            } else if (e.key === 'ArrowRight' && navigation?.next) {
                navigate(`/calls/${navigation.next.id}/v2${window.location.search}`);
            }
        };

        window.addEventListener('keydown', handleKeyPress);
        return () => window.removeEventListener('keydown', handleKeyPress);
    }, [navigation, navigate]);

    const getStatusBadge = (status) => {
        const statusConfig = {
            'ended': { label: 'Beendet', variant: 'default', icon: CheckCircle },
            'analyzed': { label: 'Analysiert', variant: 'success', icon: CheckCircle },
            'error': { label: 'Fehler', variant: 'destructive', icon: XCircle },
            'ongoing': { label: 'Laufend', variant: 'warning', icon: Clock },
            'no-answer': { label: 'Nicht erreicht', variant: 'secondary', icon: XCircle }
        };
        
        const config = statusConfig[status] || { label: status, variant: 'default', icon: Phone };
        const Icon = config.icon;
        
        return (
            <Badge variant={config.variant} className="gap-1">
                <Icon className="h-3 w-3" />
                {config.label}
            </Badge>
        );
    };

    const getUrgencyBadge = (urgency) => {
        const urgencyConfig = {
            'sehr dringend': { 
                color: 'bg-red-100', 
                borderColor: 'border-red-300',
                textColor: 'text-red-800', 
                iconColor: 'text-red-600',
                icon: AlertTriangle,
                pulse: true 
            },
            'dringend': { 
                color: 'bg-orange-100', 
                borderColor: 'border-orange-300',
                textColor: 'text-orange-800', 
                iconColor: 'text-orange-600',
                icon: AlertCircle,
                pulse: false 
            },
            'normal': { 
                color: 'bg-green-100', 
                borderColor: 'border-green-300',
                textColor: 'text-green-800', 
                iconColor: 'text-green-600',
                icon: CheckCircle,
                pulse: false 
            }
        };
        
        const config = urgencyConfig[urgency?.toLowerCase()] || urgencyConfig['normal'];
        const Icon = config.icon;
        
        return (
            <div className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border ${config.color} ${config.borderColor} ${config.textColor} ${config.pulse ? 'animate-pulse' : ''}`}>
                <Icon className={`h-3.5 w-3.5 ${config.iconColor}`} />
                <span className="font-medium text-xs">{urgency || 'Normal'}</span>
            </div>
        );
    };

    const formatDuration = (seconds) => {
        if (!seconds && seconds !== 0) return '0:00';
        const secs = parseInt(seconds);
        if (isNaN(secs)) return '0:00';
        const minutes = Math.floor(secs / 60);
        const remainingSeconds = secs % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    };

    // Kosten basierend auf Kundenpreismodell berechnen (0,42€/Min, sekundengenau)
    const calculateCallCost = (durationInSeconds) => {
        if (!durationInSeconds && durationInSeconds !== 0) return '0.00';
        const secs = parseInt(durationInSeconds);
        if (isNaN(secs)) return '0.00';
        const pricePerMinute = 0.42; // Kundenpreis pro Minute
        const costInEuro = (secs / 60) * pricePerMinute;
        return costInEuro.toFixed(2);
    };

    // Zusammenfassung übersetzen
    const translateSummary = async (summaryText) => {
        if (!summaryText || isTranslating) return;
        
        setIsTranslating(true);
        try {
            const response = await axiosInstance.post(`/calls/${callId}/translate`, {
                text: summaryText,
                target_language: 'de',
                field: 'summary'
            });

            setTranslatedSummary(response.data.translated_text || summaryText);
        } catch (error) {
            setTranslatedSummary(summaryText);
        } finally {
            setIsTranslating(false);
        }
    };

    // Audio Control Functions
    const handlePlayPause = () => {
        if (!audioRef.current) return;
        
        if (isPlaying) {
            audioRef.current.pause();
        } else {
            audioRef.current.play();
        }
        setIsPlaying(!isPlaying);
    };

    const handleTimeUpdate = () => {
        if (audioRef.current) {
            setAudioCurrentTime(audioRef.current.currentTime);
        }
    };

    const handleLoadedMetadata = () => {
        if (audioRef.current) {
            setAudioDuration(audioRef.current.duration);
        }
    };

    const handleSeek = (e) => {
        const newTime = parseFloat(e.target.value);
        if (audioRef.current) {
            audioRef.current.currentTime = newTime;
            setAudioCurrentTime(newTime);
        }
    };

    const handleVolumeChange = (e) => {
        const newVolume = parseFloat(e.target.value);
        setVolume(newVolume);
        if (audioRef.current) {
            audioRef.current.volume = newVolume;
        }
    };

    const formatTime = (seconds) => {
        if (!seconds || isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    const handleEmailSent = () => {
        setShowEmailDialog(false);
        // Refresh activities to show the email sent event
        setTimeout(() => {
            fetchActivities();
        }, 1000);
    };

    const getCallTypeIcon = (type) => {
        switch(type) {
            case 'inbound':
                return <PhoneIncoming className="h-5 w-5 text-green-500" />;
            case 'outbound':
                return <PhoneOutgoing className="h-5 w-5 text-blue-500" />;
            default:
                return <Phone className="h-5 w-5 text-gray-500" />;
        }
    };

    if (loading) {
        return (
            <div className="p-6 flex items-center justify-center">
                <div className="text-center">
                    <Phone className="h-12 w-12 animate-pulse mx-auto text-gray-400" />
                    <p className="mt-2 text-gray-500">Lade Anrufdetails...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="p-6">
                <Alert variant="destructive">
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>
                        Fehler beim Laden der Anrufdetails: {error}
                    </AlertDescription>
                </Alert>
            </div>
        );
    }

    if (!call) {
        return (
            <div className="p-6">
                <Alert>
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>
                        Anruf nicht gefunden.
                    </AlertDescription>
                </Alert>
            </div>
        );
    }


    // Extract customer name and company
    const customerName = call.customer?.name || 
                        call.extracted_name ||
                        call.custom_analysis_data?.caller_full_name ||
                        call.customer_data_backup?.full_name ||
                        'Unbekannter Kunde';
    
    const companyName = call.custom_analysis_data?.company_name || 
                       call.customer_data_backup?.company ||
                       call.customer?.company_name ||
                       null;
    
    const customerNumber = call.custom_analysis_data?.customer_number || 
                          call.customer_data_backup?.customer_number ||
                          null;

    return (
        <div className="business-portal-wrapper p-4 sm:p-6 space-y-4 sm:space-y-6 relative overflow-x-hidden">
            {/* Company/Branch Context Header */}
            <div className="bg-gray-50 border-b border-gray-200 -m-4 sm:-m-6 mb-4 sm:mb-6 p-3 sm:p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => navigate('/calls')}
                        >
                            <ArrowLeft className="h-4 w-4 mr-1" />
                            Zurück
                        </Button>
                        
                        {/* Navigation Controls */}
                        <div className="flex items-center gap-2 border-l pl-4">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => navigation?.previous && navigate(`/calls/${navigation.previous.id}/v2${window.location.search}`)}
                                disabled={!navigation?.previous || loadingNavigation}
                                title={navigation?.previous ? `Vorheriger Anruf: ${navigation.previous.extracted_name || navigation.previous.from_number}` : 'Kein vorheriger Anruf'}
                            >
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                            
                            {navigation && (
                                <span className="text-sm text-gray-600 px-2">
                                    {navigation.position} von {navigation.total}
                                </span>
                            )}
                            
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => navigation?.next && navigate(`/calls/${navigation.next.id}/v2${window.location.search}`)}
                                disabled={!navigation?.next || loadingNavigation}
                                title={navigation?.next ? `Nächster Anruf: ${navigation.next.extracted_name || navigation.next.from_number}` : 'Kein nächster Anruf'}
                            >
                                <ArrowLeft className="h-4 w-4 rotate-180" />
                            </Button>
                        </div>
                        
                        <div className="border-l pl-4">
                            <p className="text-sm text-gray-500">Anruf für</p>
                            <p className="font-semibold flex items-center gap-2">
                                <Building className="h-4 w-4 text-gray-600" />
                                {call.branch?.name || 'Hauptfiliale'}
                                {call.to_number && (
                                    <>
                                        <span className="font-normal text-sm text-gray-500 ml-2">weitergeleitet an</span>
                                        <span className="font-medium text-sm">{call.to_number}</span>
                                    </>
                                )}
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-sm text-gray-500">Anruf vom</p>
                        <p className="font-medium">{dayjs(call.created_at).format('DD. MMMM YYYY, HH:mm')} Uhr</p>
                        <p className="text-sm mt-1 cursor-help" title={`Kosten: €${calculateCallCost(call.duration_sec || call.duration || 0)}`}>
                            <span className="flex items-center gap-1 justify-end hover:text-blue-600 transition-colors">
                                <Clock className="h-3 w-3" />
                                {formatDuration(call.duration_sec || call.duration || 0)}
                            </span>
                        </p>
                    </div>
                </div>
            </div>


            {/* Main Content Grid */}
            <div className="call-detail-grid grid gap-4 sm:gap-6 lg:grid-cols-[1fr,380px] xl:grid-cols-3">
                {/* Left Column: Customer & Call Info */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Customer Hero Section */}
                    <Card className="bg-gradient-to-r from-blue-50 to-white border-blue-200">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                                        <div className="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                            {customerName.charAt(0).toUpperCase()}
                                        </div>
                                        {customerName}
                                    </h1>
                                    {companyName && (
                                        <p className="text-lg text-gray-600 mt-1 flex items-center gap-2">
                                            <Building2 className="h-4 w-4" />
                                            {companyName}
                                            {customerNumber && <span className="text-sm text-gray-500">• Kundennr: {customerNumber}</span>}
                                        </p>
                                    )}
                                </div>
                                {/* Dringlichkeit im Namen-Kasten */}
                                {call.custom_analysis_data?.urgency_level && (
                                    <div className="flex items-start">
                                        {getUrgencyBadge(call.custom_analysis_data.urgency_level)}
                                    </div>
                                )}
                            </div>
                            
                            {/* Quick Actions */}
                            <div className="flex gap-2 mt-4">
                                <Button className="bg-blue-600 hover:bg-blue-700" onClick={() => window.location.href = `tel:${call.from_number}`}>
                                    <Phone className="h-4 w-4 mr-2" />
                                    Anrufen
                                </Button>
                                {(call.customer?.email || call.extracted_email) && (
                                    <Button variant="outline" onClick={() => window.location.href = `mailto:${call.customer?.email || call.extracted_email}`}>
                                        <Mail className="h-4 w-4 mr-2" />
                                        E-Mail
                                    </Button>
                                )}
                            </div>
                            
                            {/* Termin Info Badge */}
                            {(call.appointment_made || call.datum_termin || call.uhrzeit_termin) && (
                                <div className="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <div className="flex items-center gap-2">
                                        <Calendar className="h-4 w-4 text-green-600" />
                                        <span className="text-sm font-medium text-green-900">
                                            Termin vereinbart
                                            {call.datum_termin && call.uhrzeit_termin && (
                                                <>: {call.datum_termin} um {call.uhrzeit_termin}</>
                                            )}
                                        </span>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Compact Info Cards - 2 Column Layout */}
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Contact Card */}
                        <Card className="hover:shadow-md transition-shadow duration-150">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Phone className="h-4 w-4 text-blue-600" />
                                    Kontakt
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <p className="text-xs text-muted-foreground mb-1">Anrufer</p>
                                    <p className="font-medium flex items-center gap-2">
                                        <PhoneIncoming className="h-3 w-3 text-green-600" />
                                        {call.from_number}
                                    </p>
                                </div>
                                {call.extracted_phone && 
                                 call.extracted_phone !== call.from_number && 
                                 !call.extracted_phone.includes('{{') && (
                                    <div>
                                        <p className="text-xs text-muted-foreground mb-1">Angegeben</p>
                                        <p className="font-medium flex items-center gap-2">
                                            <Phone className="h-3 w-3" />
                                            {call.extracted_phone}
                                        </p>
                                    </div>
                                )}
                                {(call.customer?.email || call.extracted_email) && (
                                    <div>
                                        <p className="text-xs text-muted-foreground mb-1">E-Mail</p>
                                        <p className="font-medium text-sm break-all">
                                            {call.customer?.email || call.extracted_email}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Company Card */}
                        <Card className="hover:shadow-md transition-shadow duration-150">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Building2 className="h-4 w-4 text-blue-600" />
                                    Firma
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <p className="text-xs text-muted-foreground mb-1">Firmenname</p>
                                    <p className="font-medium">{companyName || '-'}</p>
                                </div>
                                {customerNumber && (
                                    <div>
                                        <p className="text-xs text-muted-foreground mb-1">Kundennummer</p>
                                        <p className="font-medium">{customerNumber}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Main Content Area */}
                    <Card>
                        <CardContent className="p-0">
                            <Tabs value={activeTab} onValueChange={setActiveTab}>
                                <TabsList className="grid w-full grid-cols-2">
                                    <TabsTrigger value="details">Details</TabsTrigger>
                                    <TabsTrigger value="audio-transcript">Audio & Transkript</TabsTrigger>
                                </TabsList>
                                
                                <TabsContent value="details" className="p-6 space-y-4">
                                    {/* Zusammenfassung */}
                                    {(call.summary || translatedSummary) && (
                                        <div>
                                            <h3 className="font-semibold mb-3 flex items-center gap-2">
                                                <FileText className="h-4 w-4" />
                                                Zusammenfassung
                                            </h3>
                                            <div className="bg-gray-50 rounded-lg p-4">
                                                {isTranslating && (
                                                    <p className="text-sm text-gray-500 mb-2">Übersetze...</p>
                                                )}
                                                <p className="text-sm leading-relaxed">
                                                    {translatedSummary || call.summary}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Kundenanfrage */}
                                    {(call.custom_analysis_data?.customer_request || call.customer_data_backup?.request) && (
                                        <div>
                                            <h3 className="font-semibold mb-3 flex items-center gap-2">
                                                <MessageSquare className="h-4 w-4" />
                                                Kundenanfrage
                                            </h3>
                                            <div className="bg-blue-50 rounded-lg p-4">
                                                <p className="text-sm">
                                                    {call.custom_analysis_data?.customer_request || 
                                                     call.customer_data_backup?.request}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Enhanced Appointment Details */}
                                    {(call.appointment_requested || call.appointment_id || call.datum_termin) && (
                                        <AppointmentDetails 
                                            call={call} 
                                            appointment={call.appointment}
                                            onUpdate={() => {
                                                // Refresh call data after appointment update
                                                window.location.reload();
                                            }}
                                        />
                                    )}
                                </TabsContent>

                                <TabsContent value="audio-transcript" className="p-6 space-y-4">
                                    {/* Audio Player */}
                                    {call.recording_url && (
                                        <div className="bg-gray-50 rounded-lg p-4 space-y-4">
                                            <h3 className="font-semibold text-sm flex items-center gap-2">
                                                <Volume2 className="h-4 w-4" />
                                                Audioaufnahme
                                            </h3>
                                            
                                            <audio
                                                ref={audioRef}
                                                src={call.recording_url || call.audio_url || call.recordingUrl}
                                                onTimeUpdate={handleTimeUpdate}
                                                onLoadedMetadata={handleLoadedMetadata}
                                                onEnded={() => setIsPlaying(false)}
                                                onError={() => toast.error('Fehler beim Laden der Audioaufnahme')}
                                                className="hidden"
                                                preload="metadata"
                                            />
                                            
                                            <div className="space-y-3">
                                                {/* Play/Pause Button and Time Display */}
                                                <div className="flex items-center gap-4">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={handlePlayPause}
                                                        className="flex items-center gap-2"
                                                    >
                                                        {isPlaying ? (
                                                            <>
                                                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <rect x="6" y="4" width="4" height="16" fill="currentColor" />
                                                                    <rect x="14" y="4" width="4" height="16" fill="currentColor" />
                                                                </svg>
                                                                Pause
                                                            </>
                                                        ) : (
                                                            <>
                                                                <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path d="M8 5v14l11-7z" />
                                                                </svg>
                                                                Abspielen
                                                            </>
                                                        )}
                                                    </Button>
                                                    <span className="text-sm text-gray-600">
                                                        {formatTime(audioCurrentTime)} / {formatTime(audioDuration)}
                                                    </span>
                                                    
                                                    {/* Download Button */}
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => {
                                                            const link = document.createElement('a');
                                                            link.href = call.recording_url || call.audio_url || call.recordingUrl;
                                                            link.download = `anruf_${call.id}_${call.created_at.split('T')[0]}.mp3`;
                                                            document.body.appendChild(link);
                                                            link.click();
                                                            document.body.removeChild(link);
                                                        }}
                                                        className="ml-auto"
                                                        title="Aufnahme herunterladen"
                                                    >
                                                        <Download className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                                
                                                {/* Progress Bar */}
                                                <div className="flex items-center gap-3 w-full">
                                                    <input
                                                        type="range"
                                                        min="0"
                                                        max={audioDuration || 0}
                                                        value={audioCurrentTime}
                                                        onChange={handleSeek}
                                                        className="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                                        style={{
                                                            background: `linear-gradient(to right, #3b82f6 ${(audioCurrentTime / (audioDuration || 1)) * 100}%, #e5e7eb ${(audioCurrentTime / (audioDuration || 1)) * 100}%)`
                                                        }}
                                                    />
                                                </div>
                                                
                                                {/* Volume Control */}
                                                <div className="flex items-center gap-3">
                                                    <Volume2 className="h-4 w-4 text-gray-500" />
                                                    <input
                                                        type="range"
                                                        min="0"
                                                        max="1"
                                                        step="0.05"
                                                        value={volume}
                                                        onChange={handleVolumeChange}
                                                        className="w-24 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                                        style={{
                                                            background: `linear-gradient(to right, #3b82f6 ${volume * 100}%, #e5e7eb ${volume * 100}%)`
                                                        }}
                                                    />
                                                    <span className="text-sm text-gray-500">{Math.round(volume * 100)}%</span>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                    
                                    <Separator />
                                    
                                    {/* Transcript */}
                                    <div>
                                        <h3 className="font-semibold mb-3">Gesprächstranskript</h3>
                                        {call.transcript ? (
                                            <div className="space-y-3">
                                                {call.transcript.split('\n').map((line, index) => {
                                                    const isAgent = line.toLowerCase().includes('agent:');
                                                    return (
                                                        <div
                                                            key={index}
                                                            className={`p-3 rounded-lg ${
                                                                isAgent 
                                                                    ? 'bg-blue-50 ml-8' 
                                                                    : 'bg-gray-50 mr-8'
                                                            }`}
                                                        >
                                                            <p className="text-sm">
                                                                {line}
                                                            </p>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        ) : (
                                            <div className="text-center py-8 text-muted-foreground">
                                                <MessageSquare className="h-12 w-12 mx-auto mb-2" />
                                                <p>Kein Transkript verfügbar</p>
                                            </div>
                                        )}
                                    </div>
                                </TabsContent>
                            </Tabs>
                        </CardContent>
                    </Card>
                </div>

                {/* Right Column: Status Timeline */}
                <div className="space-y-4 sm:space-y-6">
                    {/* Status & History Card */}
                    <Card className="sticky-sidebar">
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <Activity className="h-5 w-5 text-blue-600" />
                                Status & Verlauf
                            </CardTitle>
                            <CardDescription>
                                Aktuelle Bearbeitung und Historie
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {/* Current Status Section */}
                            <div className="mb-4 p-4 bg-gradient-to-r from-blue-50 to-white rounded-lg border border-blue-200">
                                <div className="flex items-center justify-between mb-2">
                                    <h4 className="text-sm font-semibold text-gray-700">Aktueller Status</h4>
                                    {getStatusBadge(call.status || 'ended')}
                                </div>
                                
                                <div className="text-xs text-gray-500 mb-2">
                                    {dayjs(call.updated_at || call.created_at).format('DD.MM.YYYY HH:mm')} Uhr
                                </div>
                                
                                {/* Zuweisungsinformation wenn vorhanden */}
                                {call.callPortalData?.assigned_to && (
                                    <div className="mt-3 pt-3 border-t border-blue-100">
                                        <p className="text-sm text-gray-700 flex items-center gap-2">
                                            <UserCheck className="h-3.5 w-3.5 text-blue-600" />
                                            Zugewiesen an: <span className="font-medium">{call.callPortalData.assignedTo?.name || 'Unbekannt'}</span>
                                        </p>
                                        {call.callPortalData.assigned_at && (
                                            <p className="text-xs text-gray-500 mt-1">
                                                seit {dayjs(call.callPortalData.assigned_at).fromNow()}
                                            </p>
                                        )}
                                    </div>
                                )}
                                
                                {/* Email Forward Counter */}
                                <div className="mt-3 pt-3 border-t border-blue-100 flex items-center gap-4 text-sm">
                                    <div className="flex items-center gap-1.5 text-gray-600">
                                        <Mail className="h-3.5 w-3.5 text-blue-600" />
                                        <span>E-Mails versendet: <strong className="text-blue-700">{activities.filter(a => a.activity_type === 'email_sent').length}</strong></span>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Quick Actions - moved here under status */}
                            <div className="mt-4 space-y-2">
                                <Button 
                                    size="sm" 
                                    variant="outline" 
                                    className="w-full justify-start"
                                    onClick={() => setShowEmailDialog(true)}
                                >
                                    <Send className="h-4 w-4 mr-2" />
                                    Zusammenfassung senden
                                </Button>
                                <Button size="sm" variant="outline" className="w-full justify-start">
                                    <UserCheck className="h-4 w-4 mr-2" />
                                    Zuweisen an...
                                </Button>
                            </div>
                            
                            <Separator className="my-4" />
                            
                            {/* Timeline Section */}
                            <div>
                                <h4 className="text-sm font-semibold text-gray-700 mb-3">Verlauf</h4>
                                
                                {loadingActivities ? (
                                    <div className="flex items-center justify-center py-8">
                                    <div className="text-center">
                                        <Clock className="h-8 w-8 animate-pulse mx-auto text-gray-400" />
                                        <p className="mt-2 text-sm text-gray-500">Lade Verlauf...</p>
                                    </div>
                                </div>
                            ) : activities.length > 0 ? (
                                <div className="space-y-4">
                                    {activities.map((event, index) => {
                                        const IconComponent = activityIcons[event.icon] || Activity;
                                        const isLast = index === activities.length - 1;
                                        
                                        return (
                                            <div key={event.id} className="relative">
                                                {!isLast && (
                                                    <div className="absolute left-5 top-10 bottom-0 w-0.5 bg-gray-200" />
                                                )}
                                                
                                                <div className="flex gap-3">
                                                    <div className={`w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 bg-${event.color || 'gray'}-100`}>
                                                        <IconComponent className={`h-5 w-5 text-${event.color || 'gray'}-600`} />
                                                    </div>
                                                    
                                                    <div className="flex-1 pb-4">
                                                        <p className="font-medium text-sm">{event.title}</p>
                                                        {event.description && (
                                                            <p className="text-xs text-gray-600 mt-0.5">{event.description}</p>
                                                        )}
                                                        <p className="text-xs text-gray-500 mt-1">
                                                            {event.user?.name || 'System'} • {event.time_ago}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                                ) : (
                                    <div className="text-center py-8 text-gray-500">
                                        <Activity className="h-8 w-8 mx-auto mb-2 text-gray-400" />
                                        <p className="text-sm">Noch keine Aktivitäten</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recording Quick Access */}
                    {call.recording_url && (
                        <Card>
                            <CardContent className="p-4">
                                <Button 
                                    variant="outline" 
                                    className="w-full"
                                    onClick={() => setActiveTab('audio-transcript')}
                                >
                                    <Volume2 className="h-4 w-4 mr-2" />
                                    Aufnahme anhören
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>

            {/* Metadata Footer - moved to bottom */}
            <div className="mt-12 pt-6 border-t">
                <div className="text-center text-sm text-muted-foreground space-y-2">
                    <p>
                        Erstellt: {dayjs(call.created_at).format('DD.MM.YYYY HH:mm')} • 
                        Aktualisiert: {dayjs(call.updated_at).format('DD.MM.YYYY HH:mm')}
                        {call.analyzed_at && (
                            <> • Analysiert: {dayjs(call.analyzed_at).format('DD.MM.YYYY HH:mm')}</>
                        )}
                    </p>
                    {navigation && (navigation.previous || navigation.next) && (
                        <p className="text-xs text-gray-400">
                            Tipp: Verwenden Sie die Pfeiltasten (← →) zur Navigation zwischen Anrufen
                        </p>
                    )}
                </div>
            </div>

            {/* Floating Action Bar - Fixed Position */}
            <div className="floating-action-bar">
                <div className="bg-white rounded-full shadow-lg border p-2 sm:p-3 flex gap-2">
                    <Button 
                        size="icon" 
                        className="rounded-full bg-blue-600 hover:bg-blue-700 min-w-[44px] min-h-[44px]"
                        onClick={() => window.location.href = `tel:${call.from_number}`}
                        title="Anrufen"
                    >
                        <Phone className="h-4 w-4 sm:h-5 sm:w-5" />
                    </Button>
                    {(call.customer?.email || call.extracted_email) && (
                        <Button 
                            size="icon" 
                            variant="outline" 
                            className="rounded-full min-w-[44px] min-h-[44px]"
                            onClick={() => window.location.href = `mailto:${call.customer?.email || call.extracted_email}`}
                            title="E-Mail senden"
                        >
                            <Mail className="h-4 w-4 sm:h-5 sm:w-5" />
                        </Button>
                    )}
                </div>
            </div>
            
            {/* Email Dialog */}
            <Dialog open={showEmailDialog} onOpenChange={setShowEmailDialog}>
                <DialogContent className="email-composer-dialog max-w-[95vw] lg:max-w-4xl max-h-[90vh] overflow-y-auto mx-2 sm:mx-auto">
                    <DialogHeader>
                        <DialogTitle>E-Mail versenden</DialogTitle>
                    </DialogHeader>
                    <EmailComposerWithPreview 
                        call={call}
                        onClose={handleEmailSent}
                        csrfToken={csrfToken}
                    />
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default CallShowV2;