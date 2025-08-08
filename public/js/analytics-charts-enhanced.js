// Enhanced Analytics Charts with Better Design
console.log('📊 Enhanced Analytics Charts loaded');

window.EnhancedChartManager = {
    charts: {},
    
    // Modern color schemes
    colors: {
        primary: ['#3b82f6', '#60a5fa', '#93c5fd', '#dbeafe'],
        success: ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0'],
        warning: ['#f59e0b', '#fbbf24', '#fcd34d', '#fde68a'],
        danger: ['#ef4444', '#f87171', '#fca5a5', '#fecaca'],
        purple: ['#8b5cf6', '#a78bfa', '#c4b5fd', '#e9d5ff'],
        gradient: {
            blue: 'linear-gradient(180deg, rgba(59, 130, 246, 0.8) 0%, rgba(59, 130, 246, 0.2) 100%)',
            green: 'linear-gradient(180deg, rgba(16, 185, 129, 0.8) 0%, rgba(16, 185, 129, 0.2) 100%)',
            purple: 'linear-gradient(180deg, rgba(139, 92, 246, 0.8) 0%, rgba(139, 92, 246, 0.2) 100%)'
        }
    },
    
    destroy(id) {
        if (this.charts[id]) {
            try {
                this.charts[id].destroy();
                delete this.charts[id];
            } catch (e) {
                console.error(`Error destroying ${id}:`, e);
            }
        }
    },
    
    destroyAll() {
        Object.keys(this.charts).forEach(id => this.destroy(id));
    },
    
    createAppointmentsChart(element, data) {
        if (!element || !data.labels || !data.appointments) return;
        
        const ctx = element.getContext('2d');
        
        // Create gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.1)');
        
        this.charts.appointments = new Chart(element, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Termine',
                    data: data.appointments,
                    backgroundColor: gradient,
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    barThickness: 'flex',
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleColor: '#f3f4f6',
                        bodyColor: '#d1d5db',
                        borderColor: '#374151',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: (items) => items[0].label,
                            label: (item) => `Termine: ${item.formattedValue}`,
                            afterLabel: (item) => {
                                const total = data.appointments.reduce((a, b) => a + b, 0);
                                const percent = ((item.raw / total) * 100).toFixed(1);
                                return `${percent}% vom Total`;
                            }
                        }
                    },
                    datalabels: {
                        display: true,
                        color: '#374151',
                        anchor: 'end',
                        align: 'top',
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        formatter: (value) => value > 0 ? value : ''
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(229, 231, 235, 0.5)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11
                            },
                            padding: 8,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                }
            }
        });
    },
    
    createRevenueChart(element, data) {
        if (!element || !data.labels || !data.revenue) return;
        
        const ctx = element.getContext('2d');
        
        // Create gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.8)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.01)');
        
        this.charts.revenue = new Chart(element, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Umsatz',
                    data: data.revenue,
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#10b981',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleColor: '#f3f4f6',
                        bodyColor: '#d1d5db',
                        borderColor: '#374151',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: (items) => items[0].label,
                            label: (item) => `Umsatz: ${item.formattedValue.toLocaleString('de-DE')} €`,
                            afterLabel: (item) => {
                                const max = Math.max(...data.revenue);
                                const percent = ((item.raw / max) * 100).toFixed(1);
                                return `${percent}% vom Maximum`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(229, 231, 235, 0.5)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11
                            },
                            padding: 8,
                            callback: function(value) {
                                return value.toLocaleString('de-DE') + ' €';
                            }
                        }
                    }
                }
            }
        });
    },
    
    createCallDistributionChart(element, metrics) {
        if (!element || !metrics.inbound) return;
        
        const inbound = metrics.inbound.total_calls || 0;
        const outbound = (metrics.outbound && metrics.outbound.total_calls) || 0;
        
        if (inbound === 0 && outbound === 0) {
            this.showNoData(element.parentElement);
            return;
        }
        
        this.charts.callDist = new Chart(element, {
            type: 'doughnut',
            data: {
                labels: ['Eingehend', 'Ausgehend'],
                datasets: [{
                    data: [inbound, outbound],
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            color: '#6b7280',
                            font: {
                                size: 12,
                                weight: 500
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleColor: '#f3f4f6',
                        bodyColor: '#d1d5db',
                        borderColor: '#374151',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return [`${label}: ${value} Anrufe`, `${percentage}% vom Total`];
                            }
                        }
                    },
                    // Center text plugin
                    centerText: {
                        display: true,
                        text: `${inbound + outbound}`,
                        label: 'Gesamt'
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                beforeDraw: function(chart, args, options) {
                    if (options.display) {
                        const { ctx, chartArea: { width, height } } = chart;
                        ctx.save();
                        
                        // Draw main number
                        ctx.font = 'bold 24px sans-serif';
                        ctx.fillStyle = '#1f2937';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        const centerX = width / 2;
                        const centerY = height / 2 - 10;
                        ctx.fillText(options.text, centerX, centerY);
                        
                        // Draw label
                        ctx.font = '12px sans-serif';
                        ctx.fillStyle = '#6b7280';
                        ctx.fillText(options.label, centerX, centerY + 20);
                        
                        ctx.restore();
                    }
                }
            }]
        });
    },
    
    createCallsTimelineChart(element, data) {
        if (!element || !data.labels || !data.calls) return;
        
        const ctx = element.getContext('2d');
        
        // Create gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(139, 92, 246, 0.8)');
        gradient.addColorStop(1, 'rgba(139, 92, 246, 0.01)');
        
        this.charts.callsTimeline = new Chart(element, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Anrufe',
                    data: data.calls,
                    borderColor: '#8b5cf6',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#8b5cf6',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleColor: '#f3f4f6',
                        bodyColor: '#d1d5db',
                        borderColor: '#374151',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: (items) => items[0].label,
                            label: (item) => `Anrufe: ${item.formattedValue}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(229, 231, 235, 0.5)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 11
                            },
                            padding: 8,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                }
            }
        });
    },
    
    createHeatmap(element, data) {
        if (!element || !data || data.length === 0) return;
        
        this.charts.heatmap = new ApexCharts(element, {
            series: data,
            chart: {
                height: 350,
                type: 'heatmap',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                },
                animations: {
                    enabled: true,
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    }
                }
            },
            plotOptions: {
                heatmap: {
                    shadeIntensity: 0.5,
                    radius: 4,
                    useFillColorAsStroke: false,
                    colorScale: {
                        ranges: [
                            { from: 0, to: 0, name: 'Keine', color: '#f3f4f6' },
                            { from: 1, to: 3, name: 'Wenig', color: '#bfdbfe' },
                            { from: 4, to: 7, name: 'Mittel', color: '#60a5fa' },
                            { from: 8, to: 12, name: 'Viel', color: '#3b82f6' },
                            { from: 13, to: 999, name: 'Sehr viel', color: '#1e40af' }
                        ]
                    }
                }
            },
            dataLabels: {
                enabled: true,
                style: {
                    colors: ['#1f2937'],
                    fontSize: '11px',
                    fontWeight: 600
                }
            },
            stroke: {
                width: 1,
                colors: ['#fff']
            },
            xaxis: {
                type: 'category',
                categories: ['8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'],
                labels: {
                    style: {
                        colors: '#6b7280',
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#6b7280',
                        fontSize: '11px'
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' Termine';
                    }
                },
                style: {
                    fontSize: '12px'
                }
            },
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '12px',
                labels: {
                    colors: '#6b7280'
                }
            }
        });
        this.charts.heatmap.render();
    },
    
    showNoData(container) {
        const noDataHtml = `
            <div class="no-data-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                <p>Keine Daten verfügbar</p>
            </div>
        `;
        container.innerHTML = noDataHtml;
    },
    
    create() {
        console.log('🎨 Creating enhanced charts...');
        this.destroyAll();
        
        const chartData = window.analyticsChartData || {};
        const heatmapData = window.analyticsHeatmapData || [];
        const callMetrics = window.analyticsCallMetrics || {};
        
        if (!chartData || Object.keys(chartData).length === 0) {
            console.log('No chart data available');
            return;
        }
        
        // Create charts with enhanced design
        const appointmentsEl = document.getElementById('appointmentsChart');
        if (appointmentsEl) this.createAppointmentsChart(appointmentsEl, chartData);
        
        const revenueEl = document.getElementById('revenueChart');
        if (revenueEl) this.createRevenueChart(revenueEl, chartData);
        
        const callDistEl = document.getElementById('callDistributionChart');
        if (callDistEl) this.createCallDistributionChart(callDistEl, callMetrics);
        
        const callsTimelineEl = document.getElementById('callsTimelineChart');
        if (callsTimelineEl) this.createCallsTimelineChart(callsTimelineEl, chartData);
        
        const heatmapEl = document.getElementById('heatmap');
        if (heatmapEl) this.createHeatmap(heatmapEl, heatmapData);
        
        console.log('✅ Enhanced charts created');
    }
};

// Make it available globally
window.createEnhancedCharts = function() {
    window.EnhancedChartManager.create();
};

console.log('💡 Use window.createEnhancedCharts() to create enhanced charts');