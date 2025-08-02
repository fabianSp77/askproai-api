// Smart Filter - Natural language filtering with AI
export class SmartFilter {
    constructor(options = {}) {
        this.options = {
            enableAI: true,
            cacheResults: true,
            debounceDelay: 300,
            ...options
        };
        
        this.filters = [];
        this.cache = new Map();
        this.debounceTimer = null;
        
        this.init();
    }
    
    init() {
        // Load filter patterns
        this.loadFilterPatterns();
        
        // Initialize AI if enabled
        if (this.options.enableAI) {
            this.initializeAI();
        }
    }
    
    loadFilterPatterns() {
        // Define natural language patterns
        this.patterns = {
            // Date patterns
            datePatterns: [
                { regex: /today/i, value: () => new Date().toISOString().split('T')[0] },
                { regex: /yesterday/i, value: () => {
                    const date = new Date();
                    date.setDate(date.getDate() - 1);
                    return date.toISOString().split('T')[0];
                }},
                { regex: /tomorrow/i, value: () => {
                    const date = new Date();
                    date.setDate(date.getDate() + 1);
                    return date.toISOString().split('T')[0];
                }},
                { regex: /last (\d+) days?/i, value: (match) => {
                    const days = parseInt(match[1]);
                    const date = new Date();
                    date.setDate(date.getDate() - days);
                    return date.toISOString().split('T')[0];
                }},
                { regex: /this week/i, value: () => 'this_week' },
                { regex: /last week/i, value: () => 'last_week' },
                { regex: /this month/i, value: () => 'this_month' },
                { regex: /last month/i, value: () => 'last_month' },
                { regex: /(\d{1,2})\/(\d{1,2})\/(\d{2,4})/i, value: (match) => {
                    // Parse date formats
                    return new Date(match[3], match[1] - 1, match[2]).toISOString().split('T')[0];
                }}
            ],
            
            // Status patterns
            statusPatterns: [
                { regex: /pending/i, field: 'status', value: 'pending' },
                { regex: /completed?/i, field: 'status', value: 'completed' },
                { regex: /confirm(ed)?/i, field: 'status', value: 'confirmed' },
                { regex: /cancel(l?ed)?/i, field: 'status', value: 'cancelled' },
                { regex: /active/i, field: 'status', value: 'active' },
                { regex: /inactive/i, field: 'status', value: 'inactive' },
                { regex: /draft/i, field: 'status', value: 'draft' },
                { regex: /published/i, field: 'status', value: 'published' }
            ],
            
            // Sentiment patterns
            sentimentPatterns: [
                { regex: /positive/i, field: 'sentiment', value: 'positive' },
                { regex: /negative/i, field: 'sentiment', value: 'negative' },
                { regex: /neutral/i, field: 'sentiment', value: 'neutral' },
                { regex: /happy/i, field: 'sentiment', value: 'positive' },
                { regex: /angry/i, field: 'sentiment', value: 'negative' },
                { regex: /satisfied/i, field: 'sentiment', value: 'positive' },
                { regex: /dissatisfied/i, field: 'sentiment', value: 'negative' }
            ],
            
            // Priority patterns
            priorityPatterns: [
                { regex: /urgent/i, field: 'priority', value: 'urgent' },
                { regex: /high priority/i, field: 'priority', value: 'high' },
                { regex: /medium priority/i, field: 'priority', value: 'medium' },
                { regex: /low priority/i, field: 'priority', value: 'low' },
                { regex: /important/i, field: 'priority', value: 'high' }
            ],
            
            // Comparison operators
            comparisonPatterns: [
                { regex: /greater than (\d+)/i, operator: '>', extractValue: true },
                { regex: /less than (\d+)/i, operator: '<', extractValue: true },
                { regex: /equal to (\d+)/i, operator: '=', extractValue: true },
                { regex: /between (\d+) and (\d+)/i, operator: 'between', extractValue: true },
                { regex: /(?:more|greater) than (\d+)/i, operator: '>', extractValue: true },
                { regex: /(?:less|fewer) than (\d+)/i, operator: '<', extractValue: true },
                { regex: /at least (\d+)/i, operator: '>=', extractValue: true },
                { regex: /at most (\d+)/i, operator: '<=', extractValue: true }
            ],
            
            // Boolean patterns
            booleanPatterns: [
                { regex: /has attachment/i, field: 'has_attachment', value: true },
                { regex: /no attachment/i, field: 'has_attachment', value: false },
                { regex: /with recording/i, field: 'has_recording', value: true },
                { regex: /without recording/i, field: 'has_recording', value: false },
                { regex: /is archived/i, field: 'archived', value: true },
                { regex: /not archived/i, field: 'archived', value: false }
            ],
            
            // Field-specific patterns
            fieldPatterns: [
                { regex: /from (?:customer )?(.+)/i, field: 'customer_name', extractValue: true },
                { regex: /by (?:staff|agent) (.+)/i, field: 'staff_name', extractValue: true },
                { regex: /(?:phone|number) (.+)/i, field: 'phone', extractValue: true },
                { regex: /email (.+)/i, field: 'email', extractValue: true },
                { regex: /containing "([^"]+)"/i, field: 'content', extractValue: true },
                { regex: /tagged? (?:as |with )?(.+)/i, field: 'tags', extractValue: true }
            ],
            
            // Duration patterns
            durationPatterns: [
                { regex: /longer than (\d+) (?:minutes?|mins?)/i, field: 'duration', operator: '>', unit: 'minutes' },
                { regex: /shorter than (\d+) (?:minutes?|mins?)/i, field: 'duration', operator: '<', unit: 'minutes' },
                { regex: /(\d+) to (\d+) (?:minutes?|mins?)/i, field: 'duration', operator: 'between', unit: 'minutes' }
            ]
        };
    }
    
    initializeAI() {
        // Initialize AI model for advanced parsing
        // This would connect to an AI service or use a local model
        this.aiReady = false;
        
        // Simulate AI initialization
        setTimeout(() => {
            this.aiReady = true;
            console.log('Smart Filter AI initialized');
        }, 1000);
    }
    
    async parseQuery(query) {
        // Check cache first
        if (this.options.cacheResults && this.cache.has(query)) {
            return this.cache.get(query);
        }
        
        // Clear existing filters
        this.filters = [];
        
        // Normalize query
        const normalizedQuery = query.toLowerCase().trim();
        
        // Parse using patterns
        this.parseWithPatterns(normalizedQuery);
        
        // Use AI for complex queries if available
        if (this.options.enableAI && this.aiReady) {
            await this.parseWithAI(normalizedQuery);
        }
        
        // Generate suggestions
        const suggestions = this.generateSuggestions(normalizedQuery);
        
        const result = {
            filters: this.filters,
            suggestions: suggestions,
            originalQuery: query
        };
        
        // Cache result
        if (this.options.cacheResults) {
            this.cache.set(query, result);
        }
        
        return result;
    }
    
    parseWithPatterns(query) {
        let remainingQuery = query;
        
        // Parse date patterns
        for (const pattern of this.patterns.datePatterns) {
            const match = remainingQuery.match(pattern.regex);
            if (match) {
                const value = typeof pattern.value === 'function' ? pattern.value(match) : pattern.value;
                this.filters.push({
                    id: `date-${Date.now()}`,
                    field: 'created_at',
                    operator: 'date',
                    value: value,
                    label: match[0],
                    type: 'date'
                });
                remainingQuery = remainingQuery.replace(match[0], '');
            }
        }
        
        // Parse status patterns
        for (const pattern of this.patterns.statusPatterns) {
            const match = remainingQuery.match(pattern.regex);
            if (match) {
                this.filters.push({
                    id: `status-${Date.now()}`,
                    field: pattern.field,
                    operator: '=',
                    value: pattern.value,
                    label: match[0],
                    type: 'status'
                });
                remainingQuery = remainingQuery.replace(match[0], '');
            }
        }
        
        // Parse sentiment patterns
        for (const pattern of this.patterns.sentimentPatterns) {
            const match = remainingQuery.match(pattern.regex);
            if (match) {
                this.filters.push({
                    id: `sentiment-${Date.now()}`,
                    field: pattern.field,
                    operator: '=',
                    value: pattern.value,
                    label: match[0],
                    type: 'sentiment'
                });
                remainingQuery = remainingQuery.replace(match[0], '');
            }
        }
        
        // Parse priority patterns
        for (const pattern of this.patterns.priorityPatterns) {
            const match = remainingQuery.match(pattern.regex);
            if (match) {
                this.filters.push({
                    id: `priority-${Date.now()}`,
                    field: pattern.field,
                    operator: '=',
                    value: pattern.value,
                    label: match[0],
                    type: 'priority'
                });
                remainingQuery = remainingQuery.replace(match[0], '');
            }
        }
        
        // Parse comparison patterns
        for (const pattern of this.patterns.comparisonPatterns) {
            const match = remainingQuery.match(pattern.regex);
            if (match) {
                let value;
                if (pattern.operator === 'between') {
                    value = [parseInt(match[1]), parseInt(match[2])];
                } else {
                    value = parseInt(match[1]);
                }
                
                // Try to determine field from context
                const field = this.inferFieldFromContext(remainingQuery, match.index);
                
                this.filters.push({
                    id: `comparison-${Date.now()}`,
                    field: field || 'value',
                    operator: pattern.operator,
                    value: value,
                    label: match[0],
                    type: 'comparison'
                });
                remainingQuery = remainingQuery.replace(match[0], '');
            }
        }
        
        // Parse boolean patterns
        for (const pattern of this.patterns.booleanPatterns) {
            const match = remainingQuery.match(pattern.regex);
            if (match) {
                this.filters.push({
                    id: `boolean-${Date.now()}`,
                    field: pattern.field,
                    operator: '=',
                    value: pattern.value,
                    label: match[0],
                    type: 'boolean'
                });
                remainingQuery = remainingQuery.replace(match[0], '');
            }
        }
        
        // Parse field-specific patterns
        for (const pattern of this.patterns.fieldPatterns) {
            const match = remainingQuery.match(pattern.regex);
            if (match) {
                this.filters.push({
                    id: `field-${Date.now()}`,
                    field: pattern.field,
                    operator: 'contains',
                    value: match[1].trim(),
                    label: match[0],
                    type: 'field'
                });
                remainingQuery = remainingQuery.replace(match[0], '');
            }
        }
        
        // Parse duration patterns
        for (const pattern of this.patterns.durationPatterns) {
            const match = remainingQuery.match(pattern.regex);
            if (match) {
                let value;
                if (pattern.operator === 'between') {
                    value = [parseInt(match[1]) * 60, parseInt(match[2]) * 60]; // Convert to seconds
                } else {
                    value = parseInt(match[1]) * 60; // Convert to seconds
                }
                
                this.filters.push({
                    id: `duration-${Date.now()}`,
                    field: pattern.field,
                    operator: pattern.operator,
                    value: value,
                    label: match[0],
                    type: 'duration'
                });
                remainingQuery = remainingQuery.replace(match[0], '');
            }
        }
        
        // Handle remaining text as general search
        remainingQuery = remainingQuery.trim();
        if (remainingQuery) {
            this.filters.push({
                id: `search-${Date.now()}`,
                field: 'all',
                operator: 'contains',
                value: remainingQuery,
                label: `Contains "${remainingQuery}"`,
                type: 'search'
            });
        }
    }
    
    inferFieldFromContext(query, position) {
        // Look for field indicators before the comparison
        const beforeComparison = query.substring(0, position).toLowerCase();
        
        const fieldIndicators = {
            'duration': ['duration', 'length', 'time'],
            'price': ['price', 'cost', 'amount', 'total'],
            'count': ['count', 'number', 'quantity'],
            'age': ['age', 'old'],
            'rating': ['rating', 'score', 'stars']
        };
        
        for (const [field, indicators] of Object.entries(fieldIndicators)) {
            for (const indicator of indicators) {
                if (beforeComparison.includes(indicator)) {
                    return field;
                }
            }
        }
        
        return null;
    }
    
    async parseWithAI(query) {
        // Simulate AI parsing for complex queries
        // In real implementation, this would call an AI service
        
        return new Promise((resolve) => {
            setTimeout(() => {
                // AI might identify additional intent or context
                if (query.includes('unhappy') || query.includes('complaints')) {
                    this.filters.push({
                        id: `ai-sentiment-${Date.now()}`,
                        field: 'sentiment',
                        operator: '=',
                        value: 'negative',
                        label: 'AI: Negative sentiment detected',
                        type: 'ai-inferred'
                    });
                }
                
                if (query.includes('vip') || query.includes('important customer')) {
                    this.filters.push({
                        id: `ai-tag-${Date.now()}`,
                        field: 'tags',
                        operator: 'contains',
                        value: 'VIP',
                        label: 'AI: VIP customer filter',
                        type: 'ai-inferred'
                    });
                }
                
                resolve();
            }, 100);
        });
    }
    
    generateSuggestions(query) {
        const suggestions = [];
        
        // Time-based suggestions
        if (!query.includes('today') && !query.includes('yesterday')) {
            suggestions.push('today', 'yesterday', 'last 7 days', 'this week');
        }
        
        // Status suggestions
        if (!query.includes('status')) {
            suggestions.push('pending', 'completed', 'cancelled');
        }
        
        // Sentiment suggestions
        if (!query.includes('sentiment') && !query.includes('positive') && !query.includes('negative')) {
            suggestions.push('positive sentiment', 'negative feedback');
        }
        
        // Context-specific suggestions
        const currentPath = window.location.pathname;
        if (currentPath.includes('/calls')) {
            suggestions.push('longer than 5 minutes', 'with recording', 'from last week');
        } else if (currentPath.includes('/appointments')) {
            suggestions.push('confirmed', 'this week', 'by John Smith');
        } else if (currentPath.includes('/customers')) {
            suggestions.push('tagged VIP', 'with email', 'created this month');
        }
        
        // Smart combinations based on existing filters
        if (this.filters.some(f => f.type === 'date')) {
            suggestions.push('and pending', 'and high priority');
        }
        
        return suggestions.slice(0, 8); // Limit suggestions
    }
    
    applyFilters(filters) {
        // Convert filters to query parameters
        const queryParams = this.filtersToQueryParams(filters);
        
        // Emit to Livewire
        if (window.Livewire) {
            window.Livewire.emit('applySmartFilters', queryParams);
        }
        
        return queryParams;
    }
    
    filtersToQueryParams(filters) {
        const params = {};
        
        filters.forEach(filter => {
            const key = `filter[${filter.field}]`;
            
            if (filter.operator === 'between' && Array.isArray(filter.value)) {
                params[`${key}[min]`] = filter.value[0];
                params[`${key}[max]`] = filter.value[1];
            } else if (filter.operator !== '=') {
                params[`${key}[operator]`] = filter.operator;
                params[`${key}[value]`] = filter.value;
            } else {
                params[key] = filter.value;
            }
        });
        
        return params;
    }
    
    clearFilters() {
        this.filters = [];
        this.cache.clear();
        
        if (window.Livewire) {
            window.Livewire.emit('clearSmartFilters');
        }
    }
    
    saveFilterSet(name) {
        const filterSets = JSON.parse(localStorage.getItem('smart-filter-sets') || '{}');
        filterSets[name] = {
            filters: this.filters,
            created: Date.now()
        };
        localStorage.setItem('smart-filter-sets', JSON.stringify(filterSets));
    }
    
    loadFilterSet(name) {
        const filterSets = JSON.parse(localStorage.getItem('smart-filter-sets') || '{}');
        if (filterSets[name]) {
            this.filters = filterSets[name].filters;
            this.applyFilters(this.filters);
        }
    }
    
    getSavedFilterSets() {
        const filterSets = JSON.parse(localStorage.getItem('smart-filter-sets') || '{}');
        return Object.keys(filterSets).map(name => ({
            name,
            ...filterSets[name]
        }));
    }
}