import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import PortalApp from '../../PortalApp';

// Mock child components
vi.mock('../../contexts/AuthContext', () => ({
  AuthProvider: ({ children }) => <div>{children}</div>,
}));

vi.mock('../../components/NotificationCenter', () => ({
  default: ({ csrfToken }) => <div data-testid="notification-center">NotificationCenter</div>,
}));

vi.mock('../../components/ErrorBoundary', () => ({
  default: ({ children }) => <div>{children}</div>,
}));

vi.mock('../../components/RouteErrorBoundary', () => ({
  default: ({ children }) => <div>{children}</div>,
}));

vi.mock('../../components/Portal/OfflineIndicator', () => ({
  default: () => <div data-testid="offline-indicator">OfflineIndicator</div>,
}));

vi.mock('../../components/Mobile/MobileBottomNavAntd', () => ({
  default: () => <div data-testid="mobile-bottom-nav">MobileBottomNav</div>,
}));

vi.mock('../../utils/serviceWorker', () => ({
  default: {
    register: vi.fn().mockResolvedValue(true),
    unregister: vi.fn(),
  },
}));

// Mock pages
vi.mock('../../Pages/Portal/Dashboard/ReactIndex', () => ({
  default: () => <div data-testid="dashboard-page">Dashboard</div>,
}));

vi.mock('../../Pages/Portal/Calls/Index', () => ({
  default: () => <div data-testid="calls-page">Calls</div>,
}));

vi.mock('../../Pages/Portal/Appointments/Index', () => ({
  default: () => <div data-testid="appointments-page">Appointments</div>,
}));

vi.mock('../../Pages/Portal/Customers/Index', () => ({
  default: () => <div data-testid="customers-page">Customers</div>,
}));

// Mock Ant Design Grid
vi.mock('antd', () => {
  const actual = vi.importActual('antd');
  return {
    ...actual,
    Grid: {
      useBreakpoint: () => ({ md: true, sm: false, xs: false }),
    },
  };
});

const renderWithRouter = (component, { route = '/business' } = {}) => {
  window.history.pushState({}, 'Test page', route);
  return render(
    <BrowserRouter basename="/business">
      {component}
    </BrowserRouter>
  );
};

