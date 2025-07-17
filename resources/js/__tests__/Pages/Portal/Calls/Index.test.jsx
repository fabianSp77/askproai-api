import React from 'react';
import { render, screen, waitFor, fireEvent, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import CallsIndex from '../../../../Pages/Portal/Calls/Index';
import { server } from '../../../mocks/server';
import { rest } from 'msw';

// Mock components
vi.mock('../../../../components/Portal/CallFilters', () => ({
  default: ({ onFilter }) => (
    <div data-testid="call-filters">
      <button onClick={() => onFilter({ status: 'completed' })}>
        Filter Completed
      </button>
    </div>
  ),
}));

vi.mock('../../../../components/CallDetailView', () => ({
  default: ({ call }) => (
    <div data-testid="call-detail-view">
      Call Detail: {call.id}
    </div>
  ),
}));

const mockCallsData = {
  data: [
    {
      id: 1,
      customer_name: 'Max Mustermann',
      phone_number: '+49123456789',
      duration: 180,
      created_at: '2024-01-15T10:30:00Z',
      status: 'completed',
      transcript: 'Hello, I would like to book an appointment...',
      recording_url: 'https://example.com/recording1.mp3',
      ai_summary: 'Customer wants to book appointment for next week',
    },
    {
      id: 2,
      customer_name: 'Anna Schmidt',
      phone_number: '+49987654321',
      duration: 240,
      created_at: '2024-01-15T09:15:00Z',
      status: 'completed',
      transcript: 'I need to reschedule my appointment...',
      recording_url: 'https://example.com/recording2.mp3',
      ai_summary: 'Customer needs to reschedule existing appointment',
    },
    {
      id: 3,
      customer_name: 'Unknown',
      phone_number: '+49555666777',
      duration: 45,
      created_at: '2024-01-15T08:00:00Z',
      status: 'no_answer',
      transcript: null,
      recording_url: null,
      ai_summary: null,
    },
  ],
  meta: {
    current_page: 1,
    last_page: 5,
    per_page: 20,
    total: 95,
  },
};

const renderWithRouter = (component) => {
  return render(
    <BrowserRouter>
      {component}
    </BrowserRouter>
  );
};

describe('CallsIndex', () => {
  const mockCsrfToken = 'test-csrf-token';

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders loading state initially', () => {
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);
    expect(screen.getByText(/loading calls/i)).toBeInTheDocument();
  });

  it('fetches and displays calls list', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        return res(ctx.json(mockCallsData));
      })
    );

    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Check if calls are displayed
    expect(screen.getByText('Max Mustermann')).toBeInTheDocument();
    expect(screen.getByText('Anna Schmidt')).toBeInTheDocument();
    expect(screen.getByText('Unknown')).toBeInTheDocument();
  });

  it('displays call duration in human-readable format', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        return res(ctx.json(mockCallsData));
      })
    );

    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Check duration formatting (180 seconds = 3:00)
    expect(screen.getByText('3:00')).toBeInTheDocument();
    // 240 seconds = 4:00
    expect(screen.getByText('4:00')).toBeInTheDocument();
  });

  it('handles pagination correctly', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        const page = req.url.searchParams.get('page') || '1';
        return res(ctx.json({
          ...mockCallsData,
          meta: {
            ...mockCallsData.meta,
            current_page: parseInt(page),
          },
        }));
      })
    );

    const user = userEvent.setup();
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Find pagination controls
    const nextButton = screen.getByRole('button', { name: /next/i });
    await user.click(nextButton);

    // Verify page changed
    await waitFor(() => {
      expect(screen.getByText(/page 2/i)).toBeInTheDocument();
    });
  });

  it('filters calls by status', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        const status = req.url.searchParams.get('status');
        
        if (status === 'completed') {
          return res(ctx.json({
            data: mockCallsData.data.filter(call => call.status === 'completed'),
            meta: { ...mockCallsData.meta, total: 2 },
          }));
        }
        
        return res(ctx.json(mockCallsData));
      })
    );

    const user = userEvent.setup();
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Apply filter
    const filterButton = screen.getByText('Filter Completed');
    await user.click(filterButton);

    // Verify filtered results
    await waitFor(() => {
      expect(screen.getByText('Max Mustermann')).toBeInTheDocument();
      expect(screen.getByText('Anna Schmidt')).toBeInTheDocument();
      expect(screen.queryByText('Unknown')).not.toBeInTheDocument();
    });
  });

  it('searches calls by customer name or phone', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        const search = req.url.searchParams.get('search');
        
        if (search === 'Max') {
          return res(ctx.json({
            data: [mockCallsData.data[0]],
            meta: { ...mockCallsData.meta, total: 1 },
          }));
        }
        
        return res(ctx.json(mockCallsData));
      })
    );

    const user = userEvent.setup();
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Search for "Max"
    const searchInput = screen.getByPlaceholderText(/search calls/i);
    await user.type(searchInput, 'Max');
    await user.keyboard('{Enter}');

    // Verify search results
    await waitFor(() => {
      expect(screen.getByText('Max Mustermann')).toBeInTheDocument();
      expect(screen.queryByText('Anna Schmidt')).not.toBeInTheDocument();
    });
  });

  it('plays call recording when play button is clicked', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        return res(ctx.json(mockCallsData));
      })
    );

    const user = userEvent.setup();
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Mock audio element
    const mockPlay = vi.fn();
    const mockPause = vi.fn();
    window.HTMLMediaElement.prototype.play = mockPlay;
    window.HTMLMediaElement.prototype.pause = mockPause;

    // Find and click play button for first call
    const playButtons = screen.getAllByRole('button', { name: /play/i });
    await user.click(playButtons[0]);

    // Verify audio started playing
    expect(mockPlay).toHaveBeenCalled();
  });

  it('displays call transcript in modal', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        return res(ctx.json(mockCallsData));
      })
    );

    const user = userEvent.setup();
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Click transcript button
    const transcriptButtons = screen.getAllByRole('button', { name: /transcript/i });
    await user.click(transcriptButtons[0]);

    // Verify transcript modal appears
    await waitFor(() => {
      expect(screen.getByText('Hello, I would like to book an appointment...')).toBeInTheDocument();
    });
  });

  it('exports calls data when export button is clicked', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        return res(ctx.json(mockCallsData));
      }),
      rest.post('/api/calls/export', (req, res, ctx) => {
        return res(
          ctx.set('Content-Type', 'text/csv'),
          ctx.set('Content-Disposition', 'attachment; filename="calls.csv"'),
          ctx.body('id,customer_name,phone_number,duration\n1,Max Mustermann,+49123456789,180')
        );
      })
    );

    const user = userEvent.setup();
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Mock download
    const mockCreateElement = vi.spyOn(document, 'createElement');
    const mockClick = vi.fn();
    mockCreateElement.mockReturnValue({ click: mockClick, href: '', download: '' });

    // Click export button
    const exportButton = screen.getByRole('button', { name: /export/i });
    await user.click(exportButton);

    // Select CSV format
    const csvOption = screen.getByText('CSV');
    await user.click(csvOption);

    // Verify download was triggered
    await waitFor(() => {
      expect(mockClick).toHaveBeenCalled();
    });
  });

  it('shows call status with appropriate styling', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        return res(ctx.json(mockCallsData));
      })
    );

    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Check status badges
    const completedBadges = screen.getAllByText('completed');
    expect(completedBadges[0]).toHaveClass('badge-success');

    const noAnswerBadge = screen.getByText('no_answer');
    expect(noAnswerBadge).toHaveClass('badge-warning');
  });

  it('navigates to call detail page when row is clicked', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        return res(ctx.json(mockCallsData));
      })
    );

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

    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Click on a call row
    const callRow = screen.getByText('Max Mustermann').closest('tr');
    await user.click(callRow);

    // Should navigate to detail page
    expect(mockNavigate).toHaveBeenCalledWith('/calls/1');
  });

  it('handles empty state when no calls exist', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        return res(ctx.json({
          data: [],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
          },
        }));
      })
    );

    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Check empty state message
    expect(screen.getByText(/no calls found/i)).toBeInTheDocument();
    expect(screen.getByText(/calls will appear here once customers call/i)).toBeInTheDocument();
  });

  it('refreshes call list when refresh button is clicked', async () => {
    let callCount = 0;
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        callCount++;
        return res(ctx.json(mockCallsData));
      })
    );

    const user = userEvent.setup();
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    expect(callCount).toBe(1);

    // Click refresh
    const refreshButton = screen.getByRole('button', { name: /refresh/i });
    await user.click(refreshButton);

    await waitFor(() => {
      expect(callCount).toBe(2);
    });
  });

  it('sorts calls by different columns', async () => {
    server.use(
      rest.get('/api/calls', (req, res, ctx) => {
        const sortBy = req.url.searchParams.get('sort_by');
        const sortOrder = req.url.searchParams.get('sort_order');
        
        let sortedData = [...mockCallsData.data];
        if (sortBy === 'duration') {
          sortedData.sort((a, b) => 
            sortOrder === 'desc' ? b.duration - a.duration : a.duration - b.duration
          );
        }
        
        return res(ctx.json({ ...mockCallsData, data: sortedData }));
      })
    );

    const user = userEvent.setup();
    renderWithRouter(<CallsIndex csrfToken={mockCsrfToken} />);

    await waitFor(() => {
      expect(screen.queryByText(/loading calls/i)).not.toBeInTheDocument();
    });

    // Click duration column header to sort
    const durationHeader = screen.getByText(/duration/i);
    await user.click(durationHeader);

    // Verify sorting changed
    await waitFor(() => {
      const durations = screen.getAllByText(/\d+:\d+/);
      expect(durations[0]).toHaveTextContent('4:00'); // Longest first
    });
  });
});