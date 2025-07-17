import { useState, useEffect, useCallback } from 'react';
import { useAuth } from './useAuth';

export const useGoals = () => {
    const { csrfToken } = useAuth();
    const [goals, setGoals] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    // Helper function to get CSRF token from cookie
    const getCsrfToken = () => {
        // First try from useAuth
        if (csrfToken) return csrfToken;
        
        // Then try from meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) return metaToken.getAttribute('content');
        
        // Finally try from cookie
        const match = document.cookie.match(new RegExp('(^| )XSRF-TOKEN=([^;]+)'));
        if (match && match[2]) {
            return decodeURIComponent(match[2]);
        }
        
        return '';
    };

    // Fetch all goals
    const fetchGoals = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch('/business/api/goals', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                credentials: 'include'
            });

            if (!response.ok) throw new Error('Failed to fetch goals');

            const data = await response.json();
            setGoals(data.goals || []);
            return data.goals;
        } catch (err) {
            setError(err.message);
            console.error('Error fetching goals:', err);
            return [];
        } finally {
            setLoading(false);
        }
    }, []);

    // Create a new goal
    const createGoal = useCallback(async (goalData) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch('/business/api/goals', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                credentials: 'include',
                body: JSON.stringify(goalData)
            });

            if (!response.ok) throw new Error('Failed to create goal');

            const data = await response.json();
            await fetchGoals(); // Refresh the list
            return data.goal;
        } catch (err) {
            setError(err.message);
            console.error('Error creating goal:', err);
            throw err;
        } finally {
            setLoading(false);
        }
    }, [fetchGoals]);

    // Update an existing goal
    const updateGoal = useCallback(async (goalId, goalData) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`/business/api/goals/${goalId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                credentials: 'include',
                body: JSON.stringify(goalData)
            });

            if (!response.ok) throw new Error('Failed to update goal');

            const data = await response.json();
            await fetchGoals(); // Refresh the list
            return data.goal;
        } catch (err) {
            setError(err.message);
            console.error('Error updating goal:', err);
            throw err;
        } finally {
            setLoading(false);
        }
    }, [fetchGoals]);

    // Delete a goal
    const deleteGoal = useCallback(async (goalId) => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`/business/api/goals/${goalId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                credentials: 'include'
            });

            if (!response.ok) throw new Error('Failed to delete goal');

            await fetchGoals(); // Refresh the list
            return true;
        } catch (err) {
            setError(err.message);
            console.error('Error deleting goal:', err);
            throw err;
        } finally {
            setLoading(false);
        }
    }, [fetchGoals]);

    // Fetch goal progress
    const fetchGoalProgress = useCallback(async (goalId) => {
        try {
            const response = await fetch(`/business/api/goals/${goalId}/projections`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                credentials: 'include'
            });

            if (!response.ok) {
                // Fallback to simple controller
                const simpleResponse = await fetch(`/business/api/goals/${goalId}/simple-progress`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                credentials: 'include'
                });
                
                if (!simpleResponse.ok) throw new Error('Failed to fetch goal progress');
                
                const simpleData = await simpleResponse.json();
                return simpleData.progress;
            }

            const data = await response.json();
            return data.data?.projection || data.progress;
        } catch (err) {
            console.error('Error fetching goal progress:', err);
            throw err;
        }
    }, []);

    // Fetch goal metrics
    const fetchGoalMetrics = useCallback(async (goalId, dateRange) => {
        try {
            const params = new URLSearchParams();
            if (dateRange?.start) params.append('start_date', dateRange.start);
            if (dateRange?.end) params.append('end_date', dateRange.end);

            const response = await fetch(`/business/api/goals/${goalId}/achievement-trend?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                credentials: 'include'
            });

            if (!response.ok) {
                // Fallback to simple controller
                const simpleResponse = await fetch(`/business/api/goals/${goalId}/simple-metrics?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                credentials: 'include'
                });
                
                if (!simpleResponse.ok) throw new Error('Failed to fetch goal metrics');
                
                const simpleData = await simpleResponse.json();
                return simpleData.metrics;
            }

            const data = await response.json();
            // Transform the data to match expected format
            return {
                daily: data.data?.trend || [],
                trend: []
            };
        } catch (err) {
            console.error('Error fetching goal metrics:', err);
            throw err;
        }
    }, []);

    // Fetch goal templates
    const fetchGoalTemplates = useCallback(async () => {
        try {
            const response = await fetch('/business/api/goals/templates', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                credentials: 'include'
            });

            if (!response.ok) throw new Error('Failed to fetch goal templates');

            const data = await response.json();
            return data.data || data.templates || [];
        } catch (err) {
            console.error('Error fetching goal templates:', err);
            throw err;
        }
    }, []);

    // Auto-fetch goals on mount
    useEffect(() => {
        fetchGoals();
    }, [fetchGoals]);

    return {
        goals,
        loading,
        error,
        fetchGoals,
        createGoal,
        updateGoal,
        deleteGoal,
        fetchGoalProgress,
        fetchGoalMetrics,
        fetchGoalTemplates
    };
};