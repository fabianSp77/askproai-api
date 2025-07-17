import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter, useLocation } from 'react-router-dom';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import MobileBottomNavAntd from '../../../components/Mobile/MobileBottomNavAntd';

// Mock react-router-dom
vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom');
  return {
    ...actual,
    useNavigate: () => vi.fn(),
    useLocation: vi.fn(),
  };
});

const renderWithRouter = (component, { route = '/' } = {}) => {
  window.history.pushState({}, 'Test page', route);
  return render(
    <BrowserRouter>
      {component}
    </BrowserRouter>
  );
};

describe('MobileBottomNav', () => {
  const mockNavigate = vi.fn();
  
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(useLocation).mockReturnValue({
      pathname: '/',
      search: '',
      hash: '',
      state: null,
      key: 'default',
    });
    
    // Mock useNavigate
    vi.mock('react-router-dom', async () => {
      const actual = await vi.importActual('react-router-dom');
      return {
        ...actual,
        useNavigate: () => mockNavigate,
        useLocation: vi.fn(),
      };
    });
  });

  it('renders all navigation items', () => {
    renderWithRouter(<MobileBottomNavAntd />);
    
    expect(screen.getByLabelText('Dashboard')).toBeInTheDocument();
    expect(screen.getByLabelText('Anrufe')).toBeInTheDocument();
    expect(screen.getByLabelText('Termine')).toBeInTheDocument();
    expect(screen.getByLabelText('Kunden')).toBeInTheDocument();
    expect(screen.getByLabelText('Mehr')).toBeInTheDocument();
  });

  it('highlights active navigation item based on current route', () => {
    vi.mocked(useLocation).mockReturnValue({
      pathname: '/calls',
      search: '',
      hash: '',
      state: null,
      key: 'default',
    });
    
    renderWithRouter(<MobileBottomNavAntd />);
    
    const callsNav = screen.getByLabelText('Anrufe').closest('div');
    expect(callsNav).toHaveClass('active');
  });

  it('navigates to correct route when item is clicked', async () => {
    const user = userEvent.setup();
    renderWithRouter(<MobileBottomNavAntd />);
    
    await user.click(screen.getByLabelText('Anrufe'));
    expect(mockNavigate).toHaveBeenCalledWith('/calls');
    
    await user.click(screen.getByLabelText('Termine'));
    expect(mockNavigate).toHaveBeenCalledWith('/appointments');
    
    await user.click(screen.getByLabelText('Kunden'));
    expect(mockNavigate).toHaveBeenCalledWith('/customers');
  });

  it('shows more menu when more button is clicked', async () => {
    const user = userEvent.setup();
    renderWithRouter(<MobileBottomNavAntd />);
    
    // Click more button
    await user.click(screen.getByLabelText('Mehr'));
    
    // Check if menu items appear
    expect(screen.getByText('Team')).toBeInTheDocument();
    expect(screen.getByText('Analysen')).toBeInTheDocument();
    expect(screen.getByText('Abrechnung')).toBeInTheDocument();
    expect(screen.getByText('Einstellungen')).toBeInTheDocument();
  });

  it('closes more menu when clicking outside', async () => {
    const user = userEvent.setup();
    renderWithRouter(<MobileBottomNavAntd />);
    
    // Open menu
    await user.click(screen.getByLabelText('Mehr'));
    expect(screen.getByText('Team')).toBeInTheDocument();
    
    // Click outside
    await user.click(document.body);
    
    // Menu should be closed
    expect(screen.queryByText('Team')).not.toBeInTheDocument();
  });

  it('shows notification badge when there are unread notifications', () => {
    renderWithRouter(<MobileBottomNavAntd unreadCount={5} />);
    
    const badge = screen.getByText('5');
    expect(badge).toBeInTheDocument();
    expect(badge).toHaveClass('notification-badge');
  });

  it('hides when scrolling down and shows when scrolling up', async () => {
    const { container } = renderWithRouter(<MobileBottomNavAntd />);
    const nav = container.querySelector('.mobile-bottom-nav');
    
    // Initial state - visible
    expect(nav).not.toHaveClass('hidden');
    
    // Simulate scroll down
    fireEvent.scroll(window, { target: { scrollY: 100 } });
    await new Promise(resolve => setTimeout(resolve, 100));
    
    expect(nav).toHaveClass('hidden');
    
    // Simulate scroll up
    fireEvent.scroll(window, { target: { scrollY: 50 } });
    await new Promise(resolve => setTimeout(resolve, 100));
    
    expect(nav).not.toHaveClass('hidden');
  });

  it('handles touch gestures for navigation', async () => {
    renderWithRouter(<MobileBottomNavAntd />);
    
    const dashboardItem = screen.getByLabelText('Dashboard');
    
    // Simulate touch start
    fireEvent.touchStart(dashboardItem, {
      touches: [{ clientX: 0, clientY: 0 }],
    });
    
    // Should add pressed state
    expect(dashboardItem.closest('div')).toHaveClass('pressed');
    
    // Simulate touch end
    fireEvent.touchEnd(dashboardItem);
    
    // Should navigate
    expect(mockNavigate).toHaveBeenCalledWith('/');
  });

  it('supports swipe gestures between tabs', async () => {
    renderWithRouter(<MobileBottomNavAntd />);
    
    const nav = screen.getByRole('navigation');
    
    // Simulate swipe left (next tab)
    fireEvent.touchStart(nav, {
      touches: [{ clientX: 200, clientY: 50 }],
    });
    
    fireEvent.touchMove(nav, {
      touches: [{ clientX: 50, clientY: 50 }],
    });
    
    fireEvent.touchEnd(nav);
    
    // Should navigate to next tab (calls)
    expect(mockNavigate).toHaveBeenCalledWith('/calls');
  });

  it('shows active indicator animation', () => {
    vi.mocked(useLocation).mockReturnValue({
      pathname: '/appointments',
      search: '',
      hash: '',
      state: null,
      key: 'default',
    });
    
    const { container } = renderWithRouter(<MobileBottomNavAntd />);
    
    const activeIndicator = container.querySelector('.active-indicator');
    expect(activeIndicator).toBeInTheDocument();
    expect(activeIndicator).toHaveStyle({ transform: expect.stringContaining('translateX') });
  });

  it('handles long press for quick actions', async () => {
    const onLongPress = vi.fn();
    renderWithRouter(<MobileBottomNavAntd onLongPress={onLongPress} />);
    
    const callsItem = screen.getByLabelText('Anrufe');
    
    // Simulate long press
    fireEvent.touchStart(callsItem);
    
    // Wait for long press duration (500ms)
    await new Promise(resolve => setTimeout(resolve, 600));
    
    fireEvent.touchEnd(callsItem);
    
    expect(onLongPress).toHaveBeenCalledWith('calls');
  });

  it('respects user preferences for haptic feedback', async () => {
    // Mock vibration API
    navigator.vibrate = vi.fn();
    
    const user = userEvent.setup();
    renderWithRouter(<MobileBottomNavAntd enableHaptics={true} />);
    
    await user.click(screen.getByLabelText('Anrufe'));
    
    // Should trigger haptic feedback
    expect(navigator.vibrate).toHaveBeenCalledWith(10);
  });

  it('adapts to different screen sizes', () => {
    // Mock smaller screen
    Object.defineProperty(window, 'innerWidth', {
      writable: true,
      configurable: true,
      value: 320,
    });
    
    const { container } = renderWithRouter(<MobileBottomNavAntd />);
    
    // Should use compact mode
    expect(container.querySelector('.mobile-bottom-nav')).toHaveClass('compact');
    
    // Mock larger screen
    window.innerWidth = 414;
    
    // Trigger resize
    fireEvent.resize(window);
    
    // Should use normal mode
    expect(container.querySelector('.mobile-bottom-nav')).not.toHaveClass('compact');
  });

  it('handles accessibility features', () => {
    renderWithRouter(<MobileBottomNavAntd />);
    
    const nav = screen.getByRole('navigation');
    expect(nav).toHaveAttribute('aria-label', 'Hauptnavigation');
    
    // All items should be focusable
    const navItems = screen.getAllByRole('button');
    navItems.forEach(item => {
      expect(item).toHaveAttribute('tabindex', '0');
    });
  });

  it('supports keyboard navigation', async () => {
    const user = userEvent.setup();
    renderWithRouter(<MobileBottomNavAntd />);
    
    // Tab to first item
    await user.tab();
    expect(screen.getByLabelText('Dashboard')).toHaveFocus();
    
    // Arrow right to next item
    await user.keyboard('{ArrowRight}');
    expect(screen.getByLabelText('Anrufe')).toHaveFocus();
    
    // Enter to select
    await user.keyboard('{Enter}');
    expect(mockNavigate).toHaveBeenCalledWith('/calls');
  });

  it('displays offline indicator when offline', () => {
    // Mock offline state
    Object.defineProperty(navigator, 'onLine', {
      writable: true,
      configurable: true,
      value: false,
    });
    
    renderWithRouter(<MobileBottomNavAntd />);
    
    expect(screen.getByText('Offline')).toBeInTheDocument();
    expect(screen.getByRole('navigation')).toHaveClass('offline');
  });

  it('integrates with app theme', () => {
    // Mock dark theme
    document.documentElement.classList.add('dark');
    
    const { container } = renderWithRouter(<MobileBottomNavAntd />);
    const nav = container.querySelector('.mobile-bottom-nav');
    
    expect(nav).toHaveClass('dark-theme');
    
    // Clean up
    document.documentElement.classList.remove('dark');
  });
});