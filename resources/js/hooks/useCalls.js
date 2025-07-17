import { useState, useEffect, useCallback } from 'react';

export const useCalls = (csrfToken) => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [calls, setCalls] = useState([]);
    const [stats, setStats] = useState({
        total_today: 0,
        new: 0,
        action_required: 0,
        avg_duration: 0
    });
    const [filters, setFilters] = useState({
        search: '',
        status: 'all',
        branch: 'all',
        date: '',
        page: 1,
        per_page: 25
    });
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
    });
    const [selectedCalls, setSelectedCalls] = useState([]);

    const fetchCalls = useCallback(async () => {
        try {
            setLoading(true);
            setError(null);

            const params = new URLSearchParams({
                page: filters.page,
                per_page: filters.per_page,
            });

            // Add filters
            if (filters.search) params.append('search', filters.search);
            if (filters.status && filters.status !== 'all') params.append('status', filters.status);
            if (filters.branch && filters.branch !== 'all') params.append('branch_id', filters.branch);
            if (filters.date) params.append('date', filters.date);

            const response = await fetch(`/business/api/calls?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            // Handle wrapped response structure
            const callsData = data.calls || data;
            
            setCalls(callsData.data || []);
            setPagination({
                current_page: callsData.current_page || 1,
                last_page: callsData.last_page || 1,
                from: callsData.from || 0,
                to: callsData.to || 0,
                total: callsData.total || 0
            });

            // Update stats if provided
            if (data.stats) {
                setStats(data.stats);
            }

        } catch (err) {
            setError(err.message);
            // Error is already handled by setError
        } finally {
            setLoading(false);
        }
    }, [csrfToken, filters]);

    useEffect(() => {
        fetchCalls();
    }, [fetchCalls]);

    const toggleCallSelection = (callId) => {
        setSelectedCalls(prev => {
            if (prev.includes(callId)) {
                return prev.filter(id => id !== callId);
            }
            return [...prev, callId];
        });
    };

    const selectAllCalls = (checked) => {
        if (checked) {
            setSelectedCalls(calls.map(call => call.id));
        } else {
            setSelectedCalls([]);
        }
    };

    const exportCalls = async (callIds = null) => {
        try {
            const params = new URLSearchParams();
            
            if (callIds && callIds.length > 0) {
                params.append('call_ids', callIds.join(','));
            } else {
                // Export with current filters
                if (filters.search) params.append('search', filters.search);
                if (filters.status && filters.status !== 'all') params.append('status', filters.status);
                if (filters.branch && filters.branch !== 'all') params.append('branch_id', filters.branch);
                if (filters.date) params.append('date', filters.date);
            }

            const response = await fetch(`/business/api/calls/export-csv?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Export failed');
            }

            // If response is JSON with URL
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                return data;
            }

            // If response is direct download
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `calls-export-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            return { success: true };
        } catch (err) {
            // Error is already handled by setError
            throw err;
        }
    };

    const refresh = () => {
        fetchCalls();
    };

    return {
        loading,
        error,
        calls,
        stats,
        filters,
        setFilters,
        pagination,
        selectedCalls,
        toggleCallSelection,
        selectAllCalls,
        exportCalls,
        refresh
    };
};