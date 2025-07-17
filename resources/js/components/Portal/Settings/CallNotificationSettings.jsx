import React, { useState, useEffect } from 'react';
import { 
    Mail, 
    Plus, 
    Trash2,
    CheckCircle,
    AlertCircle 
} from 'lucide-react';

export default function CallNotificationSettings() {
    const [settings, setSettings] = useState({
        send_call_summaries: false,
        call_summary_recipients: [],
        include_transcript_in_summary: false,
        include_csv_export: true,
        summary_email_frequency: 'immediate'
    });
    
    const [userPreferences, setUserPreferences] = useState({
        receive_summaries: false
    });
    
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);
    const [newRecipient, setNewRecipient] = useState('');

    useEffect(() => {
        fetchSettings();
    }, []);

    const fetchSettings = async () => {
        try {
            const response = await fetch('/business/api/settings/call-notifications', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'include'
            });
            
            if (!response.ok) throw new Error('Failed to fetch settings');
            
            const data = await response.json();
            setSettings(data.settings);
            setUserPreferences(data.user_preferences);
        } catch (error) {
            setMessage({ type: 'error', text: 'Fehler beim Laden der Einstellungen' });
        } finally {
            setLoading(false);
        }
    };

    const saveSettings = async () => {
        setSaving(true);
        setMessage(null);
        
        try {
            const response = await fetch('/business/api/settings/call-notifications', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'include',
                body: JSON.stringify(settings)
            });
            
            if (!response.ok) throw new Error('Failed to save settings');
            
            const data = await response.json();
            setMessage({ type: 'success', text: data.message || 'Einstellungen gespeichert' });
            
            // Also save user preferences
            await saveUserPreferences();
        } catch (error) {
            setMessage({ type: 'error', text: 'Fehler beim Speichern der Einstellungen' });
        } finally {
            setSaving(false);
        }
    };

    const saveUserPreferences = async () => {
        try {
            const response = await fetch('/business/api/settings/call-notifications/user', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'include',
                body: JSON.stringify(userPreferences)
            });
            
            if (!response.ok) throw new Error('Failed to save user preferences');
        } catch (error) {
            // Silently handle user preferences save error
        }
    };

    const addRecipient = () => {
        if (newRecipient && validateEmail(newRecipient)) {
            setSettings({
                ...settings,
                call_summary_recipients: [...settings.call_summary_recipients, newRecipient]
            });
            setNewRecipient('');
        }
    };

    const removeRecipient = (index) => {
        setSettings({
            ...settings,
            call_summary_recipients: settings.call_summary_recipients.filter((_, i) => i !== index)
        });
    };

    const validateEmail = (email) => {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    if (loading) {
        return (
            <div className="bg-white shadow rounded-lg p-6">
                <div className="animate-pulse">
                    <div className="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
                    <div className="h-4 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white shadow rounded-lg">
            <div className="p-6">
                <h3 className="text-lg font-medium text-gray-900 flex items-center mb-4">
                    <Mail className="h-5 w-5 text-gray-400 mr-2" />
                    Anruf-Benachrichtigungen
                </h3>

                {message && (
                    <div className={`mb-4 p-4 rounded-md flex items-start ${
                        message.type === 'success' ? 'bg-green-50' : 'bg-red-50'
                    }`}>
                        {message.type === 'success' ? (
                            <CheckCircle className="h-5 w-5 text-green-400 mr-2" />
                        ) : (
                            <AlertCircle className="h-5 w-5 text-red-400 mr-2" />
                        )}
                        <span className={`text-sm ${
                            message.type === 'success' ? 'text-green-800' : 'text-red-800'
                        }`}>
                            {message.text}
                        </span>
                    </div>
                )}

                <div className="space-y-6">
                    {/* Enable Call Summaries */}
                    <div>
                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={settings.send_call_summaries}
                                onChange={(e) => setSettings({ ...settings, send_call_summaries: e.target.checked })}
                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span className="ml-2 text-sm text-gray-900">
                                Anrufzusammenfassungen per E-Mail senden
                            </span>
                        </label>
                    </div>

                    {settings.send_call_summaries && (
                        <>
                            {/* Frequency */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Häufigkeit
                                </label>
                                <select
                                    value={settings.summary_email_frequency}
                                    onChange={(e) => setSettings({ ...settings, summary_email_frequency: e.target.value })}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option value="immediate">Sofort nach jedem Anruf</option>
                                    <option value="hourly">Stündliche Zusammenfassung</option>
                                    <option value="daily">Tägliche Zusammenfassung</option>
                                </select>
                                <p className="mt-1 text-sm text-gray-500">
                                    {settings.summary_email_frequency === 'immediate' && 
                                        'Sie erhalten nach jedem Anruf eine E-Mail'}
                                    {settings.summary_email_frequency === 'hourly' && 
                                        'Sie erhalten stündlich eine Zusammenfassung aller Anrufe'}
                                    {settings.summary_email_frequency === 'daily' && 
                                        'Sie erhalten täglich um 8:00 Uhr eine Zusammenfassung'}
                                </p>
                            </div>

                            {/* Recipients */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Empfänger
                                </label>
                                <div className="space-y-2">
                                    {settings.call_summary_recipients.map((recipient, index) => (
                                        <div key={index} className="flex items-center">
                                            <input
                                                type="email"
                                                value={recipient}
                                                readOnly
                                                className="flex-1 rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => removeRecipient(index)}
                                                className="ml-2 text-red-600 hover:text-red-800"
                                            >
                                                <Trash2 className="h-5 w-5" />
                                            </button>
                                        </div>
                                    ))}
                                    <div className="flex items-center">
                                        <input
                                            type="email"
                                            value={newRecipient}
                                            onChange={(e) => setNewRecipient(e.target.value)}
                                            placeholder="neue-email@beispiel.de"
                                            className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            onKeyPress={(e) => e.key === 'Enter' && addRecipient()}
                                        />
                                        <button
                                            type="button"
                                            onClick={addRecipient}
                                            disabled={!validateEmail(newRecipient)}
                                            className="ml-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <Plus className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Options */}
                            <div className="space-y-3">
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={settings.include_transcript_in_summary}
                                        onChange={(e) => setSettings({ ...settings, include_transcript_in_summary: e.target.checked })}
                                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span className="ml-2 text-sm text-gray-900">
                                        Gesprächsverlauf (Transkript) einschließen
                                    </span>
                                </label>

                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={settings.include_csv_export}
                                        onChange={(e) => setSettings({ ...settings, include_csv_export: e.target.checked })}
                                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span className="ml-2 text-sm text-gray-900">
                                        CSV-Export als Anhang hinzufügen
                                    </span>
                                </label>
                            </div>

                            {/* User Preferences */}
                            <div className="pt-4 border-t border-gray-200">
                                <h4 className="text-sm font-medium text-gray-900 mb-3">
                                    Persönliche Einstellungen
                                </h4>
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={userPreferences.receive_summaries}
                                        onChange={(e) => setUserPreferences({ ...userPreferences, receive_summaries: e.target.checked })}
                                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span className="ml-2 text-sm text-gray-900">
                                        Ich möchte Anrufzusammenfassungen erhalten
                                    </span>
                                </label>
                                <p className="mt-1 ml-6 text-sm text-gray-500">
                                    Diese Einstellung gilt nur für Ihren Account
                                </p>
                            </div>
                        </>
                    )}
                </div>

                <div className="mt-6 flex justify-end">
                    <button
                        type="button"
                        onClick={saveSettings}
                        disabled={saving}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {saving ? 'Wird gespeichert...' : 'Speichern'}
                    </button>
                </div>
            </div>
        </div>
    );
}