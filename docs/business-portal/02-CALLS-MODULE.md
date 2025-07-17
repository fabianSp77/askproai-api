# 📞 Calls Module - Complete Guide

## Overview

The Calls module is the heart of the Business Portal, managing all phone interactions powered by Retell.ai. It provides comprehensive call management, analytics, and customer insights.

## Core Features

### 1. Call List Management
- Real-time call updates via WebSocket
- Advanced filtering and search
- Bulk operations (export, status updates)
- Custom column preferences
- Smart pagination with infinite scroll

### 2. Call Details View
- Full transcript with speaker identification
- Audio playback with waveform visualization
- AI-generated summary and action items
- Customer information panel
- Call timeline and events

### 3. Call Analytics
- Call patterns and trends
- Conversion metrics
- Peak hour analysis
- Agent performance tracking
- Customer sentiment analysis

## Technical Architecture

### Component Structure
```
CallsIndex
├── CallsHeader
│   ├── Title & Stats
│   ├── BulkActions
│   └── ExportOptions
├── CallsFilters
│   ├── DateRangePicker
│   ├── StatusFilter
│   ├── SearchBar
│   ├── TagFilter
│   └── AdvancedFilters
├── CallsTable
│   ├── TableHeader (sortable)
│   ├── CallRow (expandable)
│   │   ├── BasicInfo
│   │   ├── QuickActions
│   │   └── ExpandedDetails
│   └── LoadMoreTrigger
└── CallDetailModal
    ├── CallHeader
    ├── TranscriptViewer
    ├── AudioPlayer
    ├── CustomerPanel
    └── ActionButtons
```

### State Management
```javascript
// Call Context
const CallContext = createContext({
    calls: [],
    filters: {
        dateRange: { start: null, end: null },
        status: 'all',
        search: '',
        tags: [],
        hasAppointment: null,
        minDuration: null,
        maxDuration: null
    },
    pagination: {
        page: 1,
        perPage: 50,
        total: 0,
        hasMore: true
    },
    selectedCall: null,
    columnPreferences: {
        visible: ['phone', 'duration', 'status', 'created_at'],
        order: ['phone', 'duration', 'status', 'created_at', 'actions']
    },
    isLoading: false,
    error: null
});
```

## API Reference

### List Calls
```http
GET /business/api/calls

Query Parameters:
- page: number (default: 1)
- per_page: number (default: 50, max: 100)
- search: string (searches phone, transcript, notes)
- status: string (answered|missed|voicemail|all)
- date_from: string (ISO 8601)
- date_to: string (ISO 8601)
- has_appointment: boolean
- tags: array (tag IDs)
- sort: string (created_at|-created_at|duration|-duration)

Response:
{
    "data": [
        {
            "id": "call_abc123",
            "company_id": 1,
            "from_number": "+49123456789",
            "to_number": "+49987654321",
            "direction": "inbound",
            "status": "answered",
            "duration": 245,
            "recording_url": "https://...",
            "transcript": {...},
            "summary": "Customer called about...",
            "appointment_created": true,
            "customer": {
                "id": 123,
                "name": "Max Mustermann",
                "email": "max@example.com",
                "tags": ["VIP", "Regular"]
            },
            "metadata": {
                "retell_call_id": "...",
                "sentiment": "positive",
                "action_items": ["Schedule follow-up", "Send quote"]
            },
            "created_at": "2025-01-10T10:30:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 50,
        "total": 245,
        "last_page": 5
    }
}
```

