import React, { useEffect } from 'react';
import { Outlet } from 'react-router-dom';
import MobileBottomNav from './MobileBottomNav';
import OfflineIndicator from './OfflineIndicator';
import { cn } from '@/lib/utils';

/**
 * Mobile Layout Wrapper
 * Handles mobile-specific layout concerns
 */
const MobileLayout = ({ children }) => {
  useEffect(() => {
    // Add mobile-specific meta tags
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
      viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover';
    }

    // Add mobile class to body
    document.body.classList.add('mobile-layout');

    // Prevent bounce scrolling on iOS
    document.body.style.overscrollBehavior = 'none';

    return () => {
      document.body.classList.remove('mobile-layout');
      document.body.style.overscrollBehavior = '';
    };
  }, []);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Offline Indicator */}
      <OfflineIndicator />
      
      {/* Main Content - Account for bottom nav */}
      <main className="pb-16 lg:pb-0">
        {children || <Outlet />}
      </main>
      
      {/* Mobile Bottom Navigation */}
      <MobileBottomNav />
    </div>
  );
};

/**
 * Mobile Header Component
 */
export const MobileHeader = ({ 
  title, 
  subtitle,
  onBack,
  actions = [],
  sticky = true,
  transparent = false,
  className = ''
}) => {
  return (
    <header className={cn(
      "bg-white border-b border-gray-200",
      sticky && "sticky top-0 z-40",
      transparent && "bg-transparent border-transparent",
      className
    )}>
      <div className="flex items-center justify-between px-4 h-14">
        {/* Back Button */}
        {onBack && (
          <button
            onClick={onBack}
            className="p-2 -ml-2 rounded-lg active:bg-gray-100 transition-colors"
            aria-label="Zurück"
          >
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
        )}
        
        {/* Title */}
        <div className={cn("flex-1", onBack && "ml-2", actions.length > 0 && "mr-2")}>
          <h1 className="text-lg font-semibold text-gray-900 truncate">
            {title}
          </h1>
          {subtitle && (
            <p className="text-xs text-gray-500 truncate">{subtitle}</p>
          )}
        </div>
        
        {/* Actions */}
        {actions.length > 0 && (
          <div className="flex items-center gap-1">
            {actions.map((action, index) => (
              <button
                key={index}
                onClick={action.onClick}
                className="p-2 rounded-lg active:bg-gray-100 transition-colors"
                aria-label={action.label}
              >
                <action.icon className="h-5 w-5 text-gray-600" />
              </button>
            ))}
          </div>
        )}
      </div>
    </header>
  );
};

/**
 * Mobile Content Container
 */
export const MobileContent = ({ children, className = '', noPadding = false }) => {
  return (
    <div className={cn(
      "flex-1",
      !noPadding && "p-4",
      className
    )}>
      {children}
    </div>
  );
};

/**
 * Mobile Card Component
 */
export const MobileCard = ({ 
  children, 
  onClick,
  className = '',
  interactive = false
}) => {
  return (
    <div
      onClick={onClick}
      className={cn(
        "bg-white rounded-lg shadow-sm",
        interactive && "active:bg-gray-50 active:scale-[0.98] transition-all cursor-pointer",
        className
      )}
    >
      {children}
    </div>
  );
};

/**
 * Mobile Section Component
 */
export const MobileSection = ({ 
  title, 
  children, 
  action,
  className = '' 
}) => {
  return (
    <section className={cn("mb-6", className)}>
      {title && (
        <div className="flex items-center justify-between mb-3 px-4">
          <h2 className="text-sm font-semibold text-gray-900 uppercase tracking-wider">
            {title}
          </h2>
          {action && (
            <button
              onClick={action.onClick}
              className="text-sm font-medium text-blue-600 active:text-blue-700"
            >
              {action.label}
            </button>
          )}
        </div>
      )}
      {children}
    </section>
  );
};

export default MobileLayout;