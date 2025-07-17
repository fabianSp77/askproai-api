# Goal System Implementation Guide

## Overview

The Goal System enables businesses to set, track, and achieve strategic objectives with automated KPI calculation and real-time progress monitoring. It's fully integrated into the Business Portal with visual dashboards and predictive analytics.

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   Goal System UI                         │
│  (Dashboard Widgets, Goal Manager, Analytics)           │
├─────────────────────────────────────────────────────────┤
│                  Goal Service Layer                      │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────┐ │
│  │Goal Service │  │Metric Service│  │Funnel Service│ │
│  └─────────────┘  └──────────────┘  └───────────────┘ │
├─────────────────────────────────────────────────────────┤
│                  Data Layer                              │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────┐ │
│  │company_goals│  │goal_metrics  │  │funnel_steps  │ │
│  └─────────────┘  └──────────────┘  └───────────────┘ │
└─────────────────────────────────────────────────────────┘
```

## Database Schema

### company_goals
```sql
CREATE TABLE company_goals (
    id bigint PRIMARY KEY,
    company_id bigint NOT NULL,
    name varchar(255) NOT NULL,
    description text,
    type enum('revenue','volume','conversion','custom'),
    target_value decimal(10,2) NOT NULL,
    current_value decimal(10,2) DEFAULT 0,
    unit varchar(50), -- EUR, %, count, etc.
    start_date date NOT NULL,
    end_date date,
    status enum('draft','active','paused','completed','failed'),
    calculation_method varchar(255), -- SQL query or method name
    notification_settings json,
    created_by bigint,
    created_at timestamp,
    updated_at timestamp,
    
    INDEX idx_company_status (company_id, status),
    INDEX idx_dates (start_date, end_date)
);
```

### goal_metrics
```sql
CREATE TABLE goal_metrics (
    id bigint PRIMARY KEY,
    goal_id bigint NOT NULL,
    metric_name varchar(255) NOT NULL,
    metric_value decimal(10,2) NOT NULL,
    metadata json, -- Additional context
    recorded_at timestamp NOT NULL,
    created_at timestamp,
    
    FOREIGN KEY (goal_id) REFERENCES company_goals(id),
    INDEX idx_goal_time (goal_id, recorded_at)
);
```

### goal_funnel_steps
```sql
CREATE TABLE goal_funnel_steps (
    id bigint PRIMARY KEY,
    goal_id bigint NOT NULL,
    step_name varchar(255) NOT NULL,
    step_order int NOT NULL,
    target_value decimal(10,2),
    current_value decimal(10,2) DEFAULT 0,
    conversion_rate decimal(5,2), -- Percentage
    
    FOREIGN KEY (goal_id) REFERENCES company_goals(id),
    UNIQUE KEY unique_goal_order (goal_id, step_order)
);
```

### goal_achievements
```sql
CREATE TABLE goal_achievements (
    id bigint PRIMARY KEY,
    goal_id bigint NOT NULL,
    achieved_value decimal(10,2) NOT NULL,
    achieved_at timestamp NOT NULL,
    achievement_type enum('milestone','completion','record'),
    notes text,
    
    FOREIGN KEY (goal_id) REFERENCES company_goals(id)
);
```

## Goal Types

### 1. Revenue Goals

Track monetary objectives:

```php
// Create revenue goal
$goal = CompanyGoal::create([
    'company_id' => $company->id,
    'name' => 'Q1 2025 Revenue Target',
    'type' => 'revenue',
    'target_value' => 150000, // €150,000
    'unit' => 'EUR',
    'start_date' => '2025-01-01',
    'end_date' => '2025-03-31',
    'calculation_method' => 'sum_appointment_revenue'
]);

