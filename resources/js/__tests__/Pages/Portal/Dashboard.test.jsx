import React from 'react';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Dashboard from '../../../Pages/Portal/Dashboard/ReactIndex';
import { server } from '../../mocks/server';
import { rest } from 'msw';

// Mock child components
vi.mock('../../../components/Portal/DashboardWidgets', () => ({
  StatsOverview: () => <div data-testid="stats-overview">Stats Overview</div>,
  RecentCalls: () => <div data-testid="recent-calls">Recent Calls</div>,
  UpcomingAppointments: () => <div data-testid="upcoming-appointments">Upcoming Appointments</div>,
  QuickActions: () => <div data-testid="quick-actions">Quick Actions</div>,
}));

vi.mock('../../../components/charts/CallVolumeChart', () => ({
  default: () => <div data-testid="call-volume-chart">Call Volume Chart</div>,
}));

vi.mock('../../../components/charts/AppointmentTrendsChart', () => ({
  default: () => <div data-testid="appointment-trends-chart">Appointment Trends Chart</div>,
}));

// Mock API responses
const mockDashboardData = {
  stats: {
    totalCalls: 152,
    totalAppointments: 48,
    newCustomers: 23,
    revenue: 4250.50,
    callsToday: 12,
    appointmentsToday: 8,
    conversionRate: 0.68,
    avgCallDuration: 245,
  },
  recentCalls: [
    {
      id: 1,
      customer_name: 'Max Mustermann',
      phone_number: '+49123456789',
      duration: 180,
      created_at: '2024-01-15T10:30:00Z',
      status: 'completed',
    },
    {
      id: 2,
      customer_name: 'Anna Schmidt',
      phone_number: '+49987654321',
      duration: 240,
      created_at: '2024-01-15T09:15:00Z',
      status: 'completed',
    },
  ],
  upcomingAppointments: [
    {
      id: 1,
      customer_name: 'Peter Weber',
      service_name: 'Beratung',
      appointment_datetime: '2024-01-15T14:00:00Z',
      duration: 60,
      status: 'scheduled',
    },
    {
      id: 2,
      customer_name: 'Lisa Meyer',
      service_name: 'Erstgespräch',
      appointment_datetime: '2024-01-15T15:30:00Z',
      duration: 30,
      status: 'confirmed',
    },
  ],
  chartData: {
    callVolume: {
      labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
      data: [45, 52, 38, 65, 42, 28, 15],
    },
    appointmentTrends: {
      labels: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun'],
      scheduled: [120, 135, 125, 140, 155, 162],
      completed: [110, 128, 120, 132, 148, 156],
      noShow: [10, 7, 5, 8, 7, 6],
    },
  },
};

