import { test, expect } from '@playwright/test';
import { loginAsUser, waitForLoadingToFinish } from './helpers/auth';
import { createTestData, cleanupTestData } from './helpers/testData';
import { mockRetellWebhook, mockCalcomWebhook } from './helpers/webhooks';

test.describe('Complete System Integration - Phone to Appointment', () => {
    let testData;
    let webhookEndpoint;

    test.beforeEach(async ({ page }) => {
        // Create test data
        testData = await createTestData({
            company: {
                name: 'Test Salon',
                retell_api_key: 'test_key',
                calcom_api_key: 'test_calcom_key'
            },
            branches: 1,
            staff: 3,
            services: 5,
            customers: 0 // Will create via phone call
        });

        // Setup webhook endpoint
        webhookEndpoint = process.env.TEST_WEBHOOK_ENDPOINT || 'http://localhost:8000';

        // Login as admin
        await loginAsUser(page, 'admin@testsalon.com', 'password');
    });

    test.afterEach(async () => {
        await cleanupTestData(testData);
    });

    test('should handle complete flow from phone call to confirmed appointment', async ({ page, request }) => {
        // Step 1: Simulate incoming phone call via Retell.ai webhook
        const callData = {
            call_id: 'call_test_123',
            from_number: '+1234567890',
            to_number: '+0987654321',
            status: 'ended',
            duration: 180,
            recording_url: 'https://example.com/recording.mp3',
            transcript: 'Customer: Hi, I would like to book a haircut appointment. Agent: Of course! When would you like to come in? Customer: Tomorrow at 2 PM would be great. Agent: Perfect! May I have your name? Customer: John Smith. Agent: And a phone number to confirm? Customer: 123-456-7890. Agent: Great! I have you scheduled for tomorrow at 2 PM for a haircut. We will send you a confirmation.',
            extracted_data: {
                customer_name: 'John Smith',
                customer_phone: '+1234567890',
                service_requested: 'Haircut',
                preferred_date: '2025-08-16',
                preferred_time: '14:00',
                appointment_confirmed: true
            },
            ai_summary: 'Customer John Smith called to book a haircut appointment for tomorrow at 2 PM. Appointment was successfully scheduled.'
        };

        // Send webhook
        const webhookResponse = await request.post(`${webhookEndpoint}/api/retell/webhook`, {
            data: callData,
            headers: {
                'x-retell-signature': 'test_signature' // Would be validated in real scenario
            }
        });

        expect(webhookResponse.ok()).toBeTruthy();

        // Step 2: Verify call record was created
        await page.goto('/calls');
        await waitForLoadingToFinish(page);

        await expect(page.locator('table tbody tr').first()).toContainText('+1234567890');
        await expect(page.locator('table tbody tr').first()).toContainText('3:00'); // Duration
        await expect(page.locator('table tbody tr').first()).toContainText('Ended');

        // Click to view call details
        await page.click('table tbody tr:first-child');
        
        // Verify call details
        await expect(page.locator('h1')).toContainText('Call Details');
        await expect(page.locator('.call-transcript')).toContainText('Hi, I would like to book a haircut');
        await expect(page.locator('.ai-summary')).toContainText('Customer John Smith called to book');
        await expect(page.locator('.extracted-data')).toContainText('John Smith');
        await expect(page.locator('.extracted-data')).toContainText('Haircut');
        await expect(page.locator('.extracted-data')).toContainText('Aug 16, 2025');
        await expect(page.locator('.extracted-data')).toContainText('2:00 PM');

        // Step 3: Verify customer was created
        await page.goto('/customers');
        await waitForLoadingToFinish(page);

        await page.fill('[data-testid="search-input"]', 'John Smith');
        await page.keyboard.press('Enter');
        await waitForLoadingToFinish(page);

        await expect(page.locator('table tbody tr')).toHaveCount(1);
        await expect(page.locator('table tbody tr').first()).toContainText('John Smith');
        await expect(page.locator('table tbody tr').first()).toContainText('+1234567890');

        // Step 4: Verify appointment was created
        await page.goto('/appointments');
        await waitForLoadingToFinish(page);

        // Filter by date
        await page.fill('[data-testid="date-filter"]', '2025-08-16');
        await waitForLoadingToFinish(page);

        await expect(page.locator('table tbody tr').first()).toContainText('John Smith');
        await expect(page.locator('table tbody tr').first()).toContainText('Haircut');
        await expect(page.locator('table tbody tr').first()).toContainText('2:00 PM');
        await expect(page.locator('table tbody tr').first()).toContainText('Scheduled');

        // Get appointment ID for next steps
        const appointmentId = await page.locator('table tbody tr').first().getAttribute('data-appointment-id');

        // Step 5: Verify Cal.com integration
        // Simulate Cal.com webhook confirming the booking
        const calcomWebhook = {
            event: 'BOOKING_CREATED',
            payload: {
                id: 'cal_booking_123',
                uid: appointmentId,
                title: 'Haircut with John Smith',
                startTime: '2025-08-16T14:00:00Z',
                endTime: '2025-08-16T14:30:00Z',
                attendees: [
                    {
                        name: 'John Smith',
                        email: 'john.smith@example.com',
                        phone: '+1234567890'
                    }
                ],
                location: 'Test Salon - Main Branch',
                status: 'ACCEPTED'
            }
        };

        await request.post(`${webhookEndpoint}/api/calcom/webhook`, {
            data: calcomWebhook,
            headers: {
                'x-cal-signature': 'test_cal_signature'
            }
        });

        // Refresh appointment page
        await page.reload();
        await waitForLoadingToFinish(page);

        // Appointment should now show Cal.com sync status
        await expect(page.locator('table tbody tr').first()).toContainText('Synced');
        await expect(page.locator('[data-testid="calcom-icon"]').first()).toBeVisible();

        // Step 6: Send appointment reminder
        await page.click(`[data-appointment-id="${appointmentId}"] button[aria-label="More actions"]`);
        await page.click('text=Send Reminder');

        // Choose reminder method
        await page.check('[data-testid="send-email-reminder"]');
        await page.check('[data-testid="send-sms-reminder"]');
        await page.click('button:has-text("Send Reminders")');

        await expect(page.locator('.toast-success')).toContainText('Reminders sent successfully');

        // Step 7: Complete appointment
        // Fast forward to appointment time (simulated)
        await page.click(`[data-appointment-id="${appointmentId}"] button[aria-label="More actions"]`);
        await page.click('text=Mark as Completed');

        // Add service notes
        await page.fill('[data-testid="service-notes"]', 'Regular haircut, customer was satisfied');
        await page.fill('[data-testid="actual-duration"]', '25');
        await page.click('button:has-text("Complete Appointment")');

        await expect(page.locator('.toast-success')).toContainText('Appointment completed');

        // Verify status change
        await expect(page.locator(`[data-appointment-id="${appointmentId}"]`)).toContainText('Completed');

        // Step 8: Process payment
        await page.click(`[data-appointment-id="${appointmentId}"]`);
        await page.click('button:has-text("Process Payment")');

        await page.selectOption('[data-testid="payment-method"]', 'card');
        await page.fill('[data-testid="payment-amount"]', '50.00');
        await page.click('button:has-text("Process Payment")');

        // Simulate Stripe webhook for successful payment
        await request.post(`${webhookEndpoint}/api/stripe/webhook`, {
            data: {
                type: 'payment_intent.succeeded',
                data: {
                    object: {
                        id: 'pi_test_123',
                        amount: 5000,
                        currency: 'usd',
                        metadata: {
                            appointment_id: appointmentId
                        }
                    }
                }
            }
        });

        await page.reload();
        await expect(page.locator('.payment-status')).toContainText('Paid');
        await expect(page.locator('.payment-amount')).toContainText('$50.00');

        // Step 9: Follow-up call
        // Simulate follow-up call after appointment
        const followUpCall = {
            call_id: 'call_followup_123',
            from_number: '+1234567890',
            status: 'ended',
            duration: 120,
            transcript: 'Agent: Hi John, this is a follow-up call about your haircut appointment today. How was your experience? Customer: It was great, very happy with the service! Agent: Wonderful! Would you like to book your next appointment? Customer: Yes, same time in 4 weeks would be perfect.',
            extracted_data: {
                customer_phone: '+1234567890',
                satisfaction: 'positive',
                rebook_requested: true,
                next_appointment_date: '2025-09-13',
                next_appointment_time: '14:00'
            },
            call_type: 'follow_up',
            related_appointment_id: appointmentId
        };

        await request.post(`${webhookEndpoint}/api/retell/webhook`, {
            data: followUpCall,
            headers: {
                'x-retell-signature': 'test_signature'
            }
        });

        // Verify follow-up call linked to appointment
        await page.goto(`/appointments/${appointmentId}`);
        await page.click('tab:has-text("Activity")');
        
        await expect(page.locator('.activity-item')).toContainText('Follow-up call');
        await expect(page.locator('.activity-item')).toContainText('Customer satisfied');
        await expect(page.locator('.activity-item')).toContainText('Next appointment booked');

        // Step 10: Verify analytics updated
        await page.goto('/analytics');
        await waitForLoadingToFinish(page);

        // Should show updated metrics
        await expect(page.locator('[data-metric="total-calls"]')).toContainText('2'); // Initial + follow-up
        await expect(page.locator('[data-metric="appointments-booked"]')).toContainText('2'); // Initial + rebook
        await expect(page.locator('[data-metric="revenue-today"]')).toContainText('$50.00');
        await expect(page.locator('[data-metric="customer-satisfaction"]')).toContainText('100%');
    });

    test('should handle error scenarios gracefully', async ({ page, request }) => {
        // Scenario 1: Invalid phone number
        const invalidCallData = {
            call_id: 'call_invalid_1',
            from_number: 'invalid_number',
            status: 'ended',
            extracted_data: {
                customer_name: 'Test User',
                service_requested: 'Haircut'
            }
        };

        const response1 = await request.post(`${webhookEndpoint}/api/retell/webhook`, {
            data: invalidCallData
        });

        // Should still accept webhook but handle gracefully
        expect(response1.ok()).toBeTruthy();

        // Check error log
        await page.goto('/system/logs');
        await page.selectOption('[data-testid="log-level"]', 'error');
        await waitForLoadingToFinish(page);
        
        await expect(page.locator('.log-entry')).toContainText('Invalid phone number format');

        // Scenario 2: Double booking attempt
        const existingAppointment = await createTestData.appointment({
            staff_id: testData.staff[0].id,
            service_id: testData.services[0].id,
            starts_at: '2025-08-20 10:00:00'
        });

        const doubleBookingCall = {
            call_id: 'call_double_1',
            from_number: '+9876543210',
            status: 'ended',
            extracted_data: {
                customer_name: 'Jane Doe',
                customer_phone: '+9876543210',
                service_requested: testData.services[0].name,
                preferred_date: '2025-08-20',
                preferred_time: '10:00',
                staff_requested: testData.staff[0].name
            }
        };

        await request.post(`${webhookEndpoint}/api/retell/webhook`, {
            data: doubleBookingCall
        });

        // Check that system handled conflict
        await page.goto('/calls');
        await page.click(`[data-call-id="call_double_1"]`);
        
        await expect(page.locator('.call-status')).toContainText('Requires Attention');
        await expect(page.locator('.conflict-notice')).toContainText('Time slot not available');
        await expect(page.locator('.suggested-times')).toBeVisible();

        // Scenario 3: Service not found
        const unknownServiceCall = {
            call_id: 'call_unknown_1',
            from_number: '+5555555555',
            status: 'ended',
            extracted_data: {
                customer_name: 'Bob Test',
                customer_phone: '+5555555555',
                service_requested: 'Unknown Service XYZ'
            }
        };

        await request.post(`${webhookEndpoint}/api/retell/webhook`, {
            data: unknownServiceCall
        });

        // System should create lead instead of appointment
        await page.goto('/leads');
        await waitForLoadingToFinish(page);
        
        await expect(page.locator('table tbody tr').first()).toContainText('Bob Test');
        await expect(page.locator('table tbody tr').first()).toContainText('Unknown Service XYZ');
        await expect(page.locator('.lead-status')).toContainText('Requires Follow-up');
    });

    test('should maintain data consistency across integrations', async ({ page, request }) => {
        // Create appointment through different channels and verify consistency
        
        // Channel 1: Phone call
        const phoneBooking = await mockRetellWebhook(request, {
            customer_name: 'Alice Brown',
            customer_phone: '+1111111111',
            service: 'Massage',
            date: '2025-08-18',
            time: '11:00'
        });

        // Channel 2: Web portal
        await page.goto('/appointments/new');
        await page.click('[data-testid="customer-select"]');
        await page.click('text=Create New Customer');
        await page.fill('[data-testid="customer-name"]', 'Bob Green');
        await page.fill('[data-testid="customer-phone"]', '+2222222222');
        await page.click('button:has-text("Create")');
        
        await page.click('[data-testid="service-select"]');
        await page.click('text=Facial');
        await page.fill('[data-testid="appointment-date"]', '2025-08-18');
        await page.fill('[data-testid="appointment-time"]', '13:00');
        await page.click('button:has-text("Create Appointment")');

        // Channel 3: Cal.com webhook
        await mockCalcomWebhook(request, {
            attendee_name: 'Carol White',
            attendee_phone: '+3333333333',
            service: 'Consultation',
            start_time: '2025-08-18T15:00:00Z'
        });

        // Verify all appointments appear consistently
        await page.goto('/appointments');
        await page.fill('[data-testid="date-filter"]', '2025-08-18');
        await waitForLoadingToFinish(page);

        // Should show all 3 appointments
        const appointmentRows = page.locator('table tbody tr');
        await expect(appointmentRows).toHaveCount(3);

        // Verify each has consistent data structure
        for (let i = 0; i < 3; i++) {
            const row = appointmentRows.nth(i);
            await expect(row.locator('[data-field="customer"]')).not.toBeEmpty();
            await expect(row.locator('[data-field="service"]')).not.toBeEmpty();
            await expect(row.locator('[data-field="time"]')).not.toBeEmpty();
            await expect(row.locator('[data-field="status"]')).toContainText('Scheduled');
        }

        // Verify in calendar view
        await page.goto('/calendar');
        await page.click('[data-date="2025-08-18"]');
        
        const calendarAppointments = page.locator('.calendar-appointment');
        await expect(calendarAppointments).toHaveCount(3);

        // Verify in reports
        await page.goto('/reports/daily');
        await page.fill('[data-testid="report-date"]', '2025-08-18');
        await page.click('button:has-text("Generate Report")');
        
        await expect(page.locator('.report-summary')).toContainText('Total Appointments: 3');
        await expect(page.locator('.channel-breakdown')).toContainText('Phone: 1');
        await expect(page.locator('.channel-breakdown')).toContainText('Web Portal: 1');
        await expect(page.locator('.channel-breakdown')).toContainText('Cal.com: 1');
    });
});