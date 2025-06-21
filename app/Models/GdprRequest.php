<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'company_id',
        'type',
        'status',
        'reason',
        'admin_notes',
        'exported_data',
        'export_file_path',
        'requested_at',
        'processed_at',
        'completed_at',
        'processed_by',
    ];

    protected $casts = [
        'exported_data' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->company_id) {
                $model->company_id = auth()->user()?->company_id ?? session('company_id');
            }
            $model->requested_at = now();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);
    }

    public function markAsCompleted(array $data = []): void
    {
        $this->update(array_merge($data, [
            'status' => 'completed',
            'completed_at' => now(),
        ]));
    }

    public function markAsRejected(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $reason,
            'completed_at' => now(),
        ]);
    }

    public function isExpired(): bool
    {
        // GDPR requires response within 30 days
        return $this->requested_at->addDays(30)->isPast();
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status === 'completed' || $this->status === 'rejected') {
            return 0;
        }

        return max(0, now()->diffInDays($this->requested_at->addDays(30), false));
    }
}