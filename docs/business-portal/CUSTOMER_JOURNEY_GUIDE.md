# Customer Journey System Guide

## Overview

The Customer Journey System tracks and manages the complete lifecycle of customer relationships, from initial contact through retention and potential churn. It provides automated stage transitions, predictive analytics, and actionable insights to optimize customer engagement.

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│              Customer Journey Dashboard                  │
│  (Timeline View, Stage Analytics, Action Center)        │
├─────────────────────────────────────────────────────────┤
│             Journey Service Layer                        │
│  ┌──────────────┐  ┌───────────────┐  ┌─────────────┐ │
│  │Journey Engine│  │Stage Manager  │  │Analytics    │ │
│  └──────────────┘  └───────────────┘  └─────────────┘ │
├─────────────────────────────────────────────────────────┤
│                  Data Layer                              │
│  ┌──────────────────┐  ┌─────────────────────────┐    │
│  │journey_stages    │  │customer_relationships  │     │
│  └──────────────────┘  └─────────────────────────┘    │
└─────────────────────────────────────────────────────────┘
```

## Database Schema

### customer_journey_stages
```sql
CREATE TABLE customer_journey_stages (
    id bigint PRIMARY KEY,
    company_id bigint NOT NULL,
    stage_key varchar(50) NOT NULL,
    stage_name varchar(255) NOT NULL,
    description text,
    order_index int NOT NULL,
    color varchar(7), -- Hex color for UI
    icon varchar(50), -- Icon identifier
    auto_transition_rules json,
    retention_days int, -- Days before considered at risk
    is_active boolean DEFAULT true,
    created_at timestamp,
    updated_at timestamp,
    
    UNIQUE KEY unique_company_stage (company_id, stage_key),
    INDEX idx_company_order (company_id, order_index)
);
```

### customer_relationships
```sql
CREATE TABLE customer_relationships (
    id bigint PRIMARY KEY,
    company_id bigint NOT NULL,
    customer_id bigint NOT NULL,
    current_stage varchar(50) NOT NULL,
    stage_entered_at timestamp NOT NULL,
    previous_stage varchar(50),
    lifetime_value decimal(10,2) DEFAULT 0,
    total_appointments int DEFAULT 0,
    last_appointment_at timestamp,
    risk_score decimal(3,2), -- 0-1 scale
    engagement_score decimal(3,2), -- 0-1 scale
    notes text,
    metadata json,
    created_at timestamp,
    updated_at timestamp,
    
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    UNIQUE KEY unique_company_customer (company_id, customer_id),
    INDEX idx_stage (company_id, current_stage),
    INDEX idx_risk (company_id, risk_score)
);
```

### journey_transitions
```sql
CREATE TABLE journey_transitions (
    id bigint PRIMARY KEY,
    relationship_id bigint NOT NULL,
    from_stage varchar(50),
    to_stage varchar(50) NOT NULL,
    transitioned_at timestamp NOT NULL,
    trigger_type enum('manual','automatic','system'),
    trigger_event varchar(255),
    performed_by bigint, -- User ID if manual
    notes text,
    
    FOREIGN KEY (relationship_id) REFERENCES customer_relationships(id),
    INDEX idx_relationship_time (relationship_id, transitioned_at)
);
```

### journey_stage_metrics
```sql
CREATE TABLE journey_stage_metrics (
    id bigint PRIMARY KEY,
    company_id bigint NOT NULL,
    stage_key varchar(50) NOT NULL,
    metric_date date NOT NULL,
    customer_count int DEFAULT 0,
    avg_time_in_stage decimal(10,2), -- Days
    conversion_rate decimal(5,2), -- To next stage
    revenue decimal(10,2),
    
    UNIQUE KEY unique_company_stage_date (company_id, stage_key, metric_date),
    INDEX idx_company_date (company_id, metric_date)
);
```

## Journey Stages

### Default Stage Configuration

```php
class DefaultJourneyStages
{
    public const STAGES = [
        'prospect' => [
            'name' => 'Prospect',
            'description' => 'Initial contact or inquiry',
            'color' => '#94A3B8',
            'icon' => 'user-plus',
            'order' => 1,
            'auto_rules' => [
                'trigger' => 'call_received',
                'conditions' => []
            ]
        ],
        'lead' => [
            'name' => 'Lead',
            'description' => 'Qualified interest expressed',
            'color' => '#60A5FA',
            'icon' => 'phone',
            'order' => 2,
            'auto_rules' => [
                'trigger' => 'appointment_requested',
                'conditions' => ['call_duration' => '>60']
            ]
        ],
        'customer' => [
            'name' => 'Customer',
            'description' => 'First appointment completed',
            'color' => '#34D399',
            'icon' => 'check-circle',
            'order' => 3,
            'auto_rules' => [
                'trigger' => 'appointment_completed',
                'conditions' => []
            ]
        ],
        'regular' => [
            'name' => 'Regular Customer',
            'description' => 'Multiple appointments completed',
            'color' => '#10B981',
            'icon' => 'star',
            'order' => 4,
            'auto_rules' => [
                'trigger' => 'appointment_count',
                'conditions' => ['count' => '>=3']
            ]
        ],
        'vip' => [
            'name' => 'VIP Customer',
            'description' => 'High-value loyal customer',
            'color' => '#8B5CF6',
            'icon' => 'crown',
            'order' => 5,
            'auto_rules' => [
                'trigger' => 'lifetime_value',
                'conditions' => ['value' => '>=1000']
            ]
        ],
        'at_risk' => [
            'name' => 'At Risk',
            'description' => 'No recent activity',
            'color' => '#F59E0B',
            'icon' => 'exclamation-triangle',
            'order' => 6,
            'auto_rules' => [
                'trigger' => 'days_since_last_appointment',
                'conditions' => ['days' => '>90']
            ]
        ],
        'churned' => [
            'name' => 'Churned',
            'description' => 'Lost customer',
            'color' => '#EF4444',
            'icon' => 'x-circle',
            'order' => 7,
            'auto_rules' => [
                'trigger' => 'days_since_last_appointment',
                'conditions' => ['days' => '>180']
            ]
        ]
    ];
}
```

## Implementation

### Customer Journey Service

```php
namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerRelationship;
use App\Models\JourneyTransition;
use App\Events\CustomerStageChanged;

