import React, { useState } from 'react';
import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-react';

/**
 * Touch-optimized Button Component
 * Minimum 44px touch target with haptic feedback
 */
export const TouchButton = ({ 
  children, 
  onClick, 
  variant = 'primary',
  size = 'md',
  fullWidth = false,
  icon: Icon,
  iconPosition = 'left',
  loading = false,
  disabled = false,
  className = '',
  haptic = true,
  ...props
}) => {
  const [isPressed, setIsPressed] = useState(false);
  
  const handleTouchStart = () => {
    setIsPressed(true);
    // Haptic feedback on supported devices
    if (haptic && window.navigator.vibrate) {
      window.navigator.vibrate(10);
    }
  };
  
  const handleTouchEnd = () => {
    setIsPressed(false);
  };

  const handleClick = (e) => {
    if (!disabled && !loading && onClick) {
      onClick(e);
    }
  };
  
  const variants = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 focus-visible:ring-blue-600',
    secondary: 'bg-gray-100 text-gray-700 hover:bg-gray-200 active:bg-gray-300 focus-visible:ring-gray-500',
    danger: 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus-visible:ring-red-600',
    success: 'bg-green-600 text-white hover:bg-green-700 active:bg-green-800 focus-visible:ring-green-600',
    ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 active:bg-gray-200 focus-visible:ring-gray-500',
    outline: 'bg-transparent border-2 border-gray-300 text-gray-700 hover:bg-gray-50 active:bg-gray-100 focus-visible:ring-gray-500'
  };
  
  const sizes = {
    sm: 'py-2 px-4 text-sm min-h-[44px]',
    md: 'py-3 px-6 text-base min-h-[44px]',
    lg: 'py-4 px-8 text-lg min-h-[52px]',
    xl: 'py-5 px-10 text-xl min-h-[60px]'
  };
  
  return (
    <button
      onClick={handleClick}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
      onMouseDown={handleTouchStart}
      onMouseUp={handleTouchEnd}
      disabled={disabled || loading}
      className={cn(
        // Base styles
        'rounded-lg font-medium transition-all duration-150',
        'min-w-[44px]', // Touch target minimum
        'flex items-center justify-center gap-2',
        'select-none cursor-pointer',
        'outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        
        // Variant styles
        variants[variant],
        
        // Size styles
        sizes[size],
        
        // Width
        fullWidth && 'w-full',
        
        // States
        isPressed && 'scale-95 shadow-inner',
        (disabled || loading) && 'opacity-50 cursor-not-allowed',
        
        // Custom className
        className
      )}
      {...props}
    >
      {loading ? (
        <>
          <Loader2 className="h-5 w-5 animate-spin" />
          {children && <span>Lädt...</span>}
        </>
      ) : (
        <>
          {Icon && iconPosition === 'left' && <Icon className="h-5 w-5 -ml-1" />}
          {children}
          {Icon && iconPosition === 'right' && <Icon className="h-5 w-5 -mr-1" />}
        </>
      )}
    </button>
  );
};

/**
 * Floating Action Button (FAB)
 * Material Design inspired floating button
 */
export const FloatingActionButton = ({
  icon: Icon,
  onClick,
  position = 'bottom-right',
  size = 'md',
  variant = 'primary',
  className = '',
  children,
  extended = false,
  ...props
}) => {
  const [isPressed, setIsPressed] = useState(false);
  
  const positions = {
    'bottom-right': 'bottom-20 right-4',
    'bottom-left': 'bottom-20 left-4',
    'bottom-center': 'bottom-20 left-1/2 -translate-x-1/2',
    'top-right': 'top-20 right-4',
    'top-left': 'top-20 left-4'
  };
  
  const sizes = {
    sm: extended ? 'h-10 px-4' : 'h-10 w-10',
    md: extended ? 'h-14 px-6' : 'h-14 w-14',
    lg: extended ? 'h-16 px-8' : 'h-16 w-16'
  };

  const iconSizes = {
    sm: 'h-5 w-5',
    md: 'h-6 w-6',
    lg: 'h-7 w-7'
  };
  
  return (
    <button
      onClick={onClick}
      onTouchStart={() => setIsPressed(true)}
      onTouchEnd={() => setIsPressed(false)}
      className={cn(
        'fixed z-50',
        positions[position],
        sizes[size],
        'bg-blue-600 text-white rounded-full shadow-lg',
        'flex items-center justify-center gap-2',
        'transition-all duration-200',
        'hover:shadow-xl active:scale-95',
        'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-600',
        isPressed && 'scale-95 shadow-inner',
        className
      )}
      {...props}
    >
      {Icon && <Icon className={iconSizes[size]} />}
      {extended && children && <span className="font-medium">{children}</span>}
    </button>
  );
};

/**
 * Icon Button
 * Touch-optimized icon-only button
 */
export const IconButton = ({
  icon: Icon,
  onClick,
  size = 'md',
  variant = 'ghost',
  className = '',
  label,
  ...props
}) => {
  const sizes = {
    sm: 'h-10 w-10',
    md: 'h-11 w-11',
    lg: 'h-12 w-12'
  };
  
  const iconSizes = {
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6'
  };
  
  return (
    <TouchButton
      onClick={onClick}
      variant={variant}
      className={cn(
        sizes[size],
        'p-0',
        className
      )}
      aria-label={label}
      {...props}
    >
      <Icon className={iconSizes[size]} />
    </TouchButton>
  );
};