### Get Call Details
```http
GET /business/api/calls/{id}

Response:
{
    "id": "call_abc123",
    "company_id": 1,
    "branch_id": 2,
    "from_number": "+49123456789",
    "to_number": "+49987654321",
    "direction": "inbound",
    "status": "answered",
    "duration": 245,
    "recording_url": "https://...",
    "transcript": {
        "text": "Full transcript text...",
        "segments": [
            {
                "speaker": "agent",
                "text": "Hello, how can I help you?",
                "timestamp": 0.5,
                "confidence": 0.98
            },
            {
                "speaker": "customer",
                "text": "I'd like to book an appointment",
                "timestamp": 2.3,
                "confidence": 0.95
            }
        ]
    },
    "summary": "Customer called to book an appointment for next week...",
    "action_items": [
        {
            "text": "Schedule appointment for Tuesday 2pm",
            "completed": true,
            "completed_at": "2025-01-10T10:45:00Z"
        }
    ],
    "customer": {
        "id": 123,
        "name": "Max Mustermann",
        "email": "max@example.com",
        "phone": "+49123456789",
        "tags": ["VIP", "Regular"],
        "total_calls": 15,
        "total_appointments": 8,
        "lifetime_value": 2500.00
    },
    "appointment": {
        "id": 456,
        "starts_at": "2025-01-17T14:00:00Z",
        "service": "Consultation",
        "staff": "Dr. Schmidt",
        "status": "scheduled"
    },
    "events": [
        {
            "type": "call_started",
            "timestamp": "2025-01-10T10:30:00Z"
        },
        {
            "type": "customer_identified",
            "timestamp": "2025-01-10T10:30:15Z",
            "data": { "customer_id": 123 }
        },
        {
            "type": "appointment_created",
            "timestamp": "2025-01-10T10:33:45Z",
            "data": { "appointment_id": 456 }
        },
        {
            "type": "call_ended",
            "timestamp": "2025-01-10T10:34:05Z"
        }
    ],
    "metadata": {
        "retell_call_id": "...",
        "ai_model": "gpt-4",
        "language": "de",
        "sentiment_scores": {
            "positive": 0.85,
            "neutral": 0.10,
            "negative": 0.05
        }
    },
    "created_at": "2025-01-10T10:30:00Z",
    "updated_at": "2025-01-10T10:35:00Z"
}
```

### Update Call Status
```http
POST /business/api/calls/{id}/status

Request:
{
    "status": "reviewed",
    "notes": "Follow-up completed"
}

Response:
{
    "success": true,
    "message": "Call status updated"
}
```

### Send Call Summary
```http
POST /business/api/calls/{id}/send-summary

Request:
{
    "recipient_email": "customer@example.com",
    "include_transcript": true,
    "include_recording": false,
    "custom_message": "Thank you for your call..."
}

Response:
{
    "success": true,
    "message": "Summary sent successfully"
}
```

## Advanced Features

### 1. Transcript Intelligence
```javascript
// Transcript analysis and highlighting
const TranscriptViewer = ({ transcript }) => {
    const [highlights, setHighlights] = useState([]);
    
    // Highlight important keywords
    const highlightKeywords = (text) => {
        const keywords = ['appointment', 'termin', 'book', 'schedule'];
        let highlighted = text;
        
        keywords.forEach(keyword => {
            const regex = new RegExp(`(${keyword})`, 'gi');
            highlighted = highlighted.replace(regex, '<mark>$1</mark>');
        });
        
        return highlighted;
    };
    
    // Sentiment coloring
    const getSentimentColor = (score) => {
        if (score > 0.7) return 'text-green-600';
        if (score < 0.3) return 'text-red-600';
        return 'text-gray-600';
    };
    
    return (
        <div className="transcript-viewer">
            {transcript.segments.map((segment, idx) => (
                <div 
                    key={idx}
                    className={`segment ${segment.speaker}`}
                >
                    <div className="speaker">{segment.speaker}</div>
                    <div 
                        className={getSentimentColor(segment.sentiment)}
                        dangerouslySetInnerHTML={{ 
                            __html: highlightKeywords(segment.text) 
                        }}
                    />
                    <div className="timestamp">
                        {formatTime(segment.timestamp)}
                    </div>
                </div>
            ))}
        </div>
    );
};
```

