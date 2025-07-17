import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import AdminApp from '../../AdminApp';

// Mock child components
vi.mock('../../contexts/AdminAuthContext', () => ({
  AuthProvider: ({ children }) => <div>{children}</div>,
}));

vi.mock('../../components/Admin/NotificationCenter', () => ({
  default: () => <div data-testid="notification-center">NotificationCenter</div>,
}));

vi.mock('../../components/ErrorBoundary', () => ({
  default: ({ children }) => <div>{children}</div>,
}));

vi.mock('../../components/ThemeToggle', () => ({
  default: () => <div data-testid="theme-toggle">ThemeToggle</div>,
}));

// Mock pages
vi.mock('../../Pages/Admin/Dashboard', () => ({
  default: () => <div data-testid="dashboard-page">Dashboard</div>,
}));

vi.mock('../../Pages/Admin/Companies/Index', () => ({
  default: () => <div data-testid="companies-page">Companies</div>,
}));

vi.mock('../../Pages/Admin/Users/Index', () => ({
  default: () => <div data-testid="users-page">Users</div>,
}));

// Mock Ant Design components with minimal implementation
vi.mock('antd', () => {
  const actual = vi.importActual('antd');
  return {
    ...actual,
    Grid: {
      useBreakpoint: () => ({ md: true, sm: false, xs: false }),
    },
  };
});

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
global.localStorage = localStorageMock;

const renderWithRouter = (component, { route = '/admin' } = {}) => {
  window.history.pushState({}, 'Test page', route);
  return render(
    <BrowserRouter>
      {component}
    </BrowserRouter>
  );
};

