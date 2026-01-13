<?php

namespace App\Policies;

use App\Models\AggregateInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for AggregateInvoice (Partner-Level Monthly Invoices).
 *
 * Controls access to sensitive invoice operations like void, send, and markPaid.
 * Implements role-based access control with partner-company scoping.
 */
class AggregateInvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Super admins bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    /**
     * Determine if the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin', 'manager', 'billing_manager', 'accountant',
            'partner_admin', 'partner_owner', 'partner_manager',
            'reseller_admin', 'reseller_owner',
        ]);
    }

    /**
     * Determine if the user can view the invoice.
     */
    public function view(User $user, AggregateInvoice $invoice): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Billing staff can view all invoices
        if ($user->hasAnyRole(['billing_manager', 'accountant', 'manager'])) {
            return true;
        }

        // Partner can view their own invoices
        if ($user->hasAnyRole(['partner_admin', 'partner_owner', 'partner_manager'])) {
            return $user->company_id === $invoice->partner_company_id;
        }

        // Resellers can view invoices of their managed companies
        if ($user->hasAnyRole(['reseller_admin', 'reseller_owner'])) {
            if ($invoice->partnerCompany && $invoice->partnerCompany->parent_company_id === $user->company_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user can create invoices.
     * Only billing staff and admins can create aggregate invoices.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }

    /**
     * Determine if the user can update the invoice.
     * Only draft invoices can be updated.
     */
    public function update(User $user, AggregateInvoice $invoice): bool
    {
        // Only draft invoices can be updated
        if ($invoice->status !== AggregateInvoice::STATUS_DRAFT) {
            return $user->hasRole('admin');
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasAnyRole(['billing_manager', 'accountant'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the invoice.
     * Only draft invoices can be deleted.
     */
    public function delete(User $user, AggregateInvoice $invoice): bool
    {
        // Paid or void invoices cannot be deleted
        if (in_array($invoice->status, [AggregateInvoice::STATUS_PAID, AggregateInvoice::STATUS_VOID])) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can restore soft-deleted invoices.
     */
    public function restore(User $user, AggregateInvoice $invoice): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, AggregateInvoice $invoice): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can finalize the invoice (draft -> open).
     */
    public function finalize(User $user, AggregateInvoice $invoice): bool
    {
        if ($invoice->status !== AggregateInvoice::STATUS_DRAFT) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }

    /**
     * Determine if the user can send the invoice via Stripe.
     */
    public function send(User $user, AggregateInvoice $invoice): bool
    {
        // Can only send open invoices
        if ($invoice->status !== AggregateInvoice::STATUS_OPEN) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }

    /**
     * Determine if the user can resend the invoice.
     */
    public function resend(User $user, AggregateInvoice $invoice): bool
    {
        // Can only resend open invoices that were already sent
        if ($invoice->status !== AggregateInvoice::STATUS_OPEN || !$invoice->sent_at) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }

    /**
     * Determine if the user can mark the invoice as paid.
     */
    public function markAsPaid(User $user, AggregateInvoice $invoice): bool
    {
        // Can only mark open invoices as paid
        if ($invoice->status !== AggregateInvoice::STATUS_OPEN) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }

    /**
     * Determine if the user can void the invoice.
     */
    public function void(User $user, AggregateInvoice $invoice): bool
    {
        // Paid invoices cannot be voided
        if ($invoice->status === AggregateInvoice::STATUS_PAID) {
            return false;
        }

        // Already voided
        if ($invoice->status === AggregateInvoice::STATUS_VOID) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'billing_manager']);
    }

    /**
     * Determine if the user can mark the invoice as uncollectible.
     */
    public function markAsUncollectible(User $user, AggregateInvoice $invoice): bool
    {
        // Can only mark open invoices as uncollectible
        if ($invoice->status !== AggregateInvoice::STATUS_OPEN) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'billing_manager']);
    }

    /**
     * Determine if the user can download the PDF.
     * Partners can download their own invoices.
     */
    public function downloadPdf(User $user, AggregateInvoice $invoice): bool
    {
        // Anyone who can view can download
        return $this->view($user, $invoice);
    }

    /**
     * Determine if the user can view Stripe dashboard link.
     * Only internal staff should see Stripe admin links.
     */
    public function viewStripeLink(User $user, AggregateInvoice $invoice): bool
    {
        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }
}
