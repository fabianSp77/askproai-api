<?php

namespace Tests\Integration\Stripe;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\BillingPeriod;
use App\Filament\Admin\Resources\InvoiceResource;
use App\Services\StripeInvoiceService;
use Livewire\Livewire;
use Filament\Actions\DeleteAction;
use Mockery;
use Stripe\StripeClient;
use Carbon\Carbon;

class InvoiceManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $company;
    protected StripeInvoiceService $stripeService;
    protected $stripeMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@example.com',
        ]);

        // Create company
        $this->company = Company::factory()->create([
            'stripe_customer_id' => 'cus_test123',
        ]);

        // Mock Stripe client
        $this->stripeMock = Mockery::mock(StripeClient::class);
        $this->stripeMock->invoices = Mockery::mock();
        $this->stripeMock->invoiceItems = Mockery::mock();
        $this->stripeMock->creditNotes = Mockery::mock();

        $this->app->bind(StripeClient::class, function () {
            return $this->stripeMock;
        });

        $this->stripeService = app(StripeInvoiceService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_viewing_invoice_list_with_filters()
    {
        // Create various invoices
        $paidInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paid',
            'total_amount' => 150.00,
            'paid_at' => Carbon::now()->subDays(5),
        ]);

        $pendingInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'finalized',
            'total_amount' => 200.00,
            'due_date' => Carbon::now()->addDays(7),
        ]);

        $overdueInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'finalized',
            'total_amount' => 300.00,
            'due_date' => Carbon::now()->subDays(3),
        ]);

        $failedInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'payment_failed',
            'total_amount' => 100.00,
            'payment_attempts' => 3,
        ]);

        // Act as admin and visit invoice list
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/invoices');
        $response->assertOk();
        $response->assertSee('Invoices');
        
        // Test filtering by status
        Livewire::test(InvoiceResource\Pages\ListInvoices::class)
            ->assertCanSeeTableRecords([$paidInvoice, $pendingInvoice, $overdueInvoice, $failedInvoice])
            ->filterTable('status', 'paid')
            ->assertCanSeeTableRecords([$paidInvoice])
            ->assertCanNotSeeTableRecords([$pendingInvoice, $overdueInvoice, $failedInvoice])
            ->resetTableFilters()
            ->filterTable('status', 'overdue')
            ->assertCanSeeTableRecords([$overdueInvoice])
            ->assertCanNotSeeTableRecords([$paidInvoice, $pendingInvoice, $failedInvoice]);

        // Test filtering by date range
        Livewire::test(InvoiceResource\Pages\ListInvoices::class)
            ->filterTable('created_at', [
                'from' => Carbon::now()->subDays(6)->format('Y-m-d'),
                'to' => Carbon::now()->subDays(4)->format('Y-m-d'),
            ])
            ->assertCanSeeTableRecords([$paidInvoice])
            ->assertCanNotSeeTableRecords([$pendingInvoice, $overdueInvoice, $failedInvoice]);

        // Test search functionality
        Livewire::test(InvoiceResource\Pages\ListInvoices::class)
            ->searchTable($paidInvoice->invoice_number)
            ->assertCanSeeTableRecords([$paidInvoice])
            ->assertCanNotSeeTableRecords([$pendingInvoice, $overdueInvoice, $failedInvoice]);
    }

    /** @test */
    public function it_allows_creating_manual_invoice_with_line_items()
    {
        $this->actingAs($this->admin);

        $billingPeriod = BillingPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
            'status' => 'open',
        ]);

        // Mock Stripe invoice creation
        $this->stripeMock->invoiceItems->shouldReceive('create')
            ->times(2)
            ->andReturn((object)['id' => 'ii_test1'], (object)['id' => 'ii_test2']);

        $this->stripeMock->invoices->shouldReceive('create')
            ->once()
            ->andReturn((object)[
                'id' => 'in_manual',
                'number' => 'INV-2024-100',
                'status' => 'draft',
                'amount_due' => 35700, // 300 + 57 tax
                'subtotal' => 30000,
                'tax' => 5700,
                'created' => time(),
            ]);

        Livewire::test(InvoiceResource\Pages\CreateInvoice::class)
            ->fillForm([
                'company_id' => $this->company->id,
                'billing_period_id' => $billingPeriod->id,
                'invoice_date' => Carbon::now()->format('Y-m-d'),
                'due_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
                'items' => [
                    [
                        'description' => 'Consulting Services',
                        'quantity' => 10,
                        'unit_price' => 20.00,
                        'total' => 200.00,
                    ],
                    [
                        'description' => 'Setup Fee',
                        'quantity' => 1,
                        'unit_price' => 100.00,
                        'total' => 100.00,
                    ],
                ],
                'notes' => 'Thank you for your business',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        // Verify invoice was created in database
        $invoice = Invoice::where('stripe_invoice_id', 'in_manual')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(357.00, $invoice->total_amount);
        $this->assertEquals(300.00, $invoice->subtotal);
        $this->assertEquals(57.00, $invoice->tax_amount);
        $this->assertEquals('draft', $invoice->status);

        // Verify line items
        $this->assertEquals(2, $invoice->items()->count());
        $item1 = $invoice->items()->where('description', 'Consulting Services')->first();
        $this->assertEquals(10, $item1->quantity);
        $this->assertEquals(20.00, $item1->unit_price);
    }

    /** @test */
    public function it_allows_editing_draft_invoice()
    {
        $this->actingAs($this->admin);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'stripe_invoice_id' => 'in_draft',
            'status' => 'draft',
            'subtotal' => 100.00,
            'tax_amount' => 19.00,
            'total_amount' => 119.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Original Item',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        // Mock Stripe update
        $this->stripeMock->invoices->shouldReceive('update')
            ->once()
            ->with('in_draft', Mockery::on(function ($data) {
                return $data['metadata']['notes'] === 'Updated notes';
            }))
            ->andReturn((object)['id' => 'in_draft']);

        $this->stripeMock->invoiceItems->shouldReceive('create')
            ->once()
            ->andReturn((object)['id' => 'ii_new']);

        Livewire::test(InvoiceResource\Pages\EditInvoice::class, ['record' => $invoice->id])
            ->assertFormSet([
                'status' => 'draft',
                'notes' => $invoice->notes,
            ])
            ->fillForm([
                'notes' => 'Updated notes',
                'items' => [
                    [
                        'description' => 'Original Item',
                        'quantity' => 1,
                        'unit_price' => 100.00,
                        'total' => 100.00,
                    ],
                    [
                        'description' => 'Additional Service',
                        'quantity' => 2,
                        'unit_price' => 50.00,
                        'total' => 100.00,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Verify changes
        $invoice->refresh();
        $this->assertEquals('Updated notes', $invoice->notes);
        $this->assertEquals(2, $invoice->items()->count());
        $this->assertEquals(238.00, $invoice->total_amount); // 200 + 38 tax
    }

    /** @test */
    public function it_prevents_editing_finalized_invoice()
    {
        $this->actingAs($this->admin);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'finalized',
        ]);

        Livewire::test(InvoiceResource\Pages\EditInvoice::class, ['record' => $invoice->id])
            ->assertFormDisabled(['items', 'subtotal', 'tax_amount'])
            ->assertSee('This invoice has been finalized and cannot be edited');
    }

    /** @test */
    public function it_allows_sending_invoice_to_customer()
    {
        $this->actingAs($this->admin);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'stripe_invoice_id' => 'in_tosend',
            'status' => 'draft',
            'total_amount' => 200.00,
        ]);

        // Mock Stripe finalize and send
        $this->stripeMock->invoices->shouldReceive('finalizeInvoice')
            ->once()
            ->with('in_tosend')
            ->andReturn((object)[
                'id' => 'in_tosend',
                'status' => 'open',
                'hosted_invoice_url' => 'https://invoice.stripe.com/test',
                'invoice_pdf' => 'https://invoice.stripe.com/test.pdf',
            ]);

        $this->stripeMock->invoices->shouldReceive('sendInvoice')
            ->once()
            ->with('in_tosend')
            ->andReturn((object)['id' => 'in_tosend']);

        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])
            ->callAction('send')
            ->assertNotified('Invoice sent successfully');

        // Verify invoice status updated
        $invoice->refresh();
        $this->assertEquals('finalized', $invoice->status);
        $this->assertNotNull($invoice->sent_at);
        $this->assertNotNull($invoice->invoice_url);
    }

    /** @test */
    public function it_allows_marking_invoice_as_paid_manually()
    {
        $this->actingAs($this->admin);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'stripe_invoice_id' => 'in_topay',
            'status' => 'finalized',
            'total_amount' => 150.00,
        ]);

        // Mock Stripe mark as paid
        $this->stripeMock->invoices->shouldReceive('pay')
            ->once()
            ->with('in_topay', ['paid_out_of_band' => true])
            ->andReturn((object)[
                'id' => 'in_topay',
                'status' => 'paid',
                'paid' => true,
                'paid_at' => time(),
            ]);

        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])
            ->callAction('markAsPaid', [
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'BANK-REF-123',
                'paid_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ])
            ->assertNotified('Invoice marked as paid');

        // Verify invoice and payment record
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(150.00, $payment->amount);
        $this->assertEquals('bank_transfer', $payment->payment_method);
        $this->assertEquals('BANK-REF-123', $payment->reference);
    }

    /** @test */
    public function it_allows_issuing_credit_note_for_paid_invoice()
    {
        $this->actingAs($this->admin);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'stripe_invoice_id' => 'in_paid',
            'status' => 'paid',
            'total_amount' => 238.00,
            'subtotal' => 200.00,
            'tax_amount' => 38.00,
            'paid_at' => Carbon::now()->subDays(5),
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Service A',
            'quantity' => 10,
            'unit_price' => 20.00,
            'total' => 200.00,
        ]);

        // Mock Stripe credit note creation
        $this->stripeMock->creditNotes->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['invoice'] === 'in_paid' &&
                       $data['amount'] === 11900 && // 100 + 19 tax
                       $data['reason'] === 'product_unsatisfactory';
            }))
            ->andReturn((object)[
                'id' => 'cn_test123',
                'number' => 'CN-2024-001',
                'amount' => 11900,
                'status' => 'issued',
                'created' => time(),
            ]);

        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])
            ->callAction('issueCreditNote', [
                'amount' => 100.00,
                'reason' => 'product_unsatisfactory',
                'description' => 'Partial refund for Service A',
                'refund_to_payment_method' => true,
            ])
            ->assertNotified('Credit note issued successfully');

        // Verify credit note record
        $this->assertDatabaseHas('credit_notes', [
            'invoice_id' => $invoice->id,
            'stripe_credit_note_id' => 'cn_test123',
            'amount' => 119.00, // Including tax
            'reason' => 'product_unsatisfactory',
        ]);

        // Verify company credit balance if not refunded
        if (!true) { // if refund_to_payment_method was false
            $this->assertEquals(119.00, $this->company->fresh()->credit_balance);
        }
    }

    /** @test */
    public function it_handles_bulk_invoice_actions()
    {
        $this->actingAs($this->admin);

        // Create multiple draft invoices
        $invoices = collect();
        for ($i = 1; $i <= 3; $i++) {
            $invoices->push(Invoice::factory()->create([
                'company_id' => $this->company->id,
                'stripe_invoice_id' => "in_bulk_$i",
                'status' => 'draft',
                'total_amount' => 100 * $i,
            ]));
        }

        // Mock Stripe bulk finalize
        foreach ($invoices as $invoice) {
            $this->stripeMock->invoices->shouldReceive('finalizeInvoice')
                ->once()
                ->with($invoice->stripe_invoice_id)
                ->andReturn((object)[
                    'id' => $invoice->stripe_invoice_id,
                    'status' => 'open',
                ]);
        }

        Livewire::test(InvoiceResource\Pages\ListInvoices::class)
            ->callTableBulkAction('finalize', $invoices->pluck('id')->toArray())
            ->assertNotified('3 invoices finalized successfully');

        // Verify all invoices were finalized
        foreach ($invoices as $invoice) {
            $this->assertEquals('finalized', $invoice->fresh()->status);
        }
    }

    /** @test */
    public function it_allows_downloading_invoice_pdf()
    {
        $this->actingAs($this->admin);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'stripe_invoice_id' => 'in_download',
            'status' => 'paid',
            'pdf_url' => 'https://invoice.stripe.com/test.pdf',
        ]);

        $response = $this->get("/admin/invoices/{$invoice->id}/download");
        
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $invoice->invoice_number . '.pdf"');
    }

    /** @test */
    public function it_shows_invoice_preview_with_company_branding()
    {
        $this->actingAs($this->admin);

        $this->company->update([
            'logo_url' => 'https://example.com/logo.png',
            'primary_color' => '#1a56db',
            'invoice_footer_text' => 'Thank you for your business!',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        InvoiceItem::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);

        $response = $this->get("/admin/invoices/{$invoice->id}/preview");
        
        $response->assertOk();
        $response->assertSee($this->company->name);
        $response->assertSee($this->company->logo_url);
        $response->assertSee('Thank you for your business!');
        $response->assertSee('style="color: #1a56db"', false);
    }

    /** @test */
    public function it_tracks_invoice_view_history()
    {
        $this->actingAs($this->admin);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // View invoice multiple times
        for ($i = 0; $i < 3; $i++) {
            $this->get("/admin/invoices/{$invoice->id}");
        }

        // Check activity log
        $activities = $invoice->activities()
            ->where('event', 'viewed')
            ->where('causer_id', $this->admin->id)
            ->get();

        $this->assertCount(3, $activities);
        
        foreach ($activities as $activity) {
            $this->assertEquals('invoice', $activity->subject_type);
            $this->assertEquals($invoice->id, $activity->subject_id);
            $this->assertArrayHasKey('ip_address', $activity->properties);
            $this->assertArrayHasKey('user_agent', $activity->properties);
        }
    }

    /** @test */
    public function it_validates_invoice_permissions()
    {
        // Create non-admin user with limited permissions
        $user = User::factory()->create([
            'is_admin' => false,
            'permissions' => ['view_invoices', 'create_invoices'],
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paid',
        ]);

        $this->actingAs($user);

        // Can view
        $response = $this->get("/admin/invoices/{$invoice->id}");
        $response->assertOk();

        // Cannot delete
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])
            ->assertActionHidden('delete');

        // Cannot issue credit note without permission
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])
            ->assertActionHidden('issueCreditNote');
    }

    /** @test */
    public function it_handles_webhook_updates_to_invoices()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'stripe_invoice_id' => 'in_webhook',
            'status' => 'finalized',
        ]);

        // Simulate webhook event
        $webhookData = [
            'type' => 'invoice.updated',
            'data' => [
                'object' => [
                    'id' => 'in_webhook',
                    'status' => 'paid',
                    'paid' => true,
                    'paid_at' => time(),
                    'payment_intent' => 'pi_webhook',
                ],
            ],
        ];

        // Process webhook
        $this->stripeService->handleWebhookEvent($webhookData);

        // Verify invoice was updated
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertEquals('pi_webhook', $invoice->stripe_payment_intent_id);

        // Verify admin can see the update in activity log
        $this->actingAs($this->admin);
        
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])
            ->assertSee('Invoice updated via webhook')
            ->assertSee('Status changed from finalized to paid');
    }
}