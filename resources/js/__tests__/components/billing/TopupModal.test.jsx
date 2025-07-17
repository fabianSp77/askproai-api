import React from 'react';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import TopupModal from '../../../components/billing/TopupModal';
import { server } from '../../mocks/server';
import { rest } from 'msw';

// Mock Stripe
const mockStripe = {
  redirectToCheckout: vi.fn().mockResolvedValue({ error: null }),
};

vi.mock('@stripe/stripe-js', () => ({
  loadStripe: vi.fn().mockResolvedValue(mockStripe),
}));

// Mock toast notifications
vi.mock('react-toastify', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    loading: vi.fn(),
    dismiss: vi.fn(),
  },
}));

const mockTopupOptions = {
  packages: [
    {
      id: 'starter',
      name: 'Starter Paket',
      amount: 50,
      credits: 60,
      bonus_percentage: 20,
      popular: false,
    },
    {
      id: 'standard',
      name: 'Standard Paket',
      amount: 100,
      credits: 130,
      bonus_percentage: 30,
      popular: true,
    },
    {
      id: 'premium',
      name: 'Premium Paket',
      amount: 200,
      credits: 280,
      bonus_percentage: 40,
      popular: false,
    },
  ],
  custom_amounts: {
    min: 20,
    max: 500,
    step: 10,
  },
  payment_methods: ['credit_card', 'sepa', 'paypal'],
  current_balance: 25.50,
};

