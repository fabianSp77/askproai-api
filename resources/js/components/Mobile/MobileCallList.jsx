import React, { useState, useCallback, useRef, useEffect } from 'react';
import { VariableSizeList as List } from 'react-window';
import { Phone, PhoneIncoming, PhoneOutgoing, Clock, ChevronRight, AlertCircle } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { de } from 'date-fns/locale';
import { cn } from '@/lib/utils';
import { useNavigate } from 'react-router-dom';

/**
 * Mobile-optimized Call List Item
 */
const CallItem = ({ call, style, onClick }) => {
  const [isPressed, setIsPressed] = useState(false);
  
  const getCallIcon = () => {
    if (call.direction === 'inbound') return PhoneIncoming;
    if (call.direction === 'outbound') return PhoneOutgoing;
    return Phone;
  };
  
  const Icon = getCallIcon();
  
  const urgencyColors = {
    urgent: 'bg-red-500',
    high: 'bg-orange-500',
    normal: 'bg-green-500',
    low: 'bg-gray-400'
  };

  const urgencyBgColors = {
    urgent: 'bg-red-50 border-red-200',
    high: 'bg-orange-50 border-orange-200',
    normal: 'bg-green-50 border-green-200',
    low: 'bg-gray-50 border-gray-200'
  };
  
  const handleClick = () => {
    // Haptic feedback
    if (window.navigator.vibrate) {
      window.navigator.vibrate(10);
    }
    onClick(call);
  };

  // Normalize urgency level
  const urgencyLevel = call.urgency_level?.toLowerCase() || 'normal';
  
  return (
    <div
      style={style}
      className="px-4 py-1"
      onTouchStart={() => setIsPressed(true)}
      onTouchEnd={() => setIsPressed(false)}
      onClick={handleClick}
    >
      <div className={cn(
        "bg-white rounded-lg shadow-sm p-4 transition-all duration-200",
        "active:bg-gray-50 active:scale-[0.98]",
        "cursor-pointer select-none",
        isPressed && "bg-gray-50 scale-[0.98]",
        urgencyLevel === 'urgent' && urgencyBgColors.urgent,
        urgencyLevel === 'high' && urgencyBgColors.high
      )}>
        <div className="flex items-start gap-3">
          {/* Call Icon with urgency indicator */}
          <div className="relative flex-shrink-0">
            <div className="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center">
              <Icon className="h-6 w-6 text-gray-600" />
            </div>
            {urgencyLevel && urgencyLevel !== 'normal' && (
              <div className={cn(
                "absolute -top-1 -right-1 h-3 w-3 rounded-full",
                urgencyColors[urgencyLevel] || urgencyColors.normal
              )} />
            )}
          </div>
          
          {/* Call Info */}
          <div className="flex-1 min-w-0">
            <div className="flex items-center justify-between mb-1">
              <h3 className="text-base font-semibold text-gray-900 truncate pr-2">
                {call.extracted_name || call.from_number || 'Unbekannt'}
              </h3>
              <ChevronRight className="h-5 w-5 text-gray-400 flex-shrink-0" />
            </div>
            
            {/* Customer Request Preview */}
            {call.customer_request && (
              <p className="text-sm text-gray-600 line-clamp-2 mb-2">
                {call.customer_request}
              </p>
            )}
            
            {/* Metadata */}
            <div className="flex items-center gap-3 text-xs text-gray-500">
              <span className="flex items-center gap-1">
                <Clock className="h-3 w-3" />
                {formatDistanceToNow(new Date(call.created_at), { 
                  addSuffix: true, 
                  locale: de 
                })}
              </span>
              <span className="font-medium">
                {call.duration_formatted || formatDuration(call.duration_sec)}
              </span>
              {call.appointment_requested && (
                <span className="bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-medium">
                  Termin
                </span>
              )}
              {urgencyLevel === 'urgent' && (
                <span className="flex items-center gap-1 text-red-600 font-medium">
                  <AlertCircle className="h-3 w-3" />
                  Dringend
                </span>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

/**
 * Pull to Refresh Component
 */
const PullToRefresh = ({ onRefresh, children }) => {
  const [pullDistance, setPullDistance] = useState(0);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const startY = useRef(0);
  const element = useRef(null);

  const handleTouchStart = (e) => {
    if (element.current.scrollTop === 0) {
      startY.current = e.touches[0].clientY;
    }
  };

  const handleTouchMove = (e) => {
    if (element.current.scrollTop === 0 && !isRefreshing) {
      const currentY = e.touches[0].clientY;
      const distance = Math.max(0, currentY - startY.current);
      setPullDistance(Math.min(distance * 0.5, 80)); // Max 80px pull
    }
  };

  const handleTouchEnd = async () => {
    if (pullDistance > 60 && !isRefreshing) {
      setIsRefreshing(true);
      await onRefresh();
      setIsRefreshing(false);
    }
    setPullDistance(0);
  };

  return (
    <div
      ref={element}
      className="relative h-full overflow-auto"
      onTouchStart={handleTouchStart}
      onTouchMove={handleTouchMove}
      onTouchEnd={handleTouchEnd}
    >
      {/* Pull indicator */}
      <div 
        className={cn(
          "absolute top-0 left-0 right-0 flex justify-center items-center transition-all duration-300",
          pullDistance > 0 ? "opacity-100" : "opacity-0"
        )}
        style={{ 
          height: `${pullDistance}px`,
          transform: `translateY(${pullDistance - 40}px)`
        }}
      >
        <div className={cn(
          "rounded-full bg-white shadow-lg p-2 transition-transform",
          isRefreshing && "animate-spin"
        )}>
          <svg className="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </div>
      </div>
      
      {/* Content */}
      <div style={{ transform: `translateY(${pullDistance}px)` }}>
        {children}
      </div>
    </div>
  );
};

/**
 * Mobile Call List Component
 */
const MobileCallList = ({ calls, onRefresh, loading = false }) => {
  const navigate = useNavigate();
  const listRef = useRef();
  const [dimensions, setDimensions] = useState({ width: 0, height: 0 });

  useEffect(() => {
    const updateDimensions = () => {
      setDimensions({
        width: window.innerWidth,
        height: window.innerHeight - 120 // Account for header + bottom nav
      });
    };

    updateDimensions();
    window.addEventListener('resize', updateDimensions);
    return () => window.removeEventListener('resize', updateDimensions);
  }, []);

  const handleCallClick = useCallback((call) => {
    navigate(`/business/calls/${call.id}/v2`);
  }, [navigate]);

  const getItemSize = useCallback((index) => {
    const call = calls[index];
    // Base height + extra height for customer request
    const baseHeight = 88;
    const hasRequest = call.customer_request ? 40 : 0;
    return baseHeight + hasRequest;
  }, [calls]);

  if (loading && calls.length === 0) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-500">Lade Anrufe...</p>
        </div>
      </div>
    );
  }

  if (calls.length === 0) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="text-center py-8 px-4">
          <Phone className="h-16 w-16 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">Keine Anrufe</h3>
          <p className="text-gray-500">Es wurden noch keine Anrufe aufgezeichnet.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="h-full bg-gray-50">
      <PullToRefresh onRefresh={onRefresh}>
        <List
          ref={listRef}
          height={dimensions.height}
          itemCount={calls.length}
          itemSize={getItemSize}
          width="100%"
          overscanCount={3}
          className="scrollbar-hide"
        >
          {({ index, style }) => (
            <CallItem 
              call={calls[index]} 
              style={style} 
              onClick={handleCallClick}
            />
          )}
        </List>
      </PullToRefresh>
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

export default MobileCallList;