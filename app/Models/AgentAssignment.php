<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Scopes\TenantScope;
use Carbon\Carbon;

class AgentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'retell_agent_id',
        'assignment_type',
        'criteria',
        'priority',
        'is_active',
        'start_time',
        'end_time',
        'days_of_week',
        'service_id',
        'branch_id',
        'is_test',
        'traffic_percentage',
        'test_start_date',
        'test_end_date',
    ];

    protected $casts = [
        'criteria' => 'array',
        'days_of_week' => 'array',
        'is_active' => 'boolean',
        'is_test' => 'boolean',
        'test_start_date' => 'datetime',
        'test_end_date' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($assignment) {
            if (!$assignment->company_id && auth()->check()) {
                $assignment->company_id = auth()->user()->company_id;
            }
        });
    }

    /**
     * Assignment types
     */
    const TYPE_TIME_BASED = 'time_based';
    const TYPE_SERVICE_BASED = 'service_based';
    const TYPE_BRANCH_BASED = 'branch_based';
    const TYPE_CUSTOMER_SEGMENT = 'customer_segment';
    const TYPE_LANGUAGE_BASED = 'language_based';
    const TYPE_SKILL_BASED = 'skill_based';

    /**
     * Get available assignment types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_TIME_BASED => 'Time-based Assignment',
            self::TYPE_SERVICE_BASED => 'Service-based Assignment',
            self::TYPE_BRANCH_BASED => 'Branch-based Assignment',
            self::TYPE_CUSTOMER_SEGMENT => 'Customer Segment Assignment',
            self::TYPE_LANGUAGE_BASED => 'Language-based Assignment',
            self::TYPE_SKILL_BASED => 'Skill-based Assignment',
        ];
    }

    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Agent relationship
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(RetellAgent::class, 'retell_agent_id');
    }

    /**
     * Service relationship
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Branch relationship
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if assignment is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if in test period
        if ($this->is_test) {
            $now = now();
            if ($this->test_start_date && $now->lt($this->test_start_date)) {
                return false;
            }
            if ($this->test_end_date && $now->gt($this->test_end_date)) {
                return false;
            }
        }

        // Check time-based assignments
        if ($this->assignment_type === self::TYPE_TIME_BASED) {
            return $this->isInTimeWindow();
        }

        return true;
    }

    /**
     * Check if current time is within assignment window
     */
    public function isInTimeWindow(): bool
    {
        if (!$this->start_time || !$this->end_time) {
            return true;
        }

        $now = now();
        $currentTime = $now->format('H:i:s');
        $currentDay = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday

        // Check day of week
        if ($this->days_of_week && !in_array($currentDay, $this->days_of_week)) {
            return false;
        }

        // Check time
        $startTime = Carbon::parse($this->start_time)->format('H:i:s');
        $endTime = Carbon::parse($this->end_time)->format('H:i:s');

        // Handle overnight assignments (e.g., 22:00 - 02:00)
        if ($endTime < $startTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Check if assignment matches given criteria
     */
    public function matchesCriteria(array $context): bool
    {
        switch ($this->assignment_type) {
            case self::TYPE_SERVICE_BASED:
                return isset($context['service_id']) && 
                       $context['service_id'] == $this->service_id;

            case self::TYPE_BRANCH_BASED:
                return isset($context['branch_id']) && 
                       $context['branch_id'] == $this->branch_id;

            case self::TYPE_LANGUAGE_BASED:
                return isset($context['language']) && 
                       isset($this->criteria['languages']) &&
                       in_array($context['language'], $this->criteria['languages']);

            case self::TYPE_CUSTOMER_SEGMENT:
                return $this->matchesCustomerSegment($context);

            case self::TYPE_SKILL_BASED:
                return $this->matchesRequiredSkills($context);

            case self::TYPE_TIME_BASED:
                return $this->isInTimeWindow();

            default:
                return true;
        }
    }

    /**
     * Check if customer segment matches
     */
    protected function matchesCustomerSegment(array $context): bool
    {
        if (!isset($context['customer']) || !isset($this->criteria['segments'])) {
            return false;
        }

        $customer = $context['customer'];
        $segments = $this->criteria['segments'];

        foreach ($segments as $segment => $condition) {
            switch ($segment) {
                case 'vip':
                    if ($condition && !($customer->is_vip ?? false)) {
                        return false;
                    }
                    break;

                case 'new_customer':
                    $isNew = $customer->created_at->gt(now()->subDays(30));
                    if ($condition && !$isNew) {
                        return false;
                    }
                    break;

                case 'inactive':
                    $daysInactive = $condition['days'] ?? 90;
                    $lastAppointment = $customer->appointments()
                        ->orderBy('scheduled_at', 'desc')
                        ->first();
                    
                    if ($lastAppointment && 
                        $lastAppointment->scheduled_at->gt(now()->subDays($daysInactive))) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Check if agent has required skills
     */
    protected function matchesRequiredSkills(array $context): bool
    {
        if (!isset($context['required_skills']) || !isset($this->criteria['skills'])) {
            return true;
        }

        $requiredSkills = $context['required_skills'];
        $agentSkills = $this->criteria['skills'];

        // Check if agent has all required skills
        foreach ($requiredSkills as $skill) {
            if (!in_array($skill, $agentSkills)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get assignment score for prioritization
     */
    public function getScore(array $context = []): int
    {
        $score = $this->priority;

        // Add bonus for exact matches
        if ($this->assignment_type === self::TYPE_SERVICE_BASED && 
            isset($context['service_id']) && 
            $context['service_id'] == $this->service_id) {
            $score += 10;
        }

        if ($this->assignment_type === self::TYPE_BRANCH_BASED && 
            isset($context['branch_id']) && 
            $context['branch_id'] == $this->branch_id) {
            $score += 10;
        }

        // Add bonus for language match
        if (isset($context['language']) && 
            isset($this->criteria['languages']) &&
            in_array($context['language'], $this->criteria['languages'])) {
            $score += 5;
        }

        return $score;
    }
}