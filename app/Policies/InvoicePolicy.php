<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin', 'manager', 'billing_manager', 'accountant',
            'company_owner', 'company_admin', 'company_manager', 'company_staff'
        ]);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Direct company match - includes customer portal roles
        if ($user->hasAnyRole(['billing_manager', 'accountant', 'manager',
                               'company_owner', 'company_admin', 'company_manager', 'company_staff']) &&
            $user->company_id === $invoice->company_id) {
            return true;
        }

        // ✅ FIX: Resellers can view their customers' invoices
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            if ($invoice->company && $invoice->company->parent_company_id === $user->company_id) {
                return true;
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        // Paid invoices cannot be updated
        if ($invoice->status === 'paid') {
            return $user->hasRole('admin');
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasAnyRole(['billing_manager', 'accountant']) &&
            $user->company_id === $invoice->company_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        // Paid invoices cannot be deleted
        if ($invoice->status === 'paid') {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('super_admin');
    }

    public function send(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasAnyRole(['billing_manager', 'accountant']) &&
            $user->company_id === $invoice->company_id) {
            return true;
        }

        return false;
    }

    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        return $this->send($user, $invoice);
    }
}