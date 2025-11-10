import React, { useState, useEffect } from 'react';
import { CalcomBridge } from './CalcomBridge';

/**
 * Branch Selector Component
 *
 * Allows users to select which branch to book appointments for.
 * Auto-selects if only one branch available (configurable).
 */
export default function BranchSelector({
    defaultBranchId,
    onBranchChange,
    className = ''
}) {
    const [branches, setBranches] = useState([]);
    const [selectedBranch, setSelectedBranch] = useState(defaultBranchId);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchBranches();
    }, []);

    const fetchBranches = async () => {
        try {
            const data = await CalcomBridge.fetch('/api/calcom-atoms/config');
            setBranches(data.branches);

            // Auto-select if only one branch or if auto-select enabled
            if (data.branches.length === 1 && window.CalcomConfig?.autoSelectSingleBranch) {
                const branch = data.branches[0];
                setSelectedBranch(branch.id);
                onBranchChange?.(branch.id);
            } else if (data.default_branch) {
                // Use default branch if set
                setSelectedBranch(data.default_branch.branch_id);
                onBranchChange?.(data.default_branch.branch_id);
            }
        } catch (error) {
            console.error('Failed to fetch branches:', error);
            CalcomBridge.emit('error', {
                message: 'Failed to load branches'
            });
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        const branchId = parseInt(e.target.value);
        setSelectedBranch(branchId);
        onBranchChange?.(branchId);

        // Emit to Livewire
        CalcomBridge.emit('branch-changed', { branch_id: branchId });
    };

    // Skip rendering if only one branch (already auto-selected)
    if (branches.length <= 1) {
        return null;
    }

    if (loading) {
        return (
            <div className={`animate-pulse ${className}`}>
                <div className="h-10 bg-gray-200 rounded"></div>
            </div>
        );
    }

    return (
        <div className={className}>
            <label className="block text-sm font-medium text-gray-700 mb-2">
                Select Branch
            </label>
            <select
                value={selectedBranch}
                onChange={handleChange}
                className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            >
                <option value="">-- Select Branch --</option>
                {branches.map((branch) => (
                    <option key={branch.id} value={branch.id}>
                        {branch.name}
                        {branch.is_default && ' (Default)'}
                    </option>
                ))}
            </select>
        </div>
    );
}
