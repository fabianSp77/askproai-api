import React, { lazy, Suspense } from 'react';
import { Loader2 } from 'lucide-react';

// Loading component
const Loading = () => (
    <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
    </div>
);

// Lazy load wrapper
export const lazyLoad = (importFunc, fallback = <Loading />) => {
    const LazyComponent = lazy(importFunc);
    
    return (props) => (
        <Suspense fallback={fallback}>
            <LazyComponent {...props} />
        </Suspense>
    );
};

// Pre-configured lazy imports for portal modules
export const LazyDashboard = lazyLoad(() => import('../Pages/Portal/Dashboard/Index'));
export const LazyCallsIndex = lazyLoad(() => import('../Pages/Portal/Calls/Index'));
export const LazyCallShow = lazyLoad(() => import('../Pages/Portal/Calls/Show'));
export const LazyAppointments = lazyLoad(() => import('../Pages/Portal/Appointments/IndexModern'));
export const LazyCustomers = lazyLoad(() => import('../Pages/Portal/Customers/Index'));
export const LazyTeam = lazyLoad(() => import('../Pages/Portal/Team/IndexModern'));
export const LazyAnalytics = lazyLoad(() => import('../Pages/Portal/Analytics/IndexModern'));
export const LazySettings = lazyLoad(() => import('../Pages/Portal/Settings/Index'));
export const LazyBilling = lazyLoad(() => import('../Pages/Portal/Billing/IndexRefactored'));