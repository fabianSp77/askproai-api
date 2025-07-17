// Customer Detail View Component
const CustomerDetailView = ({ customerId, onBack, api, useTranslation, useState, useEffect }) => {
    const { t } = useTranslation();
    const [customer, setCustomer] = useState(null);
    const [timeline, setTimeline] = useState([]);
    const [activeTab, setActiveTab] = useState('overview');
    const [loading, setLoading] = useState(true);
    const [stats, setStats] = useState({});
    
    useEffect(() => {
        fetchCustomerDetails();
        fetchTimeline();
        fetchStatistics();
    }, [customerId]);
    
    const fetchCustomerDetails = async () => {
        try {
            const response = await api.get(`/customers/${customerId}`);
            setCustomer(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching customer:', error);
        }
    };
    
    const fetchTimeline = async () => {
        try {
            const response = await api.get(`/customers/${customerId}/timeline`);
            setTimeline(response.data.data);
        } catch (error) {
            console.error('Error fetching timeline:', error);
        } finally {
            setLoading(false);
        }
    };
    
    const fetchStatistics = async () => {
        try {
            const response = await api.get(`/customers/${customerId}/statistics`);
            setStats(response.data);
        } catch (error) {
            console.error('Error fetching statistics:', error);
        }
    };
    
    const handleAddNote = async (noteData) => {
        try {
            await api.post(`/customers/${customerId}/notes`, noteData);
            // Refresh timeline
            fetchTimeline();
            alert(t('note_added_successfully') || 'Notiz erfolgreich hinzugefügt');
        } catch (error) {
            console.error('Error adding note:', error);
            alert(t('error_adding_note') || 'Fehler beim Hinzufügen der Notiz');
        }
    };
    
    const handlePortalAccess = async (enable) => {
        try {
            const endpoint = enable ? 'enable-portal' : 'disable-portal';
            await api.post(`/customers/${customerId}/${endpoint}`);
            fetchCustomerDetails();
            alert(t(enable ? 'portal_enabled' : 'portal_disabled') || (enable ? 'Portal aktiviert' : 'Portal deaktiviert'));
        } catch (error) {
            console.error('Error toggling portal access:', error);
            alert(t('error_updating_portal_access') || 'Fehler beim Aktualisieren des Portal-Zugangs');
        }
    };
    
    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }
    
    return (
        <div className="customer-detail-view">
            {/* Header */}
            <div className="bg-white shadow-sm px-6 py-4 mb-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <button
                            onClick={onBack}
                            className="text-gray-500 hover:text-gray-700"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </button>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                {customer?.name || customer?.first_name + ' ' + customer?.last_name || t('unknown_customer') || 'Unbekannter Kunde'}
                            </h1>
                            <div className="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                                <span>{customer?.phone}</span>
                                <span>•</span>
                                <span>{customer?.email}</span>
                                <span>•</span>
                                <span>{t('customer_since') || 'Kunde seit'}: {customer?.created_at ? new Date(customer.created_at).toLocaleDateString('de-DE') : '-'}</span>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => handlePortalAccess(!customer?.has_portal_access)}
                            className={`px-4 py-2 rounded-lg text-sm font-medium ${
                                customer?.has_portal_access
                                    ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                    : 'bg-green-100 text-green-700 hover:bg-green-200'
                            }`}
                        >
                            {customer?.has_portal_access ? (t('disable_portal') || 'Portal deaktivieren') : (t('enable_portal') || 'Portal aktivieren')}
                        </button>
                        <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                            {t('book_appointment') || 'Termin buchen'}
                        </button>
                    </div>
                </div>
            </div>
            
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div className="bg-white rounded-lg shadow p-4">
                    <div className="text-sm text-gray-600">{t('total_appointments') || 'Termine gesamt'}</div>
                    <div className="text-2xl font-bold text-gray-900">{stats.total_appointments || 0}</div>
                    <div className="text-xs text-green-600 mt-1">
                        {stats.completed_appointments || 0} {t('completed') || 'abgeschlossen'}
                    </div>
                </div>
                <div className="bg-white rounded-lg shadow p-4">
                    <div className="text-sm text-gray-600">{t('total_calls') || 'Anrufe gesamt'}</div>
                    <div className="text-2xl font-bold text-gray-900">{stats.total_calls || 0}</div>
                    <div className="text-xs text-gray-500 mt-1">
                        {t('last_contact') || 'Letzter Kontakt'}: {stats.last_contact ? new Date(stats.last_contact).toLocaleDateString('de-DE') : '-'}
                    </div>
                </div>
                <div className="bg-white rounded-lg shadow p-4">
                    <div className="text-sm text-gray-600">{t('no_shows') || 'Nicht erschienen'}</div>
                    <div className={`text-2xl font-bold ${stats.no_shows > 2 ? 'text-red-600' : 'text-gray-900'}`}>
                        {stats.no_shows || 0}
                    </div>
                    <div className="text-xs text-gray-500 mt-1">
                        {stats.no_shows > 2 && (t('warning_frequent_no_shows') || 'Achtung: Häufige No-Shows')}
                    </div>
                </div>
                <div className="bg-white rounded-lg shadow p-4">
                    <div className="text-sm text-gray-600">{t('lifetime_value') || 'Gesamtumsatz'}</div>
                    <div className="text-2xl font-bold text-gray-900">
                        {stats.total_spent ? `€${(stats.total_spent / 100).toFixed(2)}` : '€0.00'}
                    </div>
                </div>
            </div>
            
            {/* Tabs */}
            <div className="bg-white rounded-lg shadow">
                <div className="border-b border-gray-200">
                    <nav className="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        {[
                            { id: 'overview', label: t('overview') || 'Übersicht' },
                            { id: 'timeline', label: t('timeline') || 'Timeline' },
                            { id: 'appointments', label: t('appointments') || 'Termine' },
                            { id: 'calls', label: t('calls') || 'Anrufe' },
                            { id: 'notes', label: t('notes') || 'Notizen' },
                            { id: 'documents', label: t('documents') || 'Dokumente' }
                        ].map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`
                                    whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                    ${activeTab === tab.id
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }
                                `}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </nav>
                </div>
                
                <div className="p-6">
                    {activeTab === 'overview' && <CustomerOverview customer={customer} stats={stats} t={t} />}
                    {activeTab === 'timeline' && <CustomerTimeline timeline={timeline} onAddNote={handleAddNote} t={t} />}
                    {activeTab === 'appointments' && <CustomerAppointments customerId={customerId} api={api} t={t} useState={useState} useEffect={useEffect} />}
                    {activeTab === 'calls' && <CustomerCalls customerId={customerId} api={api} t={t} useState={useState} useEffect={useEffect} />}
                    {activeTab === 'notes' && <CustomerNotes customerId={customerId} api={api} t={t} useState={useState} useEffect={useEffect} />}
                    {activeTab === 'documents' && <CustomerDocuments customerId={customerId} api={api} t={t} useState={useState} useEffect={useEffect} />}
                </div>
            </div>
        </div>
    );
};

