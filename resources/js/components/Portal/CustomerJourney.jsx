import React, { useState, useEffect } from 'react';
import { ChevronRightIcon, PhoneIcon, CalendarIcon, UserIcon, ExclamationTriangleIcon, CheckCircleIcon, XCircleIcon, ClockIcon, ArrowTopRightOnSquareIcon } from '@heroicons/react/24/outline';
import { CheckIcon } from '@heroicons/react/24/solid';
import axios from 'axios';

export default function CustomerJourney({ callId }) {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);
    const [activeTab, setActiveTab] = useState('journey');
    const [showAllTouchpoints, setShowAllTouchpoints] = useState(false);
    const [assigningCustomer, setAssigningCustomer] = useState(false);

    useEffect(() => {
        fetchJourneyData();
    }, [callId]);

    const fetchJourneyData = async () => {
        try {
            const response = await axios.get(`/business/api/customer-journey/call/${callId}`);
            setData(response.data);
        } catch (error) {
            console.error('Error fetching journey data:', error);
        } finally {
            setLoading(false);
        }
    };

    const updateJourneyStatus = async (customerId, newStatus) => {
        try {
            await axios.post(`/business/api/customer-journey/customer/${customerId}/status`, {
                status: newStatus
            });
            fetchJourneyData();
        } catch (error) {
            console.error('Error updating journey status:', error);
        }
    };

    const assignCustomer = async (customerId) => {
        setAssigningCustomer(true);
        try {
            await axios.post(`/business/api/customer-journey/call/${callId}/assign`, {
                customer_id: customerId
            });
            fetchJourneyData();
        } catch (error) {
            console.error('Error assigning customer:', error);
        } finally {
            setAssigningCustomer(false);
        }
    };

    const addNote = async (customerId, note) => {
        try {
            await axios.post(`/business/api/customer-journey/customer/${customerId}/note`, {
                note: note
            });
            fetchJourneyData();
        } catch (error) {
            console.error('Error adding note:', error);
        }
    };

    if (loading) {
        return (
            <div className="animate-pulse">
                <div className="h-48 bg-gray-200 rounded-lg mb-4"></div>
                <div className="h-12 bg-gray-200 rounded mb-4"></div>
                <div className="h-96 bg-gray-200 rounded-lg"></div>
            </div>
        );
    }

    if (!data) return null;

    const { customer, journey_data, journey_stages, potential_matches, touchpoints, journey_events, related_customers } = data;

    return (
        <div className="customer-journey-widget space-y-4">
            {customer ? (
                <>
                    {/* Customer Journey Status Bar */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {/* Header with Customer Info */}
                        <div className="p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div className="flex items-center gap-3">
                                    <div className={`h-12 w-12 rounded-full bg-${journey_data?.current_stage?.color || 'gray'}-100 dark:bg-${journey_data?.current_stage?.color || 'gray'}-900/20 flex items-center justify-center`}>
                                        <UserIcon className={`h-6 w-6 text-${journey_data?.current_stage?.color || 'gray'}-600 dark:text-${journey_data?.current_stage?.color || 'gray'}-400`} />
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            {customer.name}
                                            {customer.company_name && (
                                                <span className="text-sm text-gray-500 dark:text-gray-400 ml-2">({customer.company_name})</span>
                                            )}
                                        </h3>
                                        <p className={`text-sm text-${journey_data?.current_stage?.color || 'gray'}-600 dark:text-${journey_data?.current_stage?.color || 'gray'}-400 font-medium`}>
                                            {journey_data?.current_stage?.name || 'Unbekannt'}
                                        </p>
                                    </div>
                                </div>

                                {/* Quick Stats */}
                                <div className="grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-4">
                                    <div className="text-center">
                                        <div className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">
                                            {journey_data?.stats?.call_count || 0}
                                        </div>
                                        <div className="text-xs text-gray-500 dark:text-gray-400">Anrufe</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">
                                            {journey_data?.stats?.appointment_count || 0}
                                        </div>
                                        <div className="text-xs text-gray-500 dark:text-gray-400">Termine</div>
                                    </div>
                                    {journey_data?.stats?.total_revenue > 0 && (
                                        <div className="text-center col-span-2 sm:col-span-1">
                                            <div className="text-xl sm:text-2xl font-bold text-green-600 dark:text-green-400">
                                                {journey_data.stats.total_revenue.toFixed(2)}€
                                            </div>
                                            <div className="text-xs text-gray-500 dark:text-gray-400">Umsatz</div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Journey Progress Bar */}
                        <div className="p-4 overflow-x-auto">
                            <div className="flex items-center gap-2 min-w-max">
                                {journey_stages.filter(stage => stage.order <= 10).map((stage, index, array) => {
                                    const isPast = journey_data?.current_stage && stage.order < journey_data.current_stage.order;
                                    const isCurrent = journey_data?.current_stage && stage.code === journey_data.current_stage.code;
                                    const isFuture = journey_data?.current_stage && stage.order > journey_data.current_stage.order;

                                    return (
                                        <React.Fragment key={stage.code}>
                                            <div className="flex items-center">
                                                <div className="relative">
                                                    <div className={`h-8 w-8 sm:h-10 sm:w-10 rounded-full flex items-center justify-center transition-all
                                                        ${isCurrent ? `bg-${stage.color}-500 ring-4 ring-${stage.color}-200 dark:ring-${stage.color}-800` : ''}
                                                        ${isPast ? 'bg-green-500' : ''}
                                                        ${isFuture ? 'bg-gray-200 dark:bg-gray-700' : ''}`}>
                                                        {isPast ? (
                                                            <CheckIcon className="h-4 w-4 sm:h-5 sm:w-5 text-white" />
                                                        ) : isCurrent ? (
                                                            <div className="h-2 w-2 sm:h-3 sm:w-3 bg-white rounded-full"></div>
                                                        ) : (
                                                            <div className="h-2 w-2 bg-gray-400 dark:bg-gray-500 rounded-full"></div>
                                                        )}
                                                    </div>

                                                    {/* Stage Label */}
                                                    <div className="absolute -bottom-6 left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                                                        <span className={`text-xs ${isCurrent ? `font-semibold text-${stage.color}-600 dark:text-${stage.color}-400` : 'text-gray-500 dark:text-gray-400'}`}>
                                                            {stage.name}
                                                        </span>
                                                    </div>
                                                </div>

                                                {index < array.length - 1 && (
                                                    <div className={`h-0.5 w-8 sm:w-12 ${isPast ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'}`}></div>
                                                )}
                                            </div>
                                        </React.Fragment>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    {/* Tabs Navigation */}
                    <div className="border-b border-gray-200 dark:border-gray-700">
                        <nav className="-mb-px flex flex-wrap sm:flex-nowrap gap-2 sm:gap-4">
                            {['journey', 'touchpoints', 'related', 'notes'].map((tab) => (
                                <button
                                    key={tab}
                                    onClick={() => setActiveTab(tab)}
                                    className={`flex-1 sm:flex-initial py-2 px-3 sm:px-4 border-b-2 font-medium text-sm transition-colors
                                        ${activeTab === tab ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'}`}
                                >
                                    {tab === 'journey' && 'Customer Journey'}
                                    {tab === 'touchpoints' && 'Interaktionen'}
                                    {tab === 'related' && 'Verbindungen'}
                                    {tab === 'notes' && 'Notizen'}
                                </button>
                            ))}
                        </nav>
                    </div>

                    {/* Tab Content */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        {/* Journey Tab */}
                        {activeTab === 'journey' && (
                            <div className="p-4 space-y-4">
                                {journey_events && journey_events.length > 0 ? (
                                    <div className="space-y-3">
                                        {journey_events.map((event, index) => (
                                            <div key={index} className="flex gap-3">
                                                <div className="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                    <ClockIcon className="h-4 w-4 text-gray-500" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-sm text-gray-900 dark:text-gray-100">
                                                        Status geändert von <span className="font-medium">{event.from_status || 'Neu'}</span>
                                                        {' '}zu <span className="font-medium">{event.to_status}</span>
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {new Date(event.created_at).toLocaleString('de-DE')}
                                                        {event.triggered_by && ` • ${event.triggered_by}`}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Keine Journey-Events vorhanden</p>
                                )}

                                {/* Next possible statuses */}
                                {journey_data?.current_stage?.next_stages && (
                                    <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Nächste mögliche Schritte:
                                        </h4>
                                        <div className="flex flex-wrap gap-2">
                                            {journey_data.current_stage.next_stages.map(nextStageCode => {
                                                const nextStage = journey_stages.find(s => s.code === nextStageCode);
                                                if (!nextStage) return null;
                                                return (
                                                    <button
                                                        key={nextStageCode}
                                                        onClick={() => updateJourneyStatus(customer.id, nextStageCode)}
                                                        className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-${nextStage.color}-100 text-${nextStage.color}-700 dark:bg-${nextStage.color}-900/20 dark:text-${nextStage.color}-300 hover:bg-${nextStage.color}-200 dark:hover:bg-${nextStage.color}-900/30 transition-colors`}
                                                    >
                                                        {nextStage.name}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Touchpoints Tab */}
                        {activeTab === 'touchpoints' && (
                            <div className="p-4">
                                {touchpoints && touchpoints.length > 0 ? (
                                    <div className="space-y-3">
                                        {touchpoints.slice(0, showAllTouchpoints ? undefined : 5).map((touchpoint, index) => (
                                            <div key={index} className={`flex gap-3 pb-3 ${index < touchpoints.length - 1 ? 'border-b border-gray-100 dark:border-gray-700' : ''}`}>
                                                <div className="flex-shrink-0">
                                                    {touchpoint.type === 'call' ? (
                                                        <div className="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                                                            <PhoneIcon className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                        </div>
                                                    ) : touchpoint.type === 'appointment' ? (
                                                        <div className="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                                                            <CalendarIcon className="h-4 w-4 text-green-600 dark:text-green-400" />
                                                        </div>
                                                    ) : (
                                                        <div className="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                            <UserIcon className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {touchpoint.type.charAt(0).toUpperCase() + touchpoint.type.slice(1)}
                                                        {touchpoint.channel && <span className="text-gray-500 dark:text-gray-400"> über {touchpoint.channel}</span>}
                                                    </p>
                                                    {touchpoint.data?.summary && (
                                                        <p className="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">
                                                            {touchpoint.data.summary}
                                                        </p>
                                                    )}
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {new Date(touchpoint.occurred_at).toLocaleString('de-DE')}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}

                                        {touchpoints.length > 5 && !showAllTouchpoints && (
                                            <button
                                                onClick={() => setShowAllTouchpoints(true)}
                                                className="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"
                                            >
                                                Alle {touchpoints.length} Interaktionen anzeigen
                                            </button>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Keine Interaktionen erfasst</p>
                                )}
                            </div>
                        )}

                        {/* Related Customers Tab */}
                        {activeTab === 'related' && (
                            <div className="p-4">
                                {related_customers && related_customers.length > 0 ? (
                                    <div className="space-y-3">
                                        {related_customers.map(relatedCustomer => (
                                            <div key={relatedCustomer.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {relatedCustomer.name}
                                                        {relatedCustomer.company_name && (
                                                            <span className="text-gray-500"> ({relatedCustomer.company_name})</span>
                                                        )}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {relatedCustomer.phone} • {relatedCustomer.call_count} Anrufe
                                                    </p>
                                                </div>
                                                <a
                                                    href={`/admin/customers/${relatedCustomer.id}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                                >
                                                    <ArrowTopRightOnSquareIcon className="h-4 w-4" />
                                                </a>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Keine verwandten Kunden gefunden</p>
                                )}
                            </div>
                        )}

                        {/* Notes Tab */}
                        {activeTab === 'notes' && (
                            <div className="p-4">
                                <div className="space-y-4">
                                    {journey_data?.internal_notes ? (
                                        <div className="prose prose-sm dark:prose-invert max-w-none">
                                            <p className="whitespace-pre-wrap">{journey_data.internal_notes}</p>
                                        </div>
                                    ) : (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Keine internen Notizen vorhanden</p>
                                    )}

                                    {journey_data?.tags && journey_data.tags.length > 0 && (
                                        <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags:</h4>
                                            <div className="flex flex-wrap gap-2">
                                                {journey_data.tags.map((tag, index) => (
                                                    <span key={index} className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                        {tag}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Add Note Form */}
                                    <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <form onSubmit={(e) => {
                                            e.preventDefault();
                                            const note = e.target.note.value;
                                            if (note.trim()) {
                                                addNote(customer.id, note);
                                                e.target.reset();
                                            }
                                        }}>
                                            <label htmlFor="note" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Notiz hinzufügen:
                                            </label>
                                            <textarea
                                                name="note"
                                                id="note"
                                                rows="3"
                                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                placeholder="Neue Notiz eingeben..."
                                            />
                                            <button
                                                type="submit"
                                                className="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                            >
                                                Notiz speichern
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </>
            ) : (
                /* No Customer Assigned - Show Potential Matches */
                potential_matches && potential_matches.length > 0 ? (
                    <div className="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                        <h3 className="text-sm font-semibold text-amber-900 dark:text-amber-100 flex items-center gap-2 mb-3">
                            <ExclamationTriangleIcon className="h-4 w-4" />
                            Mögliche Kundenübereinstimmungen
                        </h3>

                        <div className="space-y-2">
                            {potential_matches.slice(0, 5).map(match => (
                                <div key={match.id} className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 bg-white dark:bg-amber-800/20 rounded-lg">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="text-sm font-medium text-amber-900 dark:text-amber-100">
                                                {match.name}
                                            </span>
                                            {match.company_name && (
                                                <span className="text-xs text-amber-700 dark:text-amber-300">
                                                    {match.company_name}
                                                </span>
                                            )}
                                            <span className={`text-xs px-2 py-0.5 rounded-full font-medium
                                                ${match.match_confidence >= 90 ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                                match.match_confidence >= 70 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300' :
                                                'bg-gray-100 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300'}`}>
                                                {match.match_confidence}% Match
                                            </span>
                                        </div>
                                        <div className="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                            {match.phone} • {match.call_count} Anrufe •
                                            Status: {match.journey_stage?.name || 'Unbekannt'}
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            onClick={() => assignCustomer(match.id)}
                                            disabled={assigningCustomer}
                                            className="text-xs px-3 py-1 bg-amber-600 text-white rounded hover:bg-amber-700 transition-colors disabled:opacity-50"
                                        >
                                            {assigningCustomer ? 'Zuordnen...' : 'Zuordnen'}
                                        </button>
                                        <a
                                            href={`/admin/customers/${match.id}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                        >
                                            <ArrowTopRightOnSquareIcon className="h-4 w-4" />
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700">
                            <p className="text-xs text-amber-700 dark:text-amber-300">
                                Ordnen Sie diesen Anruf einem bestehenden Kunden zu oder erstellen Sie einen neuen Kunden.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Kein Kunde zugeordnet. Verwenden Sie die Aktion "Kunde zuordnen" um die Customer Journey zu verfolgen.
                        </p>
                    </div>
                )
            )}
        </div>
    );
}