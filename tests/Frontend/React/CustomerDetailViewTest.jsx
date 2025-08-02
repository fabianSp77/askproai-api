import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import { QueryClient, QueryClientProvider } from 'react-query';
import { BrowserRouter, MemoryRouter, Route, Routes } from 'react-router-dom';
import CustomerDetailView from '../../../resources/js/Pages/Portal/CustomerDetailView';
import axios from 'axios';

// Mock axios
vi.mock('axios');

// Mock navigate
const mockNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockNavigate
    };
});

const createTestQueryClient = () => new QueryClient({
    defaultOptions: {
        queries: { retry: false },
        mutations: { retry: false }
    }
});

const TestWrapper = ({ children, initialEntries = ['/customers/1'] }) => (
    <QueryClientProvider client={createTestQueryClient()}>
        <MemoryRouter initialEntries={initialEntries}>
            <Routes>
                <Route path="/customers/:id" element={children} />
            </Routes>
        </MemoryRouter>
    </QueryClientProvider>
);

describe('CustomerDetailView', () => {
    const mockCustomer = {
        data: {
            id: 1,
            first_name: 'John',
            last_name: 'Doe',
            full_name: 'John Doe',
            email: 'john@example.com',
            phone: '+1234567890',
            date_of_birth: '1990-05-15',
            address: '123 Main St',
            city: 'New York',
            postal_code: '10001',
            notes: 'VIP customer',
            tags: ['vip', 'regular'],
            created_at: '2025-01-01T00:00:00Z',
            stats: {
                total_appointments: 15,
                completed_appointments: 12,
                cancelled_appointments: 2,
                no_show_appointments: 1,
                total_calls: 8,
                lifetime_value: 1500,
                average_spend: 125,
                last_appointment: '2025-07-20T00:00:00Z',
                last_call: '2025-07-25T00:00:00Z'
            }
        }
    };

    const mockHistory = {
        data: {
            appointments: [
                {
                    id: 1,
                    service: { name: 'Haircut' },
                    staff: { full_name: 'Jane Smith' },
                    starts_at: '2025-07-20T10:00:00Z',
                    status: 'completed',
                    price: 50
                },
                {
                    id: 2,
                    service: { name: 'Massage' },
                    staff: { full_name: 'Bob Wilson' },
                    starts_at: '2025-07-15T14:00:00Z',
                    status: 'completed',
                    price: 100
                }
            ],
            calls: [
                {
                    id: 1,
                    duration: 180,
                    status: 'ended',
                    sentiment: 'positive',
                    created_at: '2025-07-25T15:00:00Z'
                }
            ],
            timeline: [
                {
                    type: 'appointment',
                    date: '2025-07-20T10:00:00Z',
                    description: 'Appointment completed'
                },
                {
                    type: 'call',
                    date: '2025-07-25T15:00:00Z',
                    description: 'Incoming call (3 minutes)'
                }
            ]
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        axios.get.mockImplementation((url) => {
            if (url.includes('/history')) {
                return Promise.resolve({ data: mockHistory });
            }
            return Promise.resolve({ data: mockCustomer });
        });
    });

    it('should render customer details', async () => {
        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
            expect(screen.getByText('john@example.com')).toBeInTheDocument();
            expect(screen.getByText('+1234567890')).toBeInTheDocument();
            expect(screen.getByText('123 Main St')).toBeInTheDocument();
            expect(screen.getByText('VIP customer')).toBeInTheDocument();
        });

        // Check tags
        expect(screen.getByText('vip')).toBeInTheDocument();
        expect(screen.getByText('regular')).toBeInTheDocument();
    });

    it('should render customer statistics', async () => {
        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('15')).toBeInTheDocument(); // Total appointments
            expect(screen.getByText('$1,500')).toBeInTheDocument(); // Lifetime value
            expect(screen.getByText('8')).toBeInTheDocument(); // Total calls
            expect(screen.getByText('$125')).toBeInTheDocument(); // Average spend
        });
    });

    it('should update customer information', async () => {
        const user = userEvent.setup();
        axios.put.mockResolvedValue({
            data: {
                success: true,
                data: {
                    customer: {
                        ...mockCustomer.data,
                        notes: 'Updated notes'
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click edit button
        const editButton = screen.getByText('Edit');
        await user.click(editButton);

        // Update notes
        const notesTextarea = screen.getByLabelText('Notes');
        await user.clear(notesTextarea);
        await user.type(notesTextarea, 'Updated notes');

        // Save changes
        const saveButton = screen.getByText('Save Changes');
        await user.click(saveButton);

        await waitFor(() => {
            expect(axios.put).toHaveBeenCalledWith(
                '/api/customers/1',
                expect.objectContaining({
                    notes: 'Updated notes'
                })
            );
        });
    });

    it('should show appointment history', async () => {
        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Appointment History')).toBeInTheDocument();
            expect(screen.getByText('Haircut')).toBeInTheDocument();
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
            expect(screen.getByText('Massage')).toBeInTheDocument();
            expect(screen.getByText('Bob Wilson')).toBeInTheDocument();
        });
    });

    it('should show call history', async () => {
        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Call History')).toBeInTheDocument();
            expect(screen.getByText('3 minutes')).toBeInTheDocument();
            expect(screen.getByText('positive')).toBeInTheDocument();
        });
    });

    it('should show timeline view', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Switch to timeline view
        const timelineTab = screen.getByText('Timeline');
        await user.click(timelineTab);

        await waitFor(() => {
            expect(screen.getByText('Appointment completed')).toBeInTheDocument();
            expect(screen.getByText('Incoming call (3 minutes)')).toBeInTheDocument();
        });
    });

    it('should add tags to customer', async () => {
        const user = userEvent.setup();
        axios.put.mockResolvedValue({
            data: {
                success: true,
                data: {
                    customer: {
                        ...mockCustomer.data,
                        tags: ['vip', 'regular', 'premium']
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click on tags section
        const addTagButton = screen.getByLabelText('Add tag');
        await user.click(addTagButton);

        // Type new tag
        const tagInput = screen.getByPlaceholderText('Add a tag...');
        await user.type(tagInput, 'premium{enter}');

        await waitFor(() => {
            expect(axios.put).toHaveBeenCalledWith(
                '/api/customers/1',
                expect.objectContaining({
                    tags: ['vip', 'regular', 'premium']
                })
            );
        });
    });

    it('should book new appointment for customer', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click book appointment button
        const bookButton = screen.getByText('Book Appointment');
        await user.click(bookButton);

        // Should navigate to appointments page with customer pre-selected
        expect(mockNavigate).toHaveBeenCalledWith(
            '/appointments/new',
            expect.objectContaining({
                state: { customerId: 1 }
            })
        );
    });

    it('should delete customer', async () => {
        const user = userEvent.setup();
        axios.delete.mockResolvedValue({
            data: {
                success: true,
                message: 'Customer deleted successfully'
            }
        });

        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click more actions
        const moreButton = screen.getByLabelText('More actions');
        await user.click(moreButton);

        // Click delete
        const deleteButton = screen.getByText('Delete Customer');
        await user.click(deleteButton);

        // Confirm deletion
        const confirmModal = screen.getByRole('dialog');
        const confirmButton = within(confirmModal).getByText('Delete');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.delete).toHaveBeenCalledWith('/api/customers/1');
            expect(mockNavigate).toHaveBeenCalledWith('/customers');
        });
    });

    it('should merge duplicate customers', async () => {
        const user = userEvent.setup();
        
        // Mock search results
        axios.get.mockImplementation((url) => {
            if (url.includes('/search')) {
                return Promise.resolve({
                    data: {
                        data: {
                            results: [
                                { id: 2, full_name: 'Johnny Doe', email: null, phone: '+1234567890' },
                                { id: 3, full_name: 'J. Doe', email: 'john@example.com', phone: null }
                            ]
                        }
                    }
                });
            }
            if (url.includes('/history')) {
                return Promise.resolve({ data: mockHistory });
            }
            return Promise.resolve({ data: mockCustomer });
        });

        axios.post.mockResolvedValue({
            data: {
                success: true,
                message: 'Customers merged successfully'
            }
        });

        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click more actions
        const moreButton = screen.getByLabelText('More actions');
        await user.click(moreButton);

        // Click merge duplicates
        const mergeButton = screen.getByText('Merge Duplicates');
        await user.click(mergeButton);

        // Should show potential duplicates
        await waitFor(() => {
            expect(screen.getByText('Johnny Doe')).toBeInTheDocument();
            expect(screen.getByText('J. Doe')).toBeInTheDocument();
        });

        // Select duplicate to merge
        const selectButton = screen.getAllByText('Select')[0];
        await user.click(selectButton);

        // Confirm merge
        const confirmButton = screen.getByText('Merge Selected');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/customers/merge',
                expect.objectContaining({
                    primary_customer_id: 1,
                    duplicate_customer_id: 2
                })
            );
        });
    });

    it('should export customer data', async () => {
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
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Click export button
        const exportButton = screen.getByText('Export');
        await user.click(exportButton);

        // Select export format
        const csvOption = screen.getByText('Export as CSV');
        await user.click(csvOption);

        expect(mockClick).toHaveBeenCalled();
        expect(mockRemove).toHaveBeenCalled();
    });

    it('should show loading state', () => {
        axios.get.mockImplementation(() => new Promise(() => {})); // Never resolves

        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        expect(screen.getByText('Loading customer details...')).toBeInTheDocument();
    });

    it('should show error state', async () => {
        axios.get.mockRejectedValue(new Error('Customer not found'));

        render(
            <TestWrapper>
                <CustomerDetailView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Customer not found')).toBeInTheDocument();
            expect(screen.getByText('Go Back')).toBeInTheDocument();
        });
    });
});