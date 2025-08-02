import crypto from 'crypto';

export async function mockRetellWebhook(request, data) {
    const webhookData = {
        call_id: `call_${Date.now()}`,
        from_number: data.customer_phone || '+1234567890',
        to_number: '+0987654321',
        status: 'ended',
        duration: data.duration || 180,
        recording_url: 'https://example.com/recording.mp3',
        transcript: data.transcript || generateTranscript(data),
        extracted_data: {
            customer_name: data.customer_name,
            customer_phone: data.customer_phone,
            service_requested: data.service,
            preferred_date: data.date,
            preferred_time: data.time,
            appointment_confirmed: true,
            ...data.extracted_data
        },
        ai_summary: data.ai_summary || generateAISummary(data),
        ...data
    };

    const signature = generateRetellSignature(webhookData);

    const response = await request.post('/api/retell/webhook', {
        data: webhookData,
        headers: {
            'x-retell-signature': signature,
            'Content-Type': 'application/json'
        }
    });

    return {
        response,
        data: webhookData
    };
}

export async function mockCalcomWebhook(request, data) {
    const webhookData = {
        event: data.event || 'BOOKING_CREATED',
        payload: {
            id: `cal_${Date.now()}`,
            uid: data.appointment_id || `apt_${Date.now()}`,
            title: data.title || `${data.service} with ${data.attendee_name}`,
            startTime: data.start_time,
            endTime: data.end_time || addMinutes(data.start_time, 60),
            attendees: [
                {
                    name: data.attendee_name,
                    email: data.attendee_email || 'customer@example.com',
                    phone: data.attendee_phone
                }
            ],
            location: data.location || 'Main Branch',
            status: data.status || 'ACCEPTED',
            ...data.payload
        }
    };

    const signature = generateCalcomSignature(webhookData);

    const response = await request.post('/api/calcom/webhook', {
        data: webhookData,
        headers: {
            'x-cal-signature': signature,
            'Content-Type': 'application/json'
        }
    });

    return {
        response,
        data: webhookData
    };
}

export async function mockStripeWebhook(request, data) {
    const webhookData = {
        type: data.type || 'payment_intent.succeeded',
        data: {
            object: {
                id: `pi_${Date.now()}`,
                amount: data.amount || 5000,
                currency: data.currency || 'usd',
                status: 'succeeded',
                metadata: {
                    appointment_id: data.appointment_id,
                    customer_id: data.customer_id,
                    ...data.metadata
                },
                ...data.object
            }
        },
        created: Math.floor(Date.now() / 1000),
        ...data
    };

    const signature = generateStripeSignature(webhookData);

    const response = await request.post('/api/stripe/webhook', {
        data: webhookData,
        headers: {
            'stripe-signature': signature,
            'Content-Type': 'application/json'
        }
    });

    return {
        response,
        data: webhookData
    };
}

// Helper functions
function generateTranscript(data) {
    return `Customer: Hi, I would like to book a ${data.service} appointment. 
Agent: Of course! When would you like to come in? 
Customer: ${data.date} at ${data.time} would be great. 
Agent: Perfect! May I have your name? 
Customer: ${data.customer_name}. 
Agent: Great! I have you scheduled for ${data.date} at ${data.time} for a ${data.service}.`;
}

function generateAISummary(data) {
    return `Customer ${data.customer_name} called to book a ${data.service} appointment for ${data.date} at ${data.time}. Appointment was successfully scheduled.`;
}

function generateRetellSignature(data) {
    const secret = process.env.RETELL_WEBHOOK_SECRET || 'test_secret';
    const payload = JSON.stringify(data);
    return crypto.createHmac('sha256', secret).update(payload).digest('hex');
}

function generateCalcomSignature(data) {
    const secret = process.env.CALCOM_WEBHOOK_SECRET || 'test_cal_secret';
    const payload = JSON.stringify(data);
    return crypto.createHmac('sha256', secret).update(payload).digest('hex');
}

