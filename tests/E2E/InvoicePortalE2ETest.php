<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Appointment;
use App\Mail\PaymentReceiptEmail;
use App\Mail\InvoiceReminderEmail;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePortalE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('invoices');

        $this->company = Company::factory()->create([
            'name' => 'Premium Spa',
            'settings' => [
                'customer_portal' => true,
                'portal_features' => [
                    'invoices' => true,
                    'online_payment' => true,
                ],
                'invoice_settings' => [
                    'prefix' => 'INV',
                    'due_days' => 14,
                    'late_fee_percentage' => 5,
                    'currency' => 'EUR',
                    'tax_rate' => 19,
                ],
                'payment_methods' => ['stripe', 'paypal', 'bank_transfer'],
            ],
            'address' => 'Business Street 123',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country' => 'Germany',
            'tax_id' => 'DE123456789',
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Downtown Spa',
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Customer',
            'email' => 'jane@customer.com',
            'phone' => '+4915123456789',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'portal_access_enabled' => true,
            'address' => 'Customer Avenue 456',
            'city' => 'Berlin',
            'postal_code' => '10117',
            'country' => 'Germany',
        ]);

        $this->actingAs($this->customer, 'customer');
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function customer_can_view_invoice_list_with_filters()
    {
        // Create various invoices
        $invoices = [
            // Open invoice
            Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'invoice_number' => 'INV-2024-001',
                'invoice_date' => now()->subDays(5),
                'due_date' => now()->addDays(9),
                'subtotal' => 150.00,
                'tax_amount' => 28.50,
                'total_amount' => 178.50,
                'status' => 'open',
            ]),
            // Overdue invoice
            Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'invoice_number' => 'INV-2024-002',
                'invoice_date' => now()->subMonth(),
                'due_date' => now()->subDays(16),
                'subtotal' => 200.00,
                'tax_amount' => 38.00,
                'total_amount' => 238.00,
                'status' => 'overdue',
                'days_overdue' => 16,
            ]),
            // Paid invoice
            Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'invoice_number' => 'INV-2023-099',
                'invoice_date' => now()->subMonths(2),
                'due_date' => now()->subMonths(2)->addDays(14),
                'subtotal' => 100.00,
                'tax_amount' => 19.00,
                'total_amount' => 119.00,
                'paid_amount' => 119.00,
                'status' => 'paid',
                'paid_at' => now()->subMonths(2)->addDays(10),
            ]),
        ];

        // Visit invoices page
        $response = $this->get('/customer/invoices');
        
        $response->assertStatus(200);
        $response->assertSee('My Invoices');
        
        // Summary cards
        $response->assertSee('Total Outstanding');
        $response->assertSee('€416.50'); // 178.50 + 238.00
        $response->assertSee('Overdue');
        $response->assertSee('€238.00');
        $response->assertSee('Due Soon');
        $response->assertSee('€178.50');
        
        // Filter tabs
        $response->assertSee('All');
        $response->assertSee('Open');
        $response->assertSee('Overdue');
        $response->assertSee('Paid');
        
        // Invoice list
        $response->assertSee('INV-2024-001');
        $response->assertSee('Due in 9 days');
        $response->assertSee('€178.50');
        $response->assertSee('Pay Now');
        
        $response->assertSee('INV-2024-002');
        $response->assertSee('16 days overdue');
        $response->assertSee('badge-danger'); // Overdue badge
        
        // Filter by status
        $response = $this->get('/customer/invoices?status=overdue');
        
        $response->assertStatus(200);
        $response->assertSee('INV-2024-002');
        $response->assertDontSee('INV-2024-001');
        
        // Search functionality
        $response = $this->get('/customer/invoices?search=INV-2023');
        
        $response->assertStatus(200);
        $response->assertSee('INV-2023-099');
        $this->assertCount(1, $response->viewData('invoices'));
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function customer_can_view_detailed_invoice()
    {
        $service1 = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Full Body Massage',
            'price' => 80.00,
        ]);

        $service2 = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Facial Treatment',
            'price' => 60.00,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2024-003',
            'invoice_date' => now()->subDays(3),
            'due_date' => now()->addDays(11),
            'subtotal' => 140.00,
            'tax_amount' => 26.60,
            'discount_amount' => 10.00,
            'total_amount' => 156.60,
            'status' => 'open',
            'notes' => 'Thank you for your business!',
            'terms' => 'Payment due within 14 days. Late fee of 5% applies after due date.',
        ]);

        // Create invoice items
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Full Body Massage - 90 minutes',
            'quantity' => 1,
            'unit_price' => 80.00,
            'total' => 80.00,
            'service_id' => $service1->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Facial Treatment - Premium',
            'quantity' => 1,
            'unit_price' => 60.00,
            'total' => 60.00,
            'service_id' => $service2->id,
        ]);

        $response = $this->get("/customer/invoices/{$invoice->id}");
        
        $response->assertStatus(200);
        
        // Header
        $response->assertSee('Invoice INV-2024-003');
        $response->assertSee('Open');
        $response->assertSee('Due in 11 days');
        
        // Company details
        $response->assertSee('Premium Spa');
        $response->assertSee('Business Street 123');
        $response->assertSee('10115 Berlin');
        $response->assertSee('Tax ID: DE123456789');
        
        // Customer details
        $response->assertSee('Bill To:');
        $response->assertSee('Jane Customer');
        $response->assertSee('Customer Avenue 456');
        
        // Invoice details
        $response->assertSee('Invoice Date:');
        $response->assertSee($invoice->invoice_date->format('F j, Y'));
        $response->assertSee('Due Date:');
        $response->assertSee($invoice->due_date->format('F j, Y'));
        
        // Line items
        $response->assertSee('Description');
        $response->assertSee('Quantity');
        $response->assertSee('Unit Price');
        $response->assertSee('Total');
        
        $response->assertSee('Full Body Massage - 90 minutes');
        $response->assertSee('€80.00');
        
        $response->assertSee('Facial Treatment - Premium');
        $response->assertSee('€60.00');
        
        // Totals
        $response->assertSee('Subtotal');
        $response->assertSee('€140.00');
        $response->assertSee('Discount');
        $response->assertSee('-€10.00');
        $response->assertSee('Tax (19%)');
        $response->assertSee('€26.60');
        $response->assertSee('Total Due');
        $response->assertSee('€156.60');
        
        // Notes and terms
        $response->assertSee('Thank you for your business!');
        $response->assertSee('Payment due within 14 days');
        
        // Actions
        $response->assertSee('Pay Now');
        $response->assertSee('Download PDF');
        $response->assertSee('Print');
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function customer_can_download_invoice_pdf()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2024-PDF',
            'total_amount' => 119.00,
            'status' => 'open',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service Item',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        // Mock PDF generation
        PDF::shouldReceive('loadView')
            ->once()
            ->with('invoices.pdf', \Mockery::any())
            ->andReturnSelf();
        
        PDF::shouldReceive('download')
            ->once()
            ->with('invoice-INV-2024-PDF.pdf')
            ->andReturn(response()->make('PDF content', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice-INV-2024-PDF.pdf"',
            ]));

        $response = $this->get("/customer/invoices/{$invoice->id}/download");
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="invoice-INV-2024-PDF.pdf"');
        
        // Verify download was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'description' => 'downloaded',
            'causer_type' => Customer::class,
            'causer_id' => $this->customer->id,
        ]);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function customer_can_pay_invoice_online()
    {
        Mail::fake();

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2024-PAY',
            'total_amount' => 99.00,
            'status' => 'open',
        ]);

        // Visit payment page
        $response = $this->get("/customer/invoices/{$invoice->id}/pay");
        
        $response->assertStatus(200);
        $response->assertSee('Pay Invoice INV-2024-PAY');
        $response->assertSee('Amount Due: €99.00');
        
        // Payment method selection
        $response->assertSee('Select Payment Method');
        $response->assertSee('Credit/Debit Card');
        $response->assertSee('PayPal');
        $response->assertSee('Bank Transfer');
        
        // Select credit card payment
        $response = $this->post("/customer/invoices/{$invoice->id}/pay", [
            'payment_method' => 'stripe',
            'stripeToken' => 'tok_visa', // Test token
        ]);
        
        $response->assertRedirect("/customer/invoices/{$invoice->id}");
        $response->assertSessionHas('success', 'Payment processed successfully!');
        
        // Verify invoice was marked as paid
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(99.00, $invoice->paid_amount);
        $this->assertNotNull($invoice->paid_at);
        
        // Verify payment record was created
        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(99.00, $payment->amount);
        $this->assertEquals('stripe', $payment->payment_method);
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals('tok_visa', $payment->transaction_id);
        
        // Verify receipt email was sent
        Mail::assertQueued(PaymentReceiptEmail::class, function ($mail) use ($invoice) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->invoice->id === $invoice->id;
        });
        
        // Verify activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'description' => 'paid',
            'properties->amount' => 99.00,
            'properties->method' => 'stripe',
        ]);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function customer_can_setup_payment_plan_for_large_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2024-LARGE',
            'total_amount' => 1200.00,
            'status' => 'open',
            'allows_payment_plan' => true,
        ]);

        // Visit payment page
        $response = $this->get("/customer/invoices/{$invoice->id}/pay");
        
        $response->assertStatus(200);
        $response->assertSee('Payment Options');
        $response->assertSee('Pay in Full: €1,200.00');
        $response->assertSee('Payment Plan Available');
        
        // View payment plan options
        $response = $this->get("/customer/invoices/{$invoice->id}/payment-plan");
        
        $response->assertStatus(200);
        $response->assertSee('Payment Plan Options');
        $response->assertSee('3 monthly payments of €400.00');
        $response->assertSee('6 monthly payments of €200.00');
        $response->assertSee('No interest or fees');
        
        // Setup payment plan
        $response = $this->post("/customer/invoices/{$invoice->id}/payment-plan", [
            'plan_type' => '3_months',
            'payment_method' => 'stripe',
            'stripeToken' => 'tok_visa',
        ]);
        
        $response->assertRedirect("/customer/invoices/{$invoice->id}");
        $response->assertSessionHas('success', 'Payment plan setup successfully!');
        
        // Verify payment plan was created
        $this->assertDatabaseHas('payment_plans', [
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 1200.00,
            'installments' => 3,
            'installment_amount' => 400.00,
            'status' => 'active',
        ]);
        
        // Verify first payment was processed
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 400.00,
            'payment_method' => 'stripe',
            'status' => 'completed',
            'description' => 'Payment plan installment 1 of 3',
        ]);
        
        // Invoice should show partial payment
        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(400.00, $invoice->paid_amount);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function customer_receives_invoice_reminders()
    {
        Mail::fake();

        // Create invoice due in 3 days
        $dueSoonInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-DUE-SOON',
            'due_date' => now()->addDays(3),
            'total_amount' => 150.00,
            'status' => 'open',
            'reminder_sent' => false,
        ]);

        // Create overdue invoice
        $overdueInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-OVERDUE',
            'due_date' => now()->subDays(7),
            'total_amount' => 200.00,
            'status' => 'overdue',
            'reminder_count' => 0,
        ]);

        // Run reminder job
        $this->artisan('invoices:send-reminders')->assertSuccessful();
        
        // Verify reminder emails were sent
        Mail::assertQueued(InvoiceReminderEmail::class, function ($mail) use ($dueSoonInvoice) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->invoice->id === $dueSoonInvoice->id &&
                   $mail->reminderType === 'due_soon';
        });
        
        Mail::assertQueued(InvoiceReminderEmail::class, function ($mail) use ($overdueInvoice) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->invoice->id === $overdueInvoice->id &&
                   $mail->reminderType === 'overdue';
        });
        
        // Verify reminder flags were updated
        $dueSoonInvoice->refresh();
        $this->assertTrue($dueSoonInvoice->reminder_sent);
        
        $overdueInvoice->refresh();
        $this->assertEquals(1, $overdueInvoice->reminder_count);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function customer_can_dispute_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-DISPUTE',
            'total_amount' => 250.00,
            'status' => 'open',
        ]);

        // Visit dispute page
        $response = $this->get("/customer/invoices/{$invoice->id}/dispute");
        
        $response->assertStatus(200);
        $response->assertSee('Dispute Invoice');
        $response->assertSee('Please explain why you are disputing this invoice');
        
        // Submit dispute
        $response = $this->post("/customer/invoices/{$invoice->id}/dispute", [
            'reason' => 'incorrect_amount',
            'details' => 'The service was quoted at €200, not €250',
            'attachments' => [], // Could include file uploads
        ]);
        
        $response->assertRedirect("/customer/invoices/{$invoice->id}");
        $response->assertSessionHas('success', 'Your dispute has been submitted and will be reviewed.');
        
        // Verify dispute was created
        $this->assertDatabaseHas('invoice_disputes', [
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'reason' => 'incorrect_amount',
            'details' => 'The service was quoted at €200, not €250',
            'status' => 'pending',
        ]);
        
        // Invoice status should change
        $invoice->refresh();
        $this->assertEquals('disputed', $invoice->status);
        
        // Staff should be notified
        $this->assertDatabaseHas('notifications', [
            'type' => 'App\Notifications\InvoiceDisputedNotification',
            'notifiable_type' => 'App\Models\User',
        ]);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function customer_can_view_payment_history()
    {
        // Create paid invoices with payments
        $paidInvoice1 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'status' => 'paid',
            'paid_at' => now()->subMonth(),
        ]);

        Payment::create([
            'invoice_id' => $paidInvoice1->id,
            'customer_id' => $this->customer->id,
            'amount' => 100.00,
            'payment_method' => 'stripe',
            'status' => 'completed',
            'transaction_id' => 'ch_1234567890',
            'paid_at' => now()->subMonth(),
        ]);

        $response = $this->get('/customer/invoices/payments');
        
        $response->assertStatus(200);
        $response->assertSee('Payment History');
        $response->assertSee('Total Paid: €100.00');
        
        // Payment details
        $response->assertSee($paidInvoice1->invoice_number);
        $response->assertSee('€100.00');
        $response->assertSee('Credit Card');
        $response->assertSee('****7890'); // Last 4 digits of transaction ID
        $response->assertSee($paidInvoice1->paid_at->format('F j, Y'));
        
        // Download receipt option
        $response->assertSee('Download Receipt');
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function invoice_access_is_restricted_to_owner()
    {
        $otherCustomer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        $otherInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $otherCustomer->id,
        ]);

        // Try to access other customer's invoice
        $response = $this->get("/customer/invoices/{$otherInvoice->id}");
        
        $response->assertStatus(403);
        $response->assertSee('You do not have permission to view this invoice');
        
        // Try to download other customer's invoice
        $response = $this->get("/customer/invoices/{$otherInvoice->id}/download");
        
        $response->assertStatus(403);
        
        // Try to pay other customer's invoice
        $response = $this->post("/customer/invoices/{$otherInvoice->id}/pay", [
            'payment_method' => 'stripe',
            'stripeToken' => 'tok_visa',
        ]);
        
        $response->assertStatus(403);
    }
}