// Automatic calculation method
public function sum_appointment_revenue($goal)
{
    return Appointment::where('company_id', $goal->company_id)
        ->whereBetween('scheduled_at', [$goal->start_date, $goal->end_date])
        ->where('status', 'completed')
        ->sum('total_amount');
}
```

### 2. Volume Goals

Count-based objectives:

```php
// Create volume goal
$goal = CompanyGoal::create([
    'company_id' => $company->id,
    'name' => 'New Customer Acquisition',
    'type' => 'volume',
    'target_value' => 100, // 100 new customers
    'unit' => 'customers',
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'calculation_method' => 'count_new_customers'
]);
```

### 3. Conversion Goals

Percentage-based targets:

```php
// Create conversion goal
$goal = CompanyGoal::create([
    'company_id' => $company->id,
    'name' => 'Call to Appointment Conversion',
    'type' => 'conversion',
    'target_value' => 45, // 45% conversion rate
    'unit' => '%',
    'calculation_method' => 'calculate_call_conversion'
]);

// With funnel tracking
GoalFunnelStep::insert([
    ['goal_id' => $goal->id, 'step_name' => 'Call Received', 'step_order' => 1],
    ['goal_id' => $goal->id, 'step_name' => 'Qualified Lead', 'step_order' => 2],
    ['goal_id' => $goal->id, 'step_name' => 'Appointment Scheduled', 'step_order' => 3],
    ['goal_id' => $goal->id, 'step_name' => 'Appointment Completed', 'step_order' => 4],
]);
```

### 4. Custom Goals

Flexible metric tracking:

```php
// Create custom goal
$goal = CompanyGoal::create([
    'company_id' => $company->id,
    'name' => 'Customer Satisfaction Score',
    'type' => 'custom',
    'target_value' => 4.5, // Out of 5
    'unit' => 'rating',
    'calculation_method' => 'average_feedback_score'
]);
```

## Implementation

### Goal Service

```php
namespace App\Services;

use App\Models\CompanyGoal;
use App\Models\GoalMetric;
use App\Events\GoalProgressUpdated;
use App\Events\GoalAchieved;

class GoalService
{
    public function createGoal(array $data): CompanyGoal
    {
        $goal = CompanyGoal::create($data);
        
        // Set up initial metrics
        $this->initializeMetrics($goal);
        
        // Schedule automatic updates
        $this->scheduleUpdates($goal);
        
        return $goal;
    }
    
    public function updateProgress(CompanyGoal $goal): void
    {
        $oldValue = $goal->current_value;
        $newValue = $this->calculateCurrentValue($goal);
        
        $goal->update(['current_value' => $newValue]);
        
        // Record metric
        GoalMetric::create([
            'goal_id' => $goal->id,
            'metric_name' => 'progress_update',
            'metric_value' => $newValue,
            'recorded_at' => now()
        ]);
        
        // Fire events
        event(new GoalProgressUpdated($goal, $oldValue, $newValue));
        
        // Check if goal achieved
        if ($this->isAchieved($goal)) {
            $this->markAsAchieved($goal);
            event(new GoalAchieved($goal));
        }
    }
    
    protected function calculateCurrentValue(CompanyGoal $goal): float
    {
        $method = $goal->calculation_method;
        
        // Use dynamic method calling
        if (method_exists($this, $method)) {
            return $this->$method($goal);
        }
        
        // Or use MCP for complex calculations
        return app(MCPAutoDiscoveryService::class)->executeTask(
            "calculate current value for goal: {$goal->name}",
            ['goal' => $goal]
        );
    }
    
    public function generateInsights(CompanyGoal $goal): array
    {
        $metrics = $goal->metrics()
            ->where('recorded_at', '>=', now()->subDays(30))
            ->orderBy('recorded_at')
            ->get();
        
        return [
            'trend' => $this->calculateTrend($metrics),
            'projection' => $this->projectCompletion($goal, $metrics),
            'recommendations' => $this->getRecommendations($goal),
            'risk_level' => $this->assessRisk($goal)
        ];
    }
}
```

### Goal Calculation Methods

```php
namespace App\Services\Goals;

trait GoalCalculations
{
    public function sum_appointment_revenue(CompanyGoal $goal): float
    {
        return Appointment::query()
            ->forCompany($goal->company_id)
            ->completed()
            ->period($goal->start_date, $goal->end_date)
            ->sum('total_amount');
    }
    
