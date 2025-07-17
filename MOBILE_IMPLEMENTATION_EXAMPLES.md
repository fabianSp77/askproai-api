# 📱 Mobile Implementation Examples - Business Portal

## 1. Mobile-Optimized Call List

```jsx
// components/Mobile/MobileCallList.jsx
import React, { useState, useCallback } from 'react';
import { VariableSizeList } from 'react-window';
import { Phone, PhoneIncoming, PhoneOutgoing, Clock, ChevronRight } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { de } from 'date-fns/locale';

const MobileCallList = ({ calls, onCallClick }) => {
  const [refreshing, setRefreshing] = useState(false);
  
  // Touch-optimized call item
  const CallItem = ({ call, style }) => {
    const getCallIcon = () => {
      if (call.direction === 'inbound') return PhoneIncoming;
      if (call.direction === 'outbound') return PhoneOutgoing;
      return Phone;
    };
    
    const Icon = getCallIcon();
    const urgencyColors = {
      high: 'bg-red-500',
      medium: 'bg-yellow-500',
      low: 'bg-green-500'
    };
    
    return (
      <div
        style={style}
        className="px-4 py-1"
        onClick={() => onCallClick(call)}
      >
        <div className="bg-white rounded-lg shadow-sm p-4 active:bg-gray-50 transition-colors">
          <div className="flex items-start gap-3">
            {/* Call Icon with urgency indicator */}
            <div className="relative flex-shrink-0">
              <div className="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center">
                <Icon className="h-6 w-6 text-gray-600" />
              </div>
              {call.urgency_level && (
                <div className={`absolute -top-1 -right-1 h-3 w-3 rounded-full ${urgencyColors[call.urgency_level]}`} />
              )}
            </div>
            
            {/* Call Info */}
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between">
                <h3 className="text-base font-semibold text-gray-900 truncate">
                  {call.extracted_name || call.from_number}
                </h3>
                <ChevronRight className="h-5 w-5 text-gray-400 flex-shrink-0" />
              </div>
              
              {/* Customer Request Preview */}
              {call.customer_request && (
                <p className="text-sm text-gray-600 mt-1 line-clamp-2">
                  {call.customer_request}
                </p>
              )}
              
              {/* Metadata */}
              <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
                <span className="flex items-center gap-1">
                  <Clock className="h-3 w-3" />
                  {formatDistanceToNow(new Date(call.created_at), { 
                    addSuffix: true, 
                    locale: de 
                  })}
                </span>
                <span>{call.duration_formatted || '0:00'}</span>
                {call.appointment_requested && (
                  <span className="bg-blue-100 text-blue-700 px-2 py-0.5 rounded">
                    Termin
                  </span>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  };
  
  // Virtual scrolling for performance
  const getItemSize = () => {
    // Dynamic height based on content
    return 120; // Base height for mobile
  };
  
  return (
    <div className="h-full bg-gray-50">
      {/* Pull to refresh */}
      <PullToRefresh onRefresh={handleRefresh}>
        <VariableSizeList
          height={window.innerHeight - 120} // Account for header + bottom nav
          itemCount={calls.length}
          itemSize={getItemSize}
          width="100%"
          overscanCount={3}
        >
          {({ index, style }) => (
            <CallItem call={calls[index]} style={style} />
          )}
        </VariableSizeList>
      </PullToRefresh>
    </div>
  );
};
```

## 2. Mobile Call Detail Page

