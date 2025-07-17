import { useState, useEffect, useCallback } from 'react';
import { toast } from 'react-toastify';

export const useGoals = (csrfToken) => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [goals, setGoals] = useState([]);
    const [templates, setTemplates] = useState([]);
    const [activeGoals, setActiveGoals] = useState([]);
    const [goalDetails, setGoalDetails] = useState({});
    const [trends, setTrends] = useState({});
    const [projections, setProjections] = useState({});

    // Fetch all goals
    const fetchGoals = useCallback(async () => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await fetch('/business/api/goals', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch goals');
            }

            const data = await response.json();
            setGoals(data.goals || []);
            setActiveGoals(data.goals?.filter(g => g.is_active) || []);
        } catch (err) {
            setError(err.message);
            toast.error('Fehler beim Laden der Ziele');
        } finally {
            setLoading(false);
        }
    }, [csrfToken]);

    // Fetch templates
    const fetchTemplates = useCallback(async () => {
        try {
            const response = await fetch('/business/api/goals/templates', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch templates');
            }

            const data = await response.json();
            setTemplates(data.templates || []);
        } catch (err) {
            console.error('Error fetching templates:', err);
        }
    }, [csrfToken]);

    // Fetch goal details with metrics and achievements
    const fetchGoalDetails = useCallback(async (goalId) => {
        try {
            setLoading(true);
            
            const response = await fetch(`/business/api/goals/${goalId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch goal details');
            }

            const data = await response.json();
            setGoalDetails(prev => ({
                ...prev,
                [goalId]: data
            }));
            return data;
        } catch (err) {
            setError(err.message);
            console.error('Error fetching goal details:', err);
            toast.error('Fehler beim Laden der Zieldetails');
        } finally {
            setLoading(false);
        }
    }, [csrfToken]);

    // Create new goal
    const createGoal = useCallback(async (goalData) => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await fetch('/business/api/goals', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include',
                body: JSON.stringify(goalData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to create goal');
            }

            const data = await response.json();
            await fetchGoals(); // Refresh goals list
            toast.success('Ziel erfolgreich erstellt');
            return data;
        } catch (err) {
            setError(err.message);
            console.error('Error creating goal:', err);
            toast.error(err.message || 'Fehler beim Erstellen des Ziels');
            throw err;
        } finally {
            setLoading(false);
        }
    }, [csrfToken, fetchGoals]);

    // Update existing goal
    const updateGoal = useCallback(async (goalId, updates) => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await fetch(`/business/api/goals/${goalId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include',
                body: JSON.stringify(updates)
            });

            if (!response.ok) {
                throw new Error('Failed to update goal');
            }

            const data = await response.json();
            await fetchGoals(); // Refresh goals list
            toast.success('Ziel erfolgreich aktualisiert');
            return data;
        } catch (err) {
            setError(err.message);
            console.error('Error updating goal:', err);
            toast.error('Fehler beim Aktualisieren des Ziels');
            throw err;
        } finally {
            setLoading(false);
        }
    }, [csrfToken, fetchGoals]);

    // Delete goal
    const deleteGoal = useCallback(async (goalId) => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await fetch(`/business/api/goals/${goalId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to delete goal');
            }

            await fetchGoals(); // Refresh goals list
            toast.success('Ziel erfolgreich gelöscht');
        } catch (err) {
            setError(err.message);
            console.error('Error deleting goal:', err);
            toast.error('Fehler beim Löschen des Ziels');
            throw err;
        } finally {
            setLoading(false);
        }
    }, [csrfToken, fetchGoals]);

    // Toggle goal active state
    const toggleGoalActive = useCallback(async (goalId, isActive) => {
        try {
            const response = await fetch(`/business/api/goals/${goalId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include',
                body: JSON.stringify({ is_active: isActive })
            });

            if (!response.ok) {
                throw new Error('Failed to toggle goal');
            }

            await fetchGoals();
            toast.success(isActive ? 'Ziel aktiviert' : 'Ziel deaktiviert');
        } catch (err) {
            console.error('Error toggling goal:', err);
            toast.error('Fehler beim Ändern des Zielstatus');
        }
    }, [csrfToken, fetchGoals]);

    // Fetch trends for a goal
    const fetchTrends = useCallback(async (goalId, period = '30d') => {
        try {
            const response = await fetch(`/business/api/goals/${goalId}/trends?period=${period}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch trends');
            }

            const data = await response.json();
            setTrends(prev => ({
                ...prev,
                [goalId]: data
            }));
            return data;
        } catch (err) {
            console.error('Error fetching trends:', err);
        }
    }, [csrfToken]);

    // Fetch projections for a goal
    const fetchProjections = useCallback(async (goalId) => {
        try {
            const response = await fetch(`/business/api/goals/${goalId}/projections`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to fetch projections');
            }

            const data = await response.json();
            setProjections(prev => ({
                ...prev,
                [goalId]: data
            }));
            return data;
        } catch (err) {
            console.error('Error fetching projections:', err);
        }
    }, [csrfToken]);

    // Calculate achievement for a goal
    const calculateAchievement = useCallback(async (goalId) => {
        try {
            const response = await fetch(`/business/api/goals/${goalId}/calculate`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to calculate achievement');
            }

            const data = await response.json();
            // Refresh goal details to get updated achievement
            await fetchGoalDetails(goalId);
            return data;
        } catch (err) {
            console.error('Error calculating achievement:', err);
        }
    }, [csrfToken, fetchGoalDetails]);

    // Initial load
    useEffect(() => {
        fetchGoals();
        fetchTemplates();
    }, [fetchGoals, fetchTemplates]);

    return {
        loading,
        error,
        goals,
        activeGoals,
        templates,
        goalDetails,
        trends,
        projections,
        fetchGoals,
        fetchGoalDetails,
        createGoal,
        updateGoal,
        deleteGoal,
        toggleGoalActive,
        fetchTrends,
        fetchProjections,
        calculateAchievement
    };
};