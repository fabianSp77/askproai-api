import { test, expect } from '@playwright/test';

/**
 * E2E Test: Complete Appointment Booking Journey
 *
 * Tests the full user flow from initial call to booking confirmation
 * Validates RCA-identified issues are handled correctly
 */
test.describe('Appointment Booking User Journey', () => {

    test.beforeEach(async ({ page }) => {
        // Navigate to test interface
        await page.goto('/test/retell-simulator');

        // Wait for app to be ready
        await expect(page.locator('[data-test="app-status"]')).toHaveText('ready', { timeout: 10000 });
    });

    test('complete booking flow with new customer', async ({ page }) => {
        // Step 1: User requests appointment
        await page.fill('[data-test="user-input"]', 'Ich möchte einen Termin am Mittwoch um 14 Uhr');
        await page.click('[data-test="send-message"]');

        // Assert: Agent confirms availability
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/14:00.*frei/i, { timeout: 5000 });

        // Step 2: User provides personal details
        await page.fill('[data-test="user-input"]', 'Mein Name ist Max Mustermann, Telefon 0151 12345678');
        await page.click('[data-test="send-message"]');

        // Assert: Agent acknowledges
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/Max Mustermann/i);

        // Step 3: User confirms booking
        await page.fill('[data-test="user-input"]', 'Ja, bitte buchen Sie den Termin');
        await page.click('[data-test="send-message"]');

        // Assert: Booking confirmation
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/erfolgreich gebucht/i, { timeout: 10000 });

        // Verify booking appears in appointment list
        await page.goto('/admin/appointments');
        await page.fill('[data-test="search"]', 'Max Mustermann');
        await page.press('[data-test="search"]', 'Enter');

        // Assert: Appointment visible
        const appointmentRow = page.locator('[data-test="appointment-row"]').first();
        await expect(appointmentRow).toContainText('Max Mustermann');
        await expect(appointmentRow).toContainText('14:00');
    });

    test('handles race condition gracefully with alternatives (RCA V85)', async ({ page, context }) => {
        /**
         * RCA Reference: RCA_AVAILABILITY_RACE_CONDITION_2025-10-14.md
         *
         * Test V85 double-check mechanism that prevents race condition errors
         */

        // Step 1: Request time that appears available
        await page.fill('[data-test="user-input"]', 'Morgen um 9:00 Uhr bitte');
        await page.click('[data-test="send-message"]');

        // Assert: Agent confirms availability
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/9:00.*frei/i);

        // Simulate external booking taking the slot during user's thinking time
        // Trigger API call to mark slot as taken
        const apiContext = await context.newPage();
        await apiContext.goto('/test/api/take-slot?time=09:00');
        await apiContext.close();

        // Wait to simulate user thinking (race condition window)
        await page.waitForTimeout(2000);

        // Step 2: User confirms booking
        await page.fill('[data-test="user-input"]', 'Ja, buchen Sie bitte');
        await page.click('[data-test="send-message"]');

        // Assert: V85 double-check detects slot taken, offers alternatives
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/wurde gerade vergeben/i, { timeout: 10000 });

        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/Alternative/i);

        // Verify no error occurred (graceful handling)
        await expect(page.locator('[data-test="error-indicator"]')).not.toBeVisible();
    });

    test('rejects duplicate booking attempts (RCA Prevention)', async ({ page }) => {
        /**
         * RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
         *
         * Prevent duplicate calcom_v2_booking_id in database
         */

        // First booking
        await page.fill('[data-test="user-input"]', 'Übermorgen 10 Uhr');
        await page.click('[data-test="send-message"]');

        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/10:00.*frei/i);

        await page.fill('[data-test="user-input"]', 'Name: Test User, Telefon: 0151 99999999');
        await page.click('[data-test="send-message"]');

        await page.fill('[data-test="user-input"]', 'Ja, buchen');
        await page.click('[data-test="send-message"]');

        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/gebucht/i);

        // Attempt duplicate booking (same time, different call)
        await page.reload();

        await page.fill('[data-test="user-input"]', 'Übermorgen 10 Uhr');
        await page.click('[data-test="send-message"]');

        // Should either show slot taken or handle gracefully
        const response = page.locator('[data-test="agent-response"]').last();
        const responseText = await response.textContent();

        // Either not available or graceful rejection
        expect(responseText).toMatch(/(nicht mehr frei|bereits gebucht|Alternative)/i);
    });

    test('validates customer name before booking', async ({ page }) => {
        /**
         * RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
         *
         * Ensure customer data matches booking attendee
         */

        await page.fill('[data-test="user-input"]', 'Morgen 15 Uhr');
        await page.click('[data-test="send-message"]');

        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/15:00.*frei/i);

        // Provide customer name
        await page.fill('[data-test="user-input"]', 'Hans Schuster, 0151 11111111');
        await page.click('[data-test="send-message"]');

        // Agent should confirm name
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/Hans Schuster/i);

        // Confirm booking
        await page.fill('[data-test="user-input"]', 'Ja');
        await page.click('[data-test="send-message"]');

        // Verify booking details in database contain correct name
        await page.goto('/admin/appointments');
        await page.fill('[data-test="search"]', 'Hans Schuster');
        await page.press('[data-test="search"]', 'Enter');

        const appointment = page.locator('[data-test="appointment-row"]').first();
        await expect(appointment).toContainText('Hans Schuster');
        await expect(appointment).not.toContainText(/Hansi|Sputer/i); // Not wrong name
    });
});