```jsx
// pages/Mobile/MobileCallDetail.jsx
import React, { useState } from 'react';
import { 
  ChevronLeft, MoreVertical, Phone, Mail, MessageSquare, 
  Calendar, Clock, User, Building, FileText, Headphones 
} from 'lucide-react';
import { Tab } from '@headlessui/react';

const MobileCallDetail = ({ call }) => {
  const [showActions, setShowActions] = useState(false);
  
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="sticky top-0 z-50 bg-white border-b">
        <div className="flex items-center justify-between p-4">
          <button 
            onClick={() => window.history.back()} 
            className="p-2 -ml-2 rounded-lg active:bg-gray-100"
          >
            <ChevronLeft className="h-5 w-5" />
          </button>
          
          <h1 className="flex-1 text-center text-lg font-semibold truncate px-2">
            {call.extracted_name || 'Unbekannt'}
          </h1>
          
          <button 
            onClick={() => setShowActions(true)}
            className="p-2 -mr-2 rounded-lg active:bg-gray-100"
          >
            <MoreVertical className="h-5 w-5" />
          </button>
        </div>
      </header>
      
      {/* Quick Actions */}
      <div className="bg-white border-b px-4 py-3">
        <div className="flex gap-2">
          <button className="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg flex items-center justify-center gap-2 active:bg-blue-700">
            <Phone className="h-5 w-5" />
            <span className="font-medium">Zurückrufen</span>
          </button>
          <button className="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg flex items-center justify-center gap-2 active:bg-gray-200">
            <Mail className="h-5 w-5" />
            <span className="font-medium">Email</span>
          </button>
        </div>
      </div>
      
      {/* Tab Navigation */}
      <Tab.Group>
        <Tab.List className="bg-white border-b sticky top-14 z-40">
          <div className="flex">
            {['Übersicht', 'Transkript', 'Verlauf'].map((tab) => (
              <Tab
                key={tab}
                className={({ selected }) =>
                  `flex-1 py-3 text-sm font-medium transition-colors outline-none
                  ${selected 
                    ? 'text-blue-600 border-b-2 border-blue-600' 
                    : 'text-gray-600 border-b-2 border-transparent'
                  }`
                }
              >
                {tab}
              </Tab>
            ))}
          </div>
        </Tab.List>
        
        <Tab.Panels>
          {/* Overview Tab */}
          <Tab.Panel>
            <div className="p-4 space-y-4">
              {/* Customer Request Card */}
              {call.customer_request && (
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <h3 className="text-sm font-medium text-blue-900 mb-2">
                    Kundenanliegen
                  </h3>
                  <p className="text-sm text-blue-800">
                    {call.customer_request}
                  </p>
                </div>
              )}
              
              {/* Contact Info */}
              <div className="bg-white rounded-lg shadow-sm p-4">
                <h3 className="text-base font-semibold text-gray-900 mb-3">
                  Kontaktinformationen
                </h3>
                <div className="space-y-3">
                  <div className="flex items-center gap-3">
                    <User className="h-5 w-5 text-gray-400" />
                    <div>
                      <p className="text-sm font-medium text-gray-900">
                        {call.extracted_name || 'Unbekannt'}
                      </p>
                      <p className="text-xs text-gray-500">Name</p>
                    </div>
                  </div>
                  
                  <div className="flex items-center gap-3">
                    <Phone className="h-5 w-5 text-gray-400" />
                    <div>
                      <p className="text-sm font-medium text-gray-900">
                        {call.from_number}
                      </p>
                      <p className="text-xs text-gray-500">Telefon</p>
                    </div>
                  </div>
                  
                  {call.extracted_email && (
                    <div className="flex items-center gap-3">
                      <Mail className="h-5 w-5 text-gray-400" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">
                          {call.extracted_email}
                        </p>
                        <p className="text-xs text-gray-500">Email</p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
              
              {/* Call Summary */}
              {call.summary && (
                <div className="bg-white rounded-lg shadow-sm p-4">
                  <h3 className="text-base font-semibold text-gray-900 mb-3">
                    Zusammenfassung
                  </h3>
                  <p className="text-sm text-gray-700 leading-relaxed">
                    {call.summary}
                  </p>
                </div>
              )}
              
              {/* Audio Player */}
              {call.recording_url && (
                <div className="bg-white rounded-lg shadow-sm p-4">
                  <h3 className="text-base font-semibold text-gray-900 mb-3">
                    Aufzeichnung
                  </h3>
                  <MobileAudioPlayer url={call.recording_url} />
                </div>
              )}
            </div>
          </Tab.Panel>
          
          {/* Transcript Tab */}
          <Tab.Panel>
            <div className="p-4">
              <TranscriptView transcript={call.transcript} />
            </div>
          </Tab.Panel>
          
          {/* Timeline Tab */}
          <Tab.Panel>
            <div className="p-4">
              <Timeline events={call.timeline} />
            </div>
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
      
      {/* Floating Action Button */}
      <div className="fixed bottom-20 right-4">
        <button className="h-14 w-14 bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center active:scale-95 transition-transform">
          <MessageSquare className="h-6 w-6" />
        </button>
      </div>
    </div>
  );
};
```

