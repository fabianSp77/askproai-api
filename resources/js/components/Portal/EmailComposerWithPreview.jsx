import React, { useState } from 'react';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Textarea } from '../ui/textarea';
import { toast } from 'react-toastify';
import { Send, Loader2, Eye, Edit3, MessageSquarePlus, X } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '../ui/dialog';

const EmailComposerWithPreview = ({ call, onClose, csrfToken }) => {
    const [recipients, setRecipients] = useState('');
    const [subject, setSubject] = useState(`Anrufzusammenfassung - ${call.extracted_name || call.from_number}`);
    const [message, setMessage] = useState(''); // Empty by default
    const [showMessageField, setShowMessageField] = useState(false); // Hide message field by default
    const [showPreview, setShowPreview] = useState(false);
    const [previewHtml, setPreviewHtml] = useState('');
    const [loadingPreview, setLoadingPreview] = useState(false);
    
    const [sending, setSending] = useState(false);
    const [includeOptions, setIncludeOptions] = useState({
        summary: true,
        transcript: false,
        customerInfo: true,
        appointmentInfo: true,
        attachCSV: true,
        attachRecording: false
    });

    // Function to get fresh CSRF token
    const getFreshCsrfToken = async () => {
        try {
            const response = await fetch('/business/api/auth-check', {
                credentials: 'include'
            });
            const data = await response.json();
            if (data.csrf_token) {
                return data.csrf_token;
            }
        } catch (error) {
            console.error('Failed to get fresh CSRF token:', error);
        }
        // Fallback to provided token or other sources
        return csrfToken || window.Laravel?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
    };

    const generatePreview = async () => {
        setLoadingPreview(true);
        try {
            // Get fresh CSRF token
            const freshToken = await getFreshCsrfToken();
            
            // Generate preview by calling an API endpoint
            const response = await fetch('/business/api/email/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': freshToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify({
                    call_id: call.id,
                    subject: subject,
                    html_content: message ? `<div>${message.replace(/\n/g, '<br>')}</div>` : null,
                    include_options: includeOptions
                })
            });

            if (!response.ok) {
                throw new Error('Fehler beim Generieren der Vorschau');
            }

            const data = await response.json();
            setPreviewHtml(data.html);
            setShowPreview(true);
        } catch (error) {
            toast.error('Vorschau konnte nicht generiert werden');
        } finally {
            setLoadingPreview(false);
        }
    };

    const handleSend = async () => {
        if (!recipients.trim()) {
            toast.error('Bitte geben Sie mindestens einen Empfänger ein');
            return;
        }

        const recipientList = recipients.split(',').map(r => r.trim()).filter(r => r);
        if (recipientList.length === 0) {
            toast.error('Bitte geben Sie gültige E-Mail-Adressen ein');
            return;
        }

        setSending(true);

        try {
            // Get fresh CSRF token
            const freshToken = await getFreshCsrfToken();
            
            const response = await fetch('/business/api/email/send-direct', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': freshToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify({
                    call_id: call.id,
                    recipients: recipientList,
                    subject: subject,
                    html_content: message ? `<div>${message.replace(/\n/g, '<br>')}</div>` : null,
                    include_options: includeOptions
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Fehler beim Versenden');
            }

            toast.success(data.message || 'E-Mail erfolgreich versendet');
            onClose();
        } catch (error) {
            toast.error(error.message || 'Fehler beim Versenden der E-Mail');
        } finally {
            setSending(false);
        }
    };

    // Get urgency level display
    const getUrgencyDisplay = () => {
        const urgency = call.urgency_level || call.custom_analysis_data?.urgency_level;
        if (!urgency) return null;

        const urgencyLabels = {
            'urgent': 'Dringend',
            'high': 'Hoch',
            'normal': 'Normal',
            'low': 'Niedrig'
        };

        const urgencyColors = {
            'urgent': 'bg-red-100 text-red-800',
            'high': 'bg-orange-100 text-orange-800',
            'normal': 'bg-green-100 text-green-800',
            'low': 'bg-gray-100 text-gray-800'
        };

        const urgencyKey = urgency.toLowerCase();
        return {
            label: urgencyLabels[urgencyKey] || urgency,
            className: urgencyColors[urgencyKey] || 'bg-gray-100 text-gray-800'
        };
    };

    const urgencyInfo = getUrgencyDisplay();

    return (
        <>
            <div className="space-y-6 p-4">
                {/* Call Info Header */}
                <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div className="flex items-center justify-between">
                        <div>
                            <h4 className="font-medium text-gray-900">
                                {call.extracted_name || call.from_number}
                            </h4>
                            <p className="text-sm text-gray-600 mt-1">
                                {new Date(call.created_at).toLocaleDateString('de-DE', {
                                    weekday: 'long',
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}
                            </p>
                        </div>
                        {urgencyInfo && (
                            <span className={`px-3 py-1 rounded-full text-sm font-medium ${urgencyInfo.className}`}>
                                {urgencyInfo.label}
                            </span>
                        )}
                    </div>
                </div>

                {/* Recipients */}
                <div className="space-y-2">
                    <Label htmlFor="recipients">Empfänger *</Label>
                    <Input
                        id="recipients"
                        type="text"
                        value={recipients}
                        onChange={(e) => setRecipients(e.target.value)}
                        placeholder="email@beispiel.de, email2@beispiel.de"
                        disabled={sending}
                        className="w-full"
                    />
                    <p className="text-xs text-gray-500">Mehrere Empfänger mit Komma trennen</p>
                </div>

                {/* Subject */}
                <div className="space-y-2">
                    <Label htmlFor="subject">Betreff</Label>
                    <Input
                        id="subject"
                        type="text"
                        value={subject}
                        onChange={(e) => setSubject(e.target.value)}
                        disabled={sending}
                        className="w-full"
                    />
                </div>

                {/* Message */}
                {!showMessageField ? (
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setShowMessageField(true)}
                            disabled={sending}
                            className="w-full justify-start"
                        >
                            <MessageSquarePlus className="h-4 w-4 mr-2" />
                            Notiz hinzufügen
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="message">
                                Zusätzliche Notiz
                            </Label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    setShowMessageField(false);
                                    setMessage('');
                                }}
                                disabled={sending}
                                className="h-8 w-8 p-0"
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </div>
                        <Textarea
                            id="message"
                            value={message}
                            onChange={(e) => setMessage(e.target.value)}
                            rows={4}
                            disabled={sending}
                            className="w-full resize-none"
                            placeholder="Hier können Sie eine persönliche Nachricht hinzufügen..."
                            autoFocus
                        />
                        <p className="text-xs text-gray-500">
                            Diese Notiz wird am Anfang der E-Mail angezeigt.
                        </p>
                    </div>
                )}

                {/* Include Options */}
                <div className="space-y-2">
                    <Label>Anrufdetails einschließen</Label>
                    <div className="space-y-3 border rounded-lg p-4 bg-gray-50">
                        {Object.entries({
                            summary: 'Zusammenfassung',
                            customerInfo: 'Kundeninformationen',
                            appointmentInfo: 'Termininformationen',
                            transcript: 'Transkript',
                        }).map(([key, label]) => (
                            <label key={key} className="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 p-2 rounded transition-colors">
                                <input
                                    type="checkbox"
                                    checked={includeOptions[key]}
                                    onChange={(e) => 
                                        setIncludeOptions(prev => ({ ...prev, [key]: e.target.checked }))
                                    }
                                    disabled={sending}
                                    className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                />
                                <span className="text-sm select-none">{label}</span>
                            </label>
                        ))}
                    </div>
                </div>

                {/* Attachments */}
                <div className="space-y-2">
                    <Label>Anhänge</Label>
                    <div className="space-y-3 border rounded-lg p-4 bg-gray-50">
                        <label className="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 p-2 rounded transition-colors">
                            <input
                                type="checkbox"
                                checked={includeOptions.attachCSV}
                                onChange={(e) => 
                                    setIncludeOptions(prev => ({ ...prev, attachCSV: e.target.checked }))
                                }
                                disabled={sending}
                                className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                            />
                            <span className="text-sm select-none">CSV-Export anhängen (Kundendaten)</span>
                        </label>
                    </div>
                </div>

                {/* Actions */}
                <div className="flex justify-between gap-3 pt-4 border-t">
                    <Button
                        variant="outline"
                        onClick={generatePreview}
                        disabled={sending || loadingPreview}
                    >
                        {loadingPreview ? (
                            <>
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                Lade...
                            </>
                        ) : (
                            <>
                                <Eye className="h-4 w-4 mr-2" />
                                Vorschau
                            </>
                        )}
                    </Button>
                    
                    <div className="flex gap-3">
                        <Button
                            variant="outline"
                            onClick={onClose}
                            disabled={sending}
                        >
                            Abbrechen
                        </Button>
                        <Button
                            onClick={handleSend}
                            disabled={sending || !recipients.trim()}
                            className="min-w-[120px]"
                        >
                            {sending ? (
                                <>
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    Sende...
                                </>
                            ) : (
                                <>
                                    <Send className="h-4 w-4 mr-2" />
                                    E-Mail senden
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </div>

            {/* Preview Dialog */}
            <Dialog open={showPreview} onOpenChange={setShowPreview}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                    <DialogHeader className="flex-shrink-0">
                        <DialogTitle className="flex items-center justify-between">
                            <span>E-Mail Vorschau</span>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setShowPreview(false)}
                            >
                                <Edit3 className="h-4 w-4 mr-2" />
                                Bearbeiten
                            </Button>
                        </DialogTitle>
                    </DialogHeader>
                    <div className="flex-1 overflow-auto p-4 bg-gray-50 rounded">
                        <div className="bg-white rounded shadow-sm">
                            <div dangerouslySetInnerHTML={{ __html: previewHtml }} />
                        </div>
                    </div>
                    <div className="flex-shrink-0 flex justify-end gap-3 pt-4 border-t">
                        <Button
                            variant="outline"
                            onClick={() => setShowPreview(false)}
                        >
                            Zurück
                        </Button>
                        <Button
                            onClick={handleSend}
                            disabled={sending || !recipients.trim()}
                        >
                            {sending ? (
                                <>
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    Sende...
                                </>
                            ) : (
                                <>
                                    <Send className="h-4 w-4 mr-2" />
                                    E-Mail senden
                                </>
                            )}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
};

export default EmailComposerWithPreview;