class CustomerJourneyService
{
    public function initializeJourney(Customer $customer): CustomerRelationship
    {
        return CustomerRelationship::firstOrCreate(
            [
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id
            ],
            [
                'current_stage' => 'prospect',
                'stage_entered_at' => now(),
                'lifetime_value' => 0,
                'total_appointments' => 0,
                'engagement_score' => 0.5,
                'risk_score' => 0
            ]
        );
    }
    
    public function updateStage(
        Customer $customer, 
        string $newStage, 
        string $trigger = 'manual',
        array $metadata = []
    ): void {
        $relationship = $customer->relationship;
        
        if (!$relationship || $relationship->current_stage === $newStage) {
            return;
        }
        
        // Record transition
        JourneyTransition::create([
            'relationship_id' => $relationship->id,
            'from_stage' => $relationship->current_stage,
            'to_stage' => $newStage,
            'transitioned_at' => now(),
            'trigger_type' => $trigger,
            'trigger_event' => $metadata['event'] ?? null,
            'performed_by' => $metadata['user_id'] ?? null,
            'notes' => $metadata['notes'] ?? null
        ]);
        
        // Update relationship
        $relationship->update([
            'previous_stage' => $relationship->current_stage,
            'current_stage' => $newStage,
            'stage_entered_at' => now()
        ]);
        
        // Fire event
        event(new CustomerStageChanged($customer, $relationship));
        
        // Trigger stage-specific actions
        $this->triggerStageActions($customer, $newStage);
    }
    
