/**
 * ASK-010: CRM Data Consistency Browser E2E Tests
 *
 * Tests customer portal UI verification for data consistency
 * Tests: Name consistency, metadata display, timeline accuracy
 */

const puppeteer = require('puppeteer');
const { expect } = require('chai');

const BASE_URL = process.env.APP_URL || 'http://localhost:8000';
const PORTAL_URL = `${BASE_URL}/portal`;

describe('CRM Data Consistency - Browser E2E Tests', function () {
    this.timeout(60000);

    let browser;
    let page;

    before(async function () {
        browser = await puppeteer.launch({
            headless: process.env.HEADLESS !== 'false',
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
        });
        page = await browser.newPage();
        await page.setViewport({ width: 1280, height: 720 });
    });

    after(async function () {
        if (browser) {
            await browser.close();
        }
    });

    /**
     * ASK-010: Test portal displays consistent customer name
     */
    it('should display consistent customer name across portal views', async function () {
        // Navigate to appointment lookup
        await page.goto(`${PORTAL_URL}/lookup`, { waitUntil: 'networkidle2' });

        // Enter customer email
        await page.waitForSelector('input[name="email"]');
        await page.type('input[name="email"]', 'max.mustermann@test.com');
        await page.click('button[type="submit"]');

        // Wait for results
        await page.waitForSelector('.appointment-details', { timeout: 10000 });

        // Verify customer name displayed
        const customerName = await page.$eval('.customer-name', el => el.textContent.trim());
        expect(customerName).to.equal('Max Mustermann');

        // Navigate to appointment details
        await page.click('.view-details-link');
        await page.waitForSelector('.appointment-header', { timeout: 10000 });

        // Verify same name on details page
        const detailsCustomerName = await page.$eval(
            '.appointment-header .customer-name',
            el => el.textContent.trim()
        );
        expect(detailsCustomerName).to.equal('Max Mustermann');
        expect(detailsCustomerName).to.equal(customerName);
    });

    /**
     * ASK-010: Test portal displays complete booking metadata
     */
    it('should display complete booking metadata on appointment details', async function () {
        await page.goto(`${PORTAL_URL}/appointments/1`, { waitUntil: 'networkidle2' });

        await page.waitForSelector('.appointment-metadata', { timeout: 10000 });

        // Verify booking source displayed
        const bookingSource = await page.$eval(
            '.booking-source',
            el => el.textContent.trim()
        );
        expect(bookingSource).to.include('Phone Call');

        // Verify created by information
        const createdBy = await page.$eval(
            '.created-by',
            el => el.textContent.trim()
        );
        expect(createdBy).to.not.be.empty;

        // Verify booking timestamp
        const bookingTimestamp = await page.$eval(
            '.booking-timestamp',
            el => el.textContent.trim()
        );
        expect(bookingTimestamp).to.not.be.empty;

        // Take screenshot for verification
        await page.screenshot({
            path: 'screenshots/appointment-metadata-complete.png',
            fullPage: true,
        });
    });

    /**
     * ASK-010: Test reschedule flow updates metadata correctly
     */
    it('should update metadata correctly during reschedule flow', async function () {
        await page.goto(`${PORTAL_URL}/appointments/1`, { waitUntil: 'networkidle2' });

        // Click reschedule button
        await page.waitForSelector('.reschedule-button');
        await page.click('.reschedule-button');

        // Wait for reschedule modal
        await page.waitForSelector('.reschedule-modal', { timeout: 10000 });

        // Select new date/time
        await page.waitForSelector('input[name="new_date"]');
        await page.type('input[name="new_date"]', '2025-10-15');
        await page.type('input[name="new_time"]', '14:00');

        // Enter reason
        await page.type('textarea[name="reason"]', 'Schedule conflict');

        // Take screenshot before submission
        await page.screenshot({
            path: 'screenshots/reschedule-modal-before.png',
        });

        // Submit reschedule
        await page.click('.reschedule-modal button[type="submit"]');

        // Wait for success message
        await page.waitForSelector('.success-message', { timeout: 10000 });

        // Verify reschedule metadata displayed
        await page.waitForSelector('.reschedule-info');
        const rescheduledBy = await page.$eval(
            '.rescheduled-by',
            el => el.textContent.trim()
        );
        expect(rescheduledBy).to.include('Customer Portal');

        const rescheduledAt = await page.$eval(
            '.rescheduled-at',
            el => el.textContent.trim()
        );
        expect(rescheduledAt).to.not.be.empty;

        // Take screenshot after reschedule
        await page.screenshot({
            path: 'screenshots/reschedule-complete.png',
            fullPage: true,
        });
    });

    /**
     * ASK-010: Test modification history displays chronologically
     */
    it('should display modification history in chronological order', async function () {
        await page.goto(`${PORTAL_URL}/appointments/1/history`, {
            waitUntil: 'networkidle2',
        });

        await page.waitForSelector('.modification-timeline', { timeout: 10000 });

        // Get all timeline entries
        const timelineEntries = await page.$$('.timeline-entry');
        expect(timelineEntries.length).to.be.greaterThan(0);

        // Verify chronological order (newest first)
        const timestamps = await page.$$eval('.timeline-entry .timestamp', els =>
            els.map(el => new Date(el.getAttribute('data-timestamp')))
        );

        for (let i = 0; i < timestamps.length - 1; i++) {
            expect(timestamps[i].getTime()).to.be.greaterThanOrEqual(
                timestamps[i + 1].getTime()
            );
        }

        // Verify each entry has required metadata
        for (const entry of timelineEntries) {
            const modificationType = await entry.$eval(
                '.modification-type',
                el => el.textContent.trim()
            );
            expect(modificationType).to.not.be.empty;

            const modifiedBy = await entry.$eval(
                '.modified-by',
                el => el.textContent.trim()
            );
            expect(modifiedBy).to.not.be.empty;
        }

        // Take screenshot
        await page.screenshot({
            path: 'screenshots/modification-timeline.png',
            fullPage: true,
        });
    });

    /**
     * ASK-010: Test cancellation flow preserves audit trail
     */
    it('should preserve complete audit trail during cancellation', async function () {
        await page.goto(`${PORTAL_URL}/appointments/1`, { waitUntil: 'networkidle2' });

        // Click cancel button
        await page.waitForSelector('.cancel-button');
        await page.click('.cancel-button');

        // Wait for cancellation modal
        await page.waitForSelector('.cancel-modal', { timeout: 10000 });

        // Enter cancellation reason
        await page.waitForSelector('textarea[name="cancellation_reason"]');
        await page.type('textarea[name="cancellation_reason"]', 'No longer needed');

        // Confirm cancellation
        await page.click('.cancel-modal button.confirm-cancel');

        // Wait for confirmation
        await page.waitForSelector('.cancellation-confirmed', { timeout: 10000 });

        // Verify cancellation metadata
        const cancelledBy = await page.$eval(
            '.cancelled-by',
            el => el.textContent.trim()
        );
        expect(cancelledBy).to.include('Customer Portal');

        const cancellationReason = await page.$eval(
            '.cancellation-reason',
            el => el.textContent.trim()
        );
        expect(cancellationReason).to.equal('No longer needed');

        // Verify status updated
        const status = await page.$eval('.appointment-status', el => el.textContent.trim());
        expect(status.toLowerCase()).to.include('cancelled');

        // Take screenshot
        await page.screenshot({
            path: 'screenshots/appointment-cancelled.png',
            fullPage: true,
        });
    });

    /**
     * ASK-010: Test name consistency after multiple modifications
     */
    it('should maintain name consistency after multiple modifications', async function () {
        await page.goto(`${PORTAL_URL}/lookup`, { waitUntil: 'networkidle2' });

        // Lookup appointment
        await page.type('input[name="email"]', 'max.mustermann@test.com');
        await page.click('button[type="submit"]');
        await page.waitForSelector('.appointment-details');

        // Get initial customer name
        const initialName = await page.$eval('.customer-name', el => el.textContent.trim());

        // View modification history
        await page.click('.view-history-link');
        await page.waitForSelector('.modification-timeline');

        // Verify name consistent in all timeline entries
        const nameReferences = await page.$$eval('.timeline-entry .customer-name', els =>
            els.map(el => el.textContent.trim())
        );

        nameReferences.forEach(name => {
            expect(name).to.equal(initialName);
            expect(name).to.equal('Max Mustermann');
        });

        // Take screenshot
        await page.screenshot({
            path: 'screenshots/name-consistency-verification.png',
            fullPage: true,
        });
    });

    /**
     * ASK-010: Test portal data matches API response
     */
    it('should display portal data matching API response', async function () {
        // First get data from API
        const apiResponse = await page.evaluate(async () => {
            const response = await fetch('/api/portal/appointments/lookup?email=max.mustermann@test.com');
            return response.json();
        });

        // Now verify portal displays same data
        await page.goto(`${PORTAL_URL}/lookup`, { waitUntil: 'networkidle2' });
        await page.type('input[name="email"]', 'max.mustermann@test.com');
        await page.click('button[type="submit"]');
        await page.waitForSelector('.appointment-details');

        // Verify customer name matches
        const portalName = await page.$eval('.customer-name', el => el.textContent.trim());
        expect(portalName).to.equal(apiResponse.appointment.customer_name);

        // Verify scheduled time matches
        const portalScheduledAt = await page.$eval(
            '.scheduled-at',
            el => el.getAttribute('data-timestamp')
        );
        expect(portalScheduledAt).to.equal(apiResponse.appointment.scheduled_at);

        // Verify booking source matches
        const portalBookingSource = await page.$eval(
            '.booking-source',
            el => el.textContent.trim()
        );
        expect(portalBookingSource.toLowerCase()).to.include(
            apiResponse.appointment.booking_source.toLowerCase()
        );
    });

    /**
     * ASK-010: Test error handling for missing metadata
     */
    it('should handle missing metadata gracefully', async function () {
        // Navigate to appointment with potentially missing metadata
        await page.goto(`${PORTAL_URL}/appointments/999`, { waitUntil: 'networkidle2' });

        // Check if error or placeholder displayed
        const hasError = await page.$('.error-message');
        const hasMetadata = await page.$('.appointment-metadata');

        if (hasMetadata) {
            // Verify fallback values displayed
            const createdBy = await page.$eval('.created-by', el => el.textContent.trim());
            expect(createdBy).to.not.be.empty;
            expect(createdBy).to.not.equal('null');
            expect(createdBy).to.not.equal('undefined');
        }

        // Take screenshot for debugging
        await page.screenshot({
            path: 'screenshots/missing-metadata-handling.png',
            fullPage: true,
        });
    });
});