### 2. Audio Player with Waveform
```javascript
// Advanced audio player component
const CallAudioPlayer = ({ audioUrl, transcript }) => {
    const [isPlaying, setIsPlaying] = useState(false);
    const [currentTime, setCurrentTime] = useState(0);
    const [duration, setDuration] = useState(0);
    const wavesurferRef = useRef(null);
    
    useEffect(() => {
        // Initialize WaveSurfer
        wavesurferRef.current = WaveSurfer.create({
            container: '#waveform',
            waveColor: '#3B82F6',
            progressColor: '#1D4ED8',
            cursorColor: '#EF4444',
            barWidth: 2,
            barRadius: 3,
            responsive: true,
            height: 60,
            normalize: true
        });
        
        wavesurferRef.current.load(audioUrl);
        
        // Sync with transcript
        wavesurferRef.current.on('audioprocess', () => {
            const time = wavesurferRef.current.getCurrentTime();
            highlightCurrentSegment(time);
        });
        
        return () => wavesurferRef.current.destroy();
    }, [audioUrl]);
    
    const highlightCurrentSegment = (time) => {
        const currentSegment = transcript.segments.find(
            segment => segment.timestamp <= time && 
                       segment.timestamp + segment.duration > time
        );
        
        if (currentSegment) {
            scrollToSegment(currentSegment.id);
        }
    };
    
    return (
        <div className="audio-player">
            <div id="waveform"></div>
            <div className="controls">
                <button onClick={togglePlayPause}>
                    {isPlaying ? <Pause /> : <Play />}
                </button>
                <span className="time">
                    {formatTime(currentTime)} / {formatTime(duration)}
                </span>
                <button onClick={() => skip(-10)}>-10s</button>
                <button onClick={() => skip(10)}>+10s</button>
                <select onChange={e => setPlaybackRate(e.target.value)}>
                    <option value="0.5">0.5x</option>
                    <option value="1" selected>1x</option>
                    <option value="1.5">1.5x</option>
                    <option value="2">2x</option>
                </select>
            </div>
        </div>
    );
};
```

### 3. Smart Filtering System
```javascript
// Advanced filter management
const useCallFilters = () => {
    const [filters, setFilters] = useState({
        dateRange: { start: null, end: null },
        status: 'all',
        search: '',
        tags: [],
        hasAppointment: null,
        duration: { min: null, max: null },
        sentiment: null,
        customFields: {}
    });
    
    // Debounced search
    const debouncedSearch = useMemo(
        () => debounce(value => {
            setFilters(prev => ({ ...prev, search: value }));
        }, 300),
        []
    );
    
    // Filter presets
    const filterPresets = {
        today: {
            dateRange: {
                start: dayjs().startOf('day'),
                end: dayjs().endOf('day')
            }
        },
        missedCalls: {
            status: 'missed',
            hasAppointment: false
        },
        vipCustomers: {
            tags: ['VIP'],
            duration: { min: 120 }
        },
        needsFollowUp: {
            customFields: {
                followUpRequired: true,
                followUpCompleted: false
            }
        }
    };
    
    // Smart suggestions based on usage
    const getFilterSuggestions = () => {
        const suggestions = [];
        
        if (filters.status === 'missed') {
            suggestions.push({
                text: 'Show only calls without appointments',
                action: () => setFilters(prev => ({ 
                    ...prev, 
                    hasAppointment: false 
                }))
            });
        }
        
        return suggestions;
    };
    
    return {
        filters,
        setFilters,
        debouncedSearch,
        filterPresets,
        suggestions: getFilterSuggestions()
    };
};
```

### 4. Real-time Updates
```javascript
// WebSocket integration for live updates
const useCallsRealtime = (companyId) => {
    const [calls, setCalls] = useState([]);
    
    useEffect(() => {
        // Subscribe to call events
        const channel = echo.private(`company.${companyId}`)
            .listen('CallStarted', (e) => {
                // Add new call to top of list
                setCalls(prev => [e.call, ...prev]);
                
                // Show notification
                showNotification({
                    title: 'New Incoming Call',
                    body: `From ${e.call.from_number}`,
                    icon: 'phone-incoming'
                });
            })
            .listen('CallEnded', (e) => {
                // Update call in list
                setCalls(prev => prev.map(call => 
                    call.id === e.call.id ? e.call : call
                ));
            })
            .listen('TranscriptReady', (e) => {
                // Update call with transcript
                setCalls(prev => prev.map(call => 
                    call.id === e.call_id 
                        ? { ...call, transcript: e.transcript }
                        : call
                ));
            });
        
        return () => {
            echo.leave(`company.${companyId}`);
        };
    }, [companyId]);
    
    return calls;
};
```

## Performance Optimizations

### 1. Virtual Scrolling for Large Lists
```javascript
import { VariableSizeList } from 'react-window';

const VirtualCallList = ({ calls }) => {
    const listRef = useRef();
    const rowHeights = useRef({});
    
    const getRowHeight = (index) => {
        return rowHeights.current[index] || 80;
    };
    
    const Row = ({ index, style }) => {
        const call = calls[index];
        const rowRef = useRef();
        
        useEffect(() => {
            if (rowRef.current) {
                const height = rowRef.current.getBoundingClientRect().height;
                if (rowHeights.current[index] !== height) {
                    rowHeights.current[index] = height;
                    listRef.current.resetAfterIndex(index);
                }
            }
        }, [call]);
        
        return (
            <div ref={rowRef} style={style}>
                <CallRow call={call} />
            </div>
        );
    };
    
    return (
        <VariableSizeList
            ref={listRef}
            height={600}
            itemCount={calls.length}
            itemSize={getRowHeight}
            width="100%"
        >
            {Row}
        </VariableSizeList>
    );
};
```

