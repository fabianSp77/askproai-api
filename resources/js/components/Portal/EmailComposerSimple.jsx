import React, { useState } from 'react';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Textarea } from '../ui/textarea';
// import { Checkbox } from '../ui/checkbox';
import { toast } from 'react-toastify';
import { Send, Loader2 } from 'lucide-react';

const EmailComposerSimple = ({ call, onClose, csrfToken }) => {
    const [recipients, setRecipients] = useState('');
    const [subject, setSubject] = useState(`Anrufzusammenfassung - ${call.extracted_name || call.from_number}`);
    const [message, setMessage] = useState(`Sehr geehrte Damen und Herren,

anbei finden Sie die Zusammenfassung des Telefonats vom ${new Date(call.created_at).toLocaleDateString('de-DE')}.

Bei Rückfragen stehen wir Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen
Ihr ${call.company?.name || 'AskProAI'} Team`);
    
    const [sending, setSending] = useState(false);
    const [includeOptions, setIncludeOptions] = useState({
        summary: true,
        transcript: false,
        customerInfo: true,
        appointmentInfo: true,
        actionItems: true,
        attachCSV: true,
        attachRecording: false
    });

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
            const response = await fetch('/business/api/email/send-direct', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || window.Laravel?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify({
                    call_id: call.id,
                    recipients: recipientList,
                    subject: subject,
                    html_content: `<div>${message.replace(/\n/g, '<br>')}</div>`,
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
            console.error('Email send error:', error);
            toast.error(error.message || 'Fehler beim Versenden der E-Mail');
        } finally {
            setSending(false);
        }
    };

    return (
        <div className="space-y-6 p-4">
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
            <div className="space-y-2">
                <Label htmlFor="message">Nachricht</Label>
                <Textarea
                    id="message"
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    rows={8}
                    disabled={sending}
                    className="w-full resize-none"
                />
            </div>

            {/* Include Options */}
            <div className="space-y-2">
                <Label>Anrufdetails einschließen</Label>
                <div className="space-y-3 border rounded-lg p-4 bg-gray-50">
                    {Object.entries({
                        summary: 'Zusammenfassung',
                        customerInfo: 'Kundeninformationen',
                        appointmentInfo: 'Termininformationen',
                        actionItems: 'Handlungsempfehlungen',
                        transcript: 'Transkript',
                    }).map(([key, label]) => (
                        <label key={key} className="flex items-center space-x-2 cursor-pointer">
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
                    <label className="flex items-center space-x-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={includeOptions.attachCSV}
                            onChange={(e) => 
                                setIncludeOptions(prev => ({ ...prev, attachCSV: e.target.checked }))
                            }
                            disabled={sending}
                            className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                        />
                        <span className="text-sm select-none">CSV-Export anhängen</span>
                    </label>
                    {call.recording_url && (
                        <label className="flex items-center space-x-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={includeOptions.attachRecording}
                                onChange={(e) => 
                                    setIncludeOptions(prev => ({ ...prev, attachRecording: e.target.checked }))
                                }
                                disabled={sending}
                                className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                            />
                            <span className="text-sm select-none">Aufnahme anhängen</span>
                        </label>
                    )}
                </div>
            </div>

            {/* Actions */}
            <div className="flex justify-end gap-3 pt-4 border-t">
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
    );
};

export default EmailComposerSimple;