    public function evaluateAutoTransitions(Customer $customer): void
    {
        $relationship = $customer->relationship;
        if (!$relationship) {
            return;
        }
        
        $stages = $this->getCompanyStages($customer->company_id);
        
        foreach ($stages as $stage) {
            if ($this->shouldTransition($customer, $stage)) {
                $this->updateStage($customer, $stage['key'], 'automatic', [
                    'event' => 'auto_transition_rule_met'
                ]);
                break; // Only one transition at a time
            }
        }
    }
    
    protected function shouldTransition(Customer $customer, array $stage): bool
    {
        $rules = $stage['auto_rules'] ?? [];
        
        if (empty($rules)) {
            return false;
        }
        
        return match($rules['trigger']) {
            'appointment_count' => $this->checkAppointmentCount($customer, $rules['conditions']),
            'lifetime_value' => $this->checkLifetimeValue($customer, $rules['conditions']),
            'days_since_last_appointment' => $this->checkInactivity($customer, $rules['conditions']),
            'call_received' => $this->checkCallReceived($customer, $rules['conditions']),
            default => false
        };
    }
    
    public function calculateEngagementScore(Customer $customer): float
    {
        $factors = [
            'appointment_frequency' => $this->getAppointmentFrequencyScore($customer),
            'recency' => $this->getRecencyScore($customer),
            'monetary_value' => $this->getMonetaryScore($customer),
            'interaction_quality' => $this->getInteractionScore($customer)
        ];
        
        // Weighted average
        $weights = [
            'appointment_frequency' => 0.3,
            'recency' => 0.3,
            'monetary_value' => 0.2,
            'interaction_quality' => 0.2
        ];
        
        $score = 0;
        foreach ($factors as $factor => $value) {
            $score += $value * $weights[$factor];
        }
        
        return round($score, 2);
    }
    
    public function calculateRiskScore(Customer $customer): float
    {
        $relationship = $customer->relationship;
        
        if (!$relationship) {
            return 0;
        }
        
        $riskFactors = [
            'days_inactive' => $this->getDaysInactive($customer),
            'missed_appointments' => $this->getMissedAppointmentRate($customer),
            'declining_frequency' => $this->getFrequencyTrend($customer),
            'support_tickets' => $this->getSupportTicketScore($customer)
        ];
        
        // Risk calculation logic
        $riskScore = 0;
        
        if ($riskFactors['days_inactive'] > 60) {
            $riskScore += 0.3;
        }
        if ($riskFactors['days_inactive'] > 90) {
            $riskScore += 0.3;
        }
        if ($riskFactors['missed_appointments'] > 0.2) {
            $riskScore += 0.2;
        }
        if ($riskFactors['declining_frequency'] < -0.3) {
            $riskScore += 0.2;
        }
        
        return min(1, $riskScore);
    }
}
```

### Journey Analytics Service

```php
namespace App\Services\Analytics;

use App\Models\CustomerRelationship;
use App\Models\JourneyTransition;
use Illuminate\Support\Facades\DB;

