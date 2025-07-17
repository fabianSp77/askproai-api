# React Components Test Priority

## 🎯 Critical Components to Test First

### 1. **Main App Components**
- **PortalApp.jsx** - Main portal application wrapper
- **AdminApp.jsx** - Admin dashboard application wrapper
- **PortalAppModern.jsx** - Modern version of portal app

### 2. **Core Business Logic Components**

#### Dashboard Components
- `Pages/Portal/Dashboard/ReactIndex.jsx` - Main portal dashboard
- `Pages/Portal/Dashboard/ReactIndexModern.jsx` - Modern dashboard version
- `Pages/Admin/Dashboard/index.jsx` - Admin dashboard

#### Call Management
- `Pages/Portal/Calls/Index.jsx` - Call listing page
- `Pages/Portal/Calls/Show.jsx` - Call detail view
- `Pages/Portal/Calls/ShowV2.jsx` - Updated call detail view
- `components/CallDetailView.jsx` - Reusable call detail component

#### Appointment Management
- `Pages/Portal/Appointments/Index.jsx` - Appointment listing
- `Pages/Portal/Appointments/IndexModern.jsx` - Modern appointment view
- `Pages/Portal/Appointments/IndexV2.jsx` - V2 appointment interface
- `components/Portal/AppointmentDetails.jsx` - Appointment detail component
- `components/Portal/AppointmentCalendar.jsx` - Calendar view component

#### Customer Management
- `Pages/Portal/Customers/Index.jsx` - Customer listing
- `Pages/Admin/Customers/Index.jsx` - Admin customer management
- `components/admin/CustomerDetailView.jsx` - Customer detail view
- `components/Portal/CustomerJourney.jsx` - Customer journey tracker

### 3. **Billing & Payment Components**
- `Pages/Portal/Billing/Index.jsx` - Main billing page
- `Pages/Portal/Billing/IndexRefactored.jsx` - Refactored billing
- `components/billing/PrepaidBalanceCard.jsx` - Balance display
- `components/billing/TopupModal.jsx` - Payment modal
- `components/billing/TransactionHistory.jsx` - Transaction list
- `components/billing/AutoTopupCard.jsx` - Auto-topup configuration

### 4. **Goal & Analytics Components**
- `Pages/Portal/Analytics/Goals.jsx` - Goals analytics
- `components/goals/GoalDashboard.jsx` - Goal tracking dashboard
- `components/goals/GoalConfiguration.jsx` - Goal setup
- `components/Portal/Goals/GoalAnalytics.jsx` - Goal analytics

### 5. **Common UI Components**
- `components/ErrorBoundary.jsx` - Error handling wrapper
- `components/NotificationCenter.jsx` - Notifications
- `components/LanguageSelector.jsx` - Language switching
- `components/ThemeToggle.jsx` - Dark/light mode toggle
- `components/SmartSearch.jsx` - Search functionality

### 6. **Mobile-Optimized Components**
- `components/Mobile/MobileLayout.jsx` - Mobile layout wrapper
- `components/Mobile/MobileDashboard.jsx` - Mobile dashboard
- `components/Mobile/MobileCallList.jsx` - Mobile call list
- `components/Mobile/MobileBottomNav.jsx` - Mobile navigation
- `components/Mobile/OfflineIndicator.jsx` - Offline status

### 7. **UI Component Library** (resources/js/components/ui/)
- `button.jsx`, `card.jsx`, `dialog.jsx`, `input.jsx` - Core UI elements
- `table.jsx`, `tabs.jsx`, `select.jsx` - Data display components
- `alert.jsx`, `badge.jsx` - Feedback components

## 📋 Testing Strategy

### Unit Tests Priority
1. **Business Logic Components** - Goal calculations, billing logic
2. **Data Display Components** - Tables, lists, detail views
3. **Form Components** - Input validation, submission handling
4. **UI Components** - Buttons, cards, modals

### Integration Tests Priority
1. **User Flows** - Login → Dashboard → Call/Appointment views
2. **Data Flows** - API calls, state management
3. **Payment Flows** - Topup process, transaction history
4. **Mobile Experience** - Responsive behavior, touch interactions

### Component Test Checklist
- [ ] Renders without crashing
- [ ] Props validation
- [ ] User interactions (clicks, inputs)
- [ ] API calls and error handling
- [ ] Responsive behavior
- [ ] Accessibility (ARIA labels, keyboard nav)
- [ ] Performance (re-renders, memoization)

## 🛠️ Testing Tools Setup
```bash
# Install testing dependencies
npm install --save-dev @testing-library/react @testing-library/jest-dom
npm install --save-dev @testing-library/user-event jest-environment-jsdom

# Run tests
npm test
npm run test:coverage
```

## 📁 Test File Structure
```
resources/js/
├── __tests__/
│   ├── components/
│   │   ├── Portal/
│   │   ├── Admin/
│   │   └── ui/
│   ├── Pages/
│   │   ├── Portal/
│   │   └── Admin/
│   └── hooks/
└── setupTests.js
```