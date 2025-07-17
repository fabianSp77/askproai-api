# 🚴 TdF-Road-Trip-Planner 2025 - Technical Specification

## Executive Summary

**Project**: Tour de France Road-Trip Planner 2025  
**Duration**: 22.–26. Juli 2025 (5 days)  
**Focus**: Etappen 18 & 19  
**Type**: Progressive Web App (PWA) with offline-first architecture  
**Primary User**: Single user with Tesla Model Y & Dog (Henry)

---

## 🎯 Project Vision

Create a **bulletproof, offline-capable trip planning companion** that handles the complexity of following the Tour de France with an electric vehicle, a dog, and the need for real-time adaptability to race conditions, weather, and infrastructure availability.

### Core Value Propositions
1. **Zero-Stress Navigation**: Know exactly where to be, when, and why
2. **EV-Optimized**: Intelligent charging stops with safety ratings
3. **Dog-Friendly**: Henry's needs integrated into every decision
4. **Offline-First**: Full functionality in mountain dead zones
5. **Real-Time Adaptable**: Drag-and-drop timeline with instant route recalculation

---

## 📋 Functional Requirements

### 1. Timeline Management System

```typescript
interface TimelineEvent {
  id: string;
  type: 'drive' | 'charge' | 'shower' | 'sleep' | 'race' | 'dog-break';
  startTime: Date;
  endTime: Date;
  location: Location;
  priority: 'critical' | 'important' | 'flexible';
  dependencies: string[]; // other event IDs
  alerts: Alert[];
  notes: string;
  dogFriendly: boolean;
}
```

**Features**:
- Horizontal/vertical timeline view toggle
- Drag-and-drop with dependency validation
- Color-coded event categories
- Time conflict detection
- Smart suggestions for schedule optimization
- Bulk operations (shift all events by X hours)

### 2. Interactive Map System

**Core Components**:
- **Base Layer**: Mapbox GL JS with terrain elevation
- **Overlay Layers**:
  - Tesla Superchargers (with real-time availability via Tesla API)
  - Alternative charging networks (Ionity, FastNed)
  - Secured parking (video surveillance, barriers)
  - Dog-friendly areas (parks, quiet zones)
  - Race route with km markers
  - Spectator zones with crowd density estimates
  - Road closures (time-based)

**Interactive Features**:
- Click event on timeline → map pans and zooms
- Click location on map → creates draft event
- Route alternatives shown as ghost lines
- Elevation profile for mountain stages
- 3D terrain mode for col visualization

### 3. Intelligent Route Engine

```typescript
interface RouteCalculation {
  segments: RouteSegment[];
  totalDistance: number;
  totalTime: number;
  chargingStops: ChargingStop[];
  energyConsumption: EnergyProfile;
  tollCosts: { CH: number; FR: number };
  alternativeRoutes: Route[];
}

interface ChargingStop {
  location: Supercharger;
  arrivalSoC: number;
  targetSoC: number;
  chargingTime: number;
  amenities: string[];
  dogArea: boolean;
  securityScore: number;
}
```

**ABRP Integration**:
- Real-time consumption model based on:
  - Elevation changes
  - Weather (headwind, temperature)
  - Speed limits
  - Payload (camping gear weight)
- 15% safety buffer on all calculations
- Alternative routes for toll avoidance
- Charging stop optimization (minimize total time)

### 4. Dog Care Intelligence Layer

```typescript
interface DogCareMetrics {
  location: string;
  noiseLevel: number; // dB estimate
  temperature: number; // °C
  shadedAreas: boolean;
  grassAvailable: boolean;
  waterSource: boolean;
  vetNearby: VetInfo | null;
  walkingPaths: Path[];
  crowdDensity: 'low' | 'medium' | 'high';
}
```

**Features**:
- Noise level predictions based on crowd data
- Temperature alerts (> 25°C in car)
- Suggested break intervals (every 2 hours)
- Dog-friendly restaurants/cafes overlay
- Emergency vet locations with 24/7 filter

### 5. Notification & Export System