class JourneyAnalyticsService
{
    public function getStageDistribution(int $companyId): array
    {
        return CustomerRelationship::where('company_id', $companyId)
            ->select('current_stage', DB::raw('COUNT(*) as count'))
            ->groupBy('current_stage')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->current_stage => $item->count];
            })
            ->toArray();
    }
    
    public function getConversionFunnel(int $companyId, string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $transitions = JourneyTransition::query()
            ->join('customer_relationships', 'journey_transitions.relationship_id', '=', 'customer_relationships.id')
            ->where('customer_relationships.company_id', $companyId)
            ->where('journey_transitions.transitioned_at', '>=', $startDate)
            ->select('from_stage', 'to_stage', DB::raw('COUNT(*) as count'))
            ->groupBy('from_stage', 'to_stage')
            ->get();
        
        return $this->buildFunnelData($transitions);
    }
    
    public function getAverageTimeInStage(int $companyId): array
    {
        $avgTimes = DB::table('journey_transitions as t1')
            ->leftJoin('journey_transitions as t2', function ($join) {
                $join->on('t1.relationship_id', '=', 't2.relationship_id')
                     ->on('t2.transitioned_at', '>', 't1.transitioned_at');
            })
            ->join('customer_relationships', 't1.relationship_id', '=', 'customer_relationships.id')
            ->where('customer_relationships.company_id', $companyId)
            ->select(
                't1.to_stage as stage',
                DB::raw('AVG(TIMESTAMPDIFF(DAY, t1.transitioned_at, IFNULL(t2.transitioned_at, NOW()))) as avg_days')
            )
            ->groupBy('t1.to_stage')
            ->get();
        
        return $avgTimes->mapWithKeys(function ($item) {
            return [$item->stage => round($item->avg_days, 1)];
        })->toArray();
    }
    
    public function getChurnPrediction(int $companyId): array
    {
        $atRiskCustomers = CustomerRelationship::where('company_id', $companyId)
            ->where('risk_score', '>', 0.7)
            ->with('customer')
            ->get();
        
        $predictions = [];
        
        foreach ($atRiskCustomers as $relationship) {
            $predictions[] = [
                'customer' => $relationship->customer,
                'risk_score' => $relationship->risk_score,
                'days_inactive' => $this->getDaysInactive($relationship),
                'predicted_churn_date' => $this->predictChurnDate($relationship),
                'retention_actions' => $this->getRetentionActions($relationship)
            ];
        }
        
        return $predictions;
    }
    
    protected function predictChurnDate(CustomerRelationship $relationship): ?string
    {
        // Simple prediction based on historical patterns
        $avgDaysToChurn = DB::table('journey_transitions')
            ->join('customer_relationships', 'journey_transitions.relationship_id', '=', 'customer_relationships.id')
            ->where('customer_relationships.company_id', $relationship->company_id)
            ->where('to_stage', 'churned')
            ->where('from_stage', $relationship->current_stage)
            ->avg(DB::raw('TIMESTAMPDIFF(DAY, stage_entered_at, transitioned_at)'));
        
        if ($avgDaysToChurn) {
            return now()->addDays($avgDaysToChurn)->format('Y-m-d');
        }
        
        return null;
    }
    
    protected function getRetentionActions(CustomerRelationship $relationship): array
    {
        $actions = [];
        
        if ($relationship->risk_score > 0.8) {
            $actions[] = [
                'type' => 'personal_call',
                'priority' => 'high',
                'description' => 'Schedule a personal check-in call'
            ];
        }
        
        if ($this->getDaysInactive($relationship) > 60) {
            $actions[] = [
                'type' => 'special_offer',
                'priority' => 'medium',
                'description' => 'Send a special return offer'
            ];
        }
        
        $actions[] = [
            'type' => 'email_campaign',
            'priority' => 'low',
            'description' => 'Include in re-engagement email campaign'
        ];
        
        return $actions;
    }
}
```

## Frontend Implementation

### Customer Journey Timeline

```javascript
// CustomerJourneyTimeline.jsx
import React, { useState, useEffect } from 'react';
import { Timeline, Card, Tag, Avatar, Tooltip } from 'antd';
import { format } from 'date-fns';

