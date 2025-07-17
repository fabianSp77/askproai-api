import React, { useState, useCallback } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import Color from '@tiptap/extension-color';
import TextStyle from '@tiptap/extension-text-style';
import Placeholder from '@tiptap/extension-placeholder';
import { 
  Bold, 
  Italic, 
  Underline as UnderlineIcon,
  Link as LinkIcon,
  List,
  ListOrdered,
  AlignLeft,
  AlignCenter,
  AlignRight,
  Image as ImageIcon,
  Send,
  Eye,
  X,
  Paperclip,
  ChevronDown,
  Check
} from 'lucide-react';

const EmailComposer = ({ 
  isOpen, 
  onClose, 
  callData, 
  onSend,
  defaultRecipients = [],
  defaultSubject = ''
}) => {
  const [recipients, setRecipients] = useState(defaultRecipients.join(', '));
  const [subject, setSubject] = useState(defaultSubject || `Anrufzusammenfassung - ${callData?.customer_name || 'Unbekannt'} - ${new Date(callData?.created_at).toLocaleDateString('de-DE')}`);
  const [includeOptions, setIncludeOptions] = useState({
    summary: true,
    transcript: true,
    customerInfo: true,
    appointmentInfo: true,
    actionItems: true,
    attachCSV: false,
    attachRecording: false
  });
  const [showPreview, setShowPreview] = useState(false);
  const [sending, setSending] = useState(false);
  const [templates, setTemplates] = useState('default');

  // TipTap Editor Configuration
  const editor = useEditor({
    extensions: [
      StarterKit,
      Underline,
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      Link.configure({
        openOnClick: false,
        HTMLAttributes: {
          class: 'text-blue-600 underline'
        }
      }),
      Image,
      Color,
      TextStyle,
      Placeholder.configure({
        placeholder: 'Schreiben Sie hier Ihre Nachricht...'
      })
    ],
    content: `
      <p>Sehr geehrte Damen und Herren,</p>
      <p><br/></p>
      <p>anbei finden Sie die Zusammenfassung unseres Telefonats vom ${new Date(callData?.created_at).toLocaleDateString('de-DE', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })} Uhr.</p>
      <p><br/></p>
      <p>Mit freundlichen Grüßen<br/>Ihr AskProAI Team</p>
    `,
    editorProps: {
      attributes: {
        class: 'prose prose-sm max-w-none focus:outline-none min-h-[200px] p-4'
      }
    }
  });

  const handleIncludeToggle = (option) => {
    setIncludeOptions(prev => ({
      ...prev,
      [option]: !prev[option]
    }));
  };

  const MenuBar = () => {
    if (!editor) return null;

    return (
      <div className="border-b border-gray-200 p-2 flex flex-wrap gap-1">
        <div className="flex items-center gap-1 mr-2">
          <button
            onClick={() => editor.chain().focus().toggleBold().run()}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive('bold') ? 'bg-gray-200' : ''}`}
            title="Fett"
          >
            <Bold className="w-4 h-4" />
          </button>
          <button
            onClick={() => editor.chain().focus().toggleItalic().run()}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive('italic') ? 'bg-gray-200' : ''}`}
            title="Kursiv"
          >
            <Italic className="w-4 h-4" />
          </button>
          <button
            onClick={() => editor.chain().focus().toggleUnderline().run()}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive('underline') ? 'bg-gray-200' : ''}`}
            title="Unterstrichen"
          >
            <UnderlineIcon className="w-4 h-4" />
          </button>
        </div>

        <div className="flex items-center gap-1 mr-2">
          <button
            onClick={() => editor.chain().focus().toggleBulletList().run()}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive('bulletList') ? 'bg-gray-200' : ''}`}
            title="Aufzählung"
          >
            <List className="w-4 h-4" />
          </button>
          <button
            onClick={() => editor.chain().focus().toggleOrderedList().run()}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive('orderedList') ? 'bg-gray-200' : ''}`}
            title="Nummerierte Liste"
          >
            <ListOrdered className="w-4 h-4" />
          </button>
        </div>

        <div className="flex items-center gap-1 mr-2">
          <button
            onClick={() => editor.chain().focus().setTextAlign('left').run()}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive({ textAlign: 'left' }) ? 'bg-gray-200' : ''}`}
            title="Linksbündig"
          >
            <AlignLeft className="w-4 h-4" />
          </button>
          <button
            onClick={() => editor.chain().focus().setTextAlign('center').run()}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive({ textAlign: 'center' }) ? 'bg-gray-200' : ''}`}
            title="Zentriert"
          >
            <AlignCenter className="w-4 h-4" />
          </button>
          <button
            onClick={() => editor.chain().focus().setTextAlign('right').run()}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive({ textAlign: 'right' }) ? 'bg-gray-200' : ''}`}
            title="Rechtsbündig"
          >
            <AlignRight className="w-4 h-4" />
          </button>
        </div>

        <div className="flex items-center gap-1">
          <button
            onClick={() => {
              const url = window.prompt('URL eingeben:');
              if (url) {
                editor.chain().focus().setLink({ href: url }).run();
              }
            }}
            className={`p-2 rounded hover:bg-gray-100 ${editor.isActive('link') ? 'bg-gray-200' : ''}`}
            title="Link einfügen"
          >
            <LinkIcon className="w-4 h-4" />
          </button>
        </div>
      </div>
    );
  };

  const handleSend = async () => {
    if (!recipients || !subject) {
      alert('Bitte geben Sie Empfänger und Betreff an.');
      return;
    }

    setSending(true);
    try {
      await onSend({
        recipients: recipients.split(',').map(r => r.trim()),
        subject,
        htmlContent: editor.getHTML(),
        includeOptions,
        callId: callData.id
      });
      onClose();
    } catch (error) {
      // Error is already handled by alert
      alert('Fehler beim Senden der E-Mail. Bitte versuchen Sie es erneut.');
    } finally {
      setSending(false);
    }
  };

  const generatePreview = () => {
    let previewHtml = `
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #1e293b;">Anrufzusammenfassung</h2>
    `;

    if (includeOptions.customerInfo && callData.customer_name) {
      previewHtml += `
        <div style="background: #f8fafc; padding: 15px; margin: 10px 0; border-radius: 8px;">
          <h3 style="margin-top: 0;">Kundeninformationen</h3>
          <p><strong>Name:</strong> ${callData.customer_name || 'Unbekannt'}</p>
          <p><strong>Telefon:</strong> ${callData.from_number}</p>
          ${callData.extracted_email ? `<p><strong>E-Mail:</strong> ${callData.extracted_email}</p>` : ''}
        </div>
      `;
    }

    if (includeOptions.summary && callData.summary) {
      previewHtml += `
        <div style="background: #f8fafc; padding: 15px; margin: 10px 0; border-radius: 8px;">
          <h3 style="margin-top: 0;">Zusammenfassung</h3>
          <p>${callData.summary}</p>
        </div>
      `;
    }

    if (includeOptions.appointmentInfo && callData.appointment_requested) {
      previewHtml += `
        <div style="background: #fef3c7; padding: 15px; margin: 10px 0; border-radius: 8px;">
          <h3 style="margin-top: 0;">Terminanfrage</h3>
          <p><strong>Datum:</strong> ${callData.datum_termin || 'Nicht angegeben'}</p>
          <p><strong>Uhrzeit:</strong> ${callData.uhrzeit_termin || 'Nicht angegeben'}</p>
          <p><strong>Dienstleistung:</strong> ${callData.dienstleistung || 'Nicht angegeben'}</p>
        </div>
      `;
    }

    previewHtml += `
      <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
        ${editor.getHTML()}
      </div>
    </div>
    `;

    return previewHtml;
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b">
          <h2 className="text-xl font-semibold">E-Mail verfassen</h2>
          <button 
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-lg"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="flex flex-1 overflow-hidden">
          {/* Main Content */}
          <div className="flex-1 flex flex-col overflow-y-auto">
            {/* Recipients & Subject */}
            <div className="p-4 space-y-3 border-b">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  An
                </label>
                <input
                  type="text"
                  value={recipients}
                  onChange={(e) => setRecipients(e.target.value)}
                  placeholder="email@beispiel.de, weitere@beispiel.de"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Betreff
                </label>
                <input
                  type="text"
                  value={subject}
                  onChange={(e) => setSubject(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            {/* Editor */}
            <div className="flex-1 bg-white">
              <MenuBar />
              <EditorContent editor={editor} />
            </div>
          </div>

          {/* Sidebar */}
          <div className="w-80 border-l bg-gray-50 p-4 overflow-y-auto">
            <h3 className="font-medium text-gray-900 mb-3">Inhalte einschließen</h3>
            <div className="space-y-2">
              {Object.entries({
                summary: 'Zusammenfassung',
                transcript: 'Transkript',
                customerInfo: 'Kundeninformationen',
                appointmentInfo: 'Termininformationen',
                actionItems: 'Handlungsempfehlungen',
                attachCSV: 'CSV-Export anhängen',
                attachRecording: 'Aufnahme anhängen'
              }).map(([key, label]) => (
                <label key={key} className="flex items-center space-x-2 p-2 hover:bg-gray-100 rounded cursor-pointer">
                  <input
                    type="checkbox"
                    checked={includeOptions[key]}
                    onChange={() => handleIncludeToggle(key)}
                    className="rounded text-blue-600 focus:ring-blue-500"
                  />
                  <span className="text-sm">{label}</span>
                </label>
              ))}
            </div>

            <div className="mt-6">
              <h3 className="font-medium text-gray-900 mb-3">Vorlagen</h3>
              <select 
                value={templates}
                onChange={(e) => setTemplates(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="default">Standard</option>
                <option value="formal">Formell</option>
                <option value="followup">Nachfassen</option>
                <option value="appointment">Terminbestätigung</option>
              </select>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between p-4 border-t bg-gray-50">
          <button
            onClick={() => setShowPreview(!showPreview)}
            className="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-lg"
          >
            <Eye className="w-4 h-4" />
            Vorschau
          </button>
          
          <div className="flex gap-2">
            <button
              onClick={onClose}
              className="px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-lg"
            >
              Abbrechen
            </button>
            <button
              onClick={handleSend}
              disabled={sending || !recipients || !subject}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {sending ? (
                <>Wird gesendet...</>
              ) : (
                <>
                  <Send className="w-4 h-4" />
                  Senden
                </>
              )}
            </button>
          </div>
        </div>

        {/* Preview Modal */}
        {showPreview && (
          <div className="absolute inset-0 bg-white z-10 flex flex-col">
            <div className="flex items-center justify-between p-4 border-b">
              <h3 className="text-lg font-semibold">E-Mail Vorschau</h3>
              <button 
                onClick={() => setShowPreview(false)}
                className="p-2 hover:bg-gray-100 rounded-lg"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            <div className="flex-1 overflow-auto p-4">
              <div className="max-w-2xl mx-auto">
                <div className="mb-4 p-4 bg-gray-100 rounded">
                  <p className="text-sm"><strong>An:</strong> {recipients}</p>
                  <p className="text-sm"><strong>Betreff:</strong> {subject}</p>
                </div>
                <div 
                  className="prose max-w-none"
                  dangerouslySetInnerHTML={{ __html: generatePreview() }}
                />
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default EmailComposer;