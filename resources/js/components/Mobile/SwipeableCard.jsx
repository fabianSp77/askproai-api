import React, { useState, useRef } from 'react';
import { cn } from '@/lib/utils';
import { Trash2, Edit, Phone, Mail } from 'lucide-react';

/**
 * Swipeable Card Component
 * Supports swipe gestures for quick actions
 */
export const SwipeableCard = ({ 
  children, 
  onDelete,
  onEdit,
  onCall,
  onEmail,
  className = '',
  threshold = 80,
  maxSwipe = 240
}) => {
  const [swipeX, setSwipeX] = useState(0);
  const [isSwipping, setIsSwipping] = useState(false);
  const startX = useRef(0);
  const cardRef = useRef(null);
  
  const handleTouchStart = (e) => {
    startX.current = e.touches[0].clientX;
    setIsSwipping(true);
  };
  
  const handleTouchMove = (e) => {
    if (!isSwipping) return;
    
    const currentX = e.touches[0].clientX;
    const deltaX = currentX - startX.current;
    
    // Limit swipe distance
    const limitedDelta = Math.max(-maxSwipe, Math.min(maxSwipe, deltaX));
    setSwipeX(limitedDelta);
  };
  
  const handleTouchEnd = () => {
    setIsSwipping(false);
    
    // Snap to action or reset
    if (Math.abs(swipeX) > threshold) {
      // Snap to full swipe
      const snapTo = swipeX > 0 ? maxSwipe : -maxSwipe;
      setSwipeX(snapTo);
      
      // Haptic feedback
      if (window.navigator.vibrate) {
        window.navigator.vibrate(20);
      }
    } else {
      // Reset position
      setSwipeX(0);
    }
  };

  const handleActionClick = (action) => {
    // Haptic feedback
    if (window.navigator.vibrate) {
      window.navigator.vibrate(10);
    }
    
    // Reset swipe
    setSwipeX(0);
    
    // Execute action
    if (action) action();
  };

  // Calculate opacity based on swipe distance
  const actionOpacity = Math.min(1, Math.abs(swipeX) / threshold);
  
  return (
    <div className={cn("relative overflow-hidden", className)}>
      {/* Left Actions (revealed on right swipe) */}
      {swipeX > 0 && (
        <div 
          className="absolute inset-y-0 left-0 flex"
          style={{ opacity: actionOpacity }}
        >
          {onCall && (
            <button
              onClick={() => handleActionClick(onCall)}
              className="bg-green-500 text-white px-4 flex items-center justify-center"
              style={{ width: `${maxSwipe / 2}px` }}
            >
              <Phone className="h-5 w-5" />
            </button>
          )}
          {onEmail && (
            <button
              onClick={() => handleActionClick(onEmail)}
              className="bg-blue-500 text-white px-4 flex items-center justify-center"
              style={{ width: `${maxSwipe / 2}px` }}
            >
              <Mail className="h-5 w-5" />
            </button>
          )}
        </div>
      )}
      
      {/* Right Actions (revealed on left swipe) */}
      {swipeX < 0 && (
        <div 
          className="absolute inset-y-0 right-0 flex"
          style={{ opacity: actionOpacity }}
        >
          {onEdit && (
            <button
              onClick={() => handleActionClick(onEdit)}
              className="bg-blue-500 text-white px-4 flex items-center justify-center"
              style={{ width: `${maxSwipe / 2}px` }}
            >
              <Edit className="h-5 w-5" />
            </button>
          )}
          {onDelete && (
            <button
              onClick={() => handleActionClick(onDelete)}
              className="bg-red-500 text-white px-4 flex items-center justify-center"
              style={{ width: `${maxSwipe / 2}px` }}
            >
              <Trash2 className="h-5 w-5" />
            </button>
          )}
        </div>
      )}
      
      {/* Main Content */}
      <div
        ref={cardRef}
        className="relative bg-white transition-transform duration-200 ease-out"
        style={{
          transform: `translateX(${swipeX}px)`,
          transition: isSwipping ? 'none' : 'transform 0.2s ease-out'
        }}
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
      >
        {children}
      </div>
    </div>
  );
};

/**
 * Swipe Action Indicator
 * Shows visual feedback during swipe
 */
export const SwipeIndicator = ({ direction, visible, children }) => {
  return (
    <div className={cn(
      "absolute inset-y-0 flex items-center px-4 transition-opacity",
      direction === 'left' ? 'left-0' : 'right-0',
      visible ? 'opacity-100' : 'opacity-0'
    )}>
      {children}
    </div>
  );
};