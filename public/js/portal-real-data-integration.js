/**
 * Real Data Integration for Business Portal
 * This file contains the updated functions to use real API data instead of mock data
 */

// Override the loadDashboard function to use real API
window.loadDashboard = async function() {
    try {
        const data = await apiClient.getDashboard();
        
        // Update stats with real data
        updateDashboardStats(data);
        updateCharts(data);
        updateRecentActivity(data.recent_activity || []);
        
        console.log('Dashboard loaded with real data:', data);
    } catch (error) {
        console.error('Failed to load dashboard, falling back to mock data:', error);
        // Fallback to mock data
        loadMockDashboard();
    }
};

// Override loadCalls to use real API
window.loadCalls = async function() {
    try {
        const response = await apiClient.getCalls({ 
            page: 1, 
            per_page: 20,
            sort_by: 'created_at',
            sort_order: 'desc'
        });
        
        displayCalls(response.data || response || []);
        
        // Update new calls count if available
        if (response.meta?.new_calls !== undefined) {
            updateNewCallsCount(response.meta.new_calls);
        }
        
        console.log('Calls loaded:', response);
    } catch (error) {
        console.error('Failed to load calls:', error);
        
        // Check if it's an auth error
        if (error.status === 401) {
            showToast('Sitzung abgelaufen. Bitte erneut anmelden.', 'error');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showToast('Anrufe konnten nicht geladen werden', 'error');
            // Show empty state
            displayCalls([]);
        }
    }
};

// Override loadBilling to use real API
window.loadBilling = async function() {
    try {
        const billing = await apiClient.getBilling();
        displayBilling(billing);
        
        console.log('Billing loaded:', billing);
    } catch (error) {
        console.error('Failed to load billing:', error);
        
        // Show mock data as fallback
        const mockBilling = {
            current_balance: 0,
            bonus_balance: 0,
            estimated_calls: 0
        };
        
        displayBilling(mockBilling);
        showToast('Abrechnungsdaten konnten nicht geladen werden', 'warning');
    }
};

// Enhanced displayCalls function with better formatting
window.displayCalls = function(calls) {
    const tbody = document.getElementById('callsTableBody');
    
    if (!calls || !calls.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-phone-slash text-4xl mb-2"></i>
                    <p>Keine Anrufe gefunden</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = calls.map(call => {
        // Format customer name
        const customerName = call.customer?.name || 
                           call.extracted_name || 
                           call.dynamic_variables?.customer_name ||
                           'Unbekannt';
        
        // Format duration
        const duration = formatDuration(call.duration || call.duration_seconds || 0);
        
        // Get status badge
        const statusBadge = getCallStatusBadge(call.status || 'unknown');
        
        // Format phone number
        const phoneNumber = formatPhoneNumber(call.from_number || call.from || '');
        
        return `
            <tr class="hover:bg-gray-50 cursor-pointer" onclick="viewCallDetails('${call.id}')">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${dayjs(call.created_at || call.start_time).format('DD.MM.YYYY HH:mm')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${phoneNumber}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${customerName}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${duration}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${statusBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-800" 
                                onclick="event.stopPropagation(); viewCall('${call.id}')"
                                title="Details anzeigen">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${call.recording_url ? `
                            <button class="text-green-600 hover:text-green-800" 
                                    onclick="event.stopPropagation(); playCallAudio('${call.id}')"
                                    title="Aufnahme abspielen">
                                <i class="fas fa-play-circle"></i>
                            </button>
                        ` : ''}
                        ${call.transcript ? `
                            <button class="text-purple-600 hover:text-purple-800" 
                                    onclick="event.stopPropagation(); showTranscript('${call.id}')"
                                    title="Transkript anzeigen">
                                <i class="fas fa-file-alt"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
};

// Helper function to format phone numbers
window.formatPhoneNumber = function(number) {
    if (!number) return '-';
    
    // Remove all non-digits
    const cleaned = number.replace(/\D/g, '');
    
    // Format German numbers
    if (cleaned.startsWith('49')) {
        // +49 123 456789
        return `+${cleaned.slice(0, 2)} ${cleaned.slice(2, 5)} ${cleaned.slice(5)}`;
    }
    
    // Default formatting
    return number;
};

// View call details
window.viewCallDetails = async function(callId) {
    try {
        showToast('Lade Anrufdetails...', 'info');
        
        const call = await apiClient.getCall(callId);
        
        // Show call details in a modal
        showCallDetailsModal(call);
    } catch (error) {
        console.error('Failed to load call details:', error);
        showToast('Anrufdetails konnten nicht geladen werden', 'error');
    }
};

// Show call details modal
window.showCallDetailsModal = function(call) {
    // Create modal HTML
    const modalHtml = `
        <div id="callDetailsModal" class="modal active">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Anrufdetails</h2>
                    <button onclick="closeCallDetailsModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <!-- Basic Info -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold mb-2">Grundinformationen</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Anrufer:</span>
                                <span class="ml-2 font-medium">${formatPhoneNumber(call.from_number || call.from || '-')}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Kunde:</span>
                                <span class="ml-2 font-medium">${call.customer?.name || call.extracted_name || 'Unbekannt'}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Datum:</span>
                                <span class="ml-2 font-medium">${dayjs(call.created_at).format('DD.MM.YYYY HH:mm')}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Dauer:</span>
                                <span class="ml-2 font-medium">${formatDuration(call.duration || 0)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary -->
                    ${call.summary ? `
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="font-semibold mb-2">Zusammenfassung</h3>
                            <p class="text-sm text-gray-700">${call.summary}</p>
                        </div>
                    ` : ''}
                    
                    <!-- Transcript -->
                    ${call.transcript ? `
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold mb-2">Transkript</h3>
                            <div class="text-sm text-gray-700 max-h-60 overflow-y-auto">
                                <pre class="whitespace-pre-wrap">${call.transcript}</pre>
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Actions -->
                    <div class="flex justify-end space-x-2 pt-4 border-t">
                        ${call.recording_url ? `
                            <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                <i class="fas fa-play mr-2"></i>
                                Aufnahme abspielen
                            </button>
                        ` : ''}
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-download mr-2"></i>
                            Als PDF exportieren
                        </button>
                        <button onclick="closeCallDetailsModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
};

// Close call details modal
window.closeCallDetailsModal = function() {
    const modal = document.getElementById('callDetailsModal');
    if (modal) {
        modal.remove();
    }
};

// Mock dashboard fallback
window.loadMockDashboard = function() {
    const mockData = {
        stats: {
            calls_today: 0,
            calls_trend: { value: 0 },
            appointments_today: 0,
            appointments_trend: { value: 0 },
            new_customers: 0,
            customers_trend: { value: 0 },
        },
        billing: {
            current_balance: 0
        },
        charts: {
            calls: {
                labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                data: [0, 0, 0, 0, 0, 0, 0]
            },
            appointments: {
                labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                data: [0, 0, 0, 0, 0, 0, 0]
            }
        },
        recent_activity: []
    };
    
    updateDashboardStats(mockData);
    updateCharts(mockData);
    updateRecentActivity(mockData.recent_activity);
    
    showToast('Dashboard im Demo-Modus', 'info');
};

// Auto-refresh dashboard every 60 seconds (if on dashboard page)
setInterval(() => {
    if (appState.currentPage === 'dashboard' && appState.isAuthenticated) {
        loadDashboard();
    }
}, 60000);

console.log('Real data integration loaded. Dashboard will now use live API data.');