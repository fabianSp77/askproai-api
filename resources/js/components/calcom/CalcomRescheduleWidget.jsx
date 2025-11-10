import React from 'react';

/**
 * Cal.com Reschedule Widget (Placeholder - Phase 3 Implementation)
 *
 * This component will integrate Cal.com's reschedule functionality.
 * Full implementation coming in Phase 3.
 */
export default function CalcomRescheduleWidget({
    appointmentId,
    rescheduleUid,
    layout = 'MONTH_VIEW'
}) {
    return (
        <div className="p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
            <h3 className="text-lg font-semibold text-yellow-900 mb-2">
                Cal.com Reschedule Widget (Phase 3)
            </h3>
            <p className="text-sm text-yellow-700 mb-4">
                This placeholder will be replaced with the reschedule interface in Phase 3.
            </p>
            <div className="space-y-2 text-xs text-yellow-600">
                <p>✓ Appointment ID: {appointmentId || 'Not set'}</p>
                <p>✓ Reschedule UID: {rescheduleUid || 'Not set'}</p>
                <p>✓ Layout: {layout}</p>
            </div>
        </div>
    );
}