## 3. Mobile-First Email Template

```html
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Anrufzusammenfassung</title>
    <style>
        /* Reset styles */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; }
        
        /* Base styles */
        body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: #1f2937;
            background-color: #f3f4f6;
        }
        
        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        /* Mobile styles */
        @media screen and (max-width: 600px) {
            /* Full width on mobile */
            .email-container {
                width: 100% !important;
            }
            
            /* Stack columns */
            .stack-column {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            
            /* Full width buttons */
            .button-td {
                width: 100% !important;
            }
            
            .button-a {
                display: block !important;
                width: 100% !important;
                padding: 16px !important;
                font-size: 16px !important;
            }
            
            /* Better spacing */
            .mobile-padding {
                padding: 20px 15px !important;
            }
            
            /* Hide on mobile */
            .hide-mobile {
                display: none !important;
            }
            
            /* Center text on mobile */
            .center-mobile {
                text-align: center !important;
            }
            
            /* Larger text for readability */
            .text-mobile-lg {
                font-size: 18px !important;
            }
            
            /* Stack metadata */
            .metadata-table td {
                display: block !important;
                width: 100% !important;
                text-align: center !important;
                padding: 10px 0 !important;
            }
            
            .metadata-separator {
                display: none !important;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #111827 !important;
            }
            
            .email-container {
                background-color: #1f2937 !important;
                color: #f9fafb !important;
            }
            
            .bg-light {
                background-color: #374151 !important;
            }
            
            .text-dark {
                color: #f9fafb !important;
            }
            
            .border-light {
                border-color: #4b5563 !important;
            }
        }
    </style>
</head>
<body>
    <div role="article" aria-label="Anrufzusammenfassung" lang="de">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td>
                    <div class="email-container">
                        <!-- Header -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td class="mobile-padding" style="padding: 30px; background-color: #1e40af; background-image: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; text-align: center;">
                                        {{ $company->name }}
                                    </h1>
                                    <p style="margin: 10px 0 0 0; color: #dbeafe; font-size: 14px; text-align: center;">
                                        Anrufzusammenfassung vom {{ $call->created_at->format('d.m.Y') }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Quick Actions -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td class="mobile-padding" style="padding: 20px 30px; background-color: #f3f4f6;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td class="button-td" style="padding: 0 5px 10px 0;">
                                                <a href="{{ $detailUrl }}" class="button-a" style="background: #3b82f6; color: #ffffff; font-family: sans-serif; font-size: 15px; font-weight: 600; text-decoration: none; padding: 13px 20px; border-radius: 8px; display: inline-block; text-align: center;">
                                                    Anruf Details →
                                                </a>
                                            </td>
                                            <td class="button-td" style="padding: 0 0 10px 5px;">
                                                <a href="{{ $audioUrl }}" class="button-a" style="background: #10b981; color: #ffffff; font-family: sans-serif; font-size: 15px; font-weight: 600; text-decoration: none; padding: 13px 20px; border-radius: 8px; display: inline-block; text-align: center;">
                                                    Audio anhören →
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Main Content -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td class="mobile-padding" style="padding: 30px;">
                                    <!-- Customer Request -->
                                    @if($customerRequest)
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
                                        <tr>
                                            <td style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 0 8px 8px 0;">
                                                <h2 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px; font-weight: 600;">
                                                    Kundenanliegen
                                                </h2>
                                                <p style="margin: 0; color: #1e293b; font-size: 15px; line-height: 1.6;">
                                                    {{ $customerRequest }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    @endif
                                    
                                    <!-- Contact Info -->
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
                                        <tr>
                                            <td>
                                                <h2 style="margin: 0 0 15px 0; color: #111827; font-size: 18px; font-weight: 600;">
                                                    Kontaktinformationen
                                                </h2>
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td style="padding-bottom: 10px;">
                                                            <strong style="color: #6b7280; font-size: 14px;">Name:</strong><br>
                                                            <span style="color: #111827; font-size: 16px;">{{ $customerName }}</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding-bottom: 10px;">
                                                            <strong style="color: #6b7280; font-size: 14px;">Telefon:</strong><br>
                                                            <a href="tel:{{ $phoneNumber }}" style="color: #3b82f6; font-size: 16px; text-decoration: none;">{{ $phoneNumber }}</a>
                                                        </td>
                                                    </tr>
                                                    @if($email)
                                                    <tr>
                                                        <td style="padding-bottom: 10px;">
                                                            <strong style="color: #6b7280; font-size: 14px;">E-Mail:</strong><br>
                                                            <a href="mailto:{{ $email }}" style="color: #3b82f6; font-size: 16px; text-decoration: none;">{{ $email }}</a>
                                                        </td>
                                                    </tr>
                                                    @endif
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Footer -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td class="mobile-padding center-mobile" style="padding: 30px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
                                    <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">
                                        Diese E-Mail wurde automatisch von AskProAI generiert.
                                    </p>
                                    <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                        <a href="https://askproai.de" style="color: #3b82f6; text-decoration: none;">askproai.de</a> • 
                                        <a href="https://askproai.de/datenschutz" style="color: #3b82f6; text-decoration: none;">Datenschutz</a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
```

