# Frontend Optimization Tasks

## Immediate Optimizations

### 1. Implement Code Splitting
```javascript
// resources/js/app.jsx
import React, { Suspense, lazy } from 'react';

// Lazy load portal pages
const PortalDashboard = lazy(() => import('./portal-dashboard'));
const PortalAnalytics = lazy(() => import('./portal-analytics'));
const PortalBilling = lazy(() => import('./portal-billing-optimized'));
const PortalCalls = lazy(() => import('./portal-calls'));
const PortalTeam = lazy(() => import('./portal-team'));

// Loading component
const PageLoader = () => (
  <div className="flex items-center justify-center min-h-screen">
    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
  </div>
);

// Use with Suspense
<Suspense fallback={<PageLoader />}>
  <PortalDashboard />
</Suspense>
```

### 2. Optimize Bundle Size
```javascript
// vite.config.js additions
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor-react': ['react', 'react-dom', '@inertiajs/react'],
          'vendor-ui': ['antd', '@ant-design/icons'],
          'vendor-charts': ['recharts'],
          'vendor-utils': ['axios', 'dayjs', 'lodash-es']
        }
      }
    }
  }
});
```

### 3. Performance Monitoring Hook
```javascript
// resources/js/hooks/usePerformanceMonitor.jsx
import { useEffect } from 'react';

export const usePerformanceMonitor = (componentName) => {
  useEffect(() => {
    // Mark component mount
    performance.mark(`${componentName}-mount-start`);
    
    // Measure after paint
    requestAnimationFrame(() => {
      performance.mark(`${componentName}-mount-end`);
      performance.measure(
        `${componentName}-mount`,
        `${componentName}-mount-start`,
        `${componentName}-mount-end`
      );
      
      // Log to monitoring service
      const measure = performance.getEntriesByName(`${componentName}-mount`)[0];
      console.log(`${componentName} mounted in ${measure.duration}ms`);
    });
    
    return () => {
      performance.clearMarks(`${componentName}-mount-start`);
      performance.clearMarks(`${componentName}-mount-end`);
      performance.clearMeasures(`${componentName}-mount`);
    };
  }, [componentName]);
};
```

### 4. Memoize Expensive Components
```javascript
// resources/js/components/analytics/RevenueChart.jsx
import React, { memo, useMemo } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip } from 'recharts';

const RevenueChart = memo(({ data, dateRange }) => {
  const processedData = useMemo(() => {
    // Expensive data processing
    return data.map(item => ({
      ...item,
      revenue: item.revenue / 100,
      formattedDate: dayjs(item.date).format('MMM DD')
    }));
  }, [data]);
  
  return (
    <LineChart width={800} height={400} data={processedData}>
      <CartesianGrid strokeDasharray="3 3" />
      <XAxis dataKey="formattedDate" />
      <YAxis />
      <Tooltip />
      <Line type="monotone" dataKey="revenue" stroke="#8884d8" />
    </LineChart>
  );
}, (prevProps, nextProps) => {
  // Custom comparison for better performance
  return prevProps.dateRange === nextProps.dateRange && 
         prevProps.data.length === nextProps.data.length;
});
```

### 5. Virtual Scrolling for Large Lists
```javascript
// resources/js/components/calls/CallList.jsx
import { VariableSizeList } from 'react-window';

const CallList = ({ calls }) => {
  const Row = ({ index, style }) => (
    <div style={style} className="call-row">
      {/* Render call item */}
    </div>
  );
  
  return (
    <VariableSizeList
      height={600}
      itemCount={calls.length}
      itemSize={() => 80} // Row height
      width="100%"
    >
      {Row}
    </VariableSizeList>
  );
};
```

## Build Optimizations

### Update package.json scripts:
```json
{
  "scripts": {
    "analyze": "vite build --mode production && vite-bundle-visualizer",
    "build:modern": "vite build --target esnext",
    "build:legacy": "vite build --target es2015"
  }
}
```

## Monitoring

### Add Web Vitals tracking:
```javascript
// resources/js/utils/webVitals.js
import { getCLS, getFID, getFCP, getLCP, getTTFB } from 'web-vitals';

function sendToAnalytics(metric) {
  // Send to your analytics endpoint
  const body = JSON.stringify({
    name: metric.name,
    value: metric.value,
    rating: metric.rating,
    delta: metric.delta,
    id: metric.id,
    url: window.location.href
  });
  
  // Use sendBeacon for reliability
  navigator.sendBeacon('/api/metrics/web-vitals', body);
}

// Initialize tracking
getCLS(sendToAnalytics);
getFID(sendToAnalytics);
getFCP(sendToAnalytics);
getLCP(sendToAnalytics);
getTTFB(sendToAnalytics);
```