    public function count_new_customers(CompanyGoal $goal): int
    {
        return Customer::query()
            ->forCompany($goal->company_id)
            ->whereBetween('created_at', [$goal->start_date, $goal->end_date])
            ->count();
    }
    
    public function calculate_call_conversion(CompanyGoal $goal): float
    {
        $totalCalls = Call::query()
            ->forCompany($goal->company_id)
            ->period($goal->start_date, $goal->end_date)
            ->count();
        
        $convertedCalls = Call::query()
            ->forCompany($goal->company_id)
            ->period($goal->start_date, $goal->end_date)
            ->whereHas('appointments')
            ->count();
        
        return $totalCalls > 0 
            ? round(($convertedCalls / $totalCalls) * 100, 2) 
            : 0;
    }
    
    public function average_feedback_score(CompanyGoal $goal): float
    {
        return Feedback::query()
            ->forCompany($goal->company_id)
            ->period($goal->start_date, $goal->end_date)
            ->avg('rating') ?? 0;
    }
}
```

### Goal Templates

```php
namespace App\Services\Goals;

class GoalTemplateService
{
    protected $templates = [
        'monthly_revenue' => [
            'name' => 'Monthly Revenue Target',
            'type' => 'revenue',
            'unit' => 'EUR',
            'calculation_method' => 'sum_appointment_revenue',
            'duration' => 'P1M', // 1 month
            'suggested_target' => 'last_month_revenue * 1.1' // 10% growth
        ],
        
        'customer_acquisition' => [
            'name' => 'New Customer Acquisition',
            'type' => 'volume',
            'unit' => 'customers',
            'calculation_method' => 'count_new_customers',
            'duration' => 'P3M', // 3 months
            'suggested_target' => 'average_monthly_customers * 3 * 1.2'
        ],
        
        'conversion_optimization' => [
            'name' => 'Conversion Rate Optimization',
            'type' => 'conversion',
            'unit' => '%',
            'calculation_method' => 'calculate_call_conversion',
            'funnel_steps' => [
                'Call Received',
                'Interest Expressed',
                'Appointment Scheduled',
                'Appointment Completed'
            ]
        ],
        
        'service_quality' => [
            'name' => 'Service Quality Score',
            'type' => 'custom',
            'unit' => 'rating',
            'calculation_method' => 'composite_quality_score',
            'components' => [
                'customer_satisfaction' => 0.4,
                'on_time_rate' => 0.3,
                'repeat_customer_rate' => 0.3
            ]
        ]
    ];
    
    public function createFromTemplate(string $templateKey, Company $company): CompanyGoal
    {
        $template = $this->templates[$templateKey];
        
        // Calculate suggested target
        $targetValue = $this->calculateSuggestedTarget($template, $company);
        
        // Create goal
        $goal = CompanyGoal::create([
            'company_id' => $company->id,
            'name' => $template['name'],
            'type' => $template['type'],
            'target_value' => $targetValue,
            'unit' => $template['unit'],
            'calculation_method' => $template['calculation_method'],
            'start_date' => now(),
            'end_date' => now()->add(new DateInterval($template['duration'])),
            'status' => 'active'
        ]);
        
        // Create funnel steps if defined
        if (isset($template['funnel_steps'])) {
            $this->createFunnelSteps($goal, $template['funnel_steps']);
        }
        
        return $goal;
    }
}
```

## Frontend Implementation

### Goal Dashboard Widget

```javascript
// GoalProgressWidget.jsx
import React, { useState, useEffect } from 'react';
import { Progress, Card, Statistic } from 'antd';
import { TrendingUpIcon, TrendingDownIcon } from '@heroicons/react/24/outline';