describe('TopupModal', () => {
  const mockOnClose = vi.fn();
  const mockOnSuccess = vi.fn();
  const mockCsrfToken = 'test-csrf-token';

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders topup options when open', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Guthaben aufladen')).toBeInTheDocument();
    });

    // Check if packages are displayed
    expect(screen.getByText('Starter Paket')).toBeInTheDocument();
    expect(screen.getByText('Standard Paket')).toBeInTheDocument();
    expect(screen.getByText('Premium Paket')).toBeInTheDocument();

    // Check popular badge
    const standardPackage = screen.getByText('Standard Paket').closest('div');
    expect(within(standardPackage).getByText('Beliebt')).toBeInTheDocument();
  });

  it('displays current balance', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Aktuelles Guthaben:')).toBeInTheDocument();
      expect(screen.getByText('€25.50')).toBeInTheDocument();
    });
  });

  it('selects package and shows details', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    const user = userEvent.setup();
    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Standard Paket')).toBeInTheDocument();
    });

    // Click on standard package
    const standardPackage = screen.getByText('Standard Paket').closest('div');
    await user.click(standardPackage);

    // Check if details are shown
    expect(screen.getByText('€100')).toBeInTheDocument();
    expect(screen.getByText('130 Credits')).toBeInTheDocument();
    expect(screen.getByText('+30% Bonus')).toBeInTheDocument();
  });

  it('allows custom amount input', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    const user = userEvent.setup();
    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Individueller Betrag')).toBeInTheDocument();
    });

    // Switch to custom amount
    const customTab = screen.getByText('Individueller Betrag');
    await user.click(customTab);

    // Input custom amount
    const amountInput = screen.getByLabelText(/betrag/i);
    await user.clear(amountInput);
    await user.type(amountInput, '75');

    // Verify amount is displayed
    expect(screen.getByDisplayValue('75')).toBeInTheDocument();
  });

  it('validates custom amount range', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    const user = userEvent.setup();
    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Individueller Betrag')).toBeInTheDocument();
    });

    // Switch to custom amount
    const customTab = screen.getByText('Individueller Betrag');
    await user.click(customTab);

    // Try amount below minimum
    const amountInput = screen.getByLabelText(/betrag/i);
    await user.clear(amountInput);
    await user.type(amountInput, '10');

    // Check validation error
    expect(screen.getByText(/mindestbetrag ist €20/i)).toBeInTheDocument();

    // Try amount above maximum
    await user.clear(amountInput);
    await user.type(amountInput, '600');

    // Check validation error
    expect(screen.getByText(/maximalbetrag ist €500/i)).toBeInTheDocument();
  });

  it('processes payment with selected package', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      }),
      rest.post('/api/billing/topup', (req, res, ctx) => {
        return res(ctx.json({
          success: true,
          session_id: 'cs_test_123456',
          checkout_url: 'https://checkout.stripe.com/pay/cs_test_123456',
        }));
      })
    );

    const user = userEvent.setup();
    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Standard Paket')).toBeInTheDocument();
    });

    // Select standard package
    const standardPackage = screen.getByText('Standard Paket').closest('div');
    await user.click(standardPackage);

    // Click payment button
    const payButton = screen.getByRole('button', { name: /jetzt bezahlen/i });
    await user.click(payButton);

    // Verify API call and redirect
    await waitFor(() => {
      expect(mockStripe.redirectToCheckout).toHaveBeenCalledWith({
        sessionId: 'cs_test_123456',
      });
    });
  });

  it('handles payment errors gracefully', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      }),
      rest.post('/api/billing/topup', (req, res, ctx) => {
        return res(
          ctx.status(400),
          ctx.json({
            success: false,
            message: 'Zahlung konnte nicht verarbeitet werden',
          })
        );
      })
    );

    const user = userEvent.setup();
    const { toast } = require('react-toastify');

    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Standard Paket')).toBeInTheDocument();
    });

    // Select and try to pay
    const standardPackage = screen.getByText('Standard Paket').closest('div');
    await user.click(standardPackage);

    const payButton = screen.getByRole('button', { name: /jetzt bezahlen/i });
    await user.click(payButton);

    // Verify error message
    await waitFor(() => {
      expect(toast.error).toHaveBeenCalledWith('Zahlung konnte nicht verarbeitet werden');
    });
  });

  it('displays loading state during payment processing', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      }),
      rest.post('/api/billing/topup', async (req, res, ctx) => {
        await new Promise(resolve => setTimeout(resolve, 100));
        return res(ctx.json({
          success: true,
          session_id: 'cs_test_123456',
        }));
      })
    );

    const user = userEvent.setup();
    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Standard Paket')).toBeInTheDocument();
    });

    // Select and pay
    const standardPackage = screen.getByText('Standard Paket').closest('div');
    await user.click(standardPackage);

    const payButton = screen.getByRole('button', { name: /jetzt bezahlen/i });
    await user.click(payButton);

    // Check loading state
    expect(screen.getByText(/verarbeitung/i)).toBeInTheDocument();
    expect(payButton).toBeDisabled();
  });

  it('closes modal when cancel button is clicked', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    const user = userEvent.setup();
    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Guthaben aufladen')).toBeInTheDocument();
    });

    // Click cancel
    const cancelButton = screen.getByRole('button', { name: /abbrechen/i });
    await user.click(cancelButton);

    expect(mockOnClose).toHaveBeenCalled();
  });

  it('shows payment method selection', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    const user = userEvent.setup();
    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Standard Paket')).toBeInTheDocument();
    });

    // Select package first
    const standardPackage = screen.getByText('Standard Paket').closest('div');
    await user.click(standardPackage);

    // Check payment methods
    expect(screen.getByLabelText(/kreditkarte/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/sepa/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/paypal/i)).toBeInTheDocument();
  });

  it('calculates and displays savings for packages', async () => {
    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Premium Paket')).toBeInTheDocument();
    });

    // Check savings display for premium package (40% bonus)
    const premiumPackage = screen.getByText('Premium Paket').closest('div');
    expect(within(premiumPackage).getByText('Sie sparen €80')).toBeInTheDocument();
  });

  it('remembers last selected payment method', async () => {
    // Mock localStorage
    const mockGetItem = vi.spyOn(Storage.prototype, 'getItem');
    mockGetItem.mockReturnValue('paypal');

    server.use(
      rest.get('/api/billing/topup-options', (req, res, ctx) => {
        return res(ctx.json(mockTopupOptions));
      })
    );

    render(
      <TopupModal
        isOpen={true}
        onClose={mockOnClose}
        onSuccess={mockOnSuccess}
        csrfToken={mockCsrfToken}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('Standard Paket')).toBeInTheDocument();
    });

    // Select a package to see payment methods
    const standardPackage = screen.getByText('Standard Paket').closest('div');
    await userEvent.click(standardPackage);

    // PayPal should be pre-selected
    const paypalRadio = screen.getByLabelText(/paypal/i);
    expect(paypalRadio).toBeChecked();
  });
});