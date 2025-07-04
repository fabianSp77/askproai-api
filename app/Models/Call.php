<?php

namespace App\Models;

use App\Traits\BelongsToCompany;

use App\Helpers\SafeQueryHelper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Scopes\TenantScope;
use App\Listeners\CallEventListener;

class Call extends Model
{
    use BelongsToCompany;

    use HasFactory;
    
    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => \App\Events\CallCreated::class,
        'updated' => \App\Events\CallUpdated::class,
    ];

    protected $fillable = [
        'call_id',
        'caller',
        'from_number',
        'to_number',
        'retell_call_id',
        'retell_agent_id',
        'call_status',
        'transcript',
        'transcription_id',
        'recording_url',
        'call_type',
        'audio_url',
        'video_url',
        'duration_sec',
        'duration_minutes',
        'duration',  // Added for the new column
        'cost',
        'cost_cents',
        'customer_id',
        'appointment_id',
        'agent_id',
        'company_id',
        'branch_id',
        'staff_id',
        'analysis',
        'webhook_data',
        'raw',
        'raw_data',
        'notes',
        'metadata',
        'tags',
        'sentiment',
        'sentiment_score',  // Added for the new column
        'status',  // Added for the new column
        // New Retell fields
        'start_timestamp',
        'end_timestamp',
        'direction',
        'disconnection_reason',
        'transcript_object',
        'transcript_with_tools',
        'latency_metrics',
        'cost_breakdown',
        'llm_usage',
        'public_log_url',
        'retell_dynamic_variables',
        'opt_out_sensitive_data',
        'details',
        'external_id',
        'phone_number',
        'conversation_id',
        'call_successful',
        'tmp_call_id',
        'agent_version',
        'retell_cost',
        'custom_sip_headers',
        // New fields from recent migration
        'agent_name',
        'urgency_level',
        'no_show_count',
        'reschedule_count',
        'first_visit',
        'insurance_type',
        'insurance_company',
        'custom_analysis_data',
        'call_summary',
        'llm_token_usage',
        'user_sentiment',
        'extracted_name',
        'extracted_email',
        'appointment_made',
        'appointment_requested',
        'reason_for_visit',
        'versicherungsstatus',
        'health_insurance_company',
        'end_to_end_latency',
        'customer_data_backup',
        'customer_data_collected_at',
        // Language detection
        'detected_language',
        'language_confidence',
        'language_mismatch'
    ];

    protected $casts = [
        'analysis' => 'array',
        'webhook_data' => 'array',
        'raw' => 'array',
        'raw_data' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'cost' => 'decimal:2',
        'duration_minutes' => 'decimal:2',
        'duration' => 'integer',  // Added for the new column
        'sentiment_score' => 'decimal:1',  // Added for the new column
        'details' => 'array',
        'transcript_object' => 'array',
        'transcript_with_tools' => 'array',
        'latency_metrics' => 'array',
        'cost_breakdown' => 'array',
        'llm_usage' => 'array',
        'retell_dynamic_variables' => 'array',
        'custom_sip_headers' => 'array',
        'custom_analysis_data' => 'array',
        'llm_token_usage' => 'array',
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
        'opt_out_sensitive_data' => 'boolean',
        'call_successful' => 'boolean',
        'first_visit' => 'boolean',
        'appointment_made' => 'boolean',
        'appointment_requested' => 'boolean',
        'agent_version' => 'integer',
        'no_show_count' => 'integer',
        'reschedule_count' => 'integer',
        'end_to_end_latency' => 'integer',
        'retell_cost' => 'decimal:4',
        'customer_data_backup' => 'array',
        'customer_data_collected_at' => 'datetime',
        'language_confidence' => 'decimal:2',
        'language_mismatch' => 'boolean'
    ];

    /**
     * Scope for recent calls
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days))
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for today's calls
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today())
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for calls in a date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        return $query->whereBetween('created_at', [$start, $end])
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for calls by status
     */
    public function scopeByStatus(Builder $query, $status): Builder
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }
        
        return $query->where('status', $status);
    }

    /**
     * Scope for successful calls
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('successful', true);
    }

    /**
     * Scope for failed calls
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('successful', false);
    }

    /**
     * Scope for calls by phone number
     */
    public function scopeFromNumber(Builder $query, string $phoneNumber): Builder
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        
        return $query->where(function ($q) use ($phoneNumber, $normalizedPhone) {
            $lastTenDigits = substr($normalizedPhone, -10);
            
            $q->where('from_number', $phoneNumber)
              ->orWhere('from_number', $normalizedPhone)
              ->orWhere(function($subQ) use ($lastTenDigits) {
                  SafeQueryHelper::whereLike($subQ, 'from_number', $lastTenDigits, 'left');
              });
        });
    }

    /**
     * Scope for calls by company
     */
    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope for calls with customer
     */
    public function scopeWithCustomer(Builder $query): Builder
    {
        return $query->whereNotNull('customer_id');
    }

    /**
     * Scope for calls without customer
     */
    public function scopeWithoutCustomer(Builder $query): Builder
    {
        return $query->whereNull('customer_id');
    }

    /**
     * Scope for calls with appointment
     */
    public function scopeWithAppointment(Builder $query): Builder
    {
        return $query->whereNotNull('appointment_id');
    }

    /**
     * Scope for calls with relations
     */
    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with([
            'customer:id,name,phone,email',
            'appointment:id,starts_at,status'
        ]);
    }

    /**
     * Scope for calls with high duration
     */
    public function scopeLongDuration(Builder $query, int $seconds = 300): Builder
    {
        return $query->where('duration_sec', '>', $seconds);
    }

    /**
     * Scope for calls with high cost
     */
    public function scopeHighCost(Builder $query, float $amount = 5.0): Builder
    {
        return $query->where('cost', '>', $amount);
    }

    /**
     * Normalize phone number for consistent searching
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If it starts with country code, keep it
        if (strpos($phone, '49') === 0) {
            return '+' . $phone;
        }
        
        // If it starts with 0, replace with +49
        if (strpos($phone, '0') === 0) {
            return '+49' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    /**
     * Get has_recording attribute
     */
    public function getHasRecordingAttribute(): bool
    {
        return !empty($this->webhook_data['recording_url']);
    }
    
    /**
     * Get sentiment attribute
     */
    public function getSentimentAttribute(): ?string
    {
        return $this->analysis['sentiment'] ?? null;
    }
    
    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        return gmdate('i:s', $this->duration_sec ?? 0);
    }
    
    /**
     * Extract entities from transcript
     */
    public function extractEntities(): array
    {
        $entities = [];
        
        // Get transcript from various sources
        $transcript = '';
        if (!empty($this->transcript)) {
            $transcript = $this->transcript;
        } elseif (!empty($this->webhook_data)) {
            // Check if webhook_data is a JSON string or array
            $webhookData = is_string($this->webhook_data) ? json_decode($this->webhook_data, true) : $this->webhook_data;
            if (is_array($webhookData) && isset($webhookData['transcript'])) {
                $transcript = $webhookData['transcript'];
            }
        }
        
        // If no transcript found, return empty entities
        if (empty($transcript)) {
            return $entities;
        }
        
        // Extract email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $transcript, $matches)) {
            $entities['email'] = $matches[0];
        }
        
        // Extract phone numbers
        if (preg_match('/(?:\+49|0)[0-9\s\-\/]{10,}/', $transcript, $matches)) {
            $entities['phone'] = $matches[0];
        }
        
        // Extract dates (simple German format)
        if (preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4}|\d{2})/', $transcript, $matches)) {
            $entities['date'] = $matches[0];
        }
        
        // Extract times
        if (preg_match('/(\d{1,2}):(\d{2})\s?(Uhr)?/', $transcript, $matches)) {
            $entities['time'] = $matches[0];
        }
        
        return $entities;
    }
    
    /**
     * Analyze sentiment from transcript
     */
    public function analyzeSentiment(): string
    {
        $transcript = strtolower($this->transcript ?? $this->webhook_data['transcript'] ?? '');
        
        $positiveWords = ['danke', 'super', 'toll', 'perfekt', 'gut', 'gerne', 'freue', 'klasse', 'wunderbar', 'ja'];
        $negativeWords = ['problem', 'schlecht', 'nein', 'nicht', 'leider', 'schwierig', 'ärger', 'beschwerde', 'unzufrieden'];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($transcript, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($transcript, $word);
        }
        
        if ($positiveCount > $negativeCount * 1.5) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount * 1.5) {
            return 'negative';
        }
        
        return 'neutral';
    }
    
    
    /**
     * Get important phrases from transcript
     */
    public function getImportantPhrases(): array
    {
        $transcript = $this->transcript ?? $this->webhook_data['transcript'] ?? '';
        $phrases = [];
        
        // Extract appointment requests
        if (preg_match('/termin.{0,20}(buchen|vereinbaren|machen)/i', $transcript, $matches)) {
            $phrases[] = $matches[0];
        }
        
        // Extract service mentions
        $services = ['beratung', 'behandlung', 'haarschnitt', 'massage', 'untersuchung'];
        foreach ($services as $service) {
            if (stripos($transcript, $service) !== false) {
                $phrases[] = ucfirst($service);
            }
        }
        
        return array_unique($phrases);
    }
    
    /**
     * Boot method to auto-analyze calls
     */
    protected static function boot()
    {
        parent::boot();
        
        // Apply tenant scope
        static::addGlobalScope(new TenantScope);
        
        static::saving(function ($call) {
            // Auto-analyze if we have a transcript
            $hasTranscript = false;
            
            if (!empty($call->transcript)) {
                $hasTranscript = true;
            } elseif (!empty($call->webhook_data)) {
                // Check if webhook_data contains a transcript
                $webhookData = is_string($call->webhook_data) ? json_decode($call->webhook_data, true) : $call->webhook_data;
                if (is_array($webhookData) && !empty($webhookData['transcript'])) {
                    $hasTranscript = true;
                }
            }
            
            if ($hasTranscript) {
                $analysis = is_array($call->analysis) ? $call->analysis : [];
                
                // Extract entities
                $analysis['entities'] = $call->extractEntities();
                
                // Analyze sentiment
                $analysis['sentiment'] = $call->analyzeSentiment();
                
                // Get important phrases
                $analysis['important_phrases'] = $call->getImportantPhrases();
                
                // Set the analysis back
                $call->analysis = $analysis;
                
                // Update sentiment column
                $call->sentiment = $analysis['sentiment'];
            }
            
            // Calculate duration in minutes if we have duration_sec
            if ($call->duration_sec && !$call->duration_minutes) {
                $call->duration_minutes = round($call->duration_sec / 60, 2);
            }
            
            // Also set duration (in seconds) from duration_sec if available
            if ($call->duration_sec && !$call->duration) {
                $call->duration = $call->duration_sec;
            }
            
            // Calculate cost in euros if we have cost_cents
            if ($call->cost_cents && !$call->cost) {
                $call->cost = $call->cost_cents / 100;
            }
        });
    }
    
    /**
     * Customer relationship
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    /**
     * Appointment relationship
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
    
    /**
     * Agent relationship - commented out as agents table doesn't exist
     */
    // public function agent(): BelongsTo
    // {
    //     return $this->belongsTo(Agent::class);
    // }
    
    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Branch relationship
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    
    /**
     * Staff relationship
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
    
    /**
     * Phone number relationship
     */
    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class, 'to_number', 'number');
    }
    
    /**
     * ML Prediction relationship
     */
    public function mlPrediction()
    {
        return $this->hasOne(MLCallPrediction::class);
    }

    /**
     * Portal data relationship
     */
    public function callPortalData()
    {
        return $this->hasOne(CallPortalData::class);
    }

    /**
     * Call notes relationship
     */
    public function callNotes()
    {
        return $this->hasMany(CallNote::class)->orderBy('created_at', 'desc');
    }

    /**
     * Call assignments relationship
     */
    public function callAssignments()
    {
        return $this->hasMany(CallAssignment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Retell webhooks relationship
     */
    public function retellWebhooks()
    {
        return $this->hasMany(RetellWebhook::class, 'call_id', 'retell_call_id');
    }

    /**
     * Call charge relationship
     */
    public function charge()
    {
        return $this->hasOne(CallCharge::class);
    }
}