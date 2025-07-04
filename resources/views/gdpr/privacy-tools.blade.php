<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Privacy Tools</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #7f8c8d;
        }
        
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .icon {
            width: 24px;
            height: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .button {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .button-primary {
            background-color: #3498db;
            color: white;
        }
        
        .button-primary:hover {
            background-color: #2980b9;
        }
        
        .button-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .button-danger:hover {
            background-color: #c0392b;
        }
        
        .info-box {
            background-color: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-box h3 {
            color: #34495e;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin-left: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .footer {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        
        .footer a {
            color: #3498db;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .lang-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .lang-switcher button {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background-color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .lang-switcher button:hover {
            background-color: #f5f5f5;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }
            
            .header, .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="lang-switcher">
        <button onclick="switchLanguage()">
            <span data-de="English" data-en="Deutsch">Deutsch</span>
        </button>
    </div>
    
    <div class="container">
        <div class="header">
            <h1 data-de="Datenschutz-Tools" data-en="Privacy Tools">Datenschutz-Tools</h1>
            <p data-de="Verwalten Sie Ihre persönlichen Daten gemäß DSGVO" data-en="Manage your personal data according to GDPR">
                Verwalten Sie Ihre persönlichen Daten gemäß DSGVO
            </p>
        </div>
        
        <div id="alerts"></div>
        
        <!-- Data Export Section -->
        <div class="card">
            <h2>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v10m0 0l-3-3m3 3l3-3m-9 8h12a2 2 0 002-2V7a2 2 0 00-2-2h-3"/>
                    <path d="M7 21h10a2 2 0 002-2v-2"/>
                </svg>
                <span data-de="Datenexport anfordern (Art. 15 DSGVO)" data-en="Request Data Export (Art. 15 GDPR)">
                    Datenexport anfordern (Art. 15 DSGVO)
                </span>
            </h2>
            
            <div class="info-box">
                <h3 data-de="Was ist ein Datenexport?" data-en="What is a data export?">Was ist ein Datenexport?</h3>
                <p data-de="Sie haben das Recht, eine Kopie aller Ihrer bei uns gespeicherten persönlichen Daten zu erhalten. Diese Daten werden Ihnen in einem maschinenlesbaren Format (JSON) zur Verfügung gestellt."
                   data-en="You have the right to receive a copy of all your personal data stored with us. This data will be provided to you in a machine-readable format (JSON).">
                    Sie haben das Recht, eine Kopie aller Ihrer bei uns gespeicherten persönlichen Daten zu erhalten. Diese Daten werden Ihnen in einem maschinenlesbaren Format (JSON) zur Verfügung gestellt.
                </p>
            </div>
            
            <form id="exportForm" onsubmit="requestExport(event)">
                <div class="form-group">
                    <label for="exportEmail" data-de="E-Mail-Adresse" data-en="Email Address">E-Mail-Adresse</label>
                    <input type="email" id="exportEmail" name="email" required placeholder="ihre@email.de">
                </div>
                
                <div class="form-group">
                    <label for="exportPhone" data-de="Telefonnummer" data-en="Phone Number">Telefonnummer</label>
                    <input type="tel" id="exportPhone" name="phone" required placeholder="+49 123 456789">
                </div>
                
                <button type="submit" class="button button-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v10m0 0l-3-3m3 3l3-3"/>
                        <path d="M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3"/>
                    </svg>
                    <span data-de="Datenexport anfordern" data-en="Request Data Export">Datenexport anfordern</span>
                </button>
            </form>
        </div>
        
        <!-- Data Deletion Section -->
        <div class="card">
            <h2>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18m-2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                </svg>
                <span data-de="Datenlöschung beantragen (Art. 17 DSGVO)" data-en="Request Data Deletion (Art. 17 GDPR)">
                    Datenlöschung beantragen (Art. 17 DSGVO)
                </span>
            </h2>
            
            <div class="info-box" style="background-color: #fee; border: 1px solid #fcc;">
                <h3 data-de="⚠️ Wichtiger Hinweis zur Datenlöschung" data-en="⚠️ Important Notice about Data Deletion">
                    ⚠️ Wichtiger Hinweis zur Datenlöschung
                </h3>
                <ul>
                    <li data-de="Die Löschung Ihrer Daten ist unwiderruflich" data-en="The deletion of your data is irreversible">
                        Die Löschung Ihrer Daten ist unwiderruflich
                    </li>
                    <li data-de="Sie können keine Termine mehr einsehen oder verwalten" data-en="You will no longer be able to view or manage appointments">
                        Sie können keine Termine mehr einsehen oder verwalten
                    </li>
                    <li data-de="Gesetzlich vorgeschriebene Daten (z.B. Rechnungen) werden gemäß Aufbewahrungsfristen gespeichert"
                        data-en="Legally required data (e.g., invoices) will be stored according to retention periods">
                        Gesetzlich vorgeschriebene Daten (z.B. Rechnungen) werden gemäß Aufbewahrungsfristen gespeichert
                    </li>
                    <li data-de="Sie erhalten eine E-Mail zur Bestätigung der Löschung" data-en="You will receive an email to confirm the deletion">
                        Sie erhalten eine E-Mail zur Bestätigung der Löschung
                    </li>
                </ul>
            </div>
            
            <form id="deletionForm" onsubmit="requestDeletion(event)">
                <div class="form-group">
                    <label for="deletionEmail" data-de="E-Mail-Adresse" data-en="Email Address">E-Mail-Adresse</label>
                    <input type="email" id="deletionEmail" name="email" required placeholder="ihre@email.de">
                </div>
                
                <div class="form-group">
                    <label for="deletionPhone" data-de="Telefonnummer" data-en="Phone Number">Telefonnummer</label>
                    <input type="tel" id="deletionPhone" name="phone" required placeholder="+49 123 456789">
                </div>
                
                <button type="submit" class="button button-danger">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18m-2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    </svg>
                    <span data-de="Datenlöschung beantragen" data-en="Request Data Deletion">Datenlöschung beantragen</span>
                </button>
            </form>
        </div>
        
        <!-- Additional Information -->
        <div class="card">
            <h2>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4m0-4h.01"/>
                </svg>
                <span data-de="Weitere Informationen" data-en="Additional Information">Weitere Informationen</span>
            </h2>
            
            <p data-de="Für weitere Informationen zu Ihren Datenschutzrechten oder bei Fragen zu unserer Datenverarbeitung:"
               data-en="For more information about your privacy rights or questions about our data processing:">
                Für weitere Informationen zu Ihren Datenschutzrechten oder bei Fragen zu unserer Datenverarbeitung:
            </p>
            
            <ul style="margin: 20px 0; line-height: 2;">
                <li>
                    <a href="/privacy" data-de="Datenschutzerklärung" data-en="Privacy Policy">Datenschutzerklärung</a>
                </li>
                <li>
                    <a href="/cookie-policy" data-de="Cookie-Richtlinie" data-en="Cookie Policy">Cookie-Richtlinie</a>
                </li>
                <li>
                    <a href="/impressum" data-de="Impressum" data-en="Legal Notice">Impressum</a>
                </li>
            </ul>
        </div>
        
        <div class="footer">
            <p data-de="© 2025 {{ config('app.name') }}. Alle Rechte vorbehalten."
               data-en="© 2025 {{ config('app.name') }}. All rights reserved.">
                © 2025 {{ config('app.name') }}. Alle Rechte vorbehalten.
            </p>
        </div>
    </div>
    
    <script>
        // Language switcher
        let currentLang = 'de';
        
        function switchLanguage() {
            currentLang = currentLang === 'de' ? 'en' : 'de';
            updateLanguage();
        }
        
        function updateLanguage() {
            document.querySelectorAll('[data-de][data-en]').forEach(element => {
                element.textContent = element.getAttribute(`data-${currentLang}`);
            });
        }
        
        // Alert handling
        function showAlert(message, type = 'success') {
            const alertsContainer = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alert.style.display = 'block';
            
            alertsContainer.innerHTML = '';
            alertsContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 10000);
        }
        
        // CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Request data export
        async function requestExport(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            try {
                const response = await fetch('/gdpr/request-export', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: form.email.value,
                        phone: form.phone.value
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    showAlert(
                        currentLang === 'de' 
                            ? 'Ihre Anfrage wurde erfolgreich versendet. Sie erhalten in Kürze eine E-Mail mit weiteren Anweisungen.'
                            : 'Your request has been sent successfully. You will receive an email with further instructions shortly.',
                        'success'
                    );
                    form.reset();
                } else {
                    const errorMessage = data.message || (
                        currentLang === 'de'
                            ? 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.'
                            : 'An error occurred. Please try again later.'
                    );
                    showAlert(errorMessage, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert(
                    currentLang === 'de'
                        ? 'Es ist ein Netzwerkfehler aufgetreten. Bitte versuchen Sie es später erneut.'
                        : 'A network error occurred. Please try again later.',
                    'error'
                );
            } finally {
                submitButton.disabled = false;
            }
        }
        
        // Request data deletion
        async function requestDeletion(event) {
            event.preventDefault();
            
            const confirmMessage = currentLang === 'de'
                ? 'Sind Sie sicher, dass Sie die Löschung Ihrer Daten beantragen möchten? Dieser Vorgang kann nicht rückgängig gemacht werden.'
                : 'Are you sure you want to request the deletion of your data? This action cannot be undone.';
                
            if (!confirm(confirmMessage)) {
                return;
            }
            
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            try {
                const response = await fetch('/gdpr/request-deletion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: form.email.value,
                        phone: form.phone.value
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    showAlert(
                        currentLang === 'de'
                            ? 'Ihre Löschanfrage wurde erfolgreich versendet. Bitte bestätigen Sie die Löschung über den Link in der E-Mail, die wir Ihnen gesendet haben.'
                            : 'Your deletion request has been sent successfully. Please confirm the deletion via the link in the email we have sent you.',
                        'success'
                    );
                    form.reset();
                } else {
                    const errorMessage = data.message || (
                        currentLang === 'de'
                            ? 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.'
                            : 'An error occurred. Please try again later.'
                    );
                    showAlert(errorMessage, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert(
                    currentLang === 'de'
                        ? 'Es ist ein Netzwerkfehler aufgetreten. Bitte versuchen Sie es später erneut.'
                        : 'A network error occurred. Please try again later.',
                    'error'
                );
            } finally {
                submitButton.disabled = false;
            }
        }
    </script>
</body>
</html>