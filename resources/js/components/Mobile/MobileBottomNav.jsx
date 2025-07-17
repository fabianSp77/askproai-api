import React from 'react';
import { useLocation, Link } from 'react-router-dom';
import { Home, Phone, Calendar, Users, MoreHorizontal } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';

const MobileBottomNav = () => {
  const location = useLocation();
  
  const navItems = [
    { 
      icon: Home, 
      label: 'Dashboard', 
      href: '/business',
      exact: true
    },
    { 
      icon: Phone, 
      label: 'Anrufe', 
      href: '/business/calls',
      exact: false
    },
    { 
      icon: Calendar, 
      label: 'Termine', 
      href: '/business/appointments',
      exact: false
    },
    { 
      icon: Users, 
      label: 'Kunden', 
      href: '/business/customers',
      exact: false
    }
  ];

  const moreItems = [
    { label: 'Team', href: '/business/team', icon: Users },
    { label: 'Analytics', href: '/business/analytics', icon: 'BarChart3' },
    { label: 'Einstellungen', href: '/business/settings', icon: 'Settings' },
    { label: 'Abrechnung', href: '/business/billing', icon: 'CreditCard' }
  ];

  const isActive = (item) => {
    if (item.exact) {
      return location.pathname === item.href;
    }
    return location.pathname.startsWith(item.href);
  };

  const handleNavClick = (e, href) => {
    // Add haptic feedback on supported devices
    if (window.navigator.vibrate) {
      window.navigator.vibrate(10);
    }
  };

  return (
    <nav className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 lg:hidden z-50 safe-area-bottom">
      <div className="grid grid-cols-5 h-16">
        {navItems.map((item) => {
          const active = isActive(item);
          return (
            <Link
              key={item.label}
              to={item.href}
              onClick={(e) => handleNavClick(e, item.href)}
              className={cn(
                "flex flex-col items-center justify-center py-2 relative",
                "text-xs font-medium transition-all duration-200",
                "active:bg-gray-100 active:scale-95",
                "focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-600",
                active ? "text-blue-600" : "text-gray-600"
              )}
            >
              {/* Active indicator */}
              {active && (
                <div className="absolute top-0 left-1/2 -translate-x-1/2 w-12 h-0.5 bg-blue-600 rounded-full" />
              )}
              
              <item.icon className={cn(
                "h-5 w-5 mb-1 transition-all",
                active && "scale-110"
              )} />
              <span className={cn(
                "transition-all",
                active && "font-semibold"
              )}>
                {item.label}
              </span>
            </Link>
          );
        })}
        
        {/* More Menu */}
        <Sheet>
          <SheetTrigger asChild>
            <button
              onClick={() => {
                if (window.navigator.vibrate) {
                  window.navigator.vibrate(10);
                }
              }}
              className={cn(
                "flex flex-col items-center justify-center py-2",
                "text-xs font-medium transition-all duration-200",
                "active:bg-gray-100 active:scale-95",
                "focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-600",
                "text-gray-600"
              )}
            >
              <MoreHorizontal className="h-5 w-5 mb-1" />
              <span>Mehr</span>
            </button>
          </SheetTrigger>
          <SheetContent side="bottom" className="rounded-t-2xl">
            <div className="py-4">
              <h3 className="text-lg font-semibold mb-4 px-4">Weitere Optionen</h3>
              <div className="space-y-1">
                {moreItems.map((item) => (
                  <Link
                    key={item.label}
                    to={item.href}
                    className={cn(
                      "flex items-center gap-3 px-4 py-3 rounded-lg",
                      "text-gray-700 hover:bg-gray-100 active:bg-gray-200",
                      "transition-colors duration-200"
                    )}
                  >
                    <div className="h-10 w-10 bg-gray-100 rounded-lg flex items-center justify-center">
                      {/* Icon placeholder - would use actual icon component */}
                      <span className="text-xs">{item.icon}</span>
                    </div>
                    <span className="font-medium">{item.label}</span>
                  </Link>
                ))}
              </div>
            </div>
          </SheetContent>
        </Sheet>
      </div>
    </nav>
  );
};

export default MobileBottomNav;