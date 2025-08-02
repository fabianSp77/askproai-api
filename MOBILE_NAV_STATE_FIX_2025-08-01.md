# Mobile Navigation State Management Fix

## Problem Analysis

The mobile navigation in the Business Portal has state synchronization issues:

1. **Missing State Definition**: `setMobileMenuVisible` is referenced but not defined
2. **Component Mismatch**: Desktop uses Ant Design Menu, mobile uses custom MobileBottomNavAntd
3. **No Unified State**: Mobile and desktop navigation states are not synchronized
4. **Missing Mobile Menu**: No hamburger menu for mobile drawer navigation

## Solution: Unified Navigation State

### 1. Create Navigation Context

```javascript
// resources/js/contexts/NavigationContext.jsx
import React, { createContext, useContext, useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';

const NavigationContext = createContext();

export const useNavigation = () => {
  const context = useContext(NavigationContext);
  if (!context) {
    throw new Error('useNavigation must be used within NavigationProvider');
  }
  return context;
};

export const NavigationProvider = ({ children }) => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [activeDrawer, setActiveDrawer] = useState(null);
  const location = useLocation();

  // Close mobile menu on route change
  useEffect(() => {
    setMobileMenuOpen(false);
    setActiveDrawer(null);
  }, [location.pathname]);

  // Close mobile menu on desktop resize
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth > 768) {
        setMobileMenuOpen(false);
      }
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const value = {
    mobileMenuOpen,
    setMobileMenuOpen,
    activeDrawer,
    setActiveDrawer,
    closeMobileMenu: () => setMobileMenuOpen(false),
    toggleMobileMenu: () => setMobileMenuOpen(prev => !prev),
  };

  return (
    <NavigationContext.Provider value={value}>
      {children}
    </NavigationContext.Provider>
  );
};
```

### 2. Update PortalApp.jsx

Add these changes to fix the state management:

```javascript
// At the top with other imports
import { NavigationProvider, useNavigation } from './contexts/NavigationContext';

// Inside PortalApp component, replace the missing state with:
const [mobileMenuVisible, setMobileMenuVisible] = useState(false);

// Add mobile drawer for side navigation
const [mobileDrawerVisible, setMobileDrawerVisible] = useState(false);

// Update handleNavigate function
const handleNavigate = (path) => {
    navigate(path);
    if (isMobile) {
        setMobileMenuVisible(false);
        setMobileDrawerVisible(false);
    }
};

// Add mobile header with hamburger menu
{isMobile && (
    <Button
        type="text"
        icon={mobileDrawerVisible ? <CloseOutlined /> : <MenuOutlined />}
        onClick={() => setMobileDrawerVisible(!mobileDrawerVisible)}
        style={{ marginRight: 16 }}
    />
)}

// Add mobile drawer component
{isMobile && (
    <Drawer
        placement="left"
        open={mobileDrawerVisible}
        onClose={() => setMobileDrawerVisible(false)}
        width={280}
        bodyStyle={{ padding: 0 }}
    >
        <div style={{ 
            height: 64, 
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center',
            borderBottom: '1px solid #f0f0f0'
        }}>
            <h2 style={{ margin: 0 }}>AskProAI</h2>
        </div>
        <Menu
            theme="light"
            selectedKeys={[location.pathname]}
            mode="inline"
            items={responsiveMenuItems}
            onClick={() => setMobileDrawerVisible(false)}
        />
    </Drawer>
)}
```

### 3. Enhanced MobileBottomNavAntd

Update the mobile navigation to work with the context:

```javascript
// Add at the top
import { useNavigation } from '../../contexts/NavigationContext';

// Inside component
const { activeDrawer, setActiveDrawer } = useNavigation();

// Update drawer visibility
const [moreDrawerVisible, setMoreDrawerVisible] = useState(false);

// Sync with context
useEffect(() => {
    setActiveDrawer(moreDrawerVisible ? 'more' : null);
}, [moreDrawerVisible, setActiveDrawer]);
```

### 4. CSS Fixes for Mobile Navigation

```css
/* Add to resources/css/portal-mobile-nav-fix.css */

/* Ensure mobile nav doesn't overlap content */
.mobile-bottom-nav {
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
}

/* Add padding to content when mobile nav is present */
@media (max-width: 768px) {
    .ant-layout-content {
        padding-bottom: 72px !important; /* 56px nav + 16px spacing */
    }
}

/* Smooth transitions for drawer */
.ant-drawer-content-wrapper {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Fix z-index conflicts */
.mobile-bottom-nav {
    z-index: 1001;
}

.ant-drawer-mask {
    z-index: 1000;
}

.ant-drawer-content-wrapper {
    z-index: 1001;
}

/* Haptic feedback visual indicator */
.nav-item-active {
    animation: tap-feedback 0.3s ease-out;
}

@keyframes tap-feedback {
    0% { transform: scale(1); }
    50% { transform: scale(0.95); }
    100% { transform: scale(1); }
}
```

## Implementation Steps

1. **Create NavigationContext.jsx** file
2. **Update PortalApp.jsx** with missing state definitions
3. **Add mobile drawer** for side navigation
4. **Update MobileBottomNavAntd** to use context
5. **Add CSS fixes** for proper spacing and z-index
6. **Test on multiple devices** and screen sizes

## Testing Checklist

- [ ] Mobile menu opens/closes properly
- [ ] Navigation state persists across route changes
- [ ] Desktop/mobile transitions work smoothly
- [ ] No z-index conflicts with modals/drawers
- [ ] Touch feedback works on mobile devices
- [ ] Content doesn't get hidden behind mobile nav
- [ ] Hamburger menu visible and functional
- [ ] More drawer in bottom nav works correctly

## Expected Result

After implementation:
- Unified navigation state across all components
- Smooth transitions between mobile/desktop
- No state synchronization issues
- Better mobile UX with proper touch feedback
- Clean separation of concerns with context