// Customer Overview Component
const CustomerOverview = ({ customer, stats, t }) => {
    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4">{t('contact_information') || 'Kontaktinformationen'}</h3>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-500">{t('phone') || 'Telefon'}</label>
                        <p className="mt-1 text-sm text-gray-900">{customer?.phone || '-'}</p>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-500">{t('email') || 'E-Mail'}</label>
                        <p className="mt-1 text-sm text-gray-900">{customer?.email || '-'}</p>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-500">{t('address') || 'Adresse'}</label>
                        <p className="mt-1 text-sm text-gray-900">{customer?.address || '-'}</p>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-500">{t('date_of_birth') || 'Geburtsdatum'}</label>
                        <p className="mt-1 text-sm text-gray-900">
                            {customer?.date_of_birth ? new Date(customer.date_of_birth).toLocaleDateString('de-DE') : '-'}
                        </p>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4">{t('tags') || 'Tags'}</h3>
                <div className="flex flex-wrap gap-2">
                    {customer?.tags && customer.tags.length > 0 ? (
                        customer.tags.map((tag, index) => (
                            <span
                                key={index}
                                className="px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700"
                            >
                                {tag}
                            </span>
                        ))
                    ) : (
                        <p className="text-sm text-gray-500">{t('no_tags') || 'Keine Tags'}</p>
                    )}
                </div>
            </div>
            
            <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4">{t('preferences') || 'Präferenzen'}</h3>
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">{t('preferred_language') || 'Bevorzugte Sprache'}</span>
                        <span className="text-sm text-gray-900">{customer?.preferred_language || 'Deutsch'}</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">{t('contact_preference') || 'Kontaktpräferenz'}</span>
                        <span className="text-sm text-gray-900">{customer?.contact_preference || 'Email'}</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">{t('newsletter_subscribed') || 'Newsletter abonniert'}</span>
                        <span className="text-sm text-gray-900">
                            {customer?.newsletter_subscribed ? (t('yes') || 'Ja') : (t('no') || 'Nein')}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
};

