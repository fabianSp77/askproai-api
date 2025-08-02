import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import { QueryClient, QueryClientProvider } from 'react-query';
import { BrowserRouter } from 'react-router-dom';
import AppointmentsView from '../../../resources/js/Pages/Portal/AppointmentsView';
import axios from 'axios';

// Mock axios
vi.mock('axios');

// Mock react-router-dom
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => vi.fn()
    };
});

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

describe('AppointmentsView', () => {
    const mockAppointments = {
        data: {
            appointments: [
                {
                    id: 1,
                    customer: { id: 1, full_name: 'John Doe' },
                    service: { id: 1, name: 'Haircut' },
                    staff: { id: 1, full_name: 'Jane Smith' },
                    branch: { id: 1, name: 'Main Branch' },
                    starts_at: '2025-08-15T10:00:00Z',
                    ends_at: '2025-08-15T11:00:00Z',
                    status: 'scheduled',
                    price: 50
                },
                {
                    id: 2,
                    customer: { id: 2, full_name: 'Alice Johnson' },
                    service: { id: 2, name: 'Massage' },
                    staff: { id: 2, full_name: 'Bob Wilson' },
                    branch: { id: 1, name: 'Main Branch' },
                    starts_at: '2025-08-15T14:00:00Z',
                    ends_at: '2025-08-15T15:00:00Z',
                    status: 'confirmed',
                    price: 100
                }
            ],
            pagination: {
                total: 2,
                per_page: 20,
                current_page: 1,
                last_page: 1
            }
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        axios.get.mockResolvedValue({ data: mockAppointments });
    });

    it('should render appointments list', async () => {
        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
            expect(screen.getByText('Haircut')).toBeInTheDocument();
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
            expect(screen.getByText('Alice Johnson')).toBeInTheDocument();
        });
    });

    it('should filter appointments by status', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click on status filter
        const statusFilter = screen.getByLabelText('Filter by status');
        await user.click(statusFilter);
        await user.click(screen.getByText('Scheduled'));

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledWith(
                expect.stringContaining('status=scheduled'),
                expect.any(Object)
            );
        });
    });

    it('should filter appointments by date range', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Select date range
        const dateRangePicker = screen.getByLabelText('Date range');
        await user.click(dateRangePicker);
        
        // Select today
        const todayButton = screen.getByText('Today');
        await user.click(todayButton);

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledWith(
                expect.stringContaining('date_from='),
                expect.any(Object)
            );
        });
    });

    it('should search appointments', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Search for customer
        const searchInput = screen.getByPlaceholderText('Search appointments...');
        await user.type(searchInput, 'John');

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledWith(
                expect.stringContaining('search=John'),
                expect.any(Object)
            );
        });
    });

    it('should create new appointment', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: {
                success: true,
                data: {
                    appointment: {
                        id: 3,
                        status: 'scheduled'
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        // Click create button
        const createButton = screen.getByText('New Appointment');
        await user.click(createButton);

        // Fill form
        const modal = screen.getByRole('dialog');
        const customerSelect = within(modal).getByLabelText('Customer');
        const serviceSelect = within(modal).getByLabelText('Service');
        const dateInput = within(modal).getByLabelText('Date');
        const timeInput = within(modal).getByLabelText('Time');

        await user.click(customerSelect);
        await user.click(screen.getByText('John Doe'));
        
        await user.click(serviceSelect);
        await user.click(screen.getByText('Haircut'));
        
        await user.type(dateInput, '2025-08-20');
        await user.type(timeInput, '10:00');

        // Submit form
        const submitButton = within(modal).getByText('Create Appointment');
        await user.click(submitButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/appointments',
                expect.objectContaining({
                    customer_id: expect.any(Number),
                    service_id: expect.any(Number),
                    date: '2025-08-20',
                    time: '10:00'
                })
            );
        });
    });

    it('should cancel appointment', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: {
                success: true,
                message: 'Appointment cancelled successfully'
            }
        });

        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click on appointment actions
        const moreButton = screen.getAllByLabelText('More actions')[0];
        await user.click(moreButton);

        // Click cancel
        const cancelButton = screen.getByText('Cancel');
        await user.click(cancelButton);

        // Confirm cancellation
        const confirmModal = screen.getByRole('dialog');
        const reasonInput = within(confirmModal).getByLabelText('Cancellation reason');
        await user.type(reasonInput, 'Customer requested');

        const confirmButton = within(confirmModal).getByText('Confirm Cancellation');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/appointments/1/cancel',
                expect.objectContaining({
                    reason: 'Customer requested'
                })
            );
        });
    });

    it('should reschedule appointment', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: {
                success: true,
                message: 'Appointment rescheduled successfully'
            }
        });

        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click on appointment actions
        const moreButton = screen.getAllByLabelText('More actions')[0];
        await user.click(moreButton);

        // Click reschedule
        const rescheduleButton = screen.getByText('Reschedule');
        await user.click(rescheduleButton);

        // Select new date and time
        const modal = screen.getByRole('dialog');
        const dateInput = within(modal).getByLabelText('New date');
        const timeInput = within(modal).getByLabelText('New time');

        await user.clear(dateInput);
        await user.type(dateInput, '2025-08-21');
        
        await user.clear(timeInput);
        await user.type(timeInput, '14:00');

        const confirmButton = within(modal).getByText('Reschedule');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/appointments/1/reschedule',
                expect.objectContaining({
                    new_date: '2025-08-21',
                    new_time: '14:00'
                })
            );
        });
    });

    it('should show appointment details', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click on appointment row
        const appointmentRow = screen.getByText('John Doe').closest('tr');
        await user.click(appointmentRow);

        // Check details modal
        const modal = screen.getByRole('dialog');
        expect(within(modal).getByText('Appointment Details')).toBeInTheDocument();
        expect(within(modal).getByText('Customer:')).toBeInTheDocument();
        expect(within(modal).getByText('John Doe')).toBeInTheDocument();
        expect(within(modal).getByText('Service:')).toBeInTheDocument();
        expect(within(modal).getByText('Haircut')).toBeInTheDocument();
        expect(within(modal).getByText('Staff:')).toBeInTheDocument();
        expect(within(modal).getByText('Jane Smith')).toBeInTheDocument();
    });

    it('should handle pagination', async () => {
        const user = userEvent.setup();
        
        // Mock response with pagination
        axios.get.mockResolvedValue({
            data: {
                ...mockAppointments,
                data: {
                    ...mockAppointments.data,
                    pagination: {
                        total: 50,
                        per_page: 20,
                        current_page: 1,
                        last_page: 3
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click next page
        const nextButton = screen.getByLabelText('Next page');
        await user.click(nextButton);

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledWith(
                expect.stringContaining('page=2'),
                expect.any(Object)
            );
        });
    });

    it('should show loading state', () => {
        axios.get.mockImplementation(() => new Promise(() => {})); // Never resolves

        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        expect(screen.getByText('Loading appointments...')).toBeInTheDocument();
    });

    it('should show error state', async () => {
        axios.get.mockRejectedValue(new Error('Network error'));

        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Failed to load appointments')).toBeInTheDocument();
            expect(screen.getByText('Try Again')).toBeInTheDocument();
        });
    });

    it('should show empty state', async () => {
        axios.get.mockResolvedValue({
            data: {
                data: {
                    appointments: [],
                    pagination: {
                        total: 0,
                        per_page: 20,
                        current_page: 1,
                        last_page: 1
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <AppointmentsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('No appointments found')).toBeInTheDocument();
            expect(screen.getByText('Create your first appointment')).toBeInTheDocument();
        });
    });
});