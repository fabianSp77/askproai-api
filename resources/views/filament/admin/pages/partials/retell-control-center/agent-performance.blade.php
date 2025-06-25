{{-- Agent Performance Dashboard --}}
<div x-data="{
    activeTab: 'overview',
    selectedPeriod: '7d',
    chartType: 'line',
    showExportMenu: false,
    
    periods: [
        { value: '24h', label: 'Last 24 Hours' },
        { value: '7d', label: 'Last 7 Days' },
        { value: '30d', label: 'Last 30 Days' },
        { value: '90d', label: 'Last 90 Days' }
    ],
    
    initCharts() {
        // Initialize charts after Alpine loads
        this.$nextTick(() => {
            this.renderCallVolumeChart();
            this.renderSuccessRateChart();
            this.renderOutcomeChart();
            this.renderCostChart();
        });
    },
    
    renderCallVolumeChart() {
        // Call volume chart implementation
    },
    
    renderSuccessRateChart() {
        // Success rate chart implementation
    },
    
    renderOutcomeChart() {
        // Outcome breakdown chart implementation
    },
    
    renderCostChart() {
        // Cost analysis chart implementation
    }
}"
x-init="initCharts()"
style="background: white; border-radius: 1rem; overflow: hidden;">
    
    {{-- Header --}}
    <div style="
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    ">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                {{-- Back Button --}}
                <button 
                    wire:click="closePerformanceDashboard"
                    style="
                        padding: 0.5rem;
                        border-radius: 0.5rem;
                        background: white;
                        border: 1px solid #e5e7eb;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    "
                    onmouseover="this.style.backgroundColor='#f9fafb'"
                    onmouseout="this.style.backgroundColor='white'">
                    <svg style="width: 1.25rem; height: 1.25rem; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </button>
                
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">
                        Performance Analytics
                    </h2>
                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">
                        {{ $performanceAgent['display_name'] ?? 'Agent' }} {{ $performanceAgent['version'] ?? '' }}
                    </p>
                </div>
            </div>
            
            {{-- Period Selector & Actions --}}
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                {{-- Period Selector --}}
                <select 
                    x-model="selectedPeriod"
                    @change="$wire.updatePerformancePeriod(selectedPeriod)"
                    style="
                        height: 40px;
                        padding: 0 2.5rem 0 0.75rem;
                        font-size: 0.875rem;
                        color: #374151;
                        background: white;
                        border: 1px solid #d1d5db;
                        border-radius: 0.5rem;
                        cursor: pointer;
                        appearance: none;
                        background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2214%22 height=%2214%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%236b7280%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3e%3cpath d=%22m6 9 6 6 6-6%22/%3e%3c/svg%3e');
                        background-repeat: no-repeat;
                        background-position: right 0.5rem center;
                        background-size: 1.25rem;
                    ">
                    <template x-for="period in periods" :key="period.value">
                        <option :value="period.value" x-text="period.label"></option>
                    </template>
                </select>
                
                {{-- Refresh Button --}}
                <button 
                    wire:click="refreshPerformanceData"
                    wire:loading.attr="disabled"
                    style="
                        height: 40px;
                        padding: 0 1rem;
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        font-size: 0.875rem;
                        font-weight: 500;
                        color: #374151;
                        background: white;
                        border: 1px solid #d1d5db;
                        border-radius: 0.5rem;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    "
                    onmouseover="this.style.backgroundColor='#f9fafb'"
                    onmouseout="this.style.backgroundColor='white'">
                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.class="loading-spinner">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span wire:loading.remove>Refresh</span>
                    <span wire:loading>Loading...</span>
                </button>
                
                {{-- Export Button --}}
                <div style="position: relative;">
                    <button 
                        @click="showExportMenu = !showExportMenu"
                        style="
                            height: 40px;
                            padding: 0 1rem;
                            display: inline-flex;
                            align-items: center;
                            gap: 0.5rem;
                            font-size: 0.875rem;
                            font-weight: 500;
                            color: white;
                            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                            border: none;
                            border-radius: 0.5rem;
                            cursor: pointer;
                            transition: all 0.2s ease;
                            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
                        "
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(99, 102, 241, 0.3)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(99, 102, 241, 0.2)'">
                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export
                    </button>
                    
                    {{-- Export Menu --}}
                    <div 
                        x-show="showExportMenu"
                        @click.away="showExportMenu = false"
                        x-transition
                        style="
                            position: absolute;
                            right: 0;
                            margin-top: 0.5rem;
                            width: 150px;
                            background: white;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.5rem;
                            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                            z-index: 50;
                        ">
                        <button 
                            wire:click="exportPerformanceReport('pdf')"
                            @click="showExportMenu = false"
                            style="
                                width: 100%;
                                padding: 0.75rem 1rem;
                                text-align: left;
                                font-size: 0.875rem;
                                color: #374151;
                                background: none;
                                border: none;
                                cursor: pointer;
                                transition: background 0.2s ease;
                            "
                            onmouseover="this.style.backgroundColor='#f9fafb'"
                            onmouseout="this.style.backgroundColor='transparent'">
                            Export as PDF
                        </button>
                        <button 
                            wire:click="exportPerformanceReport('csv')"
                            @click="showExportMenu = false"
                            style="
                                width: 100%;
                                padding: 0.75rem 1rem;
                                text-align: left;
                                font-size: 0.875rem;
                                color: #374151;
                                background: none;
                                border: none;
                                cursor: pointer;
                                transition: background 0.2s ease;
                            "
                            onmouseover="this.style.backgroundColor='#f9fafb'"
                            onmouseout="this.style.backgroundColor='transparent'">
                            Export as CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Tab Navigation --}}
    <div style="
        display: flex;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    ">
        <button 
            @click="activeTab = 'overview'"
            :class="{ 'active': activeTab === 'overview' }"
            style="
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #6b7280;
                background: none;
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: all 0.2s ease;
            "
            :style="activeTab === 'overview' ? 'background: white; color: #6366f1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : ''"
            onmouseover="if(this.getAttribute('class').indexOf('active') === -1) this.style.backgroundColor='#f3f4f6'"
            onmouseout="if(this.getAttribute('class').indexOf('active') === -1) this.style.backgroundColor='transparent'">
            Overview
        </button>
        <button 
            @click="activeTab = 'analytics'"
            :class="{ 'active': activeTab === 'analytics' }"
            style="
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #6b7280;
                background: none;
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: all 0.2s ease;
            "
            :style="activeTab === 'analytics' ? 'background: white; color: #6366f1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : ''"
            onmouseover="if(this.getAttribute('class').indexOf('active') === -1) this.style.backgroundColor='#f3f4f6'"
            onmouseout="if(this.getAttribute('class').indexOf('active') === -1) this.style.backgroundColor='transparent'">
            Analytics
        </button>
        <button 
            @click="activeTab = 'functions'"
            :class="{ 'active': activeTab === 'functions' }"
            style="
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #6b7280;
                background: none;
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: all 0.2s ease;
            "
            :style="activeTab === 'functions' ? 'background: white; color: #6366f1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : ''"
            onmouseover="if(this.getAttribute('class').indexOf('active') === -1) this.style.backgroundColor='#f3f4f6'"
            onmouseout="if(this.getAttribute('class').indexOf('active') === -1) this.style.backgroundColor='transparent'">
            Functions
        </button>
        <button 
            @click="activeTab = 'comparison'"
            :class="{ 'active': activeTab === 'comparison' }"
            style="
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #6b7280;
                background: none;
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: all 0.2s ease;
            "
            :style="activeTab === 'comparison' ? 'background: white; color: #6366f1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : ''"
            onmouseover="if(this.getAttribute('class').indexOf('active') === -1) this.style.backgroundColor='#f3f4f6'"
            onmouseout="if(this.getAttribute('class').indexOf('active') === -1) this.style.backgroundColor='transparent'">
            Comparison
        </button>
    </div>
    
    {{-- Tab Content --}}
    <div style="padding: 1.5rem;">
        {{-- Overview Tab --}}
        <div x-show="activeTab === 'overview'" x-transition>
            {{-- Key Metrics Grid --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                {{-- Total Calls --}}
                <div style="
                    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                    border: 1px solid #bfdbfe;
                    border-radius: 0.75rem;
                    padding: 1.25rem;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.75rem; font-weight: 600; color: #1e40af; text-transform: uppercase; letter-spacing: 0.05em;">
                                Total Calls
                            </span>
                            <svg style="width: 1.25rem; height: 1.25rem; color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #1e3a8a; line-height: 1;">
                            {{ number_format($performanceMetrics['total_calls'] ?? 0) }}
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.25rem; margin-top: 0.5rem;">
                            @if(($performanceMetrics['calls_trend'] ?? 0) > 0)
                                <svg style="width: 1rem; height: 1rem; color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                <span style="font-size: 0.875rem; font-weight: 500; color: #059669;">
                                    +{{ $performanceMetrics['calls_trend'] }}%
                                </span>
                            @elseif(($performanceMetrics['calls_trend'] ?? 0) < 0)
                                <svg style="width: 1rem; height: 1rem; color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                                <span style="font-size: 0.875rem; font-weight: 500; color: #dc2626;">
                                    {{ $performanceMetrics['calls_trend'] }}%
                                </span>
                            @else
                                <span style="font-size: 0.875rem; color: #6b7280;">No change</span>
                            @endif
                        </div>
                    </div>
                    {{-- Background decoration --}}
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(59, 130, 246, 0.1); border-radius: 50%;"></div>
                </div>
                
                {{-- Success Rate --}}
                <div style="
                    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                    border: 1px solid #bbf7d0;
                    border-radius: 0.75rem;
                    padding: 1.25rem;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.75rem; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: 0.05em;">
                                Success Rate
                            </span>
                            <svg style="width: 1.25rem; height: 1.25rem; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #14532d; line-height: 1;">
                            {{ number_format($performanceMetrics['success_rate'] ?? 0, 1) }}%
                        </div>
                        <div style="margin-top: 0.75rem; width: 100%; background: rgba(34, 197, 94, 0.2); border-radius: 9999px; height: 6px; overflow: hidden;">
                            <div style="background: #22c55e; height: 100%; border-radius: 9999px; width: {{ $performanceMetrics['success_rate'] ?? 0 }}%; transition: width 0.5s ease;"></div>
                        </div>
                    </div>
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(34, 197, 94, 0.1); border-radius: 50%;"></div>
                </div>
                
                {{-- Average Duration --}}
                <div style="
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                    border: 1px solid #fcd34d;
                    border-radius: 0.75rem;
                    padding: 1.25rem;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.75rem; font-weight: 600; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em;">
                                Avg Duration
                            </span>
                            <svg style="width: 1.25rem; height: 1.25rem; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #78350f; line-height: 1;">
                            {{ $performanceMetrics['avg_duration'] ?? '0:00' }}
                        </div>
                        <div style="font-size: 0.875rem; color: #92400e; margin-top: 0.5rem;">
                            {{ $performanceMetrics['duration_comparison'] ?? 'vs 3:42 average' }}
                        </div>
                    </div>
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(251, 191, 36, 0.1); border-radius: 50%;"></div>
                </div>
                
                {{-- Total Cost --}}
                <div style="
                    background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
                    border: 1px solid #f9a8d4;
                    border-radius: 0.75rem;
                    padding: 1.25rem;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.75rem; font-weight: 600; color: #831843; text-transform: uppercase; letter-spacing: 0.05em;">
                                Total Cost
                            </span>
                            <svg style="width: 1.25rem; height: 1.25rem; color: #db2777;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #500724; line-height: 1;">
                            ${{ number_format($performanceMetrics['total_cost'] ?? 0, 2) }}
                        </div>
                        <div style="font-size: 0.875rem; color: #831843; margin-top: 0.5rem;">
                            ${{ number_format($performanceMetrics['cost_per_call'] ?? 0, 3) }} per call
                        </div>
                    </div>
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(219, 39, 119, 0.1); border-radius: 50%;"></div>
                </div>
                
                {{-- Customer Rating --}}
                <div style="
                    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
                    border: 1px solid #c4b5fd;
                    border-radius: 0.75rem;
                    padding: 1.25rem;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.75rem; font-weight: 600; color: #5b21b6; text-transform: uppercase; letter-spacing: 0.05em;">
                                Customer Rating
                            </span>
                            <svg style="width: 1.25rem; height: 1.25rem; color: #7c3aed;" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #4c1d95; line-height: 1;">
                            {{ number_format($performanceMetrics['customer_rating'] ?? 0, 1) }}/5
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.125rem; margin-top: 0.5rem;">
                            @for($i = 1; $i <= 5; $i++)
                                <svg style="width: 1rem; height: 1rem; color: {{ $i <= round($performanceMetrics['customer_rating'] ?? 0) ? '#f59e0b' : '#e5e7eb' }};" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                    </div>
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(124, 58, 237, 0.1); border-radius: 50%;"></div>
                </div>
                
                {{-- Response Time --}}
                <div style="
                    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                    border: 1px solid #fca5a5;
                    border-radius: 0.75rem;
                    padding: 1.25rem;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="position: relative; z-index: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.75rem; font-weight: 600; color: #991b1b; text-transform: uppercase; letter-spacing: 0.05em;">
                                Avg Response
                            </span>
                            <svg style="width: 1.25rem; height: 1.25rem; color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #7f1d1d; line-height: 1;">
                            {{ $performanceMetrics['avg_response_time'] ?? '0' }}ms
                        </div>
                        <div style="font-size: 0.875rem; color: #991b1b; margin-top: 0.5rem;">
                            {{ $performanceMetrics['response_quality'] ?? 'Good' }} latency
                        </div>
                    </div>
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1); border-radius: 50%;"></div>
                </div>
            </div>
            
            {{-- Charts Section --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
                {{-- Call Volume Chart --}}
                <div style="
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 0.75rem;
                    padding: 1.5rem;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                ">
                    <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                        Call Volume Trend
                    </h3>
                    <div id="callVolumeChart" style="height: 300px;">
                        {{-- Chart will be rendered here --}}
                    </div>
                </div>
                
                {{-- Success Rate Chart --}}
                <div style="
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 0.75rem;
                    padding: 1.5rem;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                ">
                    <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                        Success Rate Over Time
                    </h3>
                    <div id="successRateChart" style="height: 300px;">
                        {{-- Chart will be rendered here --}}
                    </div>
                </div>
                
                {{-- Call Outcomes Breakdown --}}
                <div style="
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 0.75rem;
                    padding: 1.5rem;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                ">
                    <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                        Call Outcomes
                    </h3>
                    <div id="outcomeChart" style="height: 300px;">
                        {{-- Chart will be rendered here --}}
                    </div>
                    
                    {{-- Outcome Legend --}}
                    <div style="margin-top: 1rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                        @foreach($performanceMetrics['outcomes'] ?? [] as $outcome => $data)
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 12px; height: 12px; border-radius: 2px; background: {{ $data['color'] }};"></div>
                                <span style="font-size: 0.875rem; color: #4b5563;">{{ $outcome }}</span>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-left: auto;">
                                    {{ $data['percentage'] }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                {{-- Cost Analysis --}}
                <div style="
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 0.75rem;
                    padding: 1.5rem;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                ">
                    <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                        Cost Analysis
                    </h3>
                    <div id="costChart" style="height: 300px;">
                        {{-- Chart will be rendered here --}}
                    </div>
                    
                    {{-- Cost Breakdown --}}
                    <div style="margin-top: 1rem; padding: 1rem; background: #f9fafb; border-radius: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; color: #6b7280;">API Calls</span>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #111827;">
                                ${{ number_format($performanceMetrics['cost_breakdown']['api'] ?? 0, 2) }}
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; color: #6b7280;">Telephony</span>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #111827;">
                                ${{ number_format($performanceMetrics['cost_breakdown']['telephony'] ?? 0, 2) }}
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; border-top: 1px solid #e5e7eb;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Total</span>
                            <span style="font-size: 0.875rem; font-weight: 700; color: #111827;">
                                ${{ number_format($performanceMetrics['total_cost'] ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Analytics Tab --}}
        <div x-show="activeTab === 'analytics'" x-transition>
            <div style="text-align: center; padding: 3rem; color: #6b7280;">
                <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p>Advanced analytics coming in Phase 2</p>
            </div>
        </div>
        
        {{-- Functions Tab --}}
        <div x-show="activeTab === 'functions'" x-transition>
            <div style="text-align: center; padding: 3rem; color: #6b7280;">
                <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                <p>Function performance analytics coming in Phase 2</p>
            </div>
        </div>
        
        {{-- Comparison Tab --}}
        <div x-show="activeTab === 'comparison'" x-transition>
            <div style="text-align: center; padding: 3rem; color: #6b7280;">
                <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p>Version comparison coming in Phase 2</p>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js Integration --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('alpine:initialized', () => {
    // Charts will be initialized by Alpine component
});
</script>
@endpush