function GoalProgressWidget({ goalId }) {
    const [goal, setGoal] = useState(null);
    const [insights, setInsights] = useState(null);
    
    useEffect(() => {
        fetchGoalData();
        const interval = setInterval(fetchGoalData, 60000); // Update every minute
        return () => clearInterval(interval);
    }, [goalId]);
    
    const fetchGoalData = async () => {
        const response = await api.get(`/portal/goals/${goalId}`);
        setGoal(response.data.goal);
        setInsights(response.data.insights);
    };
    
    const progressPercent = (goal?.current_value / goal?.target_value) * 100;
    const isOnTrack = progressPercent >= getExpectedProgress();
    
    return (
        <Card className="goal-progress-widget">
            <h3>{goal?.name}</h3>
            
            <Progress
                percent={Math.min(progressPercent, 100)}
                status={isOnTrack ? 'active' : 'exception'}
                strokeColor={getProgressColor(progressPercent, isOnTrack)}
            />
            
            <div className="goal-stats">
                <Statistic
                    title="Current"
                    value={goal?.current_value}
                    suffix={goal?.unit}
                />
                <Statistic
                    title="Target"
                    value={goal?.target_value}
                    suffix={goal?.unit}
                />
                <Statistic
                    title="Trend"
                    value={insights?.trend?.percentage}
                    prefix={insights?.trend?.direction === 'up' ? 
                        <TrendingUpIcon /> : <TrendingDownIcon />}
                    suffix="%"
                    valueStyle={{ 
                        color: insights?.trend?.direction === 'up' ? 
                            '#3f8600' : '#cf1322' 
                    }}
                />
            </div>
            
            {insights?.projection && (
                <div className="goal-projection">
                    <p>Projected completion: {insights.projection.date}</p>
                    <p>Confidence: {insights.projection.confidence}%</p>
                </div>
            )}
        </Card>
    );
}
```

### Goal Creation Form

```javascript
// GoalCreationForm.jsx
import React, { useState } from 'react';
import { Form, Input, Select, DatePicker, InputNumber, Button } from 'antd';
import { useGoalTemplates } from '../hooks/useGoalTemplates';

function GoalCreationForm({ onSuccess }) {
    const [form] = Form.useForm();
    const { templates, loading } = useGoalTemplates();
    const [selectedTemplate, setSelectedTemplate] = useState(null);
    
    const handleTemplateSelect = (templateKey) => {
        const template = templates.find(t => t.key === templateKey);
        setSelectedTemplate(template);
        
        // Pre-fill form with template values
        form.setFieldsValue({
            name: template.name,
            type: template.type,
            unit: template.unit,
            target_value: template.suggested_target
        });
    };
    
    const handleSubmit = async (values) => {
        try {
            const response = await api.post('/portal/goals', {
                ...values,
                template_key: selectedTemplate?.key
            });
            
            message.success('Goal created successfully!');
            onSuccess(response.data.goal);
        } catch (error) {
            message.error('Failed to create goal');
        }
    };
    
    return (
        <Form form={form} onFinish={handleSubmit} layout="vertical">
            <Form.Item label="Use Template">
                <Select
                    placeholder="Select a goal template"
                    onChange={handleTemplateSelect}
                    loading={loading}
                >
                    {templates.map(template => (
                        <Select.Option key={template.key} value={template.key}>
                            {template.name} - {template.description}
                        </Select.Option>
                    ))}
                </Select>
            </Form.Item>
            
            <Form.Item
                name="name"
                label="Goal Name"
                rules={[{ required: true }]}
            >
                <Input placeholder="e.g., Q1 Revenue Target" />
            </Form.Item>
            
            <Form.Item
                name="type"
                label="Goal Type"
                rules={[{ required: true }]}
            >
                <Select>
                    <Select.Option value="revenue">Revenue</Select.Option>
                    <Select.Option value="volume">Volume</Select.Option>
                    <Select.Option value="conversion">Conversion</Select.Option>
                    <Select.Option value="custom">Custom</Select.Option>
                </Select>
            </Form.Item>
            
            <Form.Item
                name="target_value"
                label="Target Value"
                rules={[{ required: true }]}
            >
                <InputNumber min={0} style={{ width: '100%' }} />
            </Form.Item>
            
            <Form.Item
                name="date_range"
                label="Period"
                rules={[{ required: true }]}
            >
                <DatePicker.RangePicker style={{ width: '100%' }} />
            </Form.Item>
            
            {selectedTemplate?.has_funnel && (
                <FunnelStepConfiguration template={selectedTemplate} />
            )}
            
            <Button type="primary" htmlType="submit" block>
                Create Goal
            </Button>
        </Form>
    );
}
```

### Goal Analytics Dashboard

```javascript
// GoalAnalytics.jsx
import React from 'react';
import { Row, Col, Card } from 'antd';
import { Line, Column, Pie, Gauge } from '@ant-design/plots';

