<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Transaction $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the data to be sent in the webhook
     */
    public function getWebhookData(): array
    {
        return [
            'event' => 'transaction.created',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => $this->transaction->id,
                'tenant_id' => $this->transaction->tenant_id,
                'type' => $this->transaction->type,
                'amount_cents' => $this->transaction->amount_cents,
                'balance_before_cents' => $this->transaction->balance_before_cents,
                'balance_after_cents' => $this->transaction->balance_after_cents,
                'description' => $this->transaction->description,
                'metadata' => $this->transaction->metadata,
                'created_at' => $this->transaction->created_at->toIso8601String(),
            ]
        ];
    }
}