function generateStripeSignature(data) {
    const secret = process.env.STRIPE_WEBHOOK_SECRET || 'test_stripe_secret';
    const timestamp = Math.floor(Date.now() / 1000);
    const payload = `${timestamp}.${JSON.stringify(data)}`;
    const signature = crypto.createHmac('sha256', secret).update(payload).digest('hex');
    return `t=${timestamp},v1=${signature}`;
}

function addMinutes(dateString, minutes) {
    const date = new Date(dateString);
    date.setMinutes(date.getMinutes() + minutes);
    return date.toISOString();
}

// Webhook validators for testing
export function validateRetellWebhook(signature, payload, secret) {
    const expectedSignature = crypto.createHmac('sha256', secret).update(payload).digest('hex');
    return signature === expectedSignature;
}

export function validateCalcomWebhook(signature, payload, secret) {
    const expectedSignature = crypto.createHmac('sha256', secret).update(payload).digest('hex');
    return signature === expectedSignature;
}

export function validateStripeWebhook(signature, payload, secret) {
    const elements = signature.split(',');
    let timestamp;
    let signatures = {};
    
    for (const element of elements) {
        const [key, value] = element.split('=');
        if (key === 't') {
            timestamp = value;
        } else if (key.startsWith('v')) {
            signatures[key] = value;
        }
    }
    
    const expectedSignature = crypto
        .createHmac('sha256', secret)
        .update(`${timestamp}.${payload}`)
        .digest('hex');
    
    return signatures.v1 === expectedSignature;
}

// Mock webhook event generators
export const webhookEvents = {
    retell: {
        callStarted: (phoneNumber) => ({
            call_id: `call_${Date.now()}`,
            from_number: phoneNumber,
            status: 'started',
            timestamp: new Date().toISOString()
        }),
        
        callEnded: (callId, data) => ({
            call_id: callId,
            status: 'ended',
            duration: data.duration || 180,
            recording_url: data.recording_url,
            transcript: data.transcript,
            extracted_data: data.extracted_data,
            ai_summary: data.ai_summary
        }),
        
        callFailed: (callId, error) => ({
            call_id: callId,
            status: 'failed',
            error: error,
            timestamp: new Date().toISOString()
        })
    },
    
    calcom: {
        bookingCreated: (data) => ({
            event: 'BOOKING_CREATED',
            payload: {
                id: data.id,
                uid: data.uid,
                title: data.title,
                startTime: data.startTime,
                endTime: data.endTime,
                attendees: data.attendees,
                location: data.location,
                status: 'ACCEPTED'
            }
        }),
        
        bookingCancelled: (bookingId, reason) => ({
            event: 'BOOKING_CANCELLED',
            payload: {
                id: bookingId,
                cancellationReason: reason,
                cancelledAt: new Date().toISOString()
            }
        }),
        
        bookingRescheduled: (bookingId, newData) => ({
            event: 'BOOKING_RESCHEDULED',
            payload: {
                id: bookingId,
                previousStartTime: newData.previousStartTime,
                previousEndTime: newData.previousEndTime,
                startTime: newData.startTime,
                endTime: newData.endTime
            }
        })
    },
    
    stripe: {
        paymentSucceeded: (amount, appointmentId) => ({
            type: 'payment_intent.succeeded',
            data: {
                object: {
                    id: `pi_${Date.now()}`,
                    amount: amount,
                    currency: 'usd',
                    status: 'succeeded',
                    metadata: {
                        appointment_id: appointmentId
                    }
                }
            }
        }),
        
        paymentFailed: (amount, appointmentId, error) => ({
            type: 'payment_intent.payment_failed',
            data: {
                object: {
                    id: `pi_${Date.now()}`,
                    amount: amount,
                    currency: 'usd',
                    status: 'failed',
                    last_payment_error: {
                        message: error
                    },
                    metadata: {
                        appointment_id: appointmentId
                    }
                }
            }
        }),
        
        refundCreated: (paymentIntentId, amount) => ({
            type: 'charge.refunded',
            data: {
                object: {
                    id: `ch_${Date.now()}`,
                    payment_intent: paymentIntentId,
                    amount_refunded: amount,
                    currency: 'usd'
                }
            }
        })
    }
};