function GoalAnalytics({ companyId }) {
    const [analytics, setAnalytics] = useState(null);
    
    useEffect(() => {
        fetchAnalytics();
    }, [companyId]);
    
    const fetchAnalytics = async () => {
        const response = await api.get(`/portal/goals/analytics`, {
            params: { company_id: companyId }
        });
        setAnalytics(response.data);
    };
    
    return (
        <div className="goal-analytics">
            <Row gutter={[16, 16]}>
                <Col span={12}>
                    <Card title="Goal Progress Overview">
                        <Gauge {...getGaugeConfig(analytics?.overall_progress)} />
                    </Card>
                </Col>
                
                <Col span={12}>
                    <Card title="Goal Distribution">
                        <Pie {...getPieConfig(analytics?.goal_distribution)} />
                    </Card>
                </Col>
                
                <Col span={24}>
                    <Card title="Historical Performance">
                        <Line {...getLineConfig(analytics?.historical_data)} />
                    </Card>
                </Col>
                
                <Col span={24}>
                    <Card title="Goal Achievement Timeline">
                        <GoalTimeline goals={analytics?.timeline} />
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
```

## Automation & Notifications

### Automatic Progress Updates

```php
// app/Console/Commands/UpdateGoalProgress.php
namespace App\Console\Commands;

class UpdateGoalProgress extends Command
{
    protected $signature = 'goals:update-progress {--company=}';
    
    public function handle()
    {
        $goals = CompanyGoal::active()
            ->when($this->option('company'), function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();
        
        foreach ($goals as $goal) {
            $this->info("Updating goal: {$goal->name}");
            
            try {
                app(GoalService::class)->updateProgress($goal);
                $this->info("✓ Updated successfully");
            } catch (\Exception $e) {
                $this->error("✗ Failed: {$e->getMessage()}");
            }
        }
    }
}

// In Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('goals:update-progress')
        ->everyThirtyMinutes()
        ->withoutOverlapping();
}
```

### Goal Notifications

```php
namespace App\Notifications;

class GoalMilestoneReached extends Notification
{
    use Queueable;
    
    protected $goal;
    protected $milestone;
    
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }
    
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Milestone Reached: {$this->goal->name}")
            ->line("Congratulations! You've reached {$this->milestone}% of your goal.")
            ->line("Current progress: {$this->goal->current_value} {$this->goal->unit}")
            ->line("Target: {$this->goal->target_value} {$this->goal->unit}")
            ->action('View Goal Details', url("/portal/goals/{$this->goal->id}"))
            ->line('Keep up the great work!');
    }
    
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'type' => 'goal_milestone',
            'goal_id' => $this->goal->id,
            'milestone' => $this->milestone,
            'current_value' => $this->goal->current_value,
            'target_value' => $this->goal->target_value
        ]);
    }
}
```

## Advanced Features

### Predictive Analytics

```php
namespace App\Services\Goals;

class GoalPredictionService
{
    public function predictCompletion(CompanyGoal $goal): array
    {
        $historicalData = $goal->metrics()
            ->orderBy('recorded_at')
            ->pluck('metric_value', 'recorded_at')
            ->toArray();
        
        // Simple linear regression
        $regression = new LinearRegression();
        $regression->train($historicalData);
        
        // Predict future values
        $predictions = [];
        $currentDate = now();
        $endDate = $goal->end_date ?? now()->addMonths(3);
        
        while ($currentDate <= $endDate) {
            $predictions[$currentDate->format('Y-m-d')] = 
                $regression->predict($currentDate->timestamp);
            $currentDate->addDay();
        }
        
        // Find completion date
        $completionDate = null;
        foreach ($predictions as $date => $value) {
            if ($value >= $goal->target_value) {
                $completionDate = $date;
                break;
            }
        }
        
        return [
            'completion_date' => $completionDate,
            'completion_probability' => $this->calculateProbability($predictions, $goal),
            'predictions' => $predictions,
            'confidence_interval' => $regression->getConfidenceInterval()
        ];
    }
}
```

### Goal Dependencies

```php
// Model for goal dependencies
class GoalDependency extends Model
{
    public function parentGoal()
    {
        return $this->belongsTo(CompanyGoal::class, 'parent_goal_id');
    }
    
