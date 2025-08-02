import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import { QueryClient, QueryClientProvider } from 'react-query';
import { BrowserRouter } from 'react-router-dom';
import BillingView from '../../../resources/js/Pages/Portal/BillingView';
import axios from 'axios';

// Mock axios
vi.mock('axios');

// Mock Stripe
const mockStripe = {
    redirectToCheckout: vi.fn(),
    createToken: vi.fn(),
    elements: vi.fn(() => ({
        create: vi.fn(() => ({ mount: vi.fn(), unmount: vi.fn() }))
    }))
};
global.Stripe = vi.fn(() => mockStripe);

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

describe('BillingView', () => {
    const mockBillingData = {
        data: {
            subscription: {
                id: 'sub_123',
                status: 'active',
                plan: {
                    id: 'plan_pro',
                    name: 'Professional',
                    price: 99.99,
                    interval: 'month',
                    features: [
                        'Unlimited appointments',
                        '5 staff members',
                        'SMS reminders',
                        'Advanced analytics'
                    ]
                },
                current_period_end: '2025-09-01T00:00:00Z',
                cancel_at_period_end: false
            },
            usage: {
                appointments: {
                    current: 245,
                    limit: null,
                    percentage: null
                },
                staff: {
                    current: 3,
                    limit: 5,
                    percentage: 60
                },
                sms: {
                    current: 180,
                    limit: 500,
                    percentage: 36
                },
                storage: {
                    current: '2.4 GB',
                    limit: '10 GB',
                    percentage: 24
                }
            },
            payment_methods: [
                {
                    id: 'pm_123',
                    type: 'card',
                    card: {
                        brand: 'visa',
                        last4: '4242',
                        exp_month: 12,
                        exp_year: 2026
                    },
                    is_default: true
                }
            ],
            invoices: [
                {
                    id: 'inv_123',
                    number: 'INV-2025-001',
                    amount: 99.99,
                    status: 'paid',
                    date: '2025-07-01T00:00:00Z',
                    pdf_url: 'https://example.com/invoice.pdf'
                },
                {
                    id: 'inv_124',
                    number: 'INV-2025-002',
                    amount: 99.99,
                    status: 'paid',
                    date: '2025-06-01T00:00:00Z',
                    pdf_url: 'https://example.com/invoice2.pdf'
                }
            ],
            available_plans: [
                {
                    id: 'plan_basic',
                    name: 'Basic',
                    price: 29.99,
                    interval: 'month',
                    features: [
                        '50 appointments/month',
                        '1 staff member',
                        'Email reminders'
                    ]
                },
                {
                    id: 'plan_pro',
                    name: 'Professional',
                    price: 99.99,
                    interval: 'month',
                    features: [
                        'Unlimited appointments',
                        '5 staff members',
                        'SMS reminders',
                        'Advanced analytics'
                    ],
                    current: true
                },
                {
                    id: 'plan_enterprise',
                    name: 'Enterprise',
                    price: 299.99,
                    interval: 'month',
                    features: [
                        'Unlimited everything',
                        'Unlimited staff',
                        'Priority support',
                        'Custom integrations'
                    ]
                }
            ]
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        axios.get.mockResolvedValue({ data: mockBillingData });
    });

    it('should render billing overview', async () => {
        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Billing & Subscription')).toBeInTheDocument();
            expect(screen.getByText('Professional Plan')).toBeInTheDocument();
            expect(screen.getByText('$99.99/month')).toBeInTheDocument();
            expect(screen.getByText('Active')).toBeInTheDocument();
        });
    });

    it('should display usage statistics', async () => {
        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Usage')).toBeInTheDocument();
            expect(screen.getByText('Appointments')).toBeInTheDocument();
            expect(screen.getByText('245')).toBeInTheDocument();
            expect(screen.getByText('Unlimited')).toBeInTheDocument();
            
            expect(screen.getByText('Staff Members')).toBeInTheDocument();
            expect(screen.getByText('3 / 5')).toBeInTheDocument();
            expect(screen.getByText('60%')).toBeInTheDocument();
        });
    });

    it('should display payment methods', async () => {
        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Payment Methods')).toBeInTheDocument();
            expect(screen.getByText('•••• 4242')).toBeInTheDocument();
            expect(screen.getByText('12/26')).toBeInTheDocument();
            expect(screen.getByText('Default')).toBeInTheDocument();
        });
    });

    it('should add new payment method', async () => {
        const user = userEvent.setup();
        mockStripe.createToken.mockResolvedValue({
            token: { id: 'tok_123' }
        });
        axios.post.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Payment Methods')).toBeInTheDocument();
        });

        // Click add payment method
        const addButton = screen.getByText('Add Payment Method');
        await user.click(addButton);

        // Modal should open
        const modal = screen.getByRole('dialog');
        expect(within(modal).getByText('Add Payment Method')).toBeInTheDocument();

        // Submit form (Stripe Elements would be mounted here)
        const submitButton = within(modal).getByText('Add Card');
        await user.click(submitButton);

        await waitFor(() => {
            expect(mockStripe.createToken).toHaveBeenCalled();
            expect(axios.post).toHaveBeenCalledWith(
                '/api/billing/payment-methods',
                expect.objectContaining({
                    token: 'tok_123'
                })
            );
        });
    });

    it('should remove payment method', async () => {
        const user = userEvent.setup();
        axios.delete.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('•••• 4242')).toBeInTheDocument();
        });

        // Click remove button
        const removeButton = screen.getByLabelText('Remove payment method');
        await user.click(removeButton);

        // Confirm removal
        const confirmModal = screen.getByRole('dialog');
        const confirmButton = within(confirmModal).getByText('Remove');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.delete).toHaveBeenCalledWith('/api/billing/payment-methods/pm_123');
        });
    });

    it('should display invoices', async () => {
        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Invoices')).toBeInTheDocument();
            expect(screen.getByText('INV-2025-001')).toBeInTheDocument();
            expect(screen.getByText('INV-2025-002')).toBeInTheDocument();
            expect(screen.getAllByText('$99.99')).toHaveLength(3); // Plan price + 2 invoices
        });
    });

    it('should download invoice', async () => {
        const user = userEvent.setup();
        
        // Mock window.open
        const mockOpen = vi.fn();
        global.window.open = mockOpen;

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('INV-2025-001')).toBeInTheDocument();
        });

        // Click download button
        const downloadButtons = screen.getAllByLabelText('Download invoice');
        await user.click(downloadButtons[0]);

        expect(mockOpen).toHaveBeenCalledWith('https://example.com/invoice.pdf', '_blank');
    });

    it('should display available plans', async () => {
        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Available Plans')).toBeInTheDocument();
            expect(screen.getByText('Basic')).toBeInTheDocument();
            expect(screen.getByText('$29.99/month')).toBeInTheDocument();
            expect(screen.getByText('Professional')).toBeInTheDocument();
            expect(screen.getByText('Enterprise')).toBeInTheDocument();
            expect(screen.getByText('$299.99/month')).toBeInTheDocument();
        });
    });

    it('should upgrade plan', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: {
                success: true,
                checkout_url: 'https://checkout.stripe.com/pay/cs_123'
            }
        });

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Enterprise')).toBeInTheDocument();
        });

        // Click upgrade button for Enterprise plan
        const enterpriseCard = screen.getByText('Enterprise').closest('.plan-card');
        const upgradeButton = within(enterpriseCard).getByText('Upgrade');
        await user.click(upgradeButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/billing/upgrade',
                expect.objectContaining({
                    plan_id: 'plan_enterprise'
                })
            );
            expect(mockStripe.redirectToCheckout).toHaveBeenCalledWith({
                sessionId: 'cs_123'
            });
        });
    });

    it('should downgrade plan', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Basic')).toBeInTheDocument();
        });

        // Click downgrade button for Basic plan
        const basicCard = screen.getByText('Basic').closest('.plan-card');
        const downgradeButton = within(basicCard).getByText('Downgrade');
        await user.click(downgradeButton);

        // Confirm downgrade
        const confirmModal = screen.getByRole('dialog');
        expect(within(confirmModal).getByText('Confirm Downgrade')).toBeInTheDocument();
        expect(within(confirmModal).getByText('You will lose access to:')).toBeInTheDocument();
        
        const confirmButton = within(confirmModal).getByText('Confirm Downgrade');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/billing/downgrade',
                expect.objectContaining({
                    plan_id: 'plan_basic'
                })
            );
        });
    });

    it('should cancel subscription', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Professional Plan')).toBeInTheDocument();
        });

        // Click cancel subscription
        const cancelButton = screen.getByText('Cancel Subscription');
        await user.click(cancelButton);

        // Confirm cancellation
        const confirmModal = screen.getByRole('dialog');
        const reasonTextarea = within(confirmModal).getByLabelText('Reason for cancellation');
        await user.type(reasonTextarea, 'Too expensive');

        const confirmButton = within(confirmModal).getByText('Cancel Subscription');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/billing/cancel',
                expect.objectContaining({
                    reason: 'Too expensive'
                })
            );
        });
    });

    it('should reactivate cancelled subscription', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: { success: true }
        });

        // Mock cancelled subscription
        axios.get.mockResolvedValue({
            data: {
                ...mockBillingData,
                data: {
                    ...mockBillingData.data,
                    subscription: {
                        ...mockBillingData.data.subscription,
                        cancel_at_period_end: true
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Scheduled for cancellation')).toBeInTheDocument();
        });

        // Click reactivate
        const reactivateButton = screen.getByText('Reactivate Subscription');
        await user.click(reactivateButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith('/api/billing/reactivate');
        });
    });

    it('should export billing data', async () => {
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
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Billing & Subscription')).toBeInTheDocument();
        });

        // Click export button
        const exportButton = screen.getByText('Export Billing Data');
        await user.click(exportButton);

        // Select format
        const csvOption = screen.getByText('Export as CSV');
        await user.click(csvOption);

        expect(mockClick).toHaveBeenCalled();
    });

    it('should show loading state', () => {
        axios.get.mockImplementation(() => new Promise(() => {})); // Never resolves

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        expect(screen.getByText('Loading billing information...')).toBeInTheDocument();
    });

    it('should show error state', async () => {
        axios.get.mockRejectedValue(new Error('Failed to load billing data'));

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Failed to load billing information')).toBeInTheDocument();
            expect(screen.getByText('Try Again')).toBeInTheDocument();
        });
    });

    it('should show no subscription state', async () => {
        axios.get.mockResolvedValue({
            data: {
                data: {
                    subscription: null,
                    available_plans: mockBillingData.data.available_plans
                }
            }
        });

        render(
            <TestWrapper>
                <BillingView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('No Active Subscription')).toBeInTheDocument();
            expect(screen.getByText('Choose a plan to get started')).toBeInTheDocument();
        });
    });
});