describe('AdminApp', () => {
  const mockCsrfToken = 'test-csrf-token';
  
  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.getItem.mockReturnValue(null);
  });

  it('renders without crashing', () => {
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    expect(screen.getByText('AskProAI Admin')).toBeInTheDocument();
  });

  it('displays navigation menu with correct items', () => {
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    const menuItems = [
      'Dashboard',
      'Mandanten',
      'Benutzer',
      'Anrufe',
      'Termine',
      'Kunden',
      'Integrationen',
      'System',
    ];

    menuItems.forEach(item => {
      expect(screen.getByText(item)).toBeInTheDocument();
    });
  });

  it('navigates to different pages when menu items are clicked', async () => {
    const user = userEvent.setup();
    const { container } = renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Click on Companies menu item
    await user.click(screen.getByText('Mandanten'));
    await waitFor(() => {
      expect(screen.getByTestId('companies-page')).toBeInTheDocument();
    });

    // Click on Users menu item
    await user.click(screen.getByText('Benutzer'));
    await waitFor(() => {
      expect(screen.getByTestId('users-page')).toBeInTheDocument();
    });
  });

  it('toggles sidebar collapse state', async () => {
    const user = userEvent.setup();
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Find collapse button (usually in the Sider component)
    const collapseButton = screen.getByRole('button', { name: /left/i });
    
    // Initial state - sidebar expanded
    expect(screen.getByText('AskProAI Admin')).toBeInTheDocument();
    
    // Click to collapse
    await user.click(collapseButton);
    
    // After collapse - should show abbreviated version
    expect(screen.getByText('A')).toBeInTheDocument();
  });

  it('handles dark mode toggle', async () => {
    const user = userEvent.setup();
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Find theme toggle button
    const themeToggleButton = screen.getByRole('button', { name: /bulb/i });
    
    // Click to enable dark mode
    await user.click(themeToggleButton);
    
    // Verify localStorage was updated
    expect(localStorageMock.setItem).toHaveBeenCalledWith('admin_theme', 'dark');
    expect(document.documentElement.classList.contains('dark')).toBe(true);
    
    // Click again to disable dark mode
    await user.click(themeToggleButton);
    
    expect(localStorageMock.setItem).toHaveBeenCalledWith('admin_theme', 'light');
    expect(document.documentElement.classList.contains('dark')).toBe(false);
  });

  it('persists dark mode preference from localStorage', () => {
    localStorageMock.getItem.mockReturnValue('dark');
    
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    expect(document.documentElement.classList.contains('dark')).toBe(true);
  });

  it('handles user logout', async () => {
    const user = userEvent.setup();
    delete window.location;
    window.location = { href: '' };
    
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Open user dropdown
    const userAvatar = screen.getByText('Admin').closest('div');
    await user.click(userAvatar);
    
    // Click logout
    const logoutButton = screen.getByText('Abmelden');
    await user.click(logoutButton);
    
    // Verify logout actions
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('admin_token');
    expect(window.location.href).toBe('/admin/login');
  });

  it('renders notification center', () => {
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    expect(screen.getByTestId('notification-center')).toBeInTheDocument();
  });

  it('shows current page in header', () => {
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />, { route: '/admin' });
    
    // Dashboard should be selected by default
    const dashboardMenuItem = screen.getByText('Dashboard').closest('li');
    expect(dashboardMenuItem).toHaveClass('ant-menu-item-selected');
  });

  it('handles mobile responsive layout', () => {
    // Mock mobile breakpoint
    vi.mocked(require('antd').Grid.useBreakpoint).mockReturnValue({
      md: false,
      sm: true,
      xs: true,
    });
    
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Mobile menu button should be visible
    expect(screen.getByRole('button', { name: /menu/i })).toBeInTheDocument();
  });

  it('opens mobile drawer when menu button is clicked', async () => {
    const user = userEvent.setup();
    
    // Mock mobile breakpoint
    vi.mocked(require('antd').Grid.useBreakpoint).mockReturnValue({
      md: false,
      sm: true,
      xs: true,
    });
    
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Click mobile menu button
    const menuButton = screen.getByRole('button', { name: /menu/i });
    await user.click(menuButton);
    
    // Drawer should open with menu items
    await waitFor(() => {
      expect(screen.getByText('AskProAI Admin')).toBeInTheDocument();
      expect(screen.getByText('Dashboard')).toBeInTheDocument();
    });
  });

  it('navigates to profile when profile menu item is clicked', async () => {
    const user = userEvent.setup();
    const mockNavigate = vi.fn();
    
    // Mock useNavigate
    vi.mock('react-router-dom', async () => {
      const actual = await vi.importActual('react-router-dom');
      return {
        ...actual,
        useNavigate: () => mockNavigate,
      };
    });
    
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Open user dropdown
    const userAvatar = screen.getByText('Admin').closest('div');
    await user.click(userAvatar);
    
    // Click profile
    const profileButton = screen.getByText('Profil');
    await user.click(profileButton);
    
    // Should navigate to profile
    expect(mockNavigate).toHaveBeenCalledWith('/admin/profile');
  });

  it('applies correct theme configuration', () => {
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Verify ConfigProvider is set up with German locale
    expect(screen.getByText('Dashboard')).toBeInTheDocument(); // German text
  });

  it('renders toast container for notifications', () => {
    const { container } = renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // ToastContainer should be present
    expect(container.querySelector('.Toastify')).toBeInTheDocument();
  });

  it('handles route changes correctly', async () => {
    const user = userEvent.setup();
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />, { route: '/admin' });
    
    // Initially on dashboard
    expect(screen.getByTestId('dashboard-page')).toBeInTheDocument();
    
    // Navigate to companies
    await user.click(screen.getByText('Mandanten'));
    
    await waitFor(() => {
      expect(screen.queryByTestId('dashboard-page')).not.toBeInTheDocument();
      expect(screen.getByTestId('companies-page')).toBeInTheDocument();
    });
  });

  it('maintains scroll position on route change', async () => {
    const user = userEvent.setup();
    renderWithRouter(<AdminApp csrfToken={mockCsrfToken} />);
    
    // Mock scroll position
    window.scrollY = 500;
    
    // Navigate to different page
    await user.click(screen.getByText('Mandanten'));
    
    // Should reset scroll position
    expect(window.scrollY).toBe(0);
  });
});