test.describe('Admin Panel Call Review', () => {
    test('displays call metrics and booking details', async ({ page }) => {
        // Login as admin
        await page.goto('/login');
        await page.fill('[name="email"]', 'admin@askproai.de');
        await page.fill('[name="password"]', 'password');
        await page.click('[type="submit"]');

        // Navigate to calls
        await page.goto('/admin/calls');

        // Filter to booking-confirmed calls
        await page.selectOption('[data-test="filter-status"]', 'booking_confirmed');
        await page.click('[data-test="apply-filters"]');

        // Click first call
        await page.click('[data-test="call-row"]');

        // Assert: Call details visible
        await expect(page.locator('[data-test="call-duration"]')).toBeVisible();
        await expect(page.locator('[data-test="booking-details"]')).toBeVisible();
        await expect(page.locator('[data-test="transcript"]')).toBeVisible();

        // Verify metrics present
        const duration = await page.locator('[data-test="call-duration"]').textContent();
        expect(parseInt(duration!)).toBeGreaterThan(0);
    });

    test('shows performance metrics (RCA: 144s → <45s)', async ({ page }) => {
        await page.goto('/admin/dashboard');

        // Check performance dashboard
        const avgBookingTime = page.locator('[data-test="metric-avg-booking-time"]');
        await expect(avgBookingTime).toBeVisible();

        const avgTime = await avgBookingTime.textContent();
        const seconds = parseInt(avgTime!);

        // RCA Target: Average booking time should be < 45s
        expect(seconds).toBeLessThan(45);
    });
});

test.describe('Error Scenario Handling', () => {
    test('handles network timeout gracefully', async ({ page, context }) => {
        // Simulate slow network
        await context.route('**/api/retell/**', route => {
            setTimeout(() => route.continue(), 30000);
        });

        await page.goto('/test/retell-simulator');

        await page.fill('[data-test="user-input"]', 'Termin morgen 14 Uhr');
        await page.click('[data-test="send-message"]');

        // Assert: Timeout handled, user informed
        await expect(page.locator('[data-test="error-message"]'))
            .toContainText(/Verbindungsproblem|technisches Problem/i, { timeout: 35000 });
    });

    test('handles Cal.com API failure with callback offer', async ({ page, context }) => {
        // Mock Cal.com API failure
        await context.route('**/calcom/api/**', route => {
            route.fulfill({ status: 500, body: 'Internal Server Error' });
        });

        await page.goto('/test/retell-simulator');

        await page.fill('[data-test="user-input"]', 'Termin buchen morgen');
        await page.click('[data-test="send-message"]');

        // Assert: Graceful error message
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/technisches Problem/i);

        // Offer alternative contact method
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/(rufen Sie uns an|Rückruf)/i);
    });

    test('validates required fields before submission', async ({ page }) => {
        await page.goto('/test/retell-simulator');

        // Try to book without providing name
        await page.fill('[data-test="user-input"]', 'Morgen 14 Uhr');
        await page.click('[data-test="send-message"]');

        await page.fill('[data-test="user-input"]', 'Ja, buchen'); // Confirm without name
        await page.click('[data-test="send-message"]');

        // Assert: Agent asks for missing information
        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/(Name|wie heißen Sie)/i);
    });
});

test.describe('Performance Validation', () => {
    test('booking flow completes within 45s target (RCA)', async ({ page }) => {
        /**
         * RCA Reference: Performance target 144s → <45s
         */

        const startTime = Date.now();

        await page.goto('/test/retell-simulator');

        // Complete booking flow
        await page.fill('[data-test="user-input"]', 'Übermorgen 13 Uhr');
        await page.click('[data-test="send-message"]');

        await page.fill('[data-test="user-input"]', 'Max Performance Test, 0151 12345678');
        await page.click('[data-test="send-message"]');

        await page.fill('[data-test="user-input"]', 'Ja, bitte buchen');
        await page.click('[data-test="send-message"]');

        await expect(page.locator('[data-test="agent-response"]').last())
            .toContainText(/gebucht/i);

        const duration = (Date.now() - startTime) / 1000; // Convert to seconds

        // Assert: Completed within 45s
        expect(duration).toBeLessThan(45);
    });
});
