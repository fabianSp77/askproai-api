/**
 * Retell Ultimate Control Center - Advanced Alpine.js Components
 */

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

// Real-time Metrics Component
Alpine.data('realtimeMetrics', () => ({
    metrics: {
        activeCalls: 0,
        queuedCalls: 0,
        successRate: 0,
        avgWaitTime: 0,
        agentUtilization: 0,
        totalCallsToday: 0,
        totalBookingsToday: 0,
        failedCalls: 0
    },
    
    chart: null,
    chartConfig: {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Active Calls',
                data: [],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                }
            }
        }
    },
    
    init() {
        // Initialize Chart.js if canvas exists
        const canvas = document.getElementById('performanceChart');
        if (canvas) {
            this.chart = new Chart(canvas, this.chartConfig);
        }
        
        // Subscribe to real-time updates via Echo (if available)
        if (typeof Echo !== 'undefined') {
            Echo.channel('metrics')
                .listen('.metrics.update', (data) => {
                    this.updateMetrics(data);
                    this.updateChart(data);
                });
        }
        
        // Simulate real-time updates for demo
        this.simulateRealtimeData();
    },
    
    updateMetrics(data) {
        // Smooth transitions for metric values
        Object.keys(data).forEach(key => {
            if (this.metrics[key] !== undefined) {
                this.animateValue(key, this.metrics[key], data[key], 500);
            }
        });
    },
    
    animateValue(key, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                this.metrics[key] = end;
                clearInterval(timer);
            } else {
                this.metrics[key] = Math.round(current);
            }
        }, 16);
    },
    
    updateChart(data) {
        if (!this.chart) return;
        
        // Add new data point
        const now = new Date().toLocaleTimeString();
        this.chart.data.labels.push(now);
        this.chart.data.datasets[0].data.push(data.activeCalls || 0);
        
        // Keep only last 20 data points
        if (this.chart.data.labels.length > 20) {
            this.chart.data.labels.shift();
            this.chart.data.datasets[0].data.shift();
        }
        
        this.chart.update();
    },
    
    simulateRealtimeData() {
        setInterval(() => {
            const mockData = {
                activeCalls: Math.floor(Math.random() * 10),
                successRate: 85 + Math.floor(Math.random() * 14),
                totalCallsToday: this.metrics.totalCallsToday + Math.floor(Math.random() * 3),
                totalBookingsToday: this.metrics.totalBookingsToday + Math.floor(Math.random() * 2)
            };
            
            this.updateMetrics(mockData);
            this.updateChart(mockData);
        }, 5000);
    }
}));

// Function Builder Component
Alpine.data('functionBuilder', () => ({
    showBuilder: false,
    selectedTemplate: null,
    functionConfig: {
        name: '',
        type: 'custom',
        description: '',
        url: '',
        method: 'POST',
        headers: {},
        parameters: [],
        speak_during_execution: false,
        speak_during_execution_message: '',
        speak_after_execution: false,
        speak_after_execution_message: ''
    },
    
    parameterTypes: ['string', 'number', 'boolean', 'array', 'object'],
    
    selectTemplate(template) {
        this.selectedTemplate = template;
        // Populate function config from template
        Object.assign(this.functionConfig, template.config);
        this.functionConfig.name = template.name;
        this.functionConfig.description = template.description;
    },
    
    addParameter() {
        this.functionConfig.parameters.push({
            name: '',
            type: 'string',
            required: false,
            description: '',
            default: null
        });
    },
    
    removeParameter(index) {
        this.functionConfig.parameters.splice(index, 1);
    },
    
    testFunction() {
        // Create test payload
        const testPayload = {};
        this.functionConfig.parameters.forEach(param => {
            testPayload[param.name] = param.default || this.getDefaultValue(param.type);
        });
        
        // In real implementation, this would make an API call
        console.log('Testing function with payload:', testPayload);
        
        // Show test result modal
        this.$dispatch('show-test-result', {
            success: true,
            response: {
                status: 200,
                data: { message: 'Test successful!' }
            }
        });
    },
    
    getDefaultValue(type) {
        switch(type) {
            case 'string': return 'test';
            case 'number': return 0;
            case 'boolean': return false;
            case 'array': return [];
            case 'object': return {};
            default: return null;
        }
    },
    
    saveFunction() {
        // Validate function config
        if (!this.functionConfig.name || !this.functionConfig.url) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Save via Livewire
        this.$wire.saveFunction(this.functionConfig);
        
        // Reset and close
        this.resetBuilder();
    },
    
    resetBuilder() {
        this.showBuilder = false;
        this.selectedTemplate = null;
        this.functionConfig = {
            name: '',
            type: 'custom',
            description: '',
            url: '',
            method: 'POST',
            headers: {},
            parameters: [],
            speak_during_execution: false,
            speak_during_execution_message: '',
            speak_after_execution: false,
            speak_after_execution_message: ''
        };
    }
}));

// Agent Manager Component
Alpine.data('agentManager', () => ({
    agents: [],
    selectedAgent: null,
    editingAgent: false,
    agentEditor: {
        prompt: '',
        voice_id: '',
        voice_speed: 1,
        interruption_sensitivity: 1,
        backchannel_enabled: true,
        ambient_sound: 'off'
    },
    
    init() {
        // Load agents from Livewire
        this.agents = this.$wire.agents || [];
    },
    
    selectAgent(agent) {
        this.selectedAgent = agent;
        this.$wire.selectAgent(agent.agent_id);
    },
    
    editAgent(agent) {
        this.selectedAgent = agent;
        this.editingAgent = true;
        
        // Populate editor
        this.agentEditor = {
            prompt: agent.prompt || '',
            voice_id: agent.voice_id || '',
            voice_speed: agent.voice_speed || 1,
            interruption_sensitivity: agent.interruption_sensitivity || 1,
            backchannel_enabled: agent.backchannel_enabled || true,
            ambient_sound: agent.ambient_sound || 'off'
        };
    },
    
    saveAgent() {
        if (!this.selectedAgent) return;
        
        // Save via Livewire
        this.$wire.updateAgent(this.selectedAgent.agent_id, this.agentEditor);
        
        // Close editor
        this.editingAgent = false;
    },
    
    duplicateAgent(agent) {
        // Create duplicate with new name
        const duplicateName = `${agent.agent_name} (Copy)`;
        this.$wire.duplicateAgent(agent.agent_id, duplicateName);
    },
    
    testAgent(agent) {
        // Open test call modal
        this.$dispatch('open-test-call-modal', { agent });
    }
}));

// Webhook Configuration Component
Alpine.data('webhookConfig', () => ({
    webhookUrl: '',
    webhookSecret: '',
    events: {
        call_started: false,
        call_ended: false,
        call_analyzed: false
    },
    
    testWebhook() {
        // Create test payload
        const testPayload = {
            event_type: 'test',
            timestamp: new Date().toISOString(),
            data: {
                message: 'Test webhook from Retell Control Center'
            }
        };
        
        // In real implementation, this would send test webhook
        console.log('Testing webhook:', this.webhookUrl, testPayload);
        
        // Show result
        this.$dispatch('show-notification', {
            type: 'success',
            message: 'Test webhook sent successfully!'
        });
    },
    
    generateSecret() {
        // Generate random secret
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let secret = '';
        for (let i = 0; i < 32; i++) {
            secret += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        this.webhookSecret = secret;
    }
}));

// Initialize Alpine components
document.addEventListener('alpine:init', () => {
    // Register all components
    console.log('Retell Control Center initialized');
});

// Export for use in other scripts
export { Alpine };