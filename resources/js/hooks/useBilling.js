import { useState, useEffect, useCallback } from 'react';

export const useBilling = (csrfToken) => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [billingData, setBillingData] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [usage, setUsage] = useState(null);

    const fetchBillingData = useCallback(async () => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await fetch('/business/api/billing', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch billing data');
            }

            const data = await response.json();
            setBillingData(data);
        } catch (err) {
            setError(err.message);
            // Error is already handled by setError
        } finally {
            setLoading(false);
        }
    }, [csrfToken]);

    const fetchTransactions = useCallback(async (page = 1, filters = {}) => {
        try {
            const params = new URLSearchParams({
                page,
                ...filters
            });
            
            const response = await fetch(`/business/api/billing/transactions?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch transactions');
            }

            const data = await response.json();
            // Ensure we always set an array
            const transactionsList = data.data || data || [];
            setTransactions(Array.isArray(transactionsList) ? transactionsList : []);
            return data;
        } catch (err) {
            setError(err.message);
            // Error is already handled by setError
            return { data: [] };
        }
    }, [csrfToken]);

    const fetchUsage = useCallback(async () => {
        try {
            const response = await fetch('/business/api/billing/usage', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch usage');
            }

            const data = await response.json();
            setUsage(data);
        } catch (err) {
            setError(err.message);
            // Error is already handled by setError
        }
    }, [csrfToken]);

    const topup = useCallback(async (amount) => {
        try {
            const response = await fetch('/business/api/billing/topup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include',
                body: JSON.stringify({ amount })
            });

            if (!response.ok) {
                throw new Error('Failed to process topup');
            }

            const data = await response.json();
            return data;
        } catch (err) {
            setError(err.message);
            throw err;
        }
    }, [csrfToken]);

    const updateAutoTopup = useCallback(async (settings) => {
        try {
            const response = await fetch('/business/api/billing/auto-topup', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include',
                body: JSON.stringify(settings)
            });

            if (!response.ok) {
                throw new Error('Failed to update auto-topup settings');
            }

            const data = await response.json();
            // Refresh billing data after update
            await fetchBillingData();
            return data;
        } catch (err) {
            setError(err.message);
            throw err;
        }
    }, [csrfToken, fetchBillingData]);

    const refresh = useCallback(async () => {
        await Promise.all([
            fetchBillingData(),
            fetchTransactions(),
            fetchUsage()
        ]);
    }, [fetchBillingData, fetchTransactions, fetchUsage]);

    useEffect(() => {
        refresh();
    }, []);

    return {
        loading,
        error,
        billingData,
        transactions,
        usage,
        fetchBillingData,
        fetchTransactions,
        fetchUsage,
        topup,
        updateAutoTopup,
        refresh
    };
};