### 2. Optimistic Updates
```javascript
const useOptimisticCallUpdates = () => {
    const queryClient = useQueryClient();
    
    const updateCallStatus = useMutation(
        ({ callId, status }) => 
            axiosInstance.post(`/calls/${callId}/status`, { status }),
        {
            onMutate: async ({ callId, status }) => {
                // Cancel outgoing queries
                await queryClient.cancelQueries(['calls']);
                
                // Snapshot previous value
                const previousCalls = queryClient.getQueryData(['calls']);
                
                // Optimistically update
                queryClient.setQueryData(['calls'], old => ({
                    ...old,
                    data: old.data.map(call =>
                        call.id === callId 
                            ? { ...call, status }
                            : call
                    )
                }));
                
                return { previousCalls };
            },
            onError: (err, variables, context) => {
                // Rollback on error
                queryClient.setQueryData(['calls'], context.previousCalls);
            },
            onSettled: () => {
                queryClient.invalidateQueries(['calls']);
            }
        }
    );
    
    return { updateCallStatus };
};
```

### 3. Intelligent Caching
```javascript
// Cache strategy for call data
const callQueryOptions = {
    staleTime: 5 * 60 * 1000, // 5 minutes
    cacheTime: 10 * 60 * 1000, // 10 minutes
    refetchOnWindowFocus: false,
    refetchInterval: false,
    
    // Smart refetch based on activity
    refetchIntervalInBackground: (data) => {
        const hasRecentCalls = data?.some(call => 
            dayjs().diff(dayjs(call.created_at), 'minutes') < 5
        );
        
        return hasRecentCalls ? 30000 : false; // 30s if recent activity
    }
};
```

## Export Functionality

### 1. CSV Export
```javascript
const exportCallsToCSV = (calls, filters) => {
    const headers = [
        'Call ID',
        'Date',
        'Time',
        'From Number',
        'To Number',
        'Duration',
        'Status',
        'Customer Name',
        'Appointment Created',
        'Summary'
    ];
    
    const rows = calls.map(call => [
        call.id,
        dayjs(call.created_at).format('YYYY-MM-DD'),
        dayjs(call.created_at).format('HH:mm:ss'),
        call.from_number,
        call.to_number,
        formatDuration(call.duration),
        call.status,
        call.customer?.name || 'Unknown',
        call.appointment_created ? 'Yes' : 'No',
        call.summary?.replace(/[\n\r]/g, ' ') || ''
    ]);
    
    const csv = [
        headers.join(','),
        ...rows.map(row => row.map(cell => 
            `"${String(cell).replace(/"/g, '""')}"`
        ).join(','))
    ].join('\n');
    
    downloadFile(csv, `calls-export-${dayjs().format('YYYY-MM-DD')}.csv`, 'text/csv');
};
```

### 2. PDF Export with Formatting
```javascript
const exportCallToPDF = async (call) => {
    const doc = new jsPDF();
    
    // Header
    doc.setFontSize(20);
    doc.text('Call Summary', 20, 20);
    
    // Call details
    doc.setFontSize(12);
    doc.text(`Call ID: ${call.id}`, 20, 40);
    doc.text(`Date: ${dayjs(call.created_at).format('DD.MM.YYYY HH:mm')}`, 20, 50);
    doc.text(`Duration: ${formatDuration(call.duration)}`, 20, 60);
    doc.text(`Customer: ${call.customer?.name || 'Unknown'}`, 20, 70);
    
    // Transcript
    doc.setFontSize(14);
    doc.text('Transcript', 20, 90);
    doc.setFontSize(11);
    
    let yPosition = 100;
    call.transcript.segments.forEach(segment => {
        const lines = doc.splitTextToSize(
            `${segment.speaker.toUpperCase()}: ${segment.text}`,
            170
        );
        
        lines.forEach(line => {
            if (yPosition > 280) {
                doc.addPage();
                yPosition = 20;
            }
            doc.text(line, 20, yPosition);
            yPosition += 5;
        });
        yPosition += 5;
    });
    
    // Save
    doc.save(`call-${call.id}.pdf`);
};
```

## Mobile Optimization

### Touch-Optimized Interface
```javascript
const MobileCallList = ({ calls }) => {
    const [expandedCallId, setExpandedCallId] = useState(null);
    
    return (
        <div className="mobile-call-list">
            {calls.map(call => (
                <SwipeableCallCard
                    key={call.id}
                    call={call}
                    expanded={expandedCallId === call.id}
                    onExpand={() => setExpandedCallId(call.id)}
                    onSwipeLeft={() => showQuickActions(call)}
                    onSwipeRight={() => markAsReviewed(call)}
                />
            ))}
        </div>
    );
};