function CustomerJourneyTimeline({ customerId }) {
    const [journey, setJourney] = useState(null);
    const [transitions, setTransitions] = useState([]);
    
    useEffect(() => {
        fetchJourneyData();
    }, [customerId]);
    
    const fetchJourneyData = async () => {
        const response = await api.get(`/portal/customers/${customerId}/journey`);
        setJourney(response.data.journey);
        setTransitions(response.data.transitions);
    };
    
    const getStageIcon = (stage) => {
        const icons = {
            prospect: '🔍',
            lead: '📞',
            customer: '✅',
            regular: '⭐',
            vip: '👑',
            at_risk: '⚠️',
            churned: '❌'
        };
        return icons[stage] || '📍';
    };
    
    const getStageColor = (stage) => {
        const colors = {
            prospect: '#94A3B8',
            lead: '#60A5FA',
            customer: '#34D399',
            regular: '#10B981',
            vip: '#8B5CF6',
            at_risk: '#F59E0B',
            churned: '#EF4444'
        };
        return colors[stage] || '#666';
    };
    
    return (
        <Card title="Customer Journey Timeline">
            <div className="journey-header">
                <h3>Current Stage</h3>
                <Tag color={getStageColor(journey?.current_stage)}>
                    {getStageIcon(journey?.current_stage)} {journey?.stage_name}
                </Tag>
                <span className="stage-duration">
                    {journey?.days_in_current_stage} days
                </span>
            </div>
            
            <Timeline mode="left">
                {transitions.map((transition, index) => (
                    <Timeline.Item
                        key={transition.id}
                        color={getStageColor(transition.to_stage)}
                        dot={
                            <Avatar 
                                size="small" 
                                style={{ backgroundColor: getStageColor(transition.to_stage) }}
                            >
                                {getStageIcon(transition.to_stage)}
                            </Avatar>
                        }
                    >
                        <div className="transition-item">
                            <h4>{transition.to_stage_name}</h4>
                            <p className="transition-date">
                                {format(new Date(transition.transitioned_at), 'MMM d, yyyy')}
                            </p>
                            <p className="transition-trigger">
                                <Tag size="small">
                                    {transition.trigger_type}
                                </Tag>
                                {transition.trigger_event}
                            </p>
                            {transition.notes && (
                                <p className="transition-notes">{transition.notes}</p>
                            )}
                        </div>
                    </Timeline.Item>
                ))}
                
                <Timeline.Item 
                    color="gray"
                    dot={<Avatar size="small">🚀</Avatar>}
                >
                    <div className="transition-item">
                        <h4>Journey Started</h4>
                        <p className="transition-date">
                            {format(new Date(journey?.created_at), 'MMM d, yyyy')}
                        </p>
                    </div>
                </Timeline.Item>
            </Timeline>
        </Card>
    );
}
```

### Journey Analytics Dashboard

```javascript
// JourneyAnalytics.jsx
import React, { useState, useEffect } from 'react';
import { Row, Col, Card, Statistic } from 'antd';
import { Funnel, Sankey, Column } from '@ant-design/plots';

