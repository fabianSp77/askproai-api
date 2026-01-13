<?php

namespace App\Console\Commands;

use App\Mail\PartnerInvoiceMail;
use App\Models\AggregateInvoice;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestPartnerInvoiceEmailCommand extends Command
{
    protected $signature = 'billing:test-email
        {email : Ziel-E-Mail-Adresse}
        {--invoice= : Bestehende Invoice-ID (optional)}
        {--preview : Nur HTML anzeigen, nicht senden}';

    protected $description = 'Sendet eine Test-Partner-Rechnung per E-Mail';

    public function handle(): int
    {
        $email = $this->argument('email');
        $invoiceId = $this->option('invoice');

        $this->info("🔍 Lade Invoice-Daten...");

        // Invoice laden oder Test-Invoice erstellen
        if ($invoiceId) {
            $invoice = AggregateInvoice::with('partnerCompany')->findOrFail($invoiceId);
            $this->info("✅ Bestehende Invoice #{$invoiceId} geladen");
        } else {
            $invoice = $this->getOrCreateTestInvoice();
            $this->info("✅ Test-Invoice erstellt/geladen");
        }

        $this->newLine();
        $this->info("📄 Invoice: {$invoice->invoice_number}");
        $this->info("💰 Total: {$invoice->formatted_total}");
        $this->info("📅 Periode: {$invoice->billing_period_display}");
        $this->info("🏢 Partner: {$invoice->partnerCompany->name}");

        if ($invoice->stripe_hosted_invoice_url) {
            $this->info("🔗 Stripe URL: {$invoice->stripe_hosted_invoice_url}");
        } else {
            $this->warn("⚠️ Keine Stripe URL vorhanden - setze Mock-URL");
            $invoice->stripe_hosted_invoice_url = 'https://invoice.stripe.com/i/test_' . $invoice->id;
        }

        $mail = new PartnerInvoiceMail($invoice);

        if ($this->option('preview')) {
            $this->newLine();
            $this->line("=== HTML Preview ===");
            $this->newLine();
            $this->line($mail->render());
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("📧 Sende an: {$email}");

        try {
            Mail::to($email)->send($mail);
            $this->newLine();
            $this->info("✅ E-Mail erfolgreich gesendet!");
            $this->info("   Prüfe dein Postfach: {$email}");
        } catch (\Exception $e) {
            $this->error("❌ E-Mail-Versand fehlgeschlagen: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getOrCreateTestInvoice(): AggregateInvoice
    {
        // Versuche existierende offene Invoice zu finden
        $invoice = AggregateInvoice::whereHas('partnerCompany', function ($q) {
            $q->where('is_partner', true);
        })
        ->where('status', AggregateInvoice::STATUS_OPEN)
        ->with('partnerCompany')
        ->first();

        if ($invoice) {
            $this->info("   → Existierende Invoice gefunden: #{$invoice->id}");
            return $invoice;
        }

        // Erstelle Test-Partner wenn nötig
        $partner = Company::where('is_partner', true)->first();

        if (!$partner) {
            $this->info("   → Erstelle Test-Partner...");
            $partner = Company::factory()->create([
                'name' => 'Test Partner GmbH',
                'is_partner' => true,
                'partner_billing_email' => 'test@askproai.de',
                'partner_payment_terms_days' => 14,
            ]);
        }

        $this->info("   → Partner: {$partner->name}");
        $this->info("   → Erstelle Test-Invoice...");

        // Erstelle Test-Invoice mit Factory
        return AggregateInvoice::factory()
            ->for($partner, 'partnerCompany')
            ->sent()
            ->withStripe()
            ->create();
    }
}