## 4. Touch-Optimized Components

```jsx
// components/Touch/TouchOptimizedButton.jsx
const TouchButton = ({ 
  children, 
  onClick, 
  variant = 'primary',
  size = 'md',
  fullWidth = false,
  icon: Icon,
  loading = false,
  disabled = false,
  className = ''
}) => {
  const [isPressed, setIsPressed] = useState(false);
  
  const handleTouchStart = () => {
    setIsPressed(true);
    // Haptic feedback on supported devices
    if (window.navigator.vibrate) {
      window.navigator.vibrate(10);
    }
  };
  
  const handleTouchEnd = () => {
    setIsPressed(false);
  };
  
  const variants = {
    primary: 'bg-blue-600 text-white active:bg-blue-700',
    secondary: 'bg-gray-100 text-gray-700 active:bg-gray-200',
    danger: 'bg-red-600 text-white active:bg-red-700',
  };
  
  const sizes = {
    sm: 'py-2 px-4 text-sm',
    md: 'py-3 px-6 text-base',
    lg: 'py-4 px-8 text-lg',
  };
  
  return (
    <button
      onClick={onClick}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
      disabled={disabled || loading}
      className={cn(
        'rounded-lg font-medium transition-all duration-150',
        'min-h-[44px] min-w-[44px]', // Touch target minimum
        'flex items-center justify-center gap-2',
        'select-none', // Prevent text selection
        'outline-none focus:ring-2 focus:ring-offset-2',
        variants[variant],
        sizes[size],
        fullWidth && 'w-full',
        isPressed && 'scale-95',
        (disabled || loading) && 'opacity-50 cursor-not-allowed',
        className
      )}
    >
      {loading ? (
        <Loader2 className="h-5 w-5 animate-spin" />
      ) : (
        <>
          {Icon && <Icon className="h-5 w-5" />}
          {children}
        </>
      )}
    </button>
  );
};

// Swipeable Card Component
const SwipeableCard = ({ children, onSwipeLeft, onSwipeRight }) => {
  const [position, setPosition] = useState({ x: 0, y: 0 });
  const [startPosition, setStartPosition] = useState({ x: 0, y: 0 });
  const [isDragging, setIsDragging] = useState(false);
  
  const handleTouchStart = (e) => {
    const touch = e.touches[0];
    setStartPosition({ x: touch.clientX, y: touch.clientY });
    setIsDragging(true);
  };
  
  const handleTouchMove = (e) => {
    if (!isDragging) return;
    
    const touch = e.touches[0];
    const deltaX = touch.clientX - startPosition.x;
    
    setPosition({ x: deltaX, y: 0 });
  };
  
  const handleTouchEnd = () => {
    const threshold = 100; // Swipe threshold in pixels
    
    if (position.x > threshold && onSwipeRight) {
      onSwipeRight();
    } else if (position.x < -threshold && onSwipeLeft) {
      onSwipeLeft();
    }
    
    // Reset position with animation
    setPosition({ x: 0, y: 0 });
    setIsDragging(false);
  };
  
  return (
    <div
      className="relative touch-none"
      onTouchStart={handleTouchStart}
      onTouchMove={handleTouchMove}
      onTouchEnd={handleTouchEnd}
    >
      <div
        className="transition-transform duration-200"
        style={{
          transform: `translateX(${position.x}px)`,
          transition: isDragging ? 'none' : 'transform 0.2s ease-out'
        }}
      >
        {children}
      </div>
      
      {/* Swipe indicators */}
      {position.x > 50 && (
        <div className="absolute inset-y-0 left-0 w-16 bg-gradient-to-r from-green-500 to-transparent opacity-50" />
      )}
      {position.x < -50 && (
        <div className="absolute inset-y-0 right-0 w-16 bg-gradient-to-l from-red-500 to-transparent opacity-50" />
      )}
    </div>
  );
};
```