describe('Dashboard', () => {
  const mockCsrfToken = 'test-csrf-token';

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders loading state initially', () => {
    render(<Dashboard csrfToken={mockCsrfToken} />);
    expect(screen.getByText(/loading/i)).toBeInTheDocument();
  });

  it('fetches and displays dashboard data', async () => {
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        return res(ctx.json(mockDashboardData));
      })
    );

    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    // Check if all widgets are rendered
    expect(screen.getByTestId('stats-overview')).toBeInTheDocument();
    expect(screen.getByTestId('recent-calls')).toBeInTheDocument();
    expect(screen.getByTestId('upcoming-appointments')).toBeInTheDocument();
    expect(screen.getByTestId('call-volume-chart')).toBeInTheDocument();
  });

  it('handles API errors gracefully', async () => {
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        return res(ctx.status(500), ctx.json({ message: 'Server error' }));
      })
    );

    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.getByText(/error loading dashboard/i)).toBeInTheDocument();
    });
  });

  it('refreshes data when refresh button is clicked', async () => {
    let callCount = 0;
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        callCount++;
        return res(ctx.json({ ...mockDashboardData, callCount }));
      })
    );

    const user = userEvent.setup();
    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    // Find and click refresh button
    const refreshButton = screen.getByRole('button', { name: /refresh/i });
    await user.click(refreshButton);

    // Verify API was called again
    await waitFor(() => {
      expect(callCount).toBe(2);
    });
  });

  it('displays correct statistics', async () => {
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        return res(ctx.json(mockDashboardData));
      })
    );

    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    // Check if stats are displayed
    expect(screen.getByText('152')).toBeInTheDocument(); // Total calls
    expect(screen.getByText('48')).toBeInTheDocument(); // Total appointments
    expect(screen.getByText('23')).toBeInTheDocument(); // New customers
    expect(screen.getByText('€4,250.50')).toBeInTheDocument(); // Revenue
  });

  it('filters data by date range', async () => {
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        const dateFrom = req.url.searchParams.get('date_from');
        const dateTo = req.url.searchParams.get('date_to');
        
        // Return different data based on date range
        if (dateFrom && dateTo) {
          return res(ctx.json({
            ...mockDashboardData,
            stats: {
              ...mockDashboardData.stats,
              totalCalls: 75, // Half the calls for custom range
            },
          }));
        }
        
        return res(ctx.json(mockDashboardData));
      })
    );

    const user = userEvent.setup();
    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    // Open date range picker
    const dateRangeButton = screen.getByRole('button', { name: /date range/i });
    await user.click(dateRangeButton);

    // Select last 7 days
    const last7Days = screen.getByText(/last 7 days/i);
    await user.click(last7Days);

    // Verify filtered data is displayed
    await waitFor(() => {
      expect(screen.getByText('75')).toBeInTheDocument(); // Filtered calls
    });
  });

  it('exports dashboard data when export button is clicked', async () => {
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        return res(ctx.json(mockDashboardData));
      }),
      rest.post('/api/dashboard/export', (req, res, ctx) => {
        return res(
          ctx.set('Content-Type', 'application/pdf'),
          ctx.set('Content-Disposition', 'attachment; filename="dashboard-report.pdf"'),
          ctx.body('PDF content')
        );
      })
    );

    const user = userEvent.setup();
    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    // Mock download
    const mockCreateElement = vi.spyOn(document, 'createElement');
    const mockClick = vi.fn();
    mockCreateElement.mockReturnValue({ click: mockClick, href: '', download: '' });

    // Click export button
    const exportButton = screen.getByRole('button', { name: /export/i });
    await user.click(exportButton);

    // Verify download was triggered
    await waitFor(() => {
      expect(mockClick).toHaveBeenCalled();
    });
  });

  it('shows empty state when no data is available', async () => {
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        return res(ctx.json({
          stats: {
            totalCalls: 0,
            totalAppointments: 0,
            newCustomers: 0,
            revenue: 0,
          },
          recentCalls: [],
          upcomingAppointments: [],
          chartData: {
            callVolume: { labels: [], data: [] },
            appointmentTrends: { labels: [], scheduled: [], completed: [], noShow: [] },
          },
        }));
      })
    );

    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    // Check for empty state messages
    expect(screen.getByText(/no recent calls/i)).toBeInTheDocument();
    expect(screen.getByText(/no upcoming appointments/i)).toBeInTheDocument();
  });

  it('auto-refreshes data at intervals', async () => {
    let callCount = 0;
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        callCount++;
        return res(ctx.json(mockDashboardData));
      })
    );

    vi.useFakeTimers();
    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    expect(callCount).toBe(1);

    // Fast-forward 5 minutes (auto-refresh interval)
    vi.advanceTimersByTime(5 * 60 * 1000);

    await waitFor(() => {
      expect(callCount).toBe(2);
    });

    vi.useRealTimers();
  });

  it('handles permission errors for restricted data', async () => {
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        return res(
          ctx.status(403),
          ctx.json({ message: 'Insufficient permissions' })
        );
      })
    );

    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.getByText(/insufficient permissions/i)).toBeInTheDocument();
    });
  });

  it('displays real-time notifications for new calls', async () => {
    server.use(
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        return res(ctx.json(mockDashboardData));
      })
    );

    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    // Simulate WebSocket message for new call
    const event = new CustomEvent('newCall', {
      detail: {
        id: 3,
        customer_name: 'Neuer Kunde',
        phone_number: '+49111222333',
      },
    });
    window.dispatchEvent(event);

    // Check if notification appears
    await waitFor(() => {
      expect(screen.getByText(/new incoming call/i)).toBeInTheDocument();
    });
  });

  it('respects user preferences for dashboard layout', async () => {
    // Mock user preferences
    const mockPreferences = {
      dashboardLayout: 'compact',
      showCharts: false,
      autoRefresh: false,
    };

    server.use(
      rest.get('/api/user/preferences', (req, res, ctx) => {
        return res(ctx.json(mockPreferences));
      }),
      rest.get('/api/dashboard/overview', (req, res, ctx) => {
        return res(ctx.json(mockDashboardData));
      })
    );

    render(<Dashboard csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
    });

    // Charts should not be displayed based on preferences
    expect(screen.queryByTestId('call-volume-chart')).not.toBeInTheDocument();
    expect(screen.queryByTestId('appointment-trends-chart')).not.toBeInTheDocument();
  });
});