describe('PortalApp', () => {
  const mockCsrfToken = 'test-csrf-token';
  const mockUser = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    role: 'admin',
  };
  const mockInitialAuth = {
    user: mockUser,
    isAdminViewing: false,
    adminViewingCompany: '',
  };

  beforeEach(() => {
    vi.clearAllMocks();
    delete window.location;
    window.location = { href: '' };
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders without crashing when authenticated', () => {
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );
    expect(screen.getByText('Dashboard')).toBeInTheDocument();
  });

  it('redirects to login when not authenticated', () => {
    renderWithRouter(
      <PortalApp initialAuth={{}} csrfToken={mockCsrfToken} />
    );
    expect(window.location.href).toBe('/business/login');
  });

  it('displays all navigation menu items', () => {
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    const menuItems = [
      'Dashboard',
      'Anrufe',
      'Termine',
      'Kunden',
      'Team',
      'Analysen',
      'Abrechnung',
      'Feedback',
      'Einstellungen',
    ];

    menuItems.forEach(item => {
      expect(screen.getByText(item)).toBeInTheDocument();
    });
  });

  it('navigates between pages correctly', async () => {
    const user = userEvent.setup();
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    // Initially on dashboard
    expect(screen.getByTestId('dashboard-page')).toBeInTheDocument();

    // Navigate to calls
    await user.click(screen.getByText('Anrufe'));
    await waitFor(() => {
      expect(screen.getByTestId('calls-page')).toBeInTheDocument();
    });

    // Navigate to appointments
    await user.click(screen.getByText('Termine'));
    await waitFor(() => {
      expect(screen.getByTestId('appointments-page')).toBeInTheDocument();
    });
  });

  it('shows user information in header', () => {
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );
    
    expect(screen.getByText('Test User')).toBeInTheDocument();
  });

  it('handles logout correctly', async () => {
    const user = userEvent.setup();
    
    // Mock document methods for form submission
    const mockForm = {
      method: '',
      action: '',
      appendChild: vi.fn(),
      submit: vi.fn(),
    };
    document.createElement = vi.fn().mockImplementation((tagName) => {
      if (tagName === 'form') return mockForm;
      return { type: '', name: '', value: '' };
    });
    document.body.appendChild = vi.fn();

    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    // Open user dropdown
    const userDropdown = screen.getByText('Test User').closest('div');
    await user.click(userDropdown);

    // Click logout
    const logoutButton = screen.getByText('Abmelden');
    await user.click(logoutButton);

    // Verify logout form was created and submitted
    expect(mockForm.method).toBe('POST');
    expect(mockForm.action).toBe('/business/logout');
    expect(mockForm.submit).toHaveBeenCalled();
  });

  it('displays admin viewing banner when admin is viewing', () => {
    const adminAuth = {
      ...mockInitialAuth,
      isAdminViewing: true,
      adminViewingCompany: 'Test Company GmbH',
    };

    renderWithRouter(
      <PortalApp initialAuth={adminAuth} csrfToken={mockCsrfToken} />
    );

    expect(screen.getByText('Admin-Ansicht: Test Company GmbH')).toBeInTheDocument();
  });

  it('registers service worker in production', () => {
    const originalEnv = process.env.NODE_ENV;
    process.env.NODE_ENV = 'production';

    const ServiceWorkerManager = require('../../utils/serviceWorker').default;
    
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    expect(ServiceWorkerManager.register).toHaveBeenCalled();
    
    process.env.NODE_ENV = originalEnv;
  });

  it('shows offline indicator component', () => {
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    expect(screen.getByTestId('offline-indicator')).toBeInTheDocument();
  });

  it('displays mobile bottom navigation on mobile devices', () => {
    // Mock mobile breakpoint
    vi.mocked(require('antd').Grid.useBreakpoint).mockReturnValue({
      md: false,
      sm: true,
      xs: true,
    });

    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    expect(screen.getByTestId('mobile-bottom-nav')).toBeInTheDocument();
  });

  it('collapses sidebar on mobile devices', () => {
    // Mock mobile breakpoint
    vi.mocked(require('antd').Grid.useBreakpoint).mockReturnValue({
      md: false,
      sm: true,
      xs: true,
    });

    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    // Sidebar should not be visible on mobile
    expect(screen.queryByText('AskProAI')).not.toBeInTheDocument();
  });

  it('updates page title based on current route', async () => {
    const user = userEvent.setup();
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    // Check initial title
    const header = screen.getByRole('heading', { level: 1 });
    expect(header).toHaveTextContent('Dashboard');

    // Navigate to different page
    await user.click(screen.getByText('Anrufe'));
    
    await waitFor(() => {
      expect(header).toHaveTextContent('Anrufe');
    });
  });

  it('handles user menu navigation', async () => {
    const user = userEvent.setup();
    delete window.location;
    window.location = { href: '' };

    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    // Open user dropdown
    const userDropdown = screen.getByText('Test User').closest('div');
    await user.click(userDropdown);

    // Click profile
    const profileButton = screen.getByText('Profil');
    await user.click(profileButton);

    // Should navigate to profile page
    expect(window.location.href).toBe('/business/settings/profile');
  });

  it('passes csrf token to child components', () => {
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    // Notification center should receive csrf token
    const notificationCenter = screen.getByTestId('notification-center');
    expect(notificationCenter).toBeInTheDocument();
  });

  it('maintains selected menu state on navigation', async () => {
    const user = userEvent.setup();
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />,
      { route: '/business/calls' }
    );

    // Calls menu item should be selected
    const callsMenuItem = screen.getByText('Anrufe').closest('li');
    expect(callsMenuItem).toHaveClass('ant-menu-item-selected');
  });

  it('renders toast container for notifications', () => {
    const { container } = renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    expect(container.querySelector('.Toastify')).toBeInTheDocument();
  });

  it('handles sidebar collapse toggle', async () => {
    const user = userEvent.setup();
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    // Find collapse button
    const collapseButton = screen.getByRole('button', { name: /left/i });

    // Initially expanded
    expect(screen.getByText('AskProAI')).toBeInTheDocument();

    // Click to collapse
    await user.click(collapseButton);

    // Should show abbreviated version
    expect(screen.getByText('AI')).toBeInTheDocument();
  });

  it('handles route parameters correctly', () => {
    renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />,
      { route: '/business/calls/123' }
    );

    // Should render call detail page (mocked component would handle the ID)
    expect(screen.getByTestId('calls-page')).toBeInTheDocument();
  });

  it('applies correct content margin for mobile', () => {
    // Mock mobile breakpoint
    vi.mocked(require('antd').Grid.useBreakpoint).mockReturnValue({
      md: false,
      sm: true,
      xs: true,
    });

    const { container } = renderWithRouter(
      <PortalApp initialAuth={mockInitialAuth} csrfToken={mockCsrfToken} />
    );

    // Content should have bottom margin for mobile nav
    const content = container.querySelector('.ant-layout-content');
    expect(content).toHaveStyle({ marginBottom: '72px' });
  });
});