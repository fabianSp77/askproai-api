import React from 'react';

/**
 * Cal.com Cancel Widget (Placeholder - Phase 3 Implementation)
 *
 * This component will provide appointment cancellation with reason.
 * Full implementation coming in Phase 3.
 */
export default function CalcomCancelWidget({
    appointmentId,
    bookingUid
}) {
    return (
        <div className="p-6 bg-red-50 border border-red-200 rounded-lg">
            <h3 className="text-lg font-semibold text-red-900 mb-2">
                Cal.com Cancel Widget (Phase 3)
            </h3>
            <p className="text-sm text-red-700 mb-4">
                This placeholder will be replaced with the cancellation interface in Phase 3.
            </p>
            <div className="space-y-2 text-xs text-red-600">
                <p>✓ Appointment ID: {appointmentId || 'Not set'}</p>
                <p>✓ Booking UID: {bookingUid || 'Not set'}</p>
            </div>
        </div>
    );
}