function JourneyAnalytics({ companyId }) {
    const [analytics, setAnalytics] = useState(null);
    
    useEffect(() => {
        fetchAnalytics();
    }, [companyId]);
    
    const fetchAnalytics = async () => {
        const response = await api.get('/portal/analytics/customer-journey', {
            params: { company_id: companyId }
        });
        setAnalytics(response.data);
    };
    
    const getFunnelConfig = () => ({
        data: analytics?.funnel_data || [],
        xField: 'stage',
        yField: 'customers',
        dynamicHeight: true,
        label: {
            formatter: (datum) => `${datum.customers} (${datum.percentage}%)`,
        },
        conversionTag: {
            formatter: (datum) => `${datum.conversionRate}%`,
        },
    });
    
    const getSankeyConfig = () => ({
        data: analytics?.transitions || [],
        sourceField: 'from',
        targetField: 'to',
        weightField: 'count',
        nodeWidthRatio: 0.008,
        nodePaddingRatio: 0.03,
    });
    
    return (
        <div className="journey-analytics">
            <Row gutter={[16, 16]}>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Total Customers"
                            value={analytics?.total_customers}
                            suffix="customers"
                        />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="At Risk"
                            value={analytics?.at_risk_count}
                            valueStyle={{ color: '#F59E0B' }}
                            suffix={`(${analytics?.at_risk_percentage}%)`}
                        />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="VIP Customers"
                            value={analytics?.vip_count}
                            valueStyle={{ color: '#8B5CF6' }}
                            suffix={`(${analytics?.vip_percentage}%)`}
                        />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Avg. Lifetime Value"
                            value={analytics?.avg_lifetime_value}
                            prefix="€"
                            precision={2}
                        />
                    </Card>
                </Col>
            </Row>
            
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col span={12}>
                    <Card title="Conversion Funnel">
                        <Funnel {...getFunnelConfig()} />
                    </Card>
                </Col>
                <Col span={12}>
                    <Card title="Stage Transitions">
                        <Sankey {...getSankeyConfig()} />
                    </Card>
                </Col>
            </Row>
            
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col span={24}>
                    <Card title="Time in Each Stage">
                        <StageTimeChart data={analytics?.stage_times} />
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
```

### Customer Risk Dashboard

```javascript
// CustomerRiskDashboard.jsx
import React from 'react';
import { Table, Tag, Button, Progress, Space } from 'antd';
import { PhoneOutlined, MailOutlined, GiftOutlined } from '@ant-design/icons';

function CustomerRiskDashboard({ companyId }) {
    const [customers, setCustomers] = useState([]);
    const [loading, setLoading] = useState(false);
    
    const columns = [
        {
            title: 'Customer',
            dataIndex: 'name',
            key: 'name',
            render: (text, record) => (
                <div>
                    <strong>{text}</strong>
                    <br />
                    <small>{record.email}</small>
                </div>
            ),
        },
        {
            title: 'Risk Score',
            dataIndex: 'risk_score',
            key: 'risk_score',
            render: (score) => (
                <Progress
                    percent={score * 100}
                    size="small"
                    strokeColor={{
                        '0%': '#52c41a',
                        '50%': '#faad14',
                        '100%': '#ff4d4f',
                    }}
                    format={(percent) => `${percent.toFixed(0)}%`}
                />
            ),
            sorter: (a, b) => a.risk_score - b.risk_score,
        },
        {
            title: 'Days Inactive',
            dataIndex: 'days_inactive',
            key: 'days_inactive',
            render: (days) => (
                <Tag color={days > 90 ? 'red' : days > 60 ? 'orange' : 'green'}>
                    {days} days
                </Tag>
            ),
            sorter: (a, b) => a.days_inactive - b.days_inactive,
        },
        {
            title: 'Lifetime Value',
            dataIndex: 'lifetime_value',
            key: 'lifetime_value',
            render: (value) => `€${value.toFixed(2)}`,
            sorter: (a, b) => a.lifetime_value - b.lifetime_value,
        },
        {
            title: 'Recommended Actions',
            key: 'actions',
            render: (_, record) => (
                <Space>
                    {record.recommended_actions.includes('call') && (
                        <Button
                            icon={<PhoneOutlined />}
                            size="small"
                            onClick={() => initiateCall(record)}
                        >
                            Call
                        </Button>
                    )}
                    {record.recommended_actions.includes('email') && (
                        <Button
                            icon={<MailOutlined />}
                            size="small"
                            onClick={() => sendEmail(record)}
                        >
                            Email
                        </Button>
                    )}
                    {record.recommended_actions.includes('offer') && (
                        <Button
                            icon={<GiftOutlined />}
                            size="small"
                            onClick={() => createOffer(record)}
                        >
                            Offer
                        </Button>
                    )}
                </Space>
            ),
        },
    ];
    
    return (
        <Card title="At-Risk Customers" extra={
            <Button type="primary" onClick={refreshData}>
                Refresh Analysis
            </Button>
        }>
            <Table
                columns={columns}
                dataSource={customers}
                loading={loading}
                rowKey="id"
                pagination={{
                    pageSize: 20,
                    showSizeChanger: true,
                }}
            />
        </Card>
    );
}
```

## Automation & Events

### Event Listeners

```php
namespace App\Listeners;

use App\Events\AppointmentCompleted;
use App\Events\CallReceived;
use App\Services\CustomerJourneyService;

class UpdateCustomerJourney
{
    protected $journeyService;
    
    public function __construct(CustomerJourneyService $journeyService)
    {
        $this->journeyService = $journeyService;
    }
    
    public function handleAppointmentCompleted(AppointmentCompleted $event)
    {
        $customer = $event->appointment->customer;
        
        // Update metrics
        $relationship = $customer->relationship;
        $relationship->increment('total_appointments');
        $relationship->update([
            'last_appointment_at' => now(),
            'lifetime_value' => $relationship->lifetime_value + $event->appointment->total_amount
        ]);
        
        // Check for stage transitions
        $this->journeyService->evaluateAutoTransitions($customer);
    }
    
    public function handleCallReceived(CallReceived $event)
    {
        $customer = $event->call->customer;
        
        if (!$customer->relationship) {
            $this->journeyService->initializeJourney($customer);
        }
        
        // Move from prospect to lead if qualified
        if ($event->call->duration > 60 && $customer->relationship->current_stage === 'prospect') {
            $this->journeyService->updateStage($customer, 'lead', 'automatic', [
                'event' => 'qualified_call_received'
            ]);
        }
    }
}
```

### Scheduled Tasks

```php
namespace App\Console\Commands;

use App\Models\CustomerRelationship;
use App\Services\CustomerJourneyService;

class UpdateCustomerRiskScores extends Command
{
    protected $signature = 'journey:update-risk-scores';
    protected $description = 'Update customer risk scores and check for at-risk transitions';
    
    public function handle(CustomerJourneyService $journeyService)
    {
        $relationships = CustomerRelationship::with('customer')
            ->whereNotIn('current_stage', ['churned', 'at_risk'])
            ->chunk(100, function ($chunk) use ($journeyService) {
                foreach ($chunk as $relationship) {
                    // Calculate new scores
                    $riskScore = $journeyService->calculateRiskScore($relationship->customer);
                    $engagementScore = $journeyService->calculateEngagementScore($relationship->customer);
                    
                    $relationship->update([
                        'risk_score' => $riskScore,
                        'engagement_score' => $engagementScore
                    ]);
                    
                    // Check if should move to at_risk
                    if ($riskScore > 0.7) {
                        $journeyService->updateStage(
                            $relationship->customer,
                            'at_risk',
                            'automatic',
                            ['event' => 'high_risk_score']
                        );
                    }
                }
            });
        
        $this->info('Risk scores updated successfully');
    }
}

// In Kernel.php
$schedule->command('journey:update-risk-scores')
    ->daily()
    ->at('02:00');
```

## Stage Actions

### Automated Actions per Stage

```php
namespace App\Services\Journey;

class StageActionService
{
    protected $actions = [
        'lead' => [
            'send_welcome_email',
            'assign_to_sales_team',
            'create_follow_up_task'
        ],
        'customer' => [
            'send_onboarding_sequence',
            'create_feedback_request',
            'update_crm_status'
        ],
        'vip' => [
            'assign_dedicated_manager',
            'send_vip_benefits_email',
            'create_quarterly_review'
        ],
        'at_risk' => [
            'notify_account_manager',
            'create_retention_campaign',
            'schedule_check_in_call'
        ]
    ];
    
    public function executeStageActions(Customer $customer, string $stage): void
    {
        $actions = $this->actions[$stage] ?? [];
        
        foreach ($actions as $action) {
            $this->$action($customer);
        }
    }
    
    protected function send_welcome_email(Customer $customer): void
    {
        Mail::to($customer->email)->queue(new WelcomeEmail($customer));
    }
    
    protected function create_retention_campaign(Customer $customer): void
    {
        RetentionCampaign::create([
            'customer_id' => $customer->id,
            'type' => 'at_risk',
            'scheduled_actions' => [
                ['type' => 'email', 'delay_days' => 0],
                ['type' => 'sms', 'delay_days' => 3],
                ['type' => 'call', 'delay_days' => 7],
                ['type' => 'special_offer', 'delay_days' => 14]
            ]
        ]);
    }
}
```

## Best Practices

### 1. Stage Definition

- Keep stages simple and meaningful
- Ensure clear progression path
- Define objective criteria for transitions
- Avoid too many stages (5-8 is optimal)

### 2. Data Quality

```php
// Regular data validation
class JourneyDataValidator
{
    public function validateRelationships(): array
    {
        $issues = [];
        
        // Check for orphaned relationships
        $orphaned = CustomerRelationship::doesntHave('customer')->count();
        if ($orphaned > 0) {
            $issues[] = "Found {$orphaned} orphaned relationships";
        }
        
        // Check for invalid stages
        $invalidStages = CustomerRelationship::whereNotIn(
            'current_stage',
            array_keys(DefaultJourneyStages::STAGES)
        )->count();
        
        if ($invalidStages > 0) {
            $issues[] = "Found {$invalidStages} relationships with invalid stages";
        }
        
        return $issues;
    }
}
```

### 3. Performance Optimization

```sql
-- Essential indexes
ALTER TABLE customer_relationships 
ADD INDEX idx_stage_risk (company_id, current_stage, risk_score);

ALTER TABLE journey_transitions 
ADD INDEX idx_customer_time (relationship_id, transitioned_at DESC);

-- Materialized view for analytics
CREATE VIEW journey_analytics_summary AS
SELECT 
    company_id,
    current_stage,
    COUNT(*) as customer_count,
    AVG(lifetime_value) as avg_ltv,
    AVG(risk_score) as avg_risk
FROM customer_relationships
GROUP BY company_id, current_stage;
```

### 4. Privacy & Compliance

```php
// GDPR compliance for journey data
class JourneyDataExporter
{
    public function exportCustomerJourneyData(Customer $customer): array
    {
        return [
            'journey_stages' => $customer->relationship->toArray(),
            'transitions' => $customer->journeyTransitions->toArray(),
            'metrics' => $customer->journeyMetrics->toArray(),
            'retention_campaigns' => $customer->retentionCampaigns->toArray()
        ];
    }
    
    public function anonymizeJourneyData(Customer $customer): void
    {
        // Keep statistical data but remove identifiers
        $customer->relationship->update([
            'notes' => null,
            'metadata' => json_encode(['anonymized' => true])
        ]);
        
        $customer->journeyTransitions()->update([
            'notes' => null,
            'performed_by' => null
        ]);
    }
}
```

## Troubleshooting

### Common Issues

1. **Stages not updating automatically**
   ```bash
   # Check if cron is running
   php artisan schedule:list
   
   # Run evaluation manually
   php artisan journey:evaluate-transitions --customer=123
   
   # Check logs
   tail -f storage/logs/journey.log
   ```

2. **Incorrect risk scores**
   ```php
   // Debug risk calculation
   $service = app(CustomerJourneyService::class);
   $score = $service->calculateRiskScore($customer);
   dd($score, $service->getRiskFactors($customer));
   ```

3. **Performance issues with large datasets**
   ```bash
   # Use queue for bulk updates
   php artisan journey:queue-updates --chunk=100
   
   # Optimize queries
   php artisan journey:optimize-queries
   ```

### Monitoring

```php
// Health check command
php artisan journey:health-check

// Metrics to monitor
- Average time per stage
- Conversion rates between stages  
- Risk score distribution
- Automation success rate
```

---

*For more information, see the [main documentation](./BUSINESS_PORTAL_COMPLETE_DOCUMENTATION.md)*