    public function dependentGoal()
    {
        return $this->belongsTo(CompanyGoal::class, 'dependent_goal_id');
    }
}

// Usage
$revenueGoal = CompanyGoal::create([...]);
$customerGoal = CompanyGoal::create([...]);

GoalDependency::create([
    'parent_goal_id' => $customerGoal->id,
    'dependent_goal_id' => $revenueGoal->id,
    'impact_factor' => 0.8 // 80% impact
]);
```

### Goal Collaboration

```php
// Multiple teams working on same goal
class GoalCollaborator extends Model
{
    protected $fillable = [
        'goal_id',
        'branch_id',
        'team_id',
        'contribution_target',
        'contribution_current'
    ];
}

// Track individual contributions
$goal->collaborators()->create([
    'branch_id' => $branch->id,
    'contribution_target' => 50000, // Branch target
    'contribution_current' => 0
]);
```

## Best Practices

### 1. SMART Goals

Ensure all goals are:
- **S**pecific: Clear, well-defined objective
- **M**easurable: Quantifiable metrics
- **A**chievable: Realistic targets
- **R**elevant: Aligned with business objectives
- **T**ime-bound: Clear deadlines

### 2. Regular Reviews

```php
// Schedule regular goal reviews
GoalReview::create([
    'goal_id' => $goal->id,
    'scheduled_at' => now()->addWeeks(2),
    'review_type' => 'bi_weekly',
    'participants' => ['manager_id' => 1, 'team_ids' => [1, 2, 3]]
]);
```

### 3. Data Quality

```php
// Validate goal data integrity
class GoalDataValidator
{
    public function validate(CompanyGoal $goal): array
    {
        $issues = [];
        
        // Check for data gaps
        $gaps = $this->findDataGaps($goal);
        if (!empty($gaps)) {
            $issues[] = "Data gaps found: " . implode(', ', $gaps);
        }
        
        // Check for anomalies
        $anomalies = $this->detectAnomalies($goal);
        if (!empty($anomalies)) {
            $issues[] = "Anomalies detected in metrics";
        }
        
        return $issues;
    }
}
```

### 4. Goal Alignment

```php
// Ensure goals align with company strategy
class GoalAlignmentService
{
    public function checkAlignment(CompanyGoal $goal): array
    {
        $company = $goal->company;
        $strategy = $company->strategy;
        
        return [
            'alignment_score' => $this->calculateAlignmentScore($goal, $strategy),
            'recommendations' => $this->getAlignmentRecommendations($goal, $strategy),
            'conflicts' => $this->findConflictingGoals($goal)
        ];
    }
}
```

## Troubleshooting

### Common Issues

1. **Goals not updating automatically**
   ```bash
   # Check cron job
   php artisan schedule:list
   
   # Run manually
   php artisan goals:update-progress
   
   # Check logs
   tail -f storage/logs/goals.log
   ```

2. **Incorrect calculations**
   ```php
   // Debug calculation method
   $service = app(GoalService::class);
   $value = $service->debugCalculation($goal);
   ```

3. **Performance issues**
   ```sql
   -- Add indexes for better performance
   ALTER TABLE goal_metrics ADD INDEX idx_goal_recorded (goal_id, recorded_at);
   ALTER TABLE company_goals ADD INDEX idx_company_active (company_id, status);
   ```

### Monitoring

```php
// Goal system health check
php artisan goals:health-check

// Metrics
- Active goals per company
- Average update frequency
- Calculation accuracy
- Achievement rate
```

---

*For more information, see the [main documentation](./BUSINESS_PORTAL_COMPLETE_DOCUMENTATION.md)*