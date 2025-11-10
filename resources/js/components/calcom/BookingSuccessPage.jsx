import React from 'react';

/**
 * Custom Success Page for Cal.com BookerEmbed
 *
 * BookerEmbed doesn't have built-in success UI, so we create our own
 */
export default function BookingSuccessPage({ bookingData, onNewBooking, onViewAppointments }) {
    if (!bookingData) return null;

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('de-DE', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatTime = (dateString) => {
        return new Date(dateString).toLocaleTimeString('de-DE', {
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <div className="calcom-success-container">
            {/* Success Icon */}
            <div className="text-center mb-8">
                <div className="success-icon-wrapper inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-4">
                    <svg className="success-checkmark w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeDasharray="50" strokeDashoffset="0">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                    Termin erfolgreich gebucht!
                </h1>
                <p className="text-base md:text-lg text-gray-600">
                    Ihre Buchung wurde bestätigt
                </p>
            </div>

            {/* Booking Details Card */}
            <div className="booking-details-card mb-6">
                <h2 className="text-lg md:text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <svg className="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Termindetails
                </h2>

                <div className="space-y-1">
                    {/* Service/Title */}
                    <div className="detail-row">
                        <div className="detail-label">Service:</div>
                        <div className="detail-value">{bookingData.title || 'Termin'}</div>
                    </div>

                    {/* Date */}
                    <div className="detail-row">
                        <div className="detail-label">Datum:</div>
                        <div className="detail-value">{formatDate(bookingData.start)}</div>
                    </div>

                    {/* Time */}
                    <div className="detail-row">
                        <div className="detail-label">Uhrzeit:</div>
                        <div className="detail-value">
                            {formatTime(bookingData.start)} - {formatTime(bookingData.end)}
                        </div>
                    </div>

                    {/* Duration */}
                    {bookingData.duration && (
                        <div className="detail-row">
                            <div className="detail-label">Dauer:</div>
                            <div className="detail-value">{bookingData.duration} Minuten</div>
                        </div>
                    )}

                    {/* Location */}
                    {bookingData.location && bookingData.location !== 'somewhereElse' && (
                        <div className="detail-row">
                            <div className="detail-label">Ort:</div>
                            <div className="detail-value">{bookingData.location}</div>
                        </div>
                    )}

                    {/* Attendee Email */}
                    {bookingData.attendees?.[0]?.email && (
                        <div className="detail-row">
                            <div className="detail-label">E-Mail:</div>
                            <div className="detail-value">{bookingData.attendees[0].email}</div>
                        </div>
                    )}

                    {/* Booking UID */}
                    <div className="detail-row">
                        <div className="detail-label">Buchungs-ID:</div>
                        <div className="detail-value text-xs text-gray-500 font-mono">{bookingData.uid}</div>
                    </div>
                </div>
            </div>

            {/* Confirmation Message */}
            <div className="confirmation-banner mb-6">
                <div className="flex">
                    <svg className="w-5 h-5 text-blue-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div className="text-sm text-blue-800">
                        <p className="font-medium mb-1">Bestätigungs-E-Mail verschickt</p>
                        <p className="text-blue-700">
                            Eine Bestätigung wurde an <strong>{bookingData.attendees?.[0]?.email}</strong> gesendet.
                        </p>
                    </div>
                </div>
            </div>

            {/* Action Buttons */}
            <div className="flex flex-col sm:flex-row gap-3">
                <button
                    onClick={onNewBooking}
                    className="action-button-primary flex-1"
                >
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Weiteren Termin buchen
                </button>
                <button
                    onClick={onViewAppointments}
                    className="action-button-secondary flex-1"
                >
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Zu meinen Terminen
                </button>
            </div>
        </div>
    );
}