**Push Notifications** (Progressive Enhancement):
- 48h before: Weather forecast & route conditions
- 24h before: Confirm charging reservations
- 2h before: Road closure reminders
- 30min before: Departure alerts
- Real-time: Route deviation suggestions

**Export Formats**:
- **ICS/iCal**: Full timeline with locations and notes
- **PDF Booklet**: 
  - Cover: Trip overview map
  - Pages: Day-by-day timeline with QR codes for locations
  - Emergency contacts and backup plans
  - Offline maps for each stop
- **GPX**: For Garmin/navigation backup
- **Share Link**: Public read-only view

### 6. Offline Capability

**Service Worker Strategy**:
```javascript
// Cache-first for static assets
// Network-first for API calls with fallback
// Background sync for user edits

const CACHE_STRATEGY = {
  static: 'cache-first',
  api: 'network-first',
  maps: 'cache-with-network-update',
  images: 'lazy-cache'
};
```

**Offline Features**:
- Complete timeline access
- Pre-cached map tiles (zoom 8-16)
- Local route modifications (sync when online)
- Emergency contact list
- PDF backup auto-downloaded

---

## 🏗️ Technical Architecture

### Tech Stack

```yaml
Frontend:
  - Framework: Next.js 14 (App Router)
  - Language: TypeScript 5.3+
  - Styling: Tailwind CSS 3.4 + Radix UI
  - State: Zustand + React Query
  - Maps: Mapbox GL JS 3.0
  - PWA: Workbox 7.0

Backend:
  - Database: Supabase (PostgreSQL)
  - Auth: Supabase Auth
  - Storage: Supabase Storage (offline maps)
  - Edge Functions: Deno Deploy
  - Caching: Redis (Upstash)

DevOps:
  - Hosting: Vercel (Edge Network)
  - Monitoring: Sentry + Vercel Analytics
  - CI/CD: GitHub Actions
  - Testing: Vitest + Playwright
```

### Database Schema

```sql
-- Enhanced schema with audit trails and versioning

CREATE TABLE trips (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  title TEXT NOT NULL,
  start_date TIMESTAMPTZ NOT NULL,
  end_date TIMESTAMPTZ NOT NULL,
  settings JSONB DEFAULT '{}',
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE events (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  trip_id UUID REFERENCES trips(id) ON DELETE CASCADE,
  type event_type NOT NULL,
  start_time TIMESTAMPTZ NOT NULL,
  end_time TIMESTAMPTZ NOT NULL,
  location_id UUID REFERENCES locations(id),
  priority priority_level DEFAULT 'flexible',
  dependencies UUID[] DEFAULT '{}',
  metadata JSONB DEFAULT '{}',
  version INT DEFAULT 1,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE locations (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  coordinates GEOGRAPHY(POINT, 4326) NOT NULL,
  address JSONB,
  security_score DECIMAL(2,1) CHECK (security_score >= 0 AND security_score <= 5),
  dog_score DECIMAL(2,1) CHECK (dog_score >= 0 AND dog_score <= 5),
  amenities TEXT[],
  metadata JSONB DEFAULT '{}',
  verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE charging_stations (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  location_id UUID REFERENCES locations(id) ON DELETE CASCADE,
  provider TEXT NOT NULL,
  power_kw INT NOT NULL,
  stalls INT NOT NULL,
  connector_types TEXT[],
  pricing JSONB,
  real_time_api TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Audit trail for offline sync
CREATE TABLE sync_queue (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  action TEXT NOT NULL,
  entity_type TEXT NOT NULL,
  entity_id UUID NOT NULL,
  payload JSONB NOT NULL,
  synced BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Indexes for performance
CREATE INDEX idx_events_trip_time ON events(trip_id, start_time);
CREATE INDEX idx_locations_geo ON locations USING GIST(coordinates);
CREATE INDEX idx_locations_scores ON locations(security_score, dog_score);
```

### API Design

