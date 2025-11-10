<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Backend API Testing - AskPro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .test-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .test-card h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ccc;
        }
        .status-indicator.success { background: #10b981; }
        .status-indicator.error { background: #ef4444; }
        .status-indicator.loading {
            background: #f59e0b;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #555;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .result-box {
            margin-top: 16px;
            padding: 16px;
            border-radius: 6px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            max-height: 400px;
            overflow-y: auto;
        }
        .result-box pre {
            margin: 0;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .result-box.success {
            background: #ecfdf5;
            border-color: #10b981;
        }
        .result-box.error {
            background: #fef2f2;
            border-color: #ef4444;
        }
        .result-box .timestamp {
            font-size: 11px;
            color: #888;
            margin-bottom: 8px;
        }
        .full-width-card {
            grid-column: 1 / -1;
        }
        .flow-steps {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        .flow-step {
            flex-shrink: 0;
            background: #f3f4f6;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        .flow-step.active {
            background: #dbeafe;
            border-color: #3b82f6;
            color: #1e40af;
        }
        .flow-step.completed {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .flow-step.error {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        .info-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Backend API Testing Interface</h1>
            <p>Direkte Tests der Backend-Funktionen ohne Retell-Integration. Alle Anfragen gehen direkt an RetellFunctionCallHandler.php</p>
        </div>

        <div class="test-grid">
            <!-- Check Customer -->
            <div class="test-card">
                <h2>
                    <span class="status-indicator" id="status-check-customer"></span>
                    check_customer
                    <span class="info-badge">GET</span>
                </h2>
                <div class="form-group">
                    <label>Telefonnummer</label>
                    <input type="tel" id="input-phone-check" value="+4915112345678" placeholder="+49...">
                </div>
                <div class="form-group">
                    <label>Call ID (optional)</label>
                    <input type="text" id="input-callid-check" value="test_{{ uniqid() }}" placeholder="call_...">
                </div>
                <button class="btn" onclick="testCheckCustomer()">Kunde prüfen</button>
                <div id="result-check-customer" style="display: none;"></div>
            </div>

            <!-- Extract Booking Variables -->
            <div class="test-card">
                <h2>
                    <span class="status-indicator" id="status-extract"></span>
                    extract_booking_variables
                    <span class="info-badge">POST</span>
                </h2>
                <div class="form-group">
                    <label>Transkript</label>
                    <textarea id="input-transcript" placeholder="Hans Schuster, Herrenhaarschnitt morgen um 10 Uhr">Hans Schuster, Herrenhaarschnitt morgen um 10 Uhr</textarea>
                </div>
                <div class="form-group">
                    <label>Call ID</label>
                    <input type="text" id="input-callid-extract" value="test_{{ uniqid() }}">
                </div>
                <button class="btn" onclick="testExtract()">Variablen extrahieren</button>
                <div id="result-extract" style="display: none;"></div>
            </div>

            <!-- Check Availability -->
            <div class="test-card">
                <h2>
                    <span class="status-indicator" id="status-availability"></span>
                    check_availability_v17
                    <span class="info-badge">POST</span>
                </h2>
                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" id="input-service" value="Herrenhaarschnitt">
                </div>
                <div class="form-group">
                    <label>Datum (relativ)</label>
                    <input type="text" id="input-date" value="morgen" placeholder="morgen, übermorgen, 15.11.">
                </div>
                <div class="form-group">
                    <label>Uhrzeit</label>
                    <input type="text" id="input-time" value="10:00" placeholder="10:00, 14 Uhr">
                </div>
                <div class="form-group">
                    <label>Call ID</label>
                    <input type="text" id="input-callid-avail" value="test_{{ uniqid() }}">
                </div>
                <button class="btn" onclick="testAvailability()">Verfügbarkeit prüfen</button>
                <div id="result-availability" style="display: none;"></div>
            </div>

            <!-- Start Booking -->
            <div class="test-card">
                <h2>
                    <span class="status-indicator" id="status-booking"></span>
                    start_booking
                    <span class="info-badge">POST</span>
                </h2>
                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" id="input-service-book" value="Herrenhaarschnitt" placeholder="z.B. Herrenhaarschnitt">
                </div>
                <div class="form-group">
                    <label>Datum/Zeit (ISO)</label>
                    <input type="text" id="input-datetime-book" value="{{ date('Y-m-d') }} 10:00" placeholder="YYYY-MM-DD HH:MM">
                </div>
                <div class="form-group">
                    <label>Kundenname</label>
                    <input type="text" id="input-customer-name" value="Hans Schuster">
                </div>
                <div class="form-group">
                    <label>Telefon</label>
                    <input type="tel" id="input-customer-phone" value="+4915112345678">
                </div>
                <div class="form-group">
                    <label>Call ID</label>
                    <input type="text" id="input-callid-book" value="test_{{ uniqid() }}">
                </div>
                <button class="btn" onclick="testBooking()">Termin buchen</button>
                <div id="result-booking" style="display: none;"></div>
            </div>
        </div>

        <!-- Complete Flow Test -->
        <div class="test-grid">
            <div class="test-card full-width-card">
                <h2>
                    <span class="status-indicator" id="status-flow"></span>
                    🔄 Kompletter Buchungs-Flow
                    <span class="info-badge">E2E</span>
                </h2>

                <div class="flow-steps" id="flow-steps">
                    <div class="flow-step" data-step="1">1. Kontext laden</div>
                    <div class="flow-step" data-step="2">2. Kunde prüfen</div>
                    <div class="flow-step" data-step="3">3. Variablen extrahieren</div>
                    <div class="flow-step" data-step="4">4. Verfügbarkeit prüfen</div>
                    <div class="flow-step" data-step="5">5. Termin buchen</div>
                </div>

                <div class="form-group">
                    <label>Testdaten (JSON)</label>
                    <textarea id="input-flow-data" rows="8">{
  "phone": "+4915112345678",
  "transcript": "Hans Schuster, Herrenhaarschnitt morgen um 10 Uhr",
  "service": "Herrenhaarschnitt",
  "date": "morgen",
  "time": "10:00",
  "customer_name": "Hans Schuster"
}</textarea>
                </div>
                <button class="btn" onclick="testCompleteFlow()" id="btn-flow">Kompletten Flow testen</button>
                <div id="result-flow" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function setStatus(id, status) {
            const indicator = document.getElementById('status-' + id);
            indicator.className = 'status-indicator ' + status;
        }

        function showResult(id, data, isError = false) {
            const resultDiv = document.getElementById('result-' + id);
            const timestamp = new Date().toLocaleTimeString('de-DE');

            resultDiv.className = 'result-box ' + (isError ? 'error' : 'success');
            resultDiv.innerHTML = `
                <div class="timestamp">⏱️ ${timestamp}</div>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
            resultDiv.style.display = 'block';
        }

        async function makeRequest(endpoint, method = 'POST', data = {}) {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            };

            if (method === 'POST') {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(endpoint, options);
            const result = await response.json();

            return {
                ok: response.ok,
                status: response.status,
                data: result
            };
        }

        async function testCheckCustomer() {
            const phone = document.getElementById('input-phone-check').value;
            const callId = document.getElementById('input-callid-check').value;

            setStatus('check-customer', 'loading');

            try {
                // Call via generic function handler (how Retell calls it)
                const result = await makeRequest('/api/webhooks/retell/function', 'POST', {
                    name: 'check_customer',
                    args: {
                        phone: phone,
                        call_id: callId
                    },
                    call: {
                        call_id: callId
                    }
                });

                setStatus('check-customer', result.ok ? 'success' : 'error');
                showResult('check-customer', result.data, !result.ok);
            } catch (error) {
                setStatus('check-customer', 'error');
                showResult('check-customer', { error: error.message }, true);
            }
        }

        async function testExtract() {
            const transcript = document.getElementById('input-transcript').value;
            const callId = document.getElementById('input-callid-extract').value;

            setStatus('extract', 'loading');

            // Note: extract_booking_variables is a conversation flow node, not a function call
            // This simulates what the flow would do
            showResult('extract', {
                note: 'extract_booking_variables ist ein Flow-Node (extract_dynamic_variables), keine Function',
                simulated_output: {
                    customer_name: 'Hans Schuster',
                    service_name: 'Herrenhaarschnitt',
                    appointment_date: 'morgen',
                    appointment_time: '10 Uhr'
                }
            }, false);
            setStatus('extract', 'success');
        }

        async function testAvailability() {
            const service = document.getElementById('input-service').value;
            const date = document.getElementById('input-date').value;
            const time = document.getElementById('input-time').value;
            const callId = document.getElementById('input-callid-avail').value;

            setStatus('availability', 'loading');

            try {
                // Call via generic function handler (how Retell calls it)
                const result = await makeRequest('/api/webhooks/retell/function', 'POST', {
                    name: 'check_availability',
                    args: {
                        service_name: service,
                        appointment_date: date,
                        appointment_time: time,
                        call_id: callId
                    },
                    call: {
                        call_id: callId
                    }
                });

                setStatus('availability', result.ok ? 'success' : 'error');
                showResult('availability', result.data, !result.ok);
            } catch (error) {
                setStatus('availability', 'error');
                showResult('availability', { error: error.message }, true);
            }
        }

        async function testBooking() {
            const service = document.getElementById('input-service-book').value;
            const datetime = document.getElementById('input-datetime-book').value;
            const customerName = document.getElementById('input-customer-name').value;
            const customerPhone = document.getElementById('input-customer-phone').value;
            const callId = document.getElementById('input-callid-book').value;

            setStatus('booking', 'loading');

            try {
                // Call via generic function handler (how Retell calls it)
                const result = await makeRequest('/api/webhooks/retell/function', 'POST', {
                    name: 'start_booking',
                    args: {
                        service_name: service,
                        datetime: datetime,
                        customer_name: customerName,
                        customer_phone: customerPhone,
                        call_id: callId
                    },
                    call: {
                        call_id: callId
                    }
                });

                setStatus('booking', result.ok ? 'success' : 'error');
                showResult('booking', result.data, !result.ok);
            } catch (error) {
                setStatus('booking', 'error');
                showResult('booking', { error: error.message }, true);
            }
        }

        async function testCompleteFlow() {
            const btn = document.getElementById('btn-flow');
            btn.disabled = true;
            btn.textContent = 'Flow läuft...';

            setStatus('flow', 'loading');

            const testData = JSON.parse(document.getElementById('input-flow-data').value);
            const callId = 'flow_test_' + Date.now();
            const flowResults = [];

            // Reset flow steps
            document.querySelectorAll('.flow-step').forEach(step => {
                step.className = 'flow-step';
            });

            async function runStep(stepNum, name, fn) {
                const stepEl = document.querySelector(`[data-step="${stepNum}"]`);
                stepEl.className = 'flow-step active';

                try {
                    const result = await fn();
                    flowResults.push({ step: name, success: true, data: result });
                    stepEl.className = 'flow-step completed';
                    return result;
                } catch (error) {
                    flowResults.push({ step: name, success: false, error: error.message });
                    stepEl.className = 'flow-step error';
                    throw error;
                }
            }

            try {
                // Step 1: Get Context (via CurrentContextController)
                await runStep(1, 'get_current_context', async () => {
                    const result = await makeRequest('/api/webhooks/retell/current-context', 'POST', { call_id: callId });
                    return result.data;
                });

                // Step 2: Check Customer (via function handler)
                await runStep(2, 'check_customer', async () => {
                    const result = await makeRequest('/api/webhooks/retell/function', 'POST', {
                        name: 'check_customer',
                        args: {
                            phone: testData.phone,
                            call_id: callId
                        },
                        call: {
                            call_id: callId
                        }
                    });
                    return result.data;
                });

                // Step 3: Extract Variables (simulated - is a flow node)
                await runStep(3, 'extract_booking_variables', async () => {
                    return {
                        customer_name: testData.customer_name,
                        service_name: testData.service,
                        appointment_date: testData.date,
                        appointment_time: testData.time,
                        note: 'Simulated - extract_dynamic_variables ist Flow-Node'
                    };
                });

                // Step 4: Check Availability (via function handler)
                let availabilityData;
                await runStep(4, 'check_availability', async () => {
                    const result = await makeRequest('/api/webhooks/retell/function', 'POST', {
                        name: 'check_availability',
                        args: {
                            service_name: testData.service,
                            appointment_date: testData.date,
                            appointment_time: testData.time,
                            call_id: callId
                        },
                        call: {
                            call_id: callId
                        }
                    });
                    availabilityData = result.data;
                    return result.data;
                });

                // Step 5: Book Appointment (via function handler)
                // Use alternative time if original time not available
                await runStep(5, 'start_booking', async () => {
                    let bookingTime;
                    let useAlternative = false;

                    // 🔧 DEBUG: Log availability data structure
                    console.log('🔍 [DEBUG] Availability data for alternative selection:', {
                        available: availabilityData?.data?.available,
                        hasAlternatives: availabilityData?.data?.alternatives?.length > 0,
                        alternativesCount: availabilityData?.data?.alternatives?.length,
                        alternatives: availabilityData?.data?.alternatives
                    });

                    // Check if we need to use an alternative time
                    if (availabilityData?.data?.available === false &&
                        availabilityData?.data?.alternatives?.length > 0) {
                        // Use first alternative
                        useAlternative = true;
                        bookingTime = availabilityData.data.alternatives[0].time;
                        console.log('✅ [DEBUG] Using alternative time:', bookingTime);
                    } else {
                        // Use original time
                        useAlternative = false;
                        const tomorrow = new Date();
                        tomorrow.setDate(tomorrow.getDate() + 1);
                        const dateStr = tomorrow.toISOString().split('T')[0];
                        bookingTime = `${dateStr} ${testData.time}`;
                        console.log('⚠️ [DEBUG] Using original time (fallback):', bookingTime);
                    }

                    // 🔧 DEBUG: Log the exact payload being sent
                    const payload = {
                        name: 'start_booking',
                        args: {
                            service_name: testData.service,
                            datetime: bookingTime,
                            customer_name: testData.customer_name,
                            customer_phone: testData.phone,
                            call_id: callId
                        },
                        call: {
                            call_id: callId
                        }
                    };

                    console.log('🔍 [DEBUG] start_booking payload:', {
                        service_name: payload.args.service_name,
                        datetime: payload.args.datetime,
                        customer_name: payload.args.customer_name,
                        customer_phone: payload.args.customer_phone,
                        call_id: payload.args.call_id,
                        useAlternative: useAlternative
                    });

                    const result = await makeRequest('/api/webhooks/retell/function', 'POST', payload);

                    // 🔧 DEBUG: Log response
                    console.log('🔍 [DEBUG] start_booking response:', {
                        success: result.success,
                        status: result.data?.status,
                        error: result.data?.error,
                        raw: result.data
                    });

                    return result.data;
                });

                setStatus('flow', 'success');
                showResult('flow', {
                    success: true,
                    call_id: callId,
                    steps: flowResults,
                    summary: '✅ Alle 5 Schritte erfolgreich durchgeführt'
                });
            } catch (error) {
                setStatus('flow', 'error');
                showResult('flow', {
                    success: false,
                    call_id: callId,
                    steps: flowResults,
                    error: error.message,
                    summary: '❌ Flow abgebrochen bei Fehler'
                }, true);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Kompletten Flow testen';
            }
        }
    </script>
</body>
</html>
