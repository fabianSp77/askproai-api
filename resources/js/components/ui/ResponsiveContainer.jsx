import React from 'react';
import { cn } from '@/lib/utils';

/**
 * Responsive Container Component
 * Mobile-first container with responsive padding and max-width
 */
export const ResponsiveContainer = ({ 
  children, 
  className = '',
  noPadding = false,
  maxWidth = '7xl' // default, sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl, full
}) => {
  const maxWidthClasses = {
    'sm': 'max-w-sm',
    'md': 'max-w-md', 
    'lg': 'max-w-lg',
    'xl': 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
    '6xl': 'max-w-6xl',
    '7xl': 'max-w-7xl',
    'full': 'max-w-full'
  };

  return (
    <div className={cn(
      "w-full mx-auto",
      !noPadding && "px-4 sm:px-6 lg:px-8", // Responsive padding
      maxWidthClasses[maxWidth] || 'max-w-7xl',
      className
    )}>
      {children}
    </div>
  );
};

/**
 * Adaptive Grid Component
 * Responsive grid that adapts to different viewports
 */
export const AdaptiveGrid = ({ 
  children, 
  columns = { base: 1, sm: 1, md: 2, lg: 3, xl: 4 },
  gap = 4,
  className = ''
}) => {
  return (
    <div className={cn(
      "grid",
      `gap-${gap}`,
      `grid-cols-${columns.base || 1}`,
      columns.sm && `sm:grid-cols-${columns.sm}`,
      columns.md && `md:grid-cols-${columns.md}`,
      columns.lg && `lg:grid-cols-${columns.lg}`,
      columns.xl && `xl:grid-cols-${columns.xl}`,
      className
    )}>
      {children}
    </div>
  );
};

/**
 * Stack Component
 * Stacks elements vertically on mobile, horizontally on larger screens
 */
export const Stack = ({ 
  children, 
  direction = { base: 'vertical', md: 'horizontal' },
  gap = 4,
  align = 'start',
  className = ''
}) => {
  const isVertical = direction.base === 'vertical';
  const isMdHorizontal = direction.md === 'horizontal';

  return (
    <div className={cn(
      "flex",
      isVertical ? "flex-col" : "flex-row",
      isMdHorizontal && "md:flex-row",
      `gap-${gap}`,
      {
        'items-start': align === 'start',
        'items-center': align === 'center',
        'items-end': align === 'end',
        'items-stretch': align === 'stretch'
      },
      className
    )}>
      {children}
    </div>
  );
};

/**
 * Hide/Show utilities for responsive design
 */
export const HideMobile = ({ children, className = '' }) => (
  <div className={cn("hidden sm:block", className)}>{children}</div>
);

export const ShowMobile = ({ children, className = '' }) => (
  <div className={cn("block sm:hidden", className)}>{children}</div>
);

export const HideTablet = ({ children, className = '' }) => (
  <div className={cn("hidden md:block", className)}>{children}</div>
);

export const ShowTablet = ({ children, className = '' }) => (
  <div className={cn("block md:hidden", className)}>{children}</div>
);