```typescript
// RESTful API with GraphQL for complex queries

// REST Endpoints
GET    /api/trips/:id
PUT    /api/trips/:id
GET    /api/trips/:id/events
POST   /api/trips/:id/events
PATCH  /api/trips/:id/events/:eventId
DELETE /api/trips/:id/events/:eventId

// GraphQL for complex timeline queries
query GetTimelineWithDependencies($tripId: ID!, $start: DateTime!, $end: DateTime!) {
  trip(id: $tripId) {
    events(timeRange: { start: $start, end: $end }) {
      id
      type
      startTime
      endTime
      location {
        name
        coordinates
        chargingStation {
          realTimeAvailability
        }
      }
      dependencies {
        id
        type
        criticalPath
      }
    }
  }
}

// WebSocket for real-time updates
ws://api/trips/:id/subscribe
```

---

## 🎨 UI/UX Design System

### Design Principles
1. **Clarity over Cleverness**: Simple, obvious interactions
2. **Touch-First**: 44px minimum touch targets
3. **Information Hierarchy**: Critical info always visible
4. **Graceful Degradation**: Works without JS, better with it
5. **Contextual Help**: Inline tips, no manual needed

### Component Library

```typescript
// Core Components
<Timeline 
  orientation="horizontal|vertical"
  snapToHour={true}
  showDependencies={true}
/>

<MapView
  initialBounds={europeBounds}
  layers={['terrain', 'charging', 'dog-friendly']}
  interactive={true}
/>

<EventCard
  event={timelineEvent}
  draggable={true}
  onConflict={(conflicts) => handleConflicts(conflicts)}
/>

<RouteCalculator
  start={location}
  end={location}
  waypoints={locations[]}
  vehicle={vehicleProfile}
  preferences={routePreferences}
/>
```

### Responsive Breakpoints
- Mobile: 320px - 768px (portrait timeline)
- Tablet: 768px - 1024px (split view)
- Desktop: 1024px+ (side-by-side with map)

### Accessibility Features
- **Keyboard Navigation**: Full timeline control via arrows
- **Screen Reader**: Descriptive ARIA labels
- **High Contrast**: WCAG AAA mode available
- **Reduced Motion**: Disable animations option
- **Voice Control**: "Navigate to next charging stop"

---

## 📊 Performance Requirements

### Core Web Vitals Targets
- **LCP**: < 2.5s (mobile 4G)
- **FID**: < 100ms
- **CLS**: < 0.1
- **TTI**: < 3.5s

### Optimization Strategies
```javascript
// Route-based code splitting
const Timeline = lazy(() => import('./components/Timeline'));
const MapView = lazy(() => import('./components/MapView'));

// Image optimization
<Image
  src="/tesla-supercharger.webp"
  alt="Supercharger location"
  loading="lazy"
  placeholder="blur"
/>

// API response caching
const { data } = useQuery({
  queryKey: ['route', start, end],
  queryFn: () => calculateRoute(start, end),
  staleTime: 5 * 60 * 1000, // 5 minutes
  cacheTime: 60 * 60 * 1000, // 1 hour
});
```

---

## 🚀 Implementation Roadmap

### Phase 1: Foundation (Days 1-5)
- [ ] Repository setup with TypeScript, ESLint, Prettier
- [ ] Supabase project with schema deployment
- [ ] Next.js app with basic routing
- [ ] Component library setup (Storybook)
- [ ] CI/CD pipeline (GitHub Actions → Vercel)

### Phase 2: Core Features (Days 6-12)
- [ ] Timeline component with drag-and-drop
- [ ] Map integration with basic markers
- [ ] Event CRUD operations
- [ ] Local state management
- [ ] Basic offline support

### Phase 3: Intelligence Layer (Days 13-18)
- [ ] ABRP API integration
- [ ] Route calculation engine
- [ ] Dog-care scoring system
- [ ] Real-time data feeds
- [ ] Notification system

### Phase 4: Polish & PWA (Days 19-24)
- [ ] PWA manifest and service worker
- [ ] Offline map tile caching
- [ ] Export functionality (PDF, ICS)
- [ ] Performance optimization
- [ ] Accessibility audit
- [ ] User testing & fixes

---

## 🧪 Testing Strategy

### Test Coverage Requirements
- Unit Tests: 80% (critical functions: 100%)
- Integration Tests: Key user journeys
- E2E Tests: Happy path + edge cases
- Performance Tests: Lighthouse CI
- Accessibility Tests: axe-core automation

