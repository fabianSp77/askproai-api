import { test, expect } from '@playwright/test';
import { loginAsUser, waitForLoadingToFinish } from './helpers/auth';
import { createTestData, cleanupTestData } from './helpers/testData';

test.describe('Appointment Booking Workflow', () => {
    let testData;

    test.beforeEach(async ({ page }) => {
        // Create test data
        testData = await createTestData({
            customers: 2,
            services: 3,
            staff: 2,
            branches: 1
        });

        // Login as admin
        await loginAsUser(page, 'admin@example.com', 'password');
    });

    test.afterEach(async () => {
        await cleanupTestData(testData);
    });

    test('should complete full appointment booking flow', async ({ page }) => {
        // Step 1: Navigate to appointments
        await page.goto('/appointments');
        await waitForLoadingToFinish(page);

        // Step 2: Click create appointment
        await page.click('button:has-text("New Appointment")');
        await expect(page.locator('.modal-title')).toContainText('Create Appointment');

        // Step 3: Select customer
        await page.click('[data-testid="customer-select"]');
        await page.fill('input[placeholder="Search customers..."]', testData.customers[0].name);
        await page.click(`text=${testData.customers[0].name}`);

        // Step 4: Select service
        await page.click('[data-testid="service-select"]');
        await page.click(`text=${testData.services[0].name}`);

        // Step 5: Select staff
        await page.click('[data-testid="staff-select"]');
        await page.click(`text=${testData.staff[0].name}`);

        // Step 6: Select date and time
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dateString = tomorrow.toISOString().split('T')[0];
        
        await page.fill('[data-testid="appointment-date"]', dateString);
        await page.fill('[data-testid="appointment-time"]', '10:00');

        // Step 7: Add notes
        await page.fill('[data-testid="appointment-notes"]', 'Test appointment created via E2E test');

        // Step 8: Submit form
        await page.click('button:has-text("Create Appointment")');

        // Step 9: Verify success message
        await expect(page.locator('.toast-success')).toContainText('Appointment created successfully');

        // Step 10: Verify appointment appears in list
        await waitForLoadingToFinish(page);
        await expect(page.locator('table tbody tr').first()).toContainText(testData.customers[0].name);
        await expect(page.locator('table tbody tr').first()).toContainText(testData.services[0].name);
        await expect(page.locator('table tbody tr').first()).toContainText('10:00');
    });

    test('should handle appointment conflicts', async ({ page }) => {
        // Create existing appointment
        const existingAppointment = await createTestData.appointment({
            customer_id: testData.customers[0].id,
            staff_id: testData.staff[0].id,
            service_id: testData.services[0].id,
            starts_at: '2025-08-15 10:00:00'
        });

        await page.goto('/appointments/new');
        await waitForLoadingToFinish(page);

        // Try to book conflicting appointment
        await page.click('[data-testid="customer-select"]');
        await page.click(`text=${testData.customers[1].name}`);

        await page.click('[data-testid="service-select"]');
        await page.click(`text=${testData.services[0].name}`);

        await page.click('[data-testid="staff-select"]');
        await page.click(`text=${testData.staff[0].name}`);

        await page.fill('[data-testid="appointment-date"]', '2025-08-15');
        await page.fill('[data-testid="appointment-time"]', '10:00');

        await page.click('button:has-text("Create Appointment")');

        // Should show conflict error
        await expect(page.locator('.toast-error')).toContainText('Time slot not available');
    });

    test('should reschedule appointment', async ({ page }) => {
        // Create appointment to reschedule
        const appointment = await createTestData.appointment({
            customer_id: testData.customers[0].id,
            staff_id: testData.staff[0].id,
            service_id: testData.services[0].id,
            starts_at: '2025-08-15 10:00:00'
        });

        await page.goto('/appointments');
        await waitForLoadingToFinish(page);

        // Find appointment and click reschedule
        await page.click(`[data-appointment-id="${appointment.id}"] button[aria-label="More actions"]`);
        await page.click('text=Reschedule');

        // Select new time
        await page.fill('[data-testid="new-date"]', '2025-08-16');
        await page.fill('[data-testid="new-time"]', '14:00');

        await page.click('button:has-text("Reschedule")');

        // Verify success
        await expect(page.locator('.toast-success')).toContainText('Appointment rescheduled successfully');

        // Verify new time in list
        await waitForLoadingToFinish(page);
        await expect(page.locator(`[data-appointment-id="${appointment.id}"]`)).toContainText('14:00');
    });

    test('should cancel appointment with reason', async ({ page }) => {
        const appointment = await createTestData.appointment({
            customer_id: testData.customers[0].id,
            staff_id: testData.staff[0].id,
            service_id: testData.services[0].id,
            starts_at: '2025-08-15 10:00:00'
        });

        await page.goto('/appointments');
        await waitForLoadingToFinish(page);

        // Cancel appointment
        await page.click(`[data-appointment-id="${appointment.id}"] button[aria-label="More actions"]`);
        await page.click('text=Cancel');

        // Provide cancellation reason
        await page.fill('[data-testid="cancellation-reason"]', 'Customer requested cancellation');
        await page.click('button:has-text("Confirm Cancellation")');

        // Verify success
        await expect(page.locator('.toast-success')).toContainText('Appointment cancelled');

        // Verify status change
        await expect(page.locator(`[data-appointment-id="${appointment.id}"]`)).toContainText('Cancelled');
    });

    test('should book appointment from customer detail page', async ({ page }) => {
        await page.goto(`/customers/${testData.customers[0].id}`);
        await waitForLoadingToFinish(page);

        // Click book appointment
        await page.click('button:has-text("Book Appointment")');

        // Customer should be pre-selected
        await expect(page.locator('[data-testid="customer-select"]')).toContainText(testData.customers[0].name);

        // Complete booking
        await page.click('[data-testid="service-select"]');
        await page.click(`text=${testData.services[0].name}`);

        await page.click('[data-testid="staff-select"]');
        await page.click(`text=${testData.staff[0].name}`);

        await page.fill('[data-testid="appointment-date"]', '2025-08-20');
        await page.fill('[data-testid="appointment-time"]', '15:00');

        await page.click('button:has-text("Create Appointment")');

        // Should redirect back to customer page
        await expect(page).toHaveURL(new RegExp(`/customers/${testData.customers[0].id}`));
        
        // Should show new appointment in customer history
        await expect(page.locator('.appointment-history')).toContainText('Aug 20, 2025');
        await expect(page.locator('.appointment-history')).toContainText('15:00');
    });

    test('should handle availability checking', async ({ page }) => {
        await page.goto('/appointments/new');
        await waitForLoadingToFinish(page);

        // Select service and staff first
        await page.click('[data-testid="service-select"]');
        await page.click(`text=${testData.services[0].name}`);

        await page.click('[data-testid="staff-select"]');
        await page.click(`text=${testData.staff[0].name}`);

        // Select date
        await page.fill('[data-testid="appointment-date"]', '2025-08-15');

        // Click check availability
        await page.click('button:has-text("Check Availability")');

        // Should show available time slots
        await expect(page.locator('.availability-grid')).toBeVisible();
        await expect(page.locator('.time-slot.available')).toHaveCount(16); // 8 hours * 2 slots per hour

        // Click on available slot
        await page.click('.time-slot.available:has-text("10:00")');

        // Time should be auto-filled
        await expect(page.locator('[data-testid="appointment-time"]')).toHaveValue('10:00');
    });

    test('should send appointment confirmation email', async ({ page, request }) => {
        // Mock email sending
        await page.route('**/api/appointments', async (route) => {
            const response = await route.fetch();
            const json = await response.json();
            
            // Add email sent flag
            json.data.email_sent = true;
            
            await route.fulfill({
                response,
                json
            });
        });

        await page.goto('/appointments/new');
        await waitForLoadingToFinish(page);

        // Create appointment with email confirmation
        await page.click('[data-testid="customer-select"]');
        await page.click(`text=${testData.customers[0].name}`);

        await page.click('[data-testid="service-select"]');
        await page.click(`text=${testData.services[0].name}`);

        await page.click('[data-testid="staff-select"]');
        await page.click(`text=${testData.staff[0].name}`);

        await page.fill('[data-testid="appointment-date"]', '2025-08-15');
        await page.fill('[data-testid="appointment-time"]', '10:00');

        // Check send confirmation email
        await page.check('[data-testid="send-confirmation-email"]');

        await page.click('button:has-text("Create Appointment")');

        // Verify email sent indicator
        await expect(page.locator('.toast-success')).toContainText('Confirmation email sent');
    });

    test('should handle appointment series/recurring appointments', async ({ page }) => {
        await page.goto('/appointments/new');
        await waitForLoadingToFinish(page);

        // Fill basic details
        await page.click('[data-testid="customer-select"]');
        await page.click(`text=${testData.customers[0].name}`);

        await page.click('[data-testid="service-select"]');
        await page.click(`text=${testData.services[0].name}`);

        await page.click('[data-testid="staff-select"]');
        await page.click(`text=${testData.staff[0].name}`);

        await page.fill('[data-testid="appointment-date"]', '2025-08-15');
        await page.fill('[data-testid="appointment-time"]', '10:00');

        // Enable recurring
        await page.check('[data-testid="recurring-appointment"]');

        // Configure recurrence
        await page.selectOption('[data-testid="recurrence-pattern"]', 'weekly');
        await page.fill('[data-testid="recurrence-count"]', '4');

        // Preview series
        await page.click('button:has-text("Preview Series")');
        await expect(page.locator('.series-preview')).toContainText('4 appointments will be created');
        await expect(page.locator('.series-preview')).toContainText('Aug 15, 2025');
        await expect(page.locator('.series-preview')).toContainText('Aug 22, 2025');
        await expect(page.locator('.series-preview')).toContainText('Aug 29, 2025');
        await expect(page.locator('.series-preview')).toContainText('Sep 5, 2025');

        // Create series
        await page.click('button:has-text("Create Series")');

        // Verify success
        await expect(page.locator('.toast-success')).toContainText('4 appointments created successfully');
    });

    test('should validate appointment constraints', async ({ page }) => {
        await page.goto('/appointments/new');
        await waitForLoadingToFinish(page);

        // Try to submit empty form
        await page.click('button:has-text("Create Appointment")');

        // Should show validation errors
        await expect(page.locator('.field-error')).toContainText('Customer is required');
        await expect(page.locator('.field-error')).toContainText('Service is required');
        await expect(page.locator('.field-error')).toContainText('Staff member is required');
        await expect(page.locator('.field-error')).toContainText('Date is required');
        await expect(page.locator('.field-error')).toContainText('Time is required');

        // Try to book in the past
        await page.click('[data-testid="customer-select"]');
        await page.click(`text=${testData.customers[0].name}`);

        await page.click('[data-testid="service-select"]');
        await page.click(`text=${testData.services[0].name}`);

        await page.click('[data-testid="staff-select"]');
        await page.click(`text=${testData.staff[0].name}`);

        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        await page.fill('[data-testid="appointment-date"]', yesterday.toISOString().split('T')[0]);
        await page.fill('[data-testid="appointment-time"]', '10:00');

        await page.click('button:has-text("Create Appointment")');

        // Should show past date error
        await expect(page.locator('.toast-error')).toContainText('Cannot book appointments in the past');
    });
});