<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'billing_manager', 'accountant']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        // Admin can view all transactions
        if ($user->hasRole('admin')) {
            return true;
        }

        // Billing managers and accountants can view company transactions
        if ($user->hasAnyRole(['billing_manager', 'accountant']) &&
            $user->company_id === $transaction->company_id) {
            return true;
        }

        // Managers can view their company's transactions
        if ($user->hasRole('manager') && $user->company_id === $transaction->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // Completed transactions cannot be updated
        if ($transaction->status === 'completed') {
            return $user->hasRole('admin');
        }

        // Admin can update all transactions
        if ($user->hasRole('admin')) {
            return true;
        }

        // Billing managers can update pending transactions in their company
        if ($user->hasRole('billing_manager') &&
            $user->company_id === $transaction->company_id &&
            in_array($transaction->status, ['pending', 'processing'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // Completed transactions cannot be deleted
        if ($transaction->status === 'completed') {
            return false;
        }

        // Only admins can delete transactions
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can refund the transaction.
     */
    public function refund(User $user, Transaction $transaction): bool
    {
        // Can only refund completed transactions
        if ($transaction->status !== 'completed') {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        // Billing managers can process refunds for their company
        if ($user->hasRole('billing_manager') &&
            $user->company_id === $transaction->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export transactions.
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'billing_manager', 'accountant']);
    }
}