### Test Examples
```typescript
// Unit Test Example
describe('RouteCalculator', () => {
  it('should add charging stops when battery insufficient', async () => {
    const route = await calculateRoute({
      start: BASEL,
      end: COURCHEVEL,
      vehicle: TESLA_MODEL_Y,
      startingSoC: 50
    });
    
    expect(route.chargingStops).toHaveLength(1);
    expect(route.chargingStops[0].location.city).toBe('Martigny');
  });
});

// E2E Test Example
test('complete trip planning flow', async ({ page }) => {
  await page.goto('/planner');
  await page.click('[data-testid="add-event"]');
  await page.selectOption('[name="type"]', 'race');
  await page.fill('[name="location"]', 'Courchevel');
  await page.click('[data-testid="calculate-route"]');
  
  await expect(page.locator('.route-summary')).toContainText('2 charging stops');
});
```

---

## 📝 Deliverables

### Code Deliverables
1. **Source Code**: Clean, documented TypeScript
2. **Documentation**: 
   - API documentation (OpenAPI 3.0)
   - Component documentation (Storybook)
   - Deployment guide
3. **Tests**: Full test suite with >80% coverage
4. **Configuration**: 
   - `.env.example` with all required keys
   - Docker Compose for local development
   - Terraform for infrastructure as code

### Asset Deliverables
1. **Design System**: Figma file with all components
2. **Icons**: Optimized SVG sprite sheet
3. **Offline Maps**: Pre-generated tile packages
4. **Sample Data**: Seed data for all TdF locations

### Documentation Deliverables
1. **README.md**: Quick start guide
2. **ARCHITECTURE.md**: Technical decisions
3. **DEPLOYMENT.md**: Production setup
4. **CONTRIBUTING.md**: Development workflow

---

## 🎯 Success Metrics

### Technical Metrics
- [ ] Lighthouse Performance Score > 95
- [ ] Zero runtime errors in Sentry (first week)
- [ ] Offline functionality for 100% of core features
- [ ] < 5 second cold start on 3G network

### User Experience Metrics
- [ ] Complete trip planning in < 5 minutes
- [ ] Zero missed events due to app failure
- [ ] Successful offline navigation for entire trip
- [ ] Dog comfort maintained throughout journey

### Business Metrics
- [ ] PWA installed before trip start
- [ ] All planned stops successfully reached
- [ ] Total trip time within 5% of estimate
- [ ] User satisfaction: 10/10 😄

---

## 🤝 Handoff Checklist

### For Developer
- [ ] Access to private GitHub repository
- [ ] Supabase project invitation
- [ ] Mapbox API key
- [ ] ABRP API access
- [ ] Figma design file access
- [ ] Test device profiles (iOS/Android)

### For Deployment
- [ ] Vercel team access
- [ ] Environment variables documented
- [ ] DNS configuration (if custom domain)
- [ ] SSL certificate provisioned
- [ ] Monitoring alerts configured

### For Maintenance
- [ ] Runbook for common issues
- [ ] Database backup strategy
- [ ] Update schedule for map data
- [ ] Contact for emergency support

---

## 💡 Innovation Opportunities

### Future Enhancements (Post-MVP)
1. **AI Copilot**: "Find scenic lunch spot with charging in 30min radius"
2. **Social Features**: Share route with other TdF followers
3. **Live Race Integration**: Rider positions affect route timing
4. **Weather Routing**: Avoid rain/heat automatically
5. **Carbon Offset**: Calculate and offset trip emissions
6. **AR Navigation**: Camera overlay for finding exact parking spots
7. **Blockchain Tickets**: NFT integration for VIP area access
8. **Smart Home**: Pre-cool/heat car based on departure time

---

> **🏁 Final Note**: This specification prioritizes **reliability over features**. Every decision should optimize for the stress-free execution of this once-a-year adventure. When in doubt, choose the solution that works offline in a French mountain valley with tired humans and a hungry dog.

---

*Version 1.0 - Created: 2025-07-09*  
*Last Updated: 2025-07-09*  
*Status: Ready for Development*