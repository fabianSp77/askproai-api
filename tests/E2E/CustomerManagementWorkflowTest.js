import { test, expect } from '@playwright/test';
import { loginAsUser, waitForLoadingToFinish } from './helpers/auth';
import { createTestData, cleanupTestData } from './helpers/testData';

test.describe('Customer Management Workflow', () => {
    let testData;

    test.beforeEach(async ({ page }) => {
        // Create test data
        testData = await createTestData({
            customers: 5,
            appointments: 10
        });

        // Login as admin
        await loginAsUser(page, 'admin@example.com', 'password');
    });

    test.afterEach(async () => {
        await cleanupTestData(testData);
    });

    test('should create new customer with complete profile', async ({ page }) => {
        // Navigate to customers
        await page.goto('/customers');
        await waitForLoadingToFinish(page);

        // Click create customer
        await page.click('button:has-text("Add Customer")');
        await expect(page.locator('.modal-title')).toContainText('New Customer');

        // Fill customer details
        await page.fill('[data-testid="first-name"]', 'John');
        await page.fill('[data-testid="last-name"]', 'Doe');
        await page.fill('[data-testid="email"]', 'john.doe@example.com');
        await page.fill('[data-testid="phone"]', '+1234567890');
        await page.fill('[data-testid="date-of-birth"]', '1990-05-15');
        
        // Address information
        await page.fill('[data-testid="address"]', '123 Main Street');
        await page.fill('[data-testid="city"]', 'New York');
        await page.fill('[data-testid="postal-code"]', '10001');
        await page.selectOption('[data-testid="country"]', 'US');

        // Marketing preferences
        await page.check('[data-testid="email-marketing"]');
        await page.uncheck('[data-testid="sms-marketing"]');

        // Notes
        await page.fill('[data-testid="notes"]', 'VIP customer, prefers morning appointments');

        // Tags
        await page.click('[data-testid="tags-input"]');
        await page.type('[data-testid="tags-input"]', 'VIP');
        await page.keyboard.press('Enter');
        await page.type('[data-testid="tags-input"]', 'Regular');
        await page.keyboard.press('Enter');

        // Submit form
        await page.click('button:has-text("Create Customer")');

        // Verify success
        await expect(page.locator('.toast-success')).toContainText('Customer created successfully');

        // Verify customer appears in list
        await waitForLoadingToFinish(page);
        await expect(page.locator('table tbody tr').first()).toContainText('John Doe');
        await expect(page.locator('table tbody tr').first()).toContainText('john.doe@example.com');
        await expect(page.locator('table tbody tr').first()).toContainText('+1234567890');
    });

    test('should search and filter customers', async ({ page }) => {
        await page.goto('/customers');
        await waitForLoadingToFinish(page);

        // Search by name
        await page.fill('[data-testid="search-input"]', testData.customers[0].name);
        await page.keyboard.press('Enter');

        await waitForLoadingToFinish(page);
        await expect(page.locator('table tbody tr')).toHaveCount(1);
        await expect(page.locator('table tbody tr').first()).toContainText(testData.customers[0].name);

        // Clear search
        await page.fill('[data-testid="search-input"]', '');
        await page.keyboard.press('Enter');

        // Filter by tag
        await page.click('[data-testid="filter-button"]');
        await page.click('[data-testid="tag-filter"]');
        await page.click('text=VIP');
        await page.click('button:has-text("Apply Filters")');

        await waitForLoadingToFinish(page);
        const vipCustomers = await page.locator('table tbody tr').count();
        expect(vipCustomers).toBeGreaterThan(0);

        // Filter by date range
        await page.click('[data-testid="filter-button"]');
        await page.click('[data-testid="date-range-filter"]');
        await page.click('text=Last 30 days');
        await page.click('button:has-text("Apply Filters")');

        await waitForLoadingToFinish(page);
    });

    test('should view and edit customer details', async ({ page }) => {
        await page.goto('/customers');
        await waitForLoadingToFinish(page);

        // Click on first customer
        await page.click(`table tbody tr:first-child td:has-text("${testData.customers[0].name}")`);

        // Should navigate to customer detail page
        await expect(page).toHaveURL(new RegExp(`/customers/${testData.customers[0].id}`));

        // Verify customer information displayed
        await expect(page.locator('h1')).toContainText(testData.customers[0].name);
        await expect(page.locator('.customer-email')).toContainText(testData.customers[0].email);
        await expect(page.locator('.customer-phone')).toContainText(testData.customers[0].phone);

        // Edit customer
        await page.click('button:has-text("Edit")');

        // Update information
        await page.fill('[data-testid="notes"]', 'Updated notes - prefers afternoon appointments');
        await page.click('[data-testid="tags-input"]');
        await page.type('[data-testid="tags-input"]', 'Premium');
        await page.keyboard.press('Enter');

        // Save changes
        await page.click('button:has-text("Save Changes")');

        // Verify success
        await expect(page.locator('.toast-success')).toContainText('Customer updated successfully');

        // Verify changes persisted
        await expect(page.locator('.customer-notes')).toContainText('Updated notes - prefers afternoon appointments');
        await expect(page.locator('.customer-tags')).toContainText('Premium');
    });

    test('should manage customer appointments', async ({ page }) => {
        await page.goto(`/customers/${testData.customers[0].id}`);
        await waitForLoadingToFinish(page);

        // View appointments tab
        await page.click('button:has-text("Appointments")');

        // Should show appointment history
        const appointmentCount = await page.locator('.appointment-list-item').count();
        expect(appointmentCount).toBeGreaterThan(0);

        // Book new appointment from customer page
        await page.click('button:has-text("Book Appointment")');

        // Customer should be pre-selected
        await expect(page.locator('[data-testid="customer-select"]')).toContainText(testData.customers[0].name);

        // Complete booking (simplified)
        await page.click('[data-testid="service-select"]');
        await page.click('text=Consultation');
        
        await page.fill('[data-testid="appointment-date"]', '2025-08-25');
        await page.fill('[data-testid="appointment-time"]', '14:00');

        await page.click('button:has-text("Create Appointment")');

        // Should return to customer page with new appointment
        await expect(page).toHaveURL(new RegExp(`/customers/${testData.customers[0].id}`));
        await expect(page.locator('.appointment-list-item').first()).toContainText('Aug 25, 2025');
    });

    test('should handle customer communication preferences', async ({ page }) => {
        await page.goto(`/customers/${testData.customers[0].id}`);
        await waitForLoadingToFinish(page);

        // Navigate to preferences tab
        await page.click('button:has-text("Preferences")');

        // Update communication preferences
        await page.check('[data-testid="pref-email-reminders"]');
        await page.uncheck('[data-testid="pref-sms-reminders"]');
        await page.check('[data-testid="pref-marketing-emails"]');
        
        // Set reminder timing
        await page.selectOption('[data-testid="reminder-timing"]', '24'); // 24 hours before

        // Set preferred contact method
        await page.click('[data-testid="preferred-contact-email"]');

        // Save preferences
        await page.click('button:has-text("Save Preferences")');

        // Verify success
        await expect(page.locator('.toast-success')).toContainText('Preferences updated');
    });

    test('should merge duplicate customers', async ({ page }) => {
        // Create duplicate customer
        const duplicate = await createTestData.customer({
            first_name: testData.customers[0].first_name,
            last_name: testData.customers[0].last_name,
            email: 'duplicate@example.com',
            phone: testData.customers[0].phone
        });

        await page.goto(`/customers/${testData.customers[0].id}`);
        await waitForLoadingToFinish(page);

        // Click merge duplicates
        await page.click('button[aria-label="More actions"]');
        await page.click('text=Merge Duplicates');

        // Should show potential duplicates
        await expect(page.locator('.duplicate-suggestion')).toContainText(duplicate.name);
        await expect(page.locator('.duplicate-match-reason')).toContainText('Same phone number');

        // Select duplicate to merge
        await page.click(`[data-duplicate-id="${duplicate.id}"]`);

        // Review merge preview
        await page.click('button:has-text("Preview Merge")');
        await expect(page.locator('.merge-preview')).toContainText('Combined appointment history');
        await expect(page.locator('.merge-preview')).toContainText('Merged contact information');

        // Confirm merge
        await page.click('button:has-text("Merge Customers")');

        // Verify success
        await expect(page.locator('.toast-success')).toContainText('Customers merged successfully');

        // Duplicate should no longer exist
        await page.goto('/customers');
        await page.fill('[data-testid="search-input"]', duplicate.email);
        await page.keyboard.press('Enter');
        await waitForLoadingToFinish(page);
        await expect(page.locator('.empty-state')).toContainText('No customers found');
    });

    test('should export customer data', async ({ page }) => {
        await page.goto('/customers');
        await waitForLoadingToFinish(page);

        // Select customers to export
        await page.check('input[data-testid="select-all-customers"]');

        // Click export
        await page.click('button:has-text("Export")');

        // Choose export format
        await page.click('text=Export as CSV');

        // Configure export options
        await page.check('[data-testid="include-appointments"]');
        await page.check('[data-testid="include-notes"]');
        await page.uncheck('[data-testid="include-internal-ids"]');

        // Start export
        const downloadPromise = page.waitForEvent('download');
        await page.click('button:has-text("Export Now")');

        // Verify download
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toMatch(/customers_export_.*\.csv/);
    });

    test('should manage customer notes and timeline', async ({ page }) => {
        await page.goto(`/customers/${testData.customers[0].id}`);
        await waitForLoadingToFinish(page);

        // Navigate to timeline tab
        await page.click('button:has-text("Timeline")');

        // Add a note
        await page.click('button:has-text("Add Note")');
        await page.fill('[data-testid="note-content"]', 'Customer called to inquire about new services');
        await page.selectOption('[data-testid="note-type"]', 'phone_call');
        await page.click('button:has-text("Save Note")');

        // Verify note appears in timeline
        await expect(page.locator('.timeline-item').first()).toContainText('Phone Call');
        await expect(page.locator('.timeline-item').first()).toContainText('Customer called to inquire');

        // Should show other timeline events
        const timelineItems = await page.locator('.timeline-item').count();
        expect(timelineItems).toBeGreaterThan(1); // Note + appointment history
    });

    test('should handle customer deletion with constraints', async ({ page }) => {
        // Customer with appointments
        await page.goto(`/customers/${testData.customers[0].id}`);
        await waitForLoadingToFinish(page);

        // Try to delete
        await page.click('button[aria-label="More actions"]');
        await page.click('text=Delete Customer');

        // Should show warning about appointments
        await expect(page.locator('.delete-warning')).toContainText('This customer has appointments');
        await expect(page.locator('.delete-warning')).toContainText('appointments will also be deleted');

        // Cancel deletion
        await page.click('button:has-text("Cancel")');

        // Create customer without appointments for successful deletion
        const deleteableCustomer = await createTestData.customer({
            name: 'Delete Me',
            email: 'delete@example.com'
        });

        await page.goto(`/customers/${deleteableCustomer.id}`);
        await waitForLoadingToFinish(page);

        // Delete customer
        await page.click('button[aria-label="More actions"]');
        await page.click('text=Delete Customer');

        // Confirm deletion
        await page.click('button:has-text("Delete Customer")[data-variant="danger"]');

        // Should redirect to customers list
        await expect(page).toHaveURL('/customers');
        await expect(page.locator('.toast-success')).toContainText('Customer deleted');
    });

    test('should import customers from CSV', async ({ page }) => {
        await page.goto('/customers');
        await waitForLoadingToFinish(page);

        // Click import
        await page.click('button:has-text("Import")');

        // Upload CSV file
        const csvContent = `first_name,last_name,email,phone
Jane,Smith,jane@example.com,+1234567891
Bob,Johnson,bob@example.com,+1234567892`;
        
        const buffer = Buffer.from(csvContent);
        await page.setInputFiles('[data-testid="csv-upload"]', {
            name: 'customers.csv',
            mimeType: 'text/csv',
            buffer
        });

        // Map columns
        await expect(page.locator('.column-mapping')).toBeVisible();
        // Auto-mapping should work for standard column names

        // Preview import
        await page.click('button:has-text("Preview Import")');
        await expect(page.locator('.import-preview')).toContainText('2 customers will be imported');
        await expect(page.locator('.import-preview')).toContainText('Jane Smith');
        await expect(page.locator('.import-preview')).toContainText('Bob Johnson');

        // Configure import options
        await page.check('[data-testid="skip-duplicates"]');
        await page.check('[data-testid="send-welcome-email"]');

        // Start import
        await page.click('button:has-text("Import Customers")');

        // Show progress
        await expect(page.locator('.import-progress')).toBeVisible();
        await expect(page.locator('.import-progress')).toContainText('Importing... 0/2');

        // Wait for completion
        await expect(page.locator('.import-complete')).toBeVisible({ timeout: 10000 });
        await expect(page.locator('.import-complete')).toContainText('Successfully imported 2 customers');
    });
});