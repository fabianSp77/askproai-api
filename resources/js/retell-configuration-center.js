// Retell Configuration Center JavaScript

document.addEventListener('alpine:init', () => {
    Alpine.data('retellConfigCenter', () => ({
        // State management
        activeTab: 'dashboard',
        showAgentModal: false,
        showWebhookModal: false,
        selectedAgent: null,
        testInProgress: false,
        
        // Live update intervals
        metricsInterval: null,
        webhooksInterval: null,
        
        init() {
            // Start live updates
            this.startLiveUpdates();
            
            // Listen for Livewire events
            Livewire.on('agentUpdated', () => {
                this.showSuccessToast('Agent updated successfully');
            });
            
            Livewire.on('testCompleted', (data) => {
                this.showTestResults(data);
            });
        },
        
        startLiveUpdates() {
            // Update metrics every 30 seconds
            this.metricsInterval = setInterval(() => {
                if (this.activeTab === 'dashboard') {
                    Livewire.emit('refreshMetrics');
                }
            }, 30000);
            
            // Update webhooks every 10 seconds when on webhooks tab
            this.webhooksInterval = setInterval(() => {
                if (this.activeTab === 'webhooks') {
                    Livewire.emit('refreshWebhooks');
                }
            }, 10000);
        },
        
        stopLiveUpdates() {
            if (this.metricsInterval) {
                clearInterval(this.metricsInterval);
            }
            if (this.webhooksInterval) {
                clearInterval(this.webhooksInterval);
            }
        },
        
        // Tab management
        switchTab(tab) {
            this.activeTab = tab;
            
            // Save tab preference
            localStorage.setItem('retell_active_tab', tab);
            
            // Trigger tab-specific actions
            if (tab === 'webhooks') {
                Livewire.emit('loadWebhooks');
            }
        },
        
        // Agent management
        editAgent(agent) {
            this.selectedAgent = agent;
            this.showAgentModal = true;
            Livewire.emit('loadAgent', agent.id);
        },
        
        deleteAgent(agentId) {
            if (confirm('Are you sure you want to delete this agent?')) {
                Livewire.emit('deleteAgent', agentId);
            }
        },
        
        // Testing functions
        async testWebhook() {
            this.testInProgress = true;
            
            try {
                const response = await fetch('/api/retell/test-webhook', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        test: true
                    })
                });
                
                const data = await response.json();
                this.showTestResults(data);
            } catch (error) {
                this.showErrorToast('Test failed: ' + error.message);
            } finally {
                this.testInProgress = false;
            }
        },
        
        async testAgent(agentId) {
            try {
                const response = await fetch(`/api/retell/agents/${agentId}/test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showSuccessToast('Agent test successful');
                } else {
                    this.showErrorToast('Agent test failed: ' + data.message);
                }
            } catch (error) {
                this.showErrorToast('Test failed: ' + error.message);
            }
        },
        
        // Custom function testing
        async testCustomFunction(functionName) {
            const modal = this.$refs.functionTestModal;
            
            // Show test modal with parameters
            this.showFunctionTestModal(functionName);
        },
        
        showFunctionTestModal(functionName) {
            // Implementation for showing function test modal
            Livewire.emit('showFunctionTest', functionName);
        },
        
        // UI helpers
        showTestResults(results) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 overflow-y-auto';
            modal.innerHTML = `
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full p-6">
                        <h3 class="text-lg font-medium mb-4">Test Results</h3>
                        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded overflow-x-auto">${JSON.stringify(results, null, 2)}</pre>
                        <button onclick="this.closest('.fixed').remove()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            Close
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        },
        
        showSuccessToast(message) {
            window.FilamentNotifications.new()
                .title(message)
                .success()
                .send();
        },
        
        showErrorToast(message) {
            window.FilamentNotifications.new()
                .title(message)
                .danger()
                .send();
        },
        
        // Copy to clipboard
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.showSuccessToast('Copied to clipboard');
            }).catch(() => {
                this.showErrorToast('Failed to copy');
            });
        },
        
        // Format timestamps
        formatTimestamp(timestamp) {
            return new Date(timestamp).toLocaleString();
        },
        
        // Format duration
        formatDuration(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        },
        
        // Cleanup on destroy
        destroy() {
            this.stopLiveUpdates();
        }
    }));
});

// Enhanced webhook viewer
class WebhookViewer {
    constructor() {
        this.syntaxHighlight = this.syntaxHighlight.bind(this);
    }
    
    syntaxHighlight(json) {
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'text-gray-600';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'text-blue-600 font-semibold';
                } else {
                    cls = 'text-green-600';
                }
            } else if (/true|false/.test(match)) {
                cls = 'text-purple-600';
            } else if (/null/.test(match)) {
                cls = 'text-red-600';
            } else {
                cls = 'text-orange-600';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    }
    
    display(data, container) {
        const formatted = JSON.stringify(data, null, 2);
        const highlighted = this.syntaxHighlight(formatted);
        container.innerHTML = `<pre class="text-sm">${highlighted}</pre>`;
    }
}

// Initialize webhook viewer
window.webhookViewer = new WebhookViewer();

// Real-time updates using Echo (if available)
if (window.Echo) {
    // Listen for webhook events
    window.Echo.private(`company.${window.companyId}`)
        .listen('RetellWebhookReceived', (e) => {
            // Update webhook count
            Livewire.emit('webhookReceived', e.webhook);
            
            // Show notification
            window.FilamentNotifications.new()
                .title('New webhook received')
                .body(`Event: ${e.webhook.event_type}`)
                .success()
                .send();
        })
        .listen('RetellCallStarted', (e) => {
            // Update active calls
            Livewire.emit('callStarted', e.call);
        })
        .listen('RetellCallEnded', (e) => {
            // Update call status
            Livewire.emit('callEnded', e.call);
        });
}