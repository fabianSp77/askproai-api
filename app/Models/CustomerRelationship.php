<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;

class CustomerRelationship extends Model
{
    use BelongsToCompany;
    
    protected $fillable = [
        'customer_id',
        'related_customer_id',
        'company_id',
        'relationship_type',
        'confidence_score',
        'matching_details',
        'status',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];
    
    protected $casts = [
        'matching_details' => 'array',
        'confidence_score' => 'integer',
        'confirmed_at' => 'datetime',
    ];
    
    /**
     * Der Hauptkunde
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    
    /**
     * Der verknüpfte Kunde
     */
    public function relatedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'related_customer_id');
    }
    
    /**
     * Die Firma
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Wer hat die Beziehung erstellt
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Wer hat die Beziehung bestätigt
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
    
    /**
     * Scope für bestätigte Beziehungen
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'user_confirmed');
    }
    
    /**
     * Scope für hohe Confidence
     */
    public function scopeHighConfidence($query, $minScore = 80)
    {
        return $query->where('confidence_score', '>=', $minScore);
    }
}