const SwipeableCallCard = ({ call, expanded, onExpand, onSwipeLeft, onSwipeRight }) => {
    const handlers = useSwipeable({
        onSwipedLeft: onSwipeLeft,
        onSwipedRight: onSwipeRight,
        trackMouse: false
    });
    
    return (
        <div {...handlers} className="call-card">
            <div className="call-header" onClick={onExpand}>
                <PhoneIcon type={call.direction} status={call.status} />
                <div className="call-info">
                    <div className="phone-number">{formatPhoneNumber(call.from_number)}</div>
                    <div className="call-meta">
                        {dayjs(call.created_at).format('HH:mm')} • 
                        {formatDuration(call.duration)}
                    </div>
                </div>
                <ChevronIcon direction={expanded ? 'up' : 'down'} />
            </div>
            
            {expanded && (
                <div className="call-details">
                    <div className="summary">{call.summary}</div>
                    <div className="actions">
                        <button onClick={() => playAudio(call)}>
                            <PlayIcon /> Play Recording
                        </button>
                        <button onClick={() => viewTranscript(call)}>
                            <DocumentIcon /> Transcript
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
};
```

## Testing Strategies

### Unit Tests
```javascript
describe('CallsModule', () => {
    describe('CallFilters', () => {
        it('applies date range filter correctly', () => {
            const { result } = renderHook(() => useCallFilters());
            
            act(() => {
                result.current.setFilters({
                    dateRange: {
                        start: '2025-01-01',
                        end: '2025-01-31'
                    }
                });
            });
            
            expect(result.current.filters.dateRange).toEqual({
                start: '2025-01-01',
                end: '2025-01-31'
            });
        });
    });
    
    describe('TranscriptViewer', () => {
        it('highlights keywords in transcript', () => {
            const transcript = {
                segments: [{
                    speaker: 'customer',
                    text: 'I want to book an appointment'
                }]
            };
            
            render(<TranscriptViewer transcript={transcript} />);
            
            expect(screen.getByText(/appointment/)).toHaveClass('highlighted');
        });
    });
});
```

### Integration Tests
```javascript
describe('Calls API Integration', () => {
    it('fetches and displays calls', async () => {
        const mockCalls = [
            { id: 1, from_number: '+49123456789', duration: 120 },
            { id: 2, from_number: '+49987654321', duration: 180 }
        ];
        
        server.use(
            rest.get('/business/api/calls', (req, res, ctx) => {
                return res(ctx.json({ data: mockCalls }));
            })
        );
        
        render(<CallsIndex />);
        
        await waitFor(() => {
            expect(screen.getByText('+49123456789')).toBeInTheDocument();
            expect(screen.getByText('+49987654321')).toBeInTheDocument();
        });
    });
});
```

## Common Issues & Solutions

### Issue: Slow transcript loading
**Solution**: Implement progressive loading
```javascript
const loadTranscriptProgressive = async (callId) => {
    // Load summary first
    const summary = await fetchCallSummary(callId);
    displaySummary(summary);
    
    // Load transcript in chunks
    const chunkSize = 50;
    let offset = 0;
    
    while (true) {
        const chunk = await fetchTranscriptChunk(callId, offset, chunkSize);
        if (chunk.segments.length === 0) break;
        
        appendTranscriptSegments(chunk.segments);
        offset += chunkSize;
    }
};
```

### Issue: Audio playback on mobile
**Solution**: Handle autoplay restrictions
```javascript
const initializeAudioPlayer = async () => {
    try {
        // Create silent audio context
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Resume on user interaction
        document.addEventListener('click', () => {
            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }
        }, { once: true });
        
        return audioContext;
    } catch (error) {
        console.error('Audio initialization failed:', error);
    }
};
```