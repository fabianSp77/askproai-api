import React, { useState, useEffect, useCallback } from 'react';
import { 
    ChartBarIcon, 
    CogIcon, 
    PlayIcon, 
    StopIcon, 
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    BoltIcon,
    SignalIcon,
    EyeIcon,
    WrenchScrewdriverIcon,
    ArrowPathIcon,
    ChevronDownIcon,
    ChevronUpIcon
} from '@heroicons/react/24/outline';
import { Switch } from '../ui/switch';
import { Button } from '../ui/button';
import { Card } from '../ui/card';
import { Badge } from '../ui/badge';
import { Alert } from '../ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../ui/tabs';
import { Progress } from '../ui/progress';
import { mcpService } from '../../services/mcpService';

const MCPConfiguration = () => {
    // State management
    const [config, setConfig] = useState({
        enabled: false,
        rolloutPercentage: 0,
        tokens: {
            retell: '',
            calcom: '',
            database: ''
        },
        rateLimits: {
            requestsPerMinute: 100,
            burstLimit: 20
        },
        circuitBreaker: {
            failureThreshold: 5,
            resetTimeout: 60000,
            halfOpenRequests: 3
        }
    });

    const [metrics, setMetrics] = useState({
        totalRequests: 0,
        successRate: 0,
        averageLatency: 0,
        circuitBreakerState: 'closed',
        activeConnections: 0,
        requestsPerMinute: 0,
        errorRate: 0
    });

    const [recentCalls, setRecentCalls] = useState([]);
    const [testResults, setTestResults] = useState({});
    const [loading, setLoading] = useState(true);
    const [testing, setTesting] = useState({});
    const [saveStatus, setSaveStatus] = useState('idle');
    const [expandedSections, setExpandedSections] = useState({
        config: true,
        monitoring: true,
        testing: false
    });

    // WebSocket connection for real-time updates
    const [socket, setSocket] = useState(null);

    // Load initial data
    useEffect(() => {
        loadConfiguration();
        loadMetrics();
        loadRecentCalls();
        setupWebSocket();

        // Auto-refresh metrics every 5 seconds
        const metricsInterval = setInterval(loadMetrics, 5000);
        const callsInterval = setInterval(loadRecentCalls, 10000);

        return () => {
            clearInterval(metricsInterval);
            clearInterval(callsInterval);
            if (socket) {
                socket.close();
            }
        };
    }, []);

    // Setup WebSocket for real-time updates
    const setupWebSocket = useCallback(() => {
        if (window.Echo) {
            const channel = window.Echo.channel('mcp-metrics');
            
            channel.listen('MCPMetricsUpdated', (data) => {
                setMetrics(prev => ({
                    ...prev,
                    ...data.metrics
                }));
            });

            channel.listen('MCPCallCompleted', (data) => {
                setRecentCalls(prev => [data.call, ...prev.slice(0, 9)]);
            });

            return () => channel.stopListening();
        }
    }, []);

    // Load configuration from API
    const loadConfiguration = async () => {
        try {
            const response = await mcpService.getConfiguration();
            setConfig(response.data);
        } catch (error) {
            console.error('Failed to load MCP configuration:', error);
        }
    };

    // Load metrics
    const loadMetrics = async () => {
        try {
            const response = await mcpService.getMetrics();
            setMetrics(response.data);
            setLoading(false);
        } catch (error) {
            console.error('Failed to load metrics:', error);
            setLoading(false);
        }
    };

    // Load recent calls
    const loadRecentCalls = async () => {
        try {
            const response = await mcpService.getRecentCalls();
            setRecentCalls(response.data);
        } catch (error) {
            console.error('Failed to load recent calls:', error);
        }
    };

    // Save configuration
    const saveConfiguration = async () => {
        setSaveStatus('saving');
        try {
            await mcpService.updateConfiguration(config);
            setSaveStatus('success');
            setTimeout(() => setSaveStatus('idle'), 2000);
        } catch (error) {
            console.error('Failed to save configuration:', error);
            setSaveStatus('error');
            setTimeout(() => setSaveStatus('idle'), 3000);
        }
    };

    // Test MCP tool
    const testTool = async (toolName) => {
        setTesting(prev => ({ ...prev, [toolName]: true }));
        try {
            const response = await mcpService.testTool(toolName);
            setTestResults(prev => ({
                ...prev,
                [toolName]: {
                    success: response.success,
                    responseTime: response.responseTime,
                    data: response.data,
                    timestamp: new Date().toLocaleTimeString()
                }
            }));
        } catch (error) {
            setTestResults(prev => ({
                ...prev,
                [toolName]: {
                    success: false,
                    error: error.message,
                    timestamp: new Date().toLocaleTimeString()
                }
            }));
        } finally {
            setTesting(prev => ({ ...prev, [toolName]: false }));
        }
    };

    // Toggle circuit breaker
    const toggleCircuitBreaker = async () => {
        try {
            await mcpService.toggleCircuitBreaker();
            loadMetrics();
        } catch (error) {
            console.error('Failed to toggle circuit breaker:', error);
        }
    };

    // Reset metrics
    const resetMetrics = async () => {
        try {
            await mcpService.resetMetrics();
            loadMetrics();
        } catch (error) {
            console.error('Failed to reset metrics:', error);
        }
    };

    // Toggle section expansion
    const toggleSection = (section) => {
        setExpandedSections(prev => ({
            ...prev,
            [section]: !prev[section]
        }));
    };

    // Get status color based on value
    const getStatusColor = (status) => {
        switch (status) {
            case 'healthy':
            case 'closed':
            case 'success':
                return 'text-green-600 bg-green-100';
            case 'warning':
            case 'half-open':
                return 'text-yellow-600 bg-yellow-100';
            case 'error':
            case 'open':
            case 'failed':
                return 'text-red-600 bg-red-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="flex items-center space-x-2">
                    <ArrowPathIcon className="w-5 h-5 animate-spin" />
                    <span>Loading MCP Configuration...</span>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6 p-6 max-w-7xl mx-auto">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">MCP Configuration</h1>
                    <p className="text-gray-600 mt-1">Configure and monitor Retell.ai MCP integration</p>
                </div>
                <div className="flex items-center space-x-3">
                    <Badge 
                        className={`${getStatusColor(metrics.circuitBreakerState)} border-0`}
                    >
                        {metrics.circuitBreakerState.toUpperCase()}
                    </Badge>
                    <Button 
                        onClick={saveConfiguration}
                        disabled={saveStatus === 'saving'}
                        className="bg-blue-600 hover:bg-blue-700"
                    >
                        {saveStatus === 'saving' ? 'Saving...' : 'Save Configuration'}
                    </Button>
                </div>
            </div>

            {/* Save Status Alert */}
            {saveStatus === 'success' && (
                <Alert className="bg-green-50 border-green-200">
                    <CheckCircleIcon className="w-4 h-4" />
                    <span>Configuration saved successfully!</span>
                </Alert>
            )}
            
            {saveStatus === 'error' && (
                <Alert className="bg-red-50 border-red-200">
                    <XCircleIcon className="w-4 h-4" />
                    <span>Failed to save configuration. Please try again.</span>
                </Alert>
            )}

            <Tabs defaultValue="configuration" className="space-y-4">
                <TabsList className="grid w-full grid-cols-3">
                    <TabsTrigger value="configuration">Configuration</TabsTrigger>
                    <TabsTrigger value="monitoring">Monitoring</TabsTrigger>
                    <TabsTrigger value="testing">Testing</TabsTrigger>
                </TabsList>

                {/* Configuration Tab */}
                <TabsContent value="configuration" className="space-y-6">
                    {/* Main Configuration */}
                    <Card className="p-6">
                        <div 
                            className="flex items-center justify-between cursor-pointer"
                            onClick={() => toggleSection('config')}
                        >
                            <div className="flex items-center space-x-2">
                                <CogIcon className="w-5 h-5" />
                                <h2 className="text-xl font-semibold">MCP Settings</h2>
                            </div>
                            {expandedSections.config ? 
                                <ChevronUpIcon className="w-5 h-5" /> : 
                                <ChevronDownIcon className="w-5 h-5" />
                            }
                        </div>

                        {expandedSections.config && (
                            <div className="mt-6 space-y-6">
                                {/* Enable/Disable MCP */}
                                <div className="flex items-center justify-between">
                                    <div>
                                        <label className="text-sm font-medium text-gray-900">
                                            Enable MCP Mode
                                        </label>
                                        <p className="text-sm text-gray-500">
                                            Switch between MCP tools and traditional webhooks
                                        </p>
                                    </div>
                                    <Switch
                                        checked={config.enabled}
                                        onCheckedChange={(checked) => 
                                            setConfig(prev => ({ ...prev, enabled: checked }))
                                        }
                                    />
                                </div>

                                {/* Rollout Percentage */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-900 mb-2">
                                        Rollout Percentage: {config.rolloutPercentage}%
                                    </label>
                                    <div className="flex items-center space-x-4">
                                        <input
                                            type="range"
                                            min="0"
                                            max="100"
                                            value={config.rolloutPercentage}
                                            onChange={(e) => setConfig(prev => ({ 
                                                ...prev, 
                                                rolloutPercentage: parseInt(e.target.value) 
                                            }))}
                                            className="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                        />
                                        <div className="w-16 text-right text-sm font-medium">
                                            {config.rolloutPercentage}%
                                        </div>
                                    </div>
                                    <Progress value={config.rolloutPercentage} className="mt-2" />
                                </div>

                                {/* Rate Limits */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">
                                            Requests per Minute
                                        </label>
                                        <input
                                            type="number"
                                            value={config.rateLimits.requestsPerMinute}
                                            onChange={(e) => setConfig(prev => ({
                                                ...prev,
                                                rateLimits: {
                                                    ...prev.rateLimits,
                                                    requestsPerMinute: parseInt(e.target.value)
                                                }
                                            }))}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">
                                            Burst Limit
                                        </label>
                                        <input
                                            type="number"
                                            value={config.rateLimits.burstLimit}
                                            onChange={(e) => setConfig(prev => ({
                                                ...prev,
                                                rateLimits: {
                                                    ...prev.rateLimits,
                                                    burstLimit: parseInt(e.target.value)
                                                }
                                            }))}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>

                                {/* Circuit Breaker Settings */}
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Circuit Breaker</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Failure Threshold
                                            </label>
                                            <input
                                                type="number"
                                                value={config.circuitBreaker.failureThreshold}
                                                onChange={(e) => setConfig(prev => ({
                                                    ...prev,
                                                    circuitBreaker: {
                                                        ...prev.circuitBreaker,
                                                        failureThreshold: parseInt(e.target.value)
                                                    }
                                                }))}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Reset Timeout (ms)
                                            </label>
                                            <input
                                                type="number"
                                                value={config.circuitBreaker.resetTimeout}
                                                onChange={(e) => setConfig(prev => ({
                                                    ...prev,
                                                    circuitBreaker: {
                                                        ...prev.circuitBreaker,
                                                        resetTimeout: parseInt(e.target.value)
                                                    }
                                                }))}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Half-Open Requests
                                            </label>
                                            <input
                                                type="number"
                                                value={config.circuitBreaker.halfOpenRequests}
                                                onChange={(e) => setConfig(prev => ({
                                                    ...prev,
                                                    circuitBreaker: {
                                                        ...prev.circuitBreaker,
                                                        halfOpenRequests: parseInt(e.target.value)
                                                    }
                                                }))}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </Card>

                    {/* Token Configuration */}
                    <Card className="p-6">
                        <h2 className="text-xl font-semibold mb-4 flex items-center">
                            <BoltIcon className="w-5 h-5 mr-2" />
                            API Tokens
                        </h2>
                        <div className="space-y-4">
                            {Object.entries(config.tokens).map(([service, token]) => (
                                <div key={service}>
                                    <label className="block text-sm font-medium text-gray-700 mb-2 capitalize">
                                        {service} Token
                                    </label>
                                    <input
                                        type="password"
                                        value={token}
                                        onChange={(e) => setConfig(prev => ({
                                            ...prev,
                                            tokens: {
                                                ...prev.tokens,
                                                [service]: e.target.value
                                            }
                                        }))}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Enter API token"
                                    />
                                </div>
                            ))}
                        </div>
                    </Card>
                </TabsContent>

                {/* Monitoring Tab */}
                <TabsContent value="monitoring" className="space-y-6">
                    {/* Real-time Metrics */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Total Requests</p>
                                    <p className="text-2xl font-bold">{metrics.totalRequests.toLocaleString()}</p>
                                </div>
                                <ChartBarIcon className="w-8 h-8 text-blue-500" />
                            </div>
                        </Card>
                        <Card className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Success Rate</p>
                                    <p className="text-2xl font-bold">{metrics.successRate.toFixed(1)}%</p>
                                </div>
                                <CheckCircleIcon className="w-8 h-8 text-green-500" />
                            </div>
                        </Card>
                        <Card className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Avg Latency</p>
                                    <p className="text-2xl font-bold">{metrics.averageLatency.toFixed(0)}ms</p>
                                </div>
                                <ClockIcon className="w-8 h-8 text-yellow-500" />
                            </div>
                        </Card>
                        <Card className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Active Connections</p>
                                    <p className="text-2xl font-bold">{metrics.activeConnections}</p>
                                </div>
                                <SignalIcon className="w-8 h-8 text-purple-500" />
                            </div>
                        </Card>
                    </div>

                    {/* Circuit Breaker Status */}
                    <Card className="p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold flex items-center">
                                <ExclamationTriangleIcon className="w-5 h-5 mr-2" />
                                Circuit Breaker Status
                            </h2>
                            <Button
                                onClick={toggleCircuitBreaker}
                                variant="outline"
                                size="sm"
                            >
                                {metrics.circuitBreakerState === 'open' ? 'Reset' : 'Trip'}
                            </Button>
                        </div>
                        <div className="flex items-center space-x-4">
                            <Badge className={`${getStatusColor(metrics.circuitBreakerState)} border-0 px-3 py-1`}>
                                {metrics.circuitBreakerState.toUpperCase()}
                            </Badge>
                            <div className="text-sm text-gray-600">
                                Requests/min: {metrics.requestsPerMinute} | Error Rate: {metrics.errorRate}%
                            </div>
                        </div>
                    </Card>

                    {/* Recent MCP Calls */}
                    <Card className="p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold">Recent MCP Calls</h2>
                            <Button onClick={loadRecentCalls} variant="outline" size="sm">
                                <ArrowPathIcon className="w-4 h-4 mr-2" />
                                Refresh
                            </Button>
                        </div>
                        <div className="space-y-2">
                            {recentCalls.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">No recent calls</p>
                            ) : (
                                recentCalls.map((call, index) => (
                                    <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div className="flex items-center space-x-3">
                                            <Badge 
                                                className={`${getStatusColor(call.success ? 'success' : 'failed')} border-0`}
                                            >
                                                {call.success ? 'SUCCESS' : 'FAILED'}
                                            </Badge>
                                            <span className="font-medium">{call.tool}</span>
                                            <span className="text-sm text-gray-600">{call.operation}</span>
                                        </div>
                                        <div className="flex items-center space-x-2 text-sm text-gray-500">
                                            <span>{call.duration}ms</span>
                                            <span>{call.timestamp}</span>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </Card>
                </TabsContent>

                {/* Testing Tab */}
                <TabsContent value="testing" className="space-y-6">
                    {/* Tool Testing */}
                    <Card className="p-6">
                        <h2 className="text-xl font-semibold mb-4 flex items-center">
                            <WrenchScrewdriverIcon className="w-5 h-5 mr-2" />
                            MCP Tools Testing
                        </h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {['calcom', 'database', 'retell', 'webhook', 'queue'].map((tool) => (
                                <Card key={tool} className="p-4 border-2 hover:border-blue-300 transition-colors">
                                    <div className="flex items-center justify-between mb-3">
                                        <h3 className="font-medium capitalize">{tool} Tool</h3>
                                        <Button
                                            onClick={() => testTool(tool)}
                                            disabled={testing[tool]}
                                            size="sm"
                                            className="bg-blue-600 hover:bg-blue-700"
                                        >
                                            {testing[tool] ? (
                                                <ArrowPathIcon className="w-4 h-4 animate-spin" />
                                            ) : (
                                                <PlayIcon className="w-4 h-4" />
                                            )}
                                        </Button>
                                    </div>
                                    
                                    {testResults[tool] && (
                                        <div className="space-y-2">
                                            <div className="flex items-center space-x-2">
                                                <Badge className={`${getStatusColor(testResults[tool].success ? 'success' : 'failed')} border-0`}>
                                                    {testResults[tool].success ? 'PASS' : 'FAIL'}
                                                </Badge>
                                                {testResults[tool].responseTime && (
                                                    <span className="text-xs text-gray-500">
                                                        {testResults[tool].responseTime}ms
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-xs text-gray-600">
                                                {testResults[tool].timestamp}
                                            </p>
                                            {testResults[tool].error && (
                                                <p className="text-xs text-red-600">
                                                    {testResults[tool].error}
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </Card>
                            ))}
                        </div>
                    </Card>

                    {/* Debug Information */}
                    <Card className="p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold flex items-center">
                                <EyeIcon className="w-5 h-5 mr-2" />
                                Debug Information
                            </h2>
                            <Button onClick={resetMetrics} variant="outline" size="sm">
                                Reset Metrics
                            </Button>
                        </div>
                        <pre className="bg-gray-100 p-4 rounded-lg text-sm overflow-auto max-h-64">
                            {JSON.stringify({ config, metrics, testResults }, null, 2)}
                        </pre>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default MCPConfiguration;