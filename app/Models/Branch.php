<?php

namespace App\Models;

use App\Models\Concerns\IsUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use IsUuid, SoftDeletes, HasFactory;

    /**
     * The relationships that should always be loaded.
     * Prevents N+1 query issues by eager loading common relationships.
     */
    protected $with = ['company'];

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'company_id',
        'customer_id',
        'name',
        'slug',
        'city',
        'phone_number',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'send_call_summaries' => 'boolean',
        'include_transcript_in_summary' => 'boolean',
        'include_csv_export' => 'boolean',
        'invoice_recipient' => 'boolean',
        'call_summary_recipients' => 'array',
        'call_notification_overrides' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'home_branch_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
