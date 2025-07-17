import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Badge } from '../../../components/ui/badge';
import { Input } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { useAuth } from '../../../hooks/useAuth';
import { useCalls } from '../../../hooks/useCalls';
import { toast } from 'react-toastify';
import axiosInstance from '../../../services/axiosInstance';
import { 
    Phone, 
    RefreshCw, 
    Download,
    AlertTriangle,
    Clock,
    Calendar,
    User,
    Building,
    Filter,
    Search,
    CheckSquare,
    X,
    ChevronLeft,
    ChevronRight,
    FileText,
    PhoneIncoming,
    PhoneOutgoing,
    PhoneMissed,
    Play,
    Pause,
    Globe,
    MessageSquare,
    Copy,
    Volume2
} from 'lucide-react';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import relativeTime from 'dayjs/plugin/relativeTime';
import { useIsMobile } from '../../../hooks/useMediaQuery';
import MobileCallList from '../../../components/Mobile/MobileCallList';
import AppointmentStatusBadge from '../../../components/Portal/AppointmentStatusBadge';

dayjs.locale('de');
dayjs.extend(relativeTime);

const CallsIndex = () => {
    const navigate = useNavigate();
    const { csrfToken } = useAuth();
    const isMobile = useIsMobile();
    const {
        loading,
        error,
        calls,
        stats,
        filters,
        setFilters,
        pagination,
        selectedCalls,
        toggleCallSelection,
        selectAllCalls,
        exportCalls,
        refresh
    } = useCalls(csrfToken);

    const [searchTerm, setSearchTerm] = useState('');
    const [showFilters, setShowFilters] = useState(false);
    const [activeTab, setActiveTab] = useState('all');
    const [expandedTranscripts, setExpandedTranscripts] = useState(new Set());
    const [translatingCalls, setTranslatingCalls] = useState(new Set());
    const [translatedTranscripts, setTranslatedTranscripts] = useState({});
    const [playingCalls, setPlayingCalls] = useState(new Set());
    const audioRefs = useRef({});

    // Status Badge Mapping
    const getStatusBadge = (status) => {
        const statusConfig = {
            'ended': { label: 'Beendet', variant: 'default' },
            'analyzed': { label: 'Analysiert', variant: 'success' },
            'error': { label: 'Fehler', variant: 'destructive' },
            'ongoing': { label: 'Laufend', variant: 'warning' },
            'no-answer': { label: 'Nicht erreicht', variant: 'secondary' }
        };
        
        const config = statusConfig[status] || { label: status, variant: 'default' };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    // Call Type Icon
    const getCallTypeIcon = (type) => {
        switch(type) {
            case 'inbound':
                return <PhoneIncoming className="h-4 w-4 text-green-500" />;
            case 'outbound':
                return <PhoneOutgoing className="h-4 w-4 text-blue-500" />;
            case 'missed':
                return <PhoneMissed className="h-4 w-4 text-red-500" />;
            default:
                return <Phone className="h-4 w-4 text-gray-500" />;
        }
    };

    // Format Duration
    const formatDuration = (seconds) => {
        if (!seconds) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    // Toggle Transcript
    const toggleTranscript = (callId) => {
        const newExpanded = new Set(expandedTranscripts);
        if (newExpanded.has(callId)) {
            newExpanded.delete(callId);
        } else {
            newExpanded.add(callId);
        }
        setExpandedTranscripts(newExpanded);
    };

    // Handle Translation
    const handleTranslate = async (callId, transcript) => {
        if (!transcript || translatingCalls.has(callId)) return;

        setTranslatingCalls(prev => new Set(prev).add(callId));
        try {
            const response = await axiosInstance.post(`/business/api/calls/${callId}/translate`, {
                target_language: 'de'
            });
            setTranslatedTranscripts(prev => ({
                ...prev,
                [callId]: response.data.translated_transcript
            }));
            toast.success('Übersetzung erfolgreich: Das Transkript wurde übersetzt');
        } catch (error) {
            // Error is already handled by toast notification
            toast.error('Fehler: Übersetzung fehlgeschlagen');
        } finally {
            setTranslatingCalls(prev => {
                const newSet = new Set(prev);
                newSet.delete(callId);
                return newSet;
            });
        }
    };

    // Handle Audio Play/Pause
    const handlePlayPause = (callId, recordingUrl) => {
        if (!recordingUrl) return;

        const isPlaying = playingCalls.has(callId);
        
        if (isPlaying) {
            // Pause
            if (audioRefs.current[callId]) {
                audioRefs.current[callId].pause();
            }
            setPlayingCalls(prev => {
                const newSet = new Set(prev);
                newSet.delete(callId);
                return newSet;
            });
        } else {
            // Play
            if (!audioRefs.current[callId]) {
                audioRefs.current[callId] = new Audio(recordingUrl);
                audioRefs.current[callId].onended = () => {
                    setPlayingCalls(prev => {
                        const newSet = new Set(prev);
                        newSet.delete(callId);
                        return newSet;
                    });
                };
            }
            audioRefs.current[callId].play();
            setPlayingCalls(prev => new Set(prev).add(callId));
        }
    };

    // Copy to Clipboard
    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        toast.success('In die Zwischenablage kopiert');
    };

    // Cleanup audio on unmount
    useEffect(() => {
        return () => {
            Object.values(audioRefs.current).forEach(audio => {
                audio.pause();
                audio.src = '';
            });
        };
    }, []);

    const handleExport = async (selectedOnly = false) => {
        try {
            const callIds = selectedOnly ? selectedCalls : null;
            const result = await exportCalls(callIds);
            // Handle download or redirect
            if (result.url) {
                window.location.href = result.url;
            }
        } catch (error) {
            toast.error('Export fehlgeschlagen. Bitte versuchen Sie es erneut.');
        }
    };

    if (error) {
        return (
            <div className="p-6">
                <Alert variant="destructive">
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>
                        Fehler beim Laden der Anrufe: {error}
                    </AlertDescription>
                </Alert>
            </div>
        );
    }

    // Mobile View
    if (isMobile) {
        return (
            <div className="min-h-screen bg-gray-50">
                {/* Mobile Stats */}
                <div className="grid grid-cols-2 gap-3 p-4">
                    <Card>
                        <CardContent className="p-4">
                            <div className="text-sm text-gray-500">Heute</div>
                            <div className="text-2xl font-bold">{stats?.total_today || 0}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <div className="text-sm text-gray-500">Neu</div>
                            <div className="text-2xl font-bold text-blue-600">{stats?.new || 0}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Mobile Call List */}
                <MobileCallList 
                    calls={calls}
                    onRefresh={refresh}
                    loading={loading}
                />
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
                            <Phone className="h-8 w-8" />
                            Anrufe
                        </h1>
                        <p className="text-muted-foreground mt-1">
                            Übersicht aller eingegangenen Anrufe mit detaillierten Informationen
                        </p>
                    </div>
                <div className="flex gap-3">
                    <Button
                        variant="outline"
                        onClick={refresh}
                        disabled={loading}
                    >
                        <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Aktualisieren
                    </Button>
                    {selectedCalls.length > 0 ? (
                        <Button 
                            variant="default"
                            onClick={() => handleExport(true)}
                        >
                            <Download className="h-4 w-4 mr-2" />
                            {selectedCalls.length} ausgewählte exportieren
                        </Button>
                    ) : (
                        <Button 
                            variant="default"
                            onClick={() => handleExport(false)}
                        >
                            <Download className="h-4 w-4 mr-2" />
                            Alle exportieren
                        </Button>
                    )}
                </div>
            </div>

            {/* Stats Cards */}
            <div className="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Anrufe heute
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <Phone className="h-5 w-5 text-muted-foreground" />
                            <span className="text-2xl font-bold">{stats?.total_today || 0}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Neue Anrufe
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <FileText className="h-5 w-5 text-blue-500" />
                            <span className="text-2xl font-bold">{stats?.new || 0}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Aktion erforderlich
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-yellow-500" />
                            <span className="text-2xl font-bold">{stats?.action_required || 0}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Durchschn. Dauer
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2">
                            <Clock className="h-5 w-5 text-muted-foreground" />
                            <span className="text-2xl font-bold">{formatDuration(stats?.avg_duration || 0)}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Filters and Search */}
            <Card>
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
                                    placeholder="Suche nach Telefonnummer, Name..."
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
                                    <SelectItem value="ended">Beendet</SelectItem>
                                    <SelectItem value="analyzed">Analysiert</SelectItem>
                                    <SelectItem value="error">Fehler</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={filters.branch} onValueChange={(value) => setFilters({...filters, branch: value})}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Filiale" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Filialen</SelectItem>
                                    {/* Branch options would be loaded dynamically */}
                                </SelectContent>
                            </Select>
                            <Input
                                type="date"
                                value={filters.date}
                                onChange={(e) => setFilters({...filters, date: e.target.value})}
                                placeholder="Datum"
                            />
                        </div>
                    </CardContent>
                )}
            </Card>

            {/* Calls Table */}
            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">
                                    <input
                                        type="checkbox"
                                        onChange={(e) => selectAllCalls(e.target.checked)}
                                        checked={selectedCalls.length === calls.length && calls.length > 0}
                                        className="rounded border-gray-300"
                                    />
                                </TableHead>
                                <TableHead>Typ</TableHead>
                                <TableHead>Anrufer</TableHead>
                                <TableHead>Filiale</TableHead>
                                <TableHead>Datum & Zeit</TableHead>
                                <TableHead>Dauer</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Aktionen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {loading ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-8">
                                        <RefreshCw className="h-6 w-6 animate-spin mx-auto text-gray-400" />
                                        <p className="mt-2 text-gray-500">Lade Anrufe...</p>
                                    </TableCell>
                                </TableRow>
                            ) : calls.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center py-8">
                                        <Phone className="h-12 w-12 mx-auto text-gray-400" />
                                        <p className="mt-2 text-gray-500">Keine Anrufe gefunden</p>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                calls.map((call) => (
                                    <React.Fragment key={call.id}>
                                    <TableRow key={call.id} className="hover:bg-gray-50">
                                        <TableCell>
                                            <input
                                                type="checkbox"
                                                checked={selectedCalls.includes(call.id)}
                                                onChange={() => toggleCallSelection(call.id)}
                                                className="rounded border-gray-300"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            {getCallTypeIcon(call.type)}
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                <p className="font-medium">{call.from_number}</p>
                                                {(call.extracted_name || call.customer_name) && (
                                                    <p className="text-sm text-gray-500">
                                                        {call.extracted_name || call.customer_name}
                                                    </p>
                                                )}
                                                {/* Appointment Status Badge */}
                                                <AppointmentStatusBadge 
                                                    call={call}
                                                    onViewAppointment={(appointmentId) => {
                                                        navigate(`/business/appointments/${appointmentId}`);
                                                    }}
                                                />
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Building className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm">{call.branch_name || '-'}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                <p className="text-sm">{dayjs(call.created_at).format('DD.MM.YYYY')}</p>
                                                <p className="text-xs text-gray-500">{dayjs(call.created_at).format('HH:mm')} Uhr</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Clock className="h-4 w-4 text-gray-400" />
                                                <span>{formatDuration(call.duration)}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                {getStatusBadge(call.status)}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex gap-1">
                                                {call.recording_url && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handlePlayPause(call.id, call.recording_url)}
                                                        title="Audio abspielen"
                                                    >
                                                        {playingCalls.has(call.id) ? 
                                                            <Pause className="h-4 w-4" /> : 
                                                            <Play className="h-4 w-4" />
                                                        }
                                                    </Button>
                                                )}
                                                {call.transcript && (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => toggleTranscript(call.id)}
                                                            title="Transkript anzeigen"
                                                        >
                                                            <MessageSquare className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleTranslate(call.id, call.transcript)}
                                                            disabled={translatingCalls.has(call.id)}
                                                            title="Übersetzen"
                                                        >
                                                            <Globe className="h-4 w-4" />
                                                        </Button>
                                                    </>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => navigate(`/calls/${call.id}`)}
                                                >
                                                    Details
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => navigate(`/calls/${call.id}/v2`)}
                                                    className="text-blue-600 hover:text-blue-700"
                                                    title="Neue Ansicht testen"
                                                >
                                                    V2
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    {expandedTranscripts.has(call.id) && call.transcript && (
                                        <TableRow key={`transcript-${call.id}`}>
                                            <TableCell colSpan={8} className="bg-gray-50">
                                                <div className="p-4">
                                                    <div className="flex justify-between items-start mb-2">
                                                        <h4 className="font-medium text-sm">Transkript</h4>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => copyToClipboard(translatedTranscripts[call.id] || call.transcript)}
                                                        >
                                                            <Copy className="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                    <div className="bg-white p-3 rounded border text-sm whitespace-pre-wrap">
                                                        {translatedTranscripts[call.id] || call.transcript}
                                                    </div>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </React.Fragment>
                                ))
                            )}
                        </TableBody>
                    </Table>

                    {/* Pagination */}
                    {pagination && pagination.last_page > 1 && (
                        <div className="flex items-center justify-between px-4 py-3 border-t">
                            <div className="text-sm text-gray-700">
                                Zeige {pagination.from} bis {pagination.to} von {pagination.total} Einträgen
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setFilters({...filters, page: pagination.current_page - 1})}
                                    disabled={pagination.current_page === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Zurück
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setFilters({...filters, page: pagination.current_page + 1})}
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
        </div>
    );
};

export default CallsIndex;