// Customer Timeline Component  
const CustomerTimeline = ({ timeline, onAddNote, t }) => {
    const [showNoteModal, setShowNoteModal] = useState(false);
    const [noteData, setNoteData] = useState({ content: '', category: '', is_important: false });
    
    const getTimelineIcon = (type) => {
        switch (type) {
            case 'call':
                return (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                );
            case 'appointment':
                return (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                );
            case 'note':
                return (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                );
            case 'email':
                return (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                );
            default:
                return null;
        }
    };
    
    const handleAddNote = () => {
        onAddNote(noteData);
        setShowNoteModal(false);
        setNoteData({ content: '', category: '', is_important: false });
    };
    
    const getColorClass = (color) => {
        const colorMap = {
            'success': 'bg-green-500',
            'danger': 'bg-red-500',
            'warning': 'bg-yellow-500',
            'info': 'bg-blue-500',
            'secondary': 'bg-gray-500'
        };
        return colorMap[color] || 'bg-gray-500';
    };
    
    return (
        <div>
            <div className="mb-4 flex justify-between items-center">
                <h3 className="text-lg font-medium text-gray-900">{t('activity_timeline') || 'Aktivitäts-Timeline'}</h3>
                <button
                    onClick={() => setShowNoteModal(true)}
                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"
                >
                    {t('add_note') || 'Notiz hinzufügen'}
                </button>
            </div>
            
            <div className="flow-root">
                <ul className="-mb-8">
                    {timeline.map((item, index) => (
                        <li key={item.id}>
                            <div className="relative pb-8">
                                {index !== timeline.length - 1 && (
                                    <span
                                        className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                        aria-hidden="true"
                                    />
                                )}
                                <div className="relative flex space-x-3">
                                    <div>
                                        <span
                                            className={`h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white ${getColorClass(item.color)}`}
                                        >
                                            <span className="text-white">{getTimelineIcon(item.type)}</span>
                                        </span>
                                    </div>
                                    <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p className="text-sm text-gray-900">
                                                {item.title}
                                            </p>
                                            <p className="mt-1 text-sm text-gray-500">
                                                {item.description}
                                            </p>
                                            {item.details && Object.keys(item.details).length > 0 && (
                                                <div className="mt-2 text-xs text-gray-400">
                                                    {Object.entries(item.details).map(([key, value]) => (
                                                        value && <span key={key} className="mr-3">{key}: {value}</span>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                        <div className="text-right text-sm whitespace-nowrap text-gray-500">
                                            <time dateTime={item.timestamp}>
                                                {new Date(item.timestamp).toLocaleString('de-DE')}
                                            </time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
            
            {/* Add Note Modal */}
            {showNoteModal && (
                <div className="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
                    <div className="bg-white rounded-lg max-w-md w-full p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">{t('add_note') || 'Notiz hinzufügen'}</h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">{t('note_content') || 'Notizinhalt'}</label>
                                <textarea
                                    value={noteData.content}
                                    onChange={(e) => setNoteData({ ...noteData, content: e.target.value })}
                                    rows={4}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder={t('enter_note_content') || 'Notizinhalt eingeben'}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">{t('category') || 'Kategorie'}</label>
                                <select
                                    value={noteData.category}
                                    onChange={(e) => setNoteData({ ...noteData, category: e.target.value })}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option value="">{t('select_category') || 'Kategorie wählen'}</option>
                                    <option value="general">{t('general') || 'Allgemein'}</option>
                                    <option value="important">{t('important') || 'Wichtig'}</option>
                                    <option value="follow_up">{t('follow_up') || 'Nachfassen'}</option>
                                    <option value="complaint">{t('complaint') || 'Beschwerde'}</option>
                                </select>
                            </div>
                            <div className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={noteData.is_important}
                                    onChange={(e) => setNoteData({ ...noteData, is_important: e.target.checked })}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label className="ml-2 block text-sm text-gray-900">
                                    {t('mark_as_important') || 'Als wichtig markieren'}
                                </label>
                            </div>
                        </div>
                        <div className="mt-6 flex space-x-3">
                            <button
                                onClick={handleAddNote}
                                disabled={!noteData.content}
                                className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {t('save_note') || 'Notiz speichern'}
                            </button>
                            <button
                                onClick={() => setShowNoteModal(false)}
                                className="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
                            >
                                {t('cancel') || 'Abbrechen'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

// Customer Appointments Component
const CustomerAppointments = ({ customerId, api, t, useState, useEffect }) => {
    const [appointments, setAppointments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showCreateModal, setShowCreateModal] = useState(false);
    
    useEffect(() => {
        fetchAppointments();
    }, [customerId]);
    
    const fetchAppointments = async () => {
        try {
            const response = await api.get(`/customers/${customerId}/appointments`);
            setAppointments(response.data.data || []);
        } catch (error) {
            console.error('Error fetching appointments:', error);
        } finally {
            setLoading(false);
        }
    };
    
    const handleCancelAppointment = async (appointmentId) => {
        if (!confirm(t('confirm_cancel_appointment') || 'Möchten Sie diesen Termin wirklich stornieren?')) {
            return;
        }
        
        try {
            await api.post(`/appointments/${appointmentId}/cancel`);
            fetchAppointments();
            alert(t('appointment_cancelled') || 'Termin wurde storniert');
        } catch (error) {
            console.error('Error cancelling appointment:', error);
            alert(t('error_cancelling_appointment') || 'Fehler beim Stornieren des Termins');
        }
    };
    
    const getStatusBadge = (status) => {
        const statusConfig = {
            scheduled: { color: 'bg-blue-100 text-blue-800', label: t('scheduled') || 'Geplant' },
            confirmed: { color: 'bg-green-100 text-green-800', label: t('confirmed') || 'Bestätigt' },
            completed: { color: 'bg-gray-100 text-gray-800', label: t('completed') || 'Abgeschlossen' },
            cancelled: { color: 'bg-red-100 text-red-800', label: t('cancelled') || 'Storniert' },
            no_show: { color: 'bg-orange-100 text-orange-800', label: t('no_show') || 'Nicht erschienen' }
        };
        
        const config = statusConfig[status] || statusConfig.scheduled;
        return <span className={`px-2 py-1 text-xs font-medium rounded-full ${config.color}`}>{config.label}</span>;
    };
    
    if (loading) {
        return (
            <div className="flex items-center justify-center h-32">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        );
    }
    
    return (
        <div>
            <div className="mb-4 flex justify-between items-center">
                <h3 className="text-lg font-medium text-gray-900">{t('customer_appointments') || 'Kundentermine'}</h3>
                <button
                    onClick={() => setShowCreateModal(true)}
                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"
                >
                    {t('create_appointment') || 'Termin erstellen'}
                </button>
            </div>
            
            {appointments.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                    {t('no_appointments') || 'Keine Termine vorhanden'}
                </div>
            ) : (
                <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-300">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('date_time') || 'Datum & Zeit'}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('service') || 'Leistung'}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('staff') || 'Mitarbeiter'}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('status') || 'Status'}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('actions') || 'Aktionen'}
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {appointments.map((appointment) => (
                                <tr key={appointment.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {new Date(appointment.start_time).toLocaleString('de-DE')}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {appointment.service?.name || '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {appointment.staff?.name || '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {getStatusBadge(appointment.status)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        {appointment.status === 'scheduled' && (
                                            <button
                                                onClick={() => handleCancelAppointment(appointment.id)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                {t('cancel') || 'Stornieren'}
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
};

// Customer Calls Component
const CustomerCalls = ({ customerId, api, t, useState, useEffect }) => {
    const [calls, setCalls] = useState([]);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        fetchCalls();
    }, [customerId]);
    
    const fetchCalls = async () => {
        try {
            const response = await api.get(`/customers/${customerId}/calls`);
            setCalls(response.data.data || []);
        } catch (error) {
            console.error('Error fetching calls:', error);
        } finally {
            setLoading(false);
        }
    };
    
    const formatDuration = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };
    
    if (loading) {
        return (
            <div className="flex items-center justify-center h-32">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        );
    }
    
    return (
        <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">{t('customer_calls') || 'Kundenanrufe'}</h3>
            
            {calls.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                    {t('no_calls') || 'Keine Anrufe vorhanden'}
                </div>
            ) : (
                <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-300">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('date_time') || 'Datum & Zeit'}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('duration') || 'Dauer'}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('type') || 'Typ'}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {t('summary') || 'Zusammenfassung'}
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {calls.map((call) => (
                                <tr key={call.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {new Date(call.created_at).toLocaleString('de-DE')}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {formatDuration(call.duration_sec || 0)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {call.direction === 'inbound' ? (t('incoming') || 'Eingehend') : (t('outgoing') || 'Ausgehend')}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-900">
                                        {call.summary || '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
};

// Customer Notes Component
const CustomerNotes = ({ customerId, api, t, useState, useEffect }) => {
    const [notes, setNotes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showAddNote, setShowAddNote] = useState(false);
    const [newNote, setNewNote] = useState({ content: '', category: 'general', is_important: false });
    
    useEffect(() => {
        fetchNotes();
    }, [customerId]);
    
    const fetchNotes = async () => {
        try {
            const response = await api.get(`/customers/${customerId}/notes`);
            setNotes(response.data.data || []);
        } catch (error) {
            console.error('Error fetching notes:', error);
        } finally {
            setLoading(false);
        }
    };
    
    const handleAddNote = async () => {
        try {
            await api.post(`/customers/${customerId}/notes`, newNote);
            setNewNote({ content: '', category: 'general', is_important: false });
            setShowAddNote(false);
            fetchNotes();
        } catch (error) {
            console.error('Error adding note:', error);
            alert(t('error_adding_note') || 'Fehler beim Hinzufügen der Notiz');
        }
    };
    
    const handleDeleteNote = async (noteId) => {
        if (!confirm(t('confirm_delete_note') || 'Möchten Sie diese Notiz wirklich löschen?')) {
            return;
        }
        
        try {
            await api.delete(`/notes/${noteId}`);
            fetchNotes();
        } catch (error) {
            console.error('Error deleting note:', error);
            alert(t('error_deleting_note') || 'Fehler beim Löschen der Notiz');
        }
    };
    
    if (loading) {
        return (
            <div className="flex items-center justify-center h-32">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        );
    }
    
    return (
        <div>
            <div className="mb-4 flex justify-between items-center">
                <h3 className="text-lg font-medium text-gray-900">{t('customer_notes') || 'Kundennotizen'}</h3>
                <button
                    onClick={() => setShowAddNote(true)}
                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"
                >
                    {t('add_note') || 'Notiz hinzufügen'}
                </button>
            </div>
            
            {showAddNote && (
                <div className="mb-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <div className="space-y-3">
                        <textarea
                            value={newNote.content}
                            onChange={(e) => setNewNote({ ...newNote, content: e.target.value })}
                            placeholder={t('enter_note_content') || 'Notizinhalt eingeben'}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            rows={3}
                        />
                        <div className="flex items-center space-x-4">
                            <select
                                value={newNote.category}
                                onChange={(e) => setNewNote({ ...newNote, category: e.target.value })}
                                className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="general">{t('general') || 'Allgemein'}</option>
                                <option value="important">{t('important') || 'Wichtig'}</option>
                                <option value="follow_up">{t('follow_up') || 'Nachfassen'}</option>
                                <option value="complaint">{t('complaint') || 'Beschwerde'}</option>
                            </select>
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={newNote.is_important}
                                    onChange={(e) => setNewNote({ ...newNote, is_important: e.target.checked })}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <span className="ml-2 text-sm text-gray-700">{t('mark_as_important') || 'Als wichtig markieren'}</span>
                            </label>
                        </div>
                        <div className="flex space-x-2">
                            <button
                                onClick={handleAddNote}
                                disabled={!newNote.content}
                                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                            >
                                {t('save') || 'Speichern'}
                            </button>
                            <button
                                onClick={() => {
                                    setShowAddNote(false);
                                    setNewNote({ content: '', category: 'general', is_important: false });
                                }}
                                className="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 text-sm"
                            >
                                {t('cancel') || 'Abbrechen'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
            
            {notes.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                    {t('no_notes') || 'Keine Notizen vorhanden'}
                </div>
            ) : (
                <div className="space-y-3">
                    {notes.map((note) => (
                        <div
                            key={note.id}
                            className={`p-4 border rounded-lg ${
                                note.is_important ? 'border-orange-300 bg-orange-50' : 'border-gray-200 bg-white'
                            }`}
                        >
                            <div className="flex justify-between items-start">
                                <div className="flex-1">
                                    <div className="flex items-center space-x-2 mb-2">
                                        <span className={`px-2 py-1 text-xs font-medium rounded ${
                                            note.category === 'important' ? 'bg-red-100 text-red-800' :
                                            note.category === 'follow_up' ? 'bg-blue-100 text-blue-800' :
                                            note.category === 'complaint' ? 'bg-orange-100 text-orange-800' :
                                            'bg-gray-100 text-gray-800'
                                        }`}>
                                            {t(note.category) || note.category}
                                        </span>
                                        {note.is_important && (
                                            <svg className="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                        )}
                                    </div>
                                    <p className="text-sm text-gray-900">{note.content}</p>
                                    <p className="text-xs text-gray-500 mt-2">
                                        {note.created_by?.name || t('system') || 'System'} • {new Date(note.created_at).toLocaleString('de-DE')}
                                    </p>
                                </div>
                                <button
                                    onClick={() => handleDeleteNote(note.id)}
                                    className="ml-4 text-gray-400 hover:text-red-600"
                                >
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

// Customer Documents Component
const CustomerDocuments = ({ customerId, api, t, useState, useEffect }) => {
    const [documents, setDocuments] = useState([]);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        fetchDocuments();
    }, [customerId]);
    
    const fetchDocuments = async () => {
        try {
            const response = await api.get(`/customers/${customerId}/documents`);
            setDocuments(response.data.data || []);
        } catch (error) {
            console.error('Error fetching documents:', error);
        } finally {
            setLoading(false);
        }
    };
    
    const handleDownload = (documentId, filename) => {
        window.open(`/api/admin/documents/${documentId}/download`, '_blank');
    };
    
    if (loading) {
        return (
            <div className="flex items-center justify-center h-32">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        );
    }
    
    return (
        <div>
            <div className="mb-4 flex justify-between items-center">
                <h3 className="text-lg font-medium text-gray-900">{t('customer_documents') || 'Kundendokumente'}</h3>
                <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                    {t('upload_document') || 'Dokument hochladen'}
                </button>
            </div>
            
            {documents.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                    {t('no_documents') || 'Keine Dokumente vorhanden'}
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-4">
                    {documents.map((doc) => (
                        <div key={doc.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div className="flex items-center space-x-3">
                                <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <p className="text-sm font-medium text-gray-900">{doc.filename}</p>
                                    <p className="text-xs text-gray-500">
                                        {doc.type} • {(doc.size / 1024).toFixed(1)} KB • {new Date(doc.created_at).toLocaleDateString('de-DE')}
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={() => handleDownload(doc.id, doc.filename)}
                                className="text-blue-600 hover:text-blue-800"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};