<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\TenantScope;

class CustomerService extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'service_id',
        'invoice_id',
        'quantity',
        'unit_price',
        'total_price',
        'service_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'service_date' => 'date',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_INVOICED = 'invoiced';
    const STATUS_CANCELLED = 'cancelled';

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
        
        // Auto-calculate total price
        static::saving(function ($model) {
            if ($model->quantity && $model->unit_price) {
                $model->total_price = $model->quantity * $model->unit_price;
            }
        });
    }

    /**
     * Get the company that owns the customer service.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that owns the customer service.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the service definition.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(AdditionalService::class, 'service_id');
    }

    /**
     * Get the invoice that includes this service.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who created this service entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if service is pending invoicing.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if service has been invoiced.
     */
    public function isInvoiced(): bool
    {
        return $this->status === self::STATUS_INVOICED;
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_INVOICED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_INVOICED => 'Abgerechnet',
            self::STATUS_CANCELLED => 'Storniert',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope for pending services.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for invoiced services.
     */
    public function scopeInvoiced($query)
    {
        return $query->where('status', self::STATUS_INVOICED);
    }

    /**
     * Scope for services in date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('service_date', [$startDate, $endDate]);
    }

    /**
     * Get formatted description for invoice.
     */
    public function getInvoiceDescriptionAttribute(): string
    {
        $description = $this->service->name;
        
        if ($this->notes) {
            $description .= ': ' . $this->notes;
        }
        
        return $description;
    }
}