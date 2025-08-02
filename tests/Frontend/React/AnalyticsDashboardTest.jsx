import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import { QueryClient, QueryClientProvider } from 'react-query';
import { BrowserRouter } from 'react-router-dom';
import AnalyticsDashboard from '../../../resources/js/Pages/Portal/Analytics/Dashboard';
import axios from 'axios';

// Mock axios
vi.mock('axios');

// Mock recharts to avoid canvas rendering issues
vi.mock('recharts', () => ({
    LineChart: ({ children }) => <div data-testid="line-chart">{children}</div>,
    Line: () => null,
    AreaChart: ({ children }) => <div data-testid="area-chart">{children}</div>,
    Area: () => null,
    BarChart: ({ children }) => <div data-testid="bar-chart">{children}</div>,
    Bar: () => null,
    PieChart: ({ children }) => <div data-testid="pie-chart">{children}</div>,
    Pie: () => null,
    Cell: () => null,
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

describe('AnalyticsDashboard', () => {
    const mockAnalyticsData = {
        data: {
            revenue_prediction: {
                prediction: 15000,
                confidence_interval: {
                    lower: 13500,
                    upper: 16500,
                    confidence_level: 0.95
                },
                factors: [
                    { name: 'Seasonal Trend', impact: 0.3 },
                    { name: 'Customer Growth', impact: 0.25 },
                    { name: 'Service Expansion', impact: 0.2 }
                ],
                predicted_by_day: [
                    { date: '2025-08-01', revenue: 500 },
                    { date: '2025-08-02', revenue: 520 },
                    { date: '2025-08-03', revenue: 480 }
                ]
            },
            appointment_demand: {
                peak_hours: [
                    { hour: 10, demand: 0.85 },
                    { hour: 14, demand: 0.75 },
                    { hour: 16, demand: 0.8 }
                ],
                recommended_staff: 5,
                hourly_predictions: Array.from({ length: 24 }, (_, i) => ({
                    hour: i,
                    predicted: Math.floor(Math.random() * 10) + 1
                }))
            },
            customer_segments: {
                segments: ['loyal', 'at_risk', 'new', 'lost'],
                distribution: {
                    loyal: 45,
                    at_risk: 20,
                    new: 25,
                    lost: 10
                }
            },
            growth_metrics: {
                customer_growth: {
                    percentage: 15.5,
                    new_customers: 125,
                    total_customers: 1250
                },
                revenue_growth: {
                    percentage: 22.3,
                    current_period: 45000,
                    previous_period: 36800
                },
                appointment_growth: {
                    percentage: 18.7,
                    current_period: 890,
                    previous_period: 750
                }
            },
            performance_insights: {
                top_performers: [
                    { id: 1, name: 'Jane Smith', score: 95 },
                    { id: 2, name: 'John Doe', score: 88 }
                ],
                improvement_areas: [
                    'Reduce no-show rate',
                    'Improve appointment duration accuracy',
                    'Increase service upselling'
                ],
                recommendations: [
                    'Schedule more staff during peak hours',
                    'Implement reminder system for at-risk customers',
                    'Launch loyalty program'
                ]
            }
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        axios.get.mockResolvedValue({ data: mockAnalyticsData });
    });

    it('should render analytics dashboard with all sections', async () => {
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Analytics & Insights')).toBeInTheDocument();
            expect(screen.getByText('Revenue Prediction')).toBeInTheDocument();
            expect(screen.getByText('Appointment Demand')).toBeInTheDocument();
            expect(screen.getByText('Customer Segments')).toBeInTheDocument();
            expect(screen.getByText('Growth Metrics')).toBeInTheDocument();
            expect(screen.getByText('Performance Insights')).toBeInTheDocument();
        });
    });

    it('should display revenue prediction with confidence interval', async () => {
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('$15,000')).toBeInTheDocument(); // Predicted revenue
            expect(screen.getByText('95% Confidence: $13,500 - $16,500')).toBeInTheDocument();
        });

        // Check impact factors
        expect(screen.getByText('Seasonal Trend')).toBeInTheDocument();
        expect(screen.getByText('30%')).toBeInTheDocument();
        expect(screen.getByText('Customer Growth')).toBeInTheDocument();
        expect(screen.getByText('25%')).toBeInTheDocument();
    });

    it('should display appointment demand forecast', async () => {
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Peak Hours')).toBeInTheDocument();
            expect(screen.getByText('10:00 AM')).toBeInTheDocument();
            expect(screen.getByText('85%')).toBeInTheDocument(); // Demand percentage
            expect(screen.getByText('Recommended Staff: 5')).toBeInTheDocument();
        });
    });

    it('should display customer segments', async () => {
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Loyal')).toBeInTheDocument();
            expect(screen.getByText('45%')).toBeInTheDocument();
            expect(screen.getByText('At Risk')).toBeInTheDocument();
            expect(screen.getByText('20%')).toBeInTheDocument();
            expect(screen.getByText('New')).toBeInTheDocument();
            expect(screen.getByText('25%')).toBeInTheDocument();
        });
    });

    it('should display growth metrics', async () => {
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Customer Growth')).toBeInTheDocument();
            expect(screen.getByText('+15.5%')).toBeInTheDocument();
            expect(screen.getByText('Revenue Growth')).toBeInTheDocument();
            expect(screen.getByText('+22.3%')).toBeInTheDocument();
            expect(screen.getByText('Appointment Growth')).toBeInTheDocument();
            expect(screen.getByText('+18.7%')).toBeInTheDocument();
        });
    });

    it('should display performance insights and recommendations', async () => {
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            // Top performers
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
            expect(screen.getByText('95')).toBeInTheDocument();

            // Improvement areas
            expect(screen.getByText('Reduce no-show rate')).toBeInTheDocument();
            expect(screen.getByText('Improve appointment duration accuracy')).toBeInTheDocument();

            // Recommendations
            expect(screen.getByText('Schedule more staff during peak hours')).toBeInTheDocument();
            expect(screen.getByText('Implement reminder system for at-risk customers')).toBeInTheDocument();
        });
    });

    it('should change time period filter', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Analytics & Insights')).toBeInTheDocument();
        });

        // Change time period
        const periodSelect = screen.getByLabelText('Time Period');
        await user.click(periodSelect);
        await user.click(screen.getByText('Last 7 Days'));

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledWith(
                expect.stringContaining('period=last_7_days'),
                expect.any(Object)
            );
        });
    });

    it('should export analytics report', async () => {
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
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Analytics & Insights')).toBeInTheDocument();
        });

        // Click export button
        const exportButton = screen.getByText('Export Report');
        await user.click(exportButton);

        // Select PDF format
        const pdfOption = screen.getByText('Export as PDF');
        await user.click(pdfOption);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/analytics/export',
                expect.objectContaining({
                    format: 'pdf',
                    period: expect.any(String)
                })
            );
        });
    });

    it('should refresh data automatically', async () => {
        vi.useFakeTimers();
        
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Analytics & Insights')).toBeInTheDocument();
        });

        // Initial call
        expect(axios.get).toHaveBeenCalledTimes(1);

        // Fast forward 5 minutes
        vi.advanceTimersByTime(5 * 60 * 1000);

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledTimes(2);
        });

        vi.useRealTimers();
    });

    it('should handle drill-down on metrics', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Customer Growth')).toBeInTheDocument();
        });

        // Click on customer growth metric
        const customerGrowthCard = screen.getByText('Customer Growth').closest('.ant-card');
        const viewDetailsButton = within(customerGrowthCard).getByText('View Details');
        await user.click(viewDetailsButton);

        // Should show detailed modal
        await waitFor(() => {
            const modal = screen.getByRole('dialog');
            expect(within(modal).getByText('Customer Growth Details')).toBeInTheDocument();
            expect(within(modal).getByText('New Customers: 125')).toBeInTheDocument();
            expect(within(modal).getByText('Total Customers: 1,250')).toBeInTheDocument();
        });
    });

    it('should handle anomaly detection alerts', async () => {
        // Mock data with anomalies
        axios.get.mockResolvedValue({
            data: {
                ...mockAnalyticsData,
                data: {
                    ...mockAnalyticsData.data,
                    anomalies: [
                        {
                            type: 'revenue_spike',
                            severity: 'warning',
                            message: 'Unusual revenue spike detected',
                            date: '2025-08-01'
                        }
                    ]
                }
            }
        });

        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Anomaly Detected')).toBeInTheDocument();
            expect(screen.getByText('Unusual revenue spike detected')).toBeInTheDocument();
        });
    });

    it('should show loading state', () => {
        axios.get.mockImplementation(() => new Promise(() => {})); // Never resolves

        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        expect(screen.getByText('Loading analytics...')).toBeInTheDocument();
    });

    it('should show error state', async () => {
        axios.get.mockRejectedValue(new Error('Failed to load analytics'));

        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Failed to load analytics')).toBeInTheDocument();
            expect(screen.getByText('Try Again')).toBeInTheDocument();
        });
    });

    it('should handle empty data gracefully', async () => {
        axios.get.mockResolvedValue({
            data: {
                data: {
                    revenue_prediction: null,
                    appointment_demand: null,
                    customer_segments: null,
                    growth_metrics: null,
                    performance_insights: null
                }
            }
        });

        render(
            <TestWrapper>
                <AnalyticsDashboard />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('No data available')).toBeInTheDocument();
            expect(screen.getByText('Not enough data to generate insights')).toBeInTheDocument();
        });
    });
});