## 5. Performance Optimizations

```jsx
// hooks/useIntersectionObserver.js
const useIntersectionObserver = (options = {}) => {
  const [isIntersecting, setIsIntersecting] = useState(false);
  const targetRef = useRef(null);
  
  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => setIsIntersecting(entry.isIntersecting),
      options
    );
    
    if (targetRef.current) {
      observer.observe(targetRef.current);
    }
    
    return () => observer.disconnect();
  }, [options]);
  
  return [targetRef, isIntersecting];
};

// Lazy Image Component
const LazyImage = ({ src, alt, className, placeholder }) => {
  const [imgRef, isVisible] = useIntersectionObserver({ threshold: 0.1 });
  const [isLoaded, setIsLoaded] = useState(false);
  
  return (
    <div ref={imgRef} className={cn('relative', className)}>
      {/* Placeholder */}
      {(!isVisible || !isLoaded) && (
        <div className="absolute inset-0 bg-gray-200 animate-pulse" />
      )}
      
      {/* Actual image */}
      {isVisible && (
        <img
          src={src}
          alt={alt}
          onLoad={() => setIsLoaded(true)}
          className={cn(
            'transition-opacity duration-300',
            isLoaded ? 'opacity-100' : 'opacity-0'
          )}
        />
      )}
    </div>
  );
};

// Virtualized List with Dynamic Heights
const VirtualizedDynamicList = ({ items, renderItem, estimatedItemHeight = 100 }) => {
  const listRef = useRef();
  const rowHeights = useRef({});
  
  const getItemSize = useCallback((index) => {
    return rowHeights.current[index] || estimatedItemHeight;
  }, [estimatedItemHeight]);
  
  const handleItemSize = useCallback((index, size) => {
    rowHeights.current[index] = size;
    if (listRef.current) {
      listRef.current.resetAfterIndex(index);
    }
  }, []);
  
  const Row = ({ index, style }) => {
    const rowRef = useRef();
    
    useEffect(() => {
      if (rowRef.current) {
        const height = rowRef.current.getBoundingClientRect().height;
        if (height !== getItemSize(index)) {
          handleItemSize(index, height);
        }
      }
    }, [index]);
    
    return (
      <div style={style}>
        <div ref={rowRef}>
          {renderItem(items[index], index)}
        </div>
      </div>
    );
  };
  
  return (
    <VariableSizeList
      ref={listRef}
      height={window.innerHeight}
      itemCount={items.length}
      itemSize={getItemSize}
      width="100%"
      overscanCount={3}
    >
      {Row}
    </VariableSizeList>
  );
};
```

Diese Implementierungsbeispiele zeigen moderne, performance-optimierte Mobile-Patterns für das Business Portal mit Fokus auf Touch-Interaktionen, Geschwindigkeit und Benutzerfreundlichkeit.