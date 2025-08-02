import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import { QueryClient, QueryClientProvider } from 'react-query';
import { BrowserRouter } from 'react-router-dom';
import SystemHealthMonitoring from '../../../resources/js/Pages/Portal/SystemHealthMonitoring';
import axios from 'axios';

// Mock axios
vi.mock('axios');

// Mock recharts
vi.mock('recharts', () => ({
    LineChart: ({ children }) => <div data-testid="line-chart">{children}</div>,
    Line: () => null,
    AreaChart: ({ children }) => <div data-testid="area-chart">{children}</div>,
    Area: () => null,
    BarChart: ({ children }) => <div data-testid="bar-chart">{children}</div>,
    Bar: () => null,
    XAxis: () => null,
    YAxis: () => null,
    CartesianGrid: () => null,
    Tooltip: () => null,
    Legend: () => null,
    ResponsiveContainer: ({ children }) => <div>{children}</div>
}));

const createTestQueryClient = () => new QueryClient({
    defaultOptions: {
        queries: { retry: false },
        mutations: { retry: false }
    }
});

const TestWrapper = ({ children }) => (
    <QueryClientProvider client={createTestQueryClient()}>
        <BrowserRouter>
            {children}
        </BrowserRouter>
    </QueryClientProvider>
);

describe('SystemHealthMonitoring', () => {
    const mockHealthData = {
        data: {
            status: 'healthy',
            timestamp: '2025-08-01T10:00:00Z',
            components: {
                database: {
                    status: 'healthy',
                    latency: 12,
                    message: 'Connected to primary'
                },
                cache: {
                    status: 'healthy',
                    memory_usage: '45%',
                    hit_rate: '89%'
                },
                queue: {
                    status: 'warning',
                    pending_jobs: 1250,
                    failed_jobs: 5,
                    message: 'High queue depth'
                },
                filesystem: {
                    status: 'healthy',
                    disk_usage: '62%',
                    free_space: '38GB'
                },
                external_services: {
                    calcom: {
                        status: 'healthy',
                        latency: 145,
                        success_rate: '99.8%'
                    },
                    retell: {
                        status: 'degraded',
                        latency: 890,
                        success_rate: '95.2%',
                        message: 'Increased latency detected'
                    },
                    stripe: {
                        status: 'healthy',
                        latency: 98,
                        success_rate: '100%'
                    }
                }
            },
            metrics: {
                cpu_usage: 45.2,
                memory_usage: 68.5,
                active_connections: 152,
                requests_per_minute: 3420,
                average_response_time: 125
            },
            errors: [
                {
                    timestamp: '2025-08-01T09:55:00Z',
                    level: 'warning',
                    message: 'Queue depth exceeding threshold',
                    context: { queue: 'default', depth: 1250 }
                },
                {
                    timestamp: '2025-08-01T09:50:00Z',
                    level: 'error',
                    message: 'Retell API timeout',
                    context: { endpoint: '/v2/calls', timeout: 5000 }
                }
            ],
            history: [
                { timestamp: '2025-08-01T09:55:00Z', status: 'healthy', uptime: 99.9 },
                { timestamp: '2025-08-01T09:50:00Z', status: 'warning', uptime: 99.8 },
                { timestamp: '2025-08-01T09:45:00Z', status: 'healthy', uptime: 99.9 }
            ]
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        axios.get.mockResolvedValue({ data: mockHealthData });
    });

    it('should render system health dashboard', async () => {
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('System Health Monitoring')).toBeInTheDocument();
            expect(screen.getByText('Overall Status: Healthy')).toBeInTheDocument();
        });
    });

    it('should display component health statuses', async () => {
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            // Database
            expect(screen.getByText('Database')).toBeInTheDocument();
            expect(screen.getByText('Connected to primary')).toBeInTheDocument();
            expect(screen.getByText('12ms')).toBeInTheDocument();

            // Cache
            expect(screen.getByText('Cache')).toBeInTheDocument();
            expect(screen.getByText('Hit Rate: 89%')).toBeInTheDocument();

            // Queue (warning status)
            expect(screen.getByText('Queue')).toBeInTheDocument();
            expect(screen.getByText('High queue depth')).toBeInTheDocument();
            expect(screen.getByText('1,250 pending')).toBeInTheDocument();
        });
    });

    it('should display external service statuses', async () => {
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Cal.com')).toBeInTheDocument();
            expect(screen.getByText('99.8%')).toBeInTheDocument();

            expect(screen.getByText('Retell.ai')).toBeInTheDocument();
            expect(screen.getByText('Degraded')).toBeInTheDocument();
            expect(screen.getByText('Increased latency detected')).toBeInTheDocument();

            expect(screen.getByText('Stripe')).toBeInTheDocument();
            expect(screen.getByText('100%')).toBeInTheDocument();
        });
    });

    it('should display system metrics', async () => {
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('CPU Usage')).toBeInTheDocument();
            expect(screen.getByText('45.2%')).toBeInTheDocument();

            expect(screen.getByText('Memory Usage')).toBeInTheDocument();
            expect(screen.getByText('68.5%')).toBeInTheDocument();

            expect(screen.getByText('Active Connections')).toBeInTheDocument();
            expect(screen.getByText('152')).toBeInTheDocument();

            expect(screen.getByText('Requests/Min')).toBeInTheDocument();
            expect(screen.getByText('3,420')).toBeInTheDocument();
        });
    });

    it('should display error log', async () => {
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Recent Errors')).toBeInTheDocument();
            expect(screen.getByText('Queue depth exceeding threshold')).toBeInTheDocument();
            expect(screen.getByText('Retell API timeout')).toBeInTheDocument();
        });
    });

    it('should auto-refresh data', async () => {
        vi.useFakeTimers();
        
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('System Health Monitoring')).toBeInTheDocument();
        });

        // Initial call
        expect(axios.get).toHaveBeenCalledTimes(1);

        // Fast forward 30 seconds
        vi.advanceTimersByTime(30 * 1000);

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledTimes(2);
        });

        vi.useRealTimers();
    });

    it('should handle manual refresh', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('System Health Monitoring')).toBeInTheDocument();
        });

        const refreshButton = screen.getByLabelText('Refresh');
        await user.click(refreshButton);

        expect(axios.get).toHaveBeenCalledTimes(2);
    });

    it('should toggle auto-refresh', async () => {
        const user = userEvent.setup();
        vi.useFakeTimers();
        
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('System Health Monitoring')).toBeInTheDocument();
        });

        // Toggle auto-refresh off
        const autoRefreshToggle = screen.getByLabelText('Auto-refresh');
        await user.click(autoRefreshToggle);

        // Fast forward 30 seconds
        vi.advanceTimersByTime(30 * 1000);

        // Should still be 1 call (no auto-refresh)
        expect(axios.get).toHaveBeenCalledTimes(1);

        vi.useRealTimers();
    });

    it('should show alert for critical issues', async () => {
        axios.get.mockResolvedValue({
            data: {
                ...mockHealthData,
                data: {
                    ...mockHealthData.data,
                    status: 'critical',
                    components: {
                        ...mockHealthData.data.components,
                        database: {
                            status: 'critical',
                            message: 'Connection lost'
                        }
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('CRITICAL ISSUE DETECTED')).toBeInTheDocument();
            expect(screen.getByText('Database: Connection lost')).toBeInTheDocument();
        });
    });

    it('should export health report', async () => {
        const user = userEvent.setup();
        
        // Mock blob download
        global.URL.createObjectURL = vi.fn(() => 'blob:url');
        const mockClick = vi.fn();
        const mockRemove = vi.fn();
        
        vi.spyOn(document, 'createElement').mockImplementation((tag) => {
            if (tag === 'a') {
                return {
                    click: mockClick,
                    remove: mockRemove,
                    setAttribute: vi.fn(),
                    style: {}
                };
            }
            return document.createElement(tag);
        });

        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('System Health Monitoring')).toBeInTheDocument();
        });

        const exportButton = screen.getByText('Export Report');
        await user.click(exportButton);

        expect(mockClick).toHaveBeenCalled();
    });

    it('should filter components by status', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('System Health Monitoring')).toBeInTheDocument();
        });

        // Filter by warning status
        const filterSelect = screen.getByLabelText('Filter by status');
        await user.click(filterSelect);
        await user.click(screen.getByText('Warning'));

        // Should only show components with warning status
        expect(screen.getByText('Queue')).toBeInTheDocument();
        expect(screen.queryByText('Database')).not.toBeInTheDocument();
    });

    it('should show historical uptime chart', async () => {
        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Uptime History')).toBeInTheDocument();
            expect(screen.getByTestId('line-chart')).toBeInTheDocument();
        });
    });

    it('should handle connection to monitoring WebSocket', async () => {
        const mockWebSocket = {
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            close: vi.fn()
        };
        
        global.WebSocket = vi.fn(() => mockWebSocket);

        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('System Health Monitoring')).toBeInTheDocument();
        });

        // Should create WebSocket connection
        expect(global.WebSocket).toHaveBeenCalledWith(
            expect.stringContaining('ws://'),
            expect.any(Array)
        );

        // Simulate real-time update
        const messageHandler = mockWebSocket.addEventListener.mock.calls.find(
            call => call[0] === 'message'
        )?.[1];

        messageHandler({
            data: JSON.stringify({
                type: 'health_update',
                data: {
                    component: 'queue',
                    status: 'healthy',
                    pending_jobs: 150
                }
            })
        });

        await waitFor(() => {
            expect(screen.getByText('150 pending')).toBeInTheDocument();
        });
    });

    it('should show loading state', () => {
        axios.get.mockImplementation(() => new Promise(() => {})); // Never resolves

        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        expect(screen.getByText('Loading system health...')).toBeInTheDocument();
    });

    it('should show error state', async () => {
        axios.get.mockRejectedValue(new Error('Failed to fetch health data'));

        render(
            <TestWrapper>
                <SystemHealthMonitoring />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Failed to load system health')).toBeInTheDocument();
            expect(screen.getByText('Try Again')).toBeInTheDocument();
        });
    });
});