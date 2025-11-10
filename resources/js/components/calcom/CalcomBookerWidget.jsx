import React, { useState, useEffect } from 'react';
import { BookerEmbed, CalProvider } from '@calcom/atoms';
import BranchSelector from './BranchSelector';
import { CalcomBridge } from './CalcomBridge';
import LoadingState from './LoadingState';
import ErrorState from './ErrorState';
import BookingSuccessPage from './BookingSuccessPage';

/**
 * Cal.com BookerEmbed Widget with Custom Success Page
 *
 * BookerEmbed is designed for embeds outside Next.js but doesn't have built-in success UI.
 * We handle success state ourselves with a custom React component.
 */
export default function CalcomBookerWidget({
    initialBranchId = null,
    layout = null,
    enableBranchSelector = true
}) {
    const [branchId, setBranchId] = useState(initialBranchId || window.CalcomConfig?.defaultBranchId);
    const [branchConfig, setBranchConfig] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [bookingSuccess, setBookingSuccess] = useState(false);
    const [bookingData, setBookingData] = useState(null);

    // Responsive layout detection
    const [responsiveLayout, setResponsiveLayout] = useState(
        layout?.toLowerCase() || window.CalcomConfig?.layout?.toLowerCase() || 'month_view'
    );

    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth < 768 && !layout) {
                setResponsiveLayout('column_view');
            } else if (!layout) {
                const configLayout = window.CalcomConfig?.layout || 'MONTH_VIEW';
                setResponsiveLayout(configLayout.toLowerCase());
            }
        };

        handleResize();
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, [layout]);

    useEffect(() => {
        if (branchId) {
            fetchBranchConfig(branchId);
        }
    }, [branchId]);

    const fetchBranchConfig = async (id) => {
        setLoading(true);
        setError(null);
        try {
            const data = await CalcomBridge.fetch(`/api/calcom-atoms/branch/${id}/config`);
            setBranchConfig(data);
        } catch (error) {
            console.error('Failed to fetch branch config:', error);
            setError('Failed to load booking configuration. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleBookingSuccess = (response) => {
        console.log('✅ Booking created successfully:', response);

        try {
            // Extract the actual booking data (Cal.com wraps in {status, data})
            const booking = response.data || response;

            setBookingData(booking);
            setBookingSuccess(true);

            // Send to Laravel for logging (fire and forget)
            CalcomBridge.fetch('/api/calcom-atoms/booking-created', {
                method: 'POST',
                body: JSON.stringify({
                    booking_uid: booking.uid,
                    event_type_id: booking.eventTypeId,
                    branch_id: branchId,
                    start_time: booking.start,
                    end_time: booking.end,
                    attendee: booking.attendees?.[0] || null,
                }),
            }).catch(error => {
                console.error('⚠️ Failed to log booking in Laravel:', error);
            });
        } catch (error) {
            console.error('Error in handleBookingSuccess:', error);
        }
    };

    const handleBookingError = (error) => {
        console.error('❌ Booking error:', error);
        setError('Buchung fehlgeschlagen. Bitte versuchen Sie es erneut.');
    };

    const handleNewBooking = () => {
        setBookingSuccess(false);
        setBookingData(null);
    };

    const handleViewAppointments = () => {
        window.location.href = '/admin/appointments';
    };

    // Auto-select single branch if preference enabled
    useEffect(() => {
        if (!branchId && window.CalcomConfig?.autoSelectSingleBranch) {
            CalcomBridge.fetch('/api/calcom-atoms/config')
                .then(data => {
                    if (data.branches.length === 1) {
                        setBranchId(data.branches[0].id);
                    }
                })
                .catch(error => {
                    console.error('Failed to auto-select branch:', error);
                });
        }
    }, [branchId]);

    // Show success page after booking
    if (bookingSuccess && bookingData) {
        return (
            <BookingSuccessPage
                bookingData={bookingData}
                onNewBooking={handleNewBooking}
                onViewAppointments={handleViewAppointments}
            />
        );
    }

    if (!branchId && enableBranchSelector) {
        return (
            <div className="p-4 md:p-6">
                <BranchSelector
                    defaultBranchId={branchId}
                    onBranchChange={setBranchId}
                />
            </div>
        );
    }

    if (error) {
        return <ErrorState message={error} onRetry={() => fetchBranchConfig(branchId)} />;
    }

    if (loading || !branchConfig) {
        return <LoadingState message="Loading booking calendar..." />;
    }

    if (!branchConfig.default_event_type) {
        return (
            <ErrorState
                message="No services available for this branch. Please configure services first."
                onRetry={null}
            />
        );
    }

    return (
        <CalProvider
            clientId=""
            options={{
                apiUrl: window.CalcomConfig.apiUrl
            }}
        >
            <div className="calcom-booker-container">
                {enableBranchSelector && (
                    <div className="mb-3 md:mb-4">
                        <BranchSelector
                            defaultBranchId={branchId}
                            onBranchChange={setBranchId}
                        />
                    </div>
                )}

                <BookerEmbed
                    eventSlug={branchConfig.default_event_type}
                    username={window.CalcomConfig.teamSlug}
                    isTeamEvent={true}
                    teamId={window.CalcomConfig.teamId}
                    view={responsiveLayout}
                    onCreateBookingSuccess={handleBookingSuccess}
                    onCreateBookingError={handleBookingError}
                    customClassNames={{
                        bookerContainer: 'border border-gray-200 rounded-lg shadow-sm',
                    }}
                />
            </div>
        </CalProvider>
    );
}
