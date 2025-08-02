import { test, expect } from '@playwright/test';
import { loginAsUser, switchUser, waitForLoadingToFinish } from './helpers/auth';
import { createTestData, cleanupTestData } from './helpers/testData';

test.describe('Team Collaboration Workflow', () => {
    let testData;
    let adminUser;
    let staffUser1;
    let staffUser2;

    test.beforeEach(async () => {
        // Create test users and data
        adminUser = await createTestData.user({
            email: 'admin@test.com',
            password: 'Admin123!@#',
            role: 'admin'
        });

        staffUser1 = await createTestData.user({
            email: 'staff1@test.com',
            password: 'Staff123!@#',
            role: 'staff',
            branch_id: 1
        });

        staffUser2 = await createTestData.user({
            email: 'staff2@test.com',
            password: 'Staff123!@#',
            role: 'staff',
            branch_id: 2
        });

        testData = await createTestData({
            branches: 2,
            services: 5,
            customers: 10
        });
    });

    test.afterEach(async () => {
        await cleanupTestData(testData);
        await cleanupTestData({ users: [adminUser, staffUser1, staffUser2] });
    });

    test('should manage team member roles and permissions', async ({ page }) => {
        // Login as admin
        await loginAsUser(page, adminUser.email, adminUser.password);
        
        // Navigate to team management
        await page.goto('/team');
        await waitForLoadingToFinish(page);

        // Find staff member
        await page.fill('[data-testid="search-team"]', staffUser1.email);
        await waitForLoadingToFinish(page);

        // Edit permissions
        await page.click(`[data-user-id="${staffUser1.id}"] button[aria-label="Edit permissions"]`);

        // Grant additional permissions
        await page.check('[data-testid="permission-manage-customers"]');
        await page.check('[data-testid="permission-view-reports"]');
        await page.uncheck('[data-testid="permission-manage-billing"]');

        // Save changes
        await page.click('button:has-text("Save Permissions")');
        await expect(page.locator('.toast-success')).toContainText('Permissions updated');

        // Switch to staff user to verify permissions
        await switchUser(page, staffUser1.email, staffUser1.password);

        // Should have access to customers
        await page.goto('/customers');
        await expect(page.locator('h1')).toContainText('Customers');

        // Should have access to reports
        await page.goto('/reports');
        await expect(page.locator('h1')).toContainText('Reports');

        // Should NOT have access to billing
        await page.goto('/billing');
        await expect(page.locator('.error-403')).toContainText('Access Denied');
    });

    test('should handle multi-branch collaboration', async ({ page, context }) => {
        // Open two browser contexts for different staff members
        const page1 = page;
        const page2 = await context.newPage();

        // Staff 1 logs in (Branch 1)
        await loginAsUser(page1, staffUser1.email, staffUser1.password);
        await page1.goto('/appointments');
        await waitForLoadingToFinish(page1);

        // Staff 2 logs in (Branch 2)
        await loginAsUser(page2, staffUser2.email, staffUser2.password);
        await page2.goto('/appointments');
        await waitForLoadingToFinish(page2);

        // Each staff should only see their branch's appointments
        const branch1Appointments = await page1.locator('[data-branch-name="Main Branch"]').count();
        const branch2AppointmentsOnPage1 = await page1.locator('[data-branch-name="Second Branch"]').count();
        
        expect(branch1Appointments).toBeGreaterThan(0);
        expect(branch2AppointmentsOnPage1).toBe(0); // Should not see other branch

        // Admin can see all branches
        await switchUser(page1, adminUser.email, adminUser.password);
        await page1.goto('/appointments');
        await waitForLoadingToFinish(page1);

        // Admin should see appointments from both branches
        const allBranchesVisible = await page1.locator('[data-branch-name]').count();
        expect(allBranchesVisible).toBeGreaterThan(branch1Appointments);

        // Admin can filter by branch
        await page1.click('[data-testid="branch-filter"]');
        await page1.click('text=Second Branch');
        await waitForLoadingToFinish(page1);

        const branch2Appointments = await page1.locator('[data-branch-name="Second Branch"]').count();
        expect(branch2Appointments).toBeGreaterThan(0);

        await page2.close();
    });

    test('should handle real-time notifications between team members', async ({ page, context }) => {
        const page1 = page;
        const page2 = await context.newPage();

        // Both users login
        await loginAsUser(page1, staffUser1.email, staffUser1.password);
        await loginAsUser(page2, staffUser2.email, staffUser2.password);

        // Staff 1 creates an appointment
        await page1.goto('/appointments/new');
        await page1.click('[data-testid="customer-select"]');
        await page1.click(`text=${testData.customers[0].name}`);
        await page1.click('[data-testid="service-select"]');
        await page1.click(`text=${testData.services[0].name}`);
        await page1.fill('[data-testid="appointment-date"]', '2025-08-20');
        await page1.fill('[data-testid="appointment-time"]', '10:00');
        
        // Assign to Staff 2
        await page1.click('[data-testid="staff-select"]');
        await page1.click(`text=${staffUser2.name}`);
        
        await page1.click('button:has-text("Create Appointment")');

        // Staff 2 should receive notification
        await expect(page2.locator('.notification-toast')).toBeVisible({ timeout: 5000 });
        await expect(page2.locator('.notification-toast')).toContainText('New appointment assigned');
        await expect(page2.locator('.notification-toast')).toContainText(testData.customers[0].name);

        // Click notification to view appointment
        await page2.click('.notification-toast');
        await expect(page2).toHaveURL(/\/appointments\/\d+/);

        await page2.close();
    });

    test('should manage team schedules and availability', async ({ page }) => {
        await loginAsUser(page, adminUser.email, adminUser.password);
        
        // Navigate to team schedules
        await page.goto('/team/schedules');
        await waitForLoadingToFinish(page);

        // View weekly schedule
        await expect(page.locator('.schedule-grid')).toBeVisible();
        await expect(page.locator('.staff-row')).toHaveCount(3); // Admin + 2 staff

        // Edit staff schedule
        await page.click(`[data-staff-id="${staffUser1.id}"] button[aria-label="Edit schedule"]`);

        // Set availability
        await page.click('[data-day="monday"] [data-time="09:00"]');
        await page.click('[data-day="monday"] [data-time="17:00"]');
        
        // Add break time
        await page.click('button:has-text("Add Break")');
        await page.selectOption('[data-testid="break-day"]', 'monday');
        await page.fill('[data-testid="break-start"]', '12:00');
        await page.fill('[data-testid="break-end"]', '13:00');

        // Set as recurring weekly
        await page.check('[data-testid="recurring-schedule"]');

        // Save schedule
        await page.click('button:has-text("Save Schedule")');
        await expect(page.locator('.toast-success')).toContainText('Schedule updated');

        // Verify schedule appears correctly
        await expect(page.locator(`[data-staff-id="${staffUser1.id}"] .schedule-block`)).toContainText('9:00 AM - 5:00 PM');
        await expect(page.locator(`[data-staff-id="${staffUser1.id}"] .break-block`)).toContainText('Break: 12:00 PM');
    });

    test('should handle team communication and notes', async ({ page }) => {
        await loginAsUser(page, staffUser1.email, staffUser1.password);

        // Create appointment with team note
        const appointment = await createTestData.appointment({
            customer_id: testData.customers[0].id,
            staff_id: staffUser1.id,
            service_id: testData.services[0].id
        });

        await page.goto(`/appointments/${appointment.id}`);
        await waitForLoadingToFinish(page);

        // Add internal note
        await page.click('button:has-text("Add Note")');
        await page.selectOption('[data-testid="note-visibility"]', 'internal');
        await page.fill('[data-testid="note-content"]', 'Customer prefers quiet environment, has anxiety issues');
        await page.click('button:has-text("Save Note")');

        // Switch to another staff member
        await switchUser(page, staffUser2.email, staffUser2.password);
        await page.goto(`/appointments/${appointment.id}`);
        
        // Should see internal note
        await expect(page.locator('.internal-note')).toContainText('Customer prefers quiet environment');
        await expect(page.locator('.note-author')).toContainText(staffUser1.name);

        // Add reply
        await page.click('button:has-text("Reply")');
        await page.fill('[data-testid="reply-content"]', 'Thanks for the heads up, will ensure a calm atmosphere');
        await page.click('button:has-text("Send Reply")');

        // Both staff should see the conversation thread
        await expect(page.locator('.note-thread')).toContainText('Thanks for the heads up');
    });

    test('should manage team performance and metrics', async ({ page }) => {
        await loginAsUser(page, adminUser.email, adminUser.password);
        
        // Navigate to team performance
        await page.goto('/team/performance');
        await waitForLoadingToFinish(page);

        // View team metrics
        await expect(page.locator('.performance-dashboard')).toBeVisible();
        
        // Select date range
        await page.click('[data-testid="date-range-picker"]');
        await page.click('text=Last 30 days');
        await waitForLoadingToFinish(page);

        // Should show key metrics
        await expect(page.locator('.metric-card')).toContainText('Total Appointments');
        await expect(page.locator('.metric-card')).toContainText('Revenue Generated');
        await expect(page.locator('.metric-card')).toContainText('Customer Satisfaction');
        await expect(page.locator('.metric-card')).toContainText('Average Service Time');

        // View individual performance
        await page.click(`[data-staff-id="${staffUser1.id}"] .view-details`);
        
        // Should show detailed metrics
        await expect(page.locator('.staff-details')).toContainText(staffUser1.name);
        await expect(page.locator('.appointment-stats')).toBeVisible();
        await expect(page.locator('.revenue-chart')).toBeVisible();
        await expect(page.locator('.feedback-summary')).toBeVisible();

        // Set performance goals
        await page.click('button:has-text("Set Goals")');
        await page.fill('[data-testid="appointments-per-day-goal"]', '8');
        await page.fill('[data-testid="revenue-per-month-goal"]', '5000');
        await page.fill('[data-testid="satisfaction-rating-goal"]', '4.5');
        await page.click('button:has-text("Save Goals")');

        await expect(page.locator('.toast-success')).toContainText('Goals updated');
    });

    test('should handle shift swapping between team members', async ({ page, context }) => {
        // Create scheduled shifts
        const shift1 = await createTestData.shift({
            staff_id: staffUser1.id,
            date: '2025-08-20',
            start_time: '09:00',
            end_time: '17:00'
        });

        const page1 = page;
        const page2 = await context.newPage();

        // Staff 1 requests shift swap
        await loginAsUser(page1, staffUser1.email, staffUser1.password);
        await page1.goto('/my-schedule');
        await waitForLoadingToFinish(page1);

        await page1.click(`[data-shift-id="${shift1.id}"] button[aria-label="Request swap"]`);
        await page1.fill('[data-testid="swap-reason"]', 'Family emergency');
        await page1.click('button:has-text("Request Swap")');

        // Staff 2 receives notification
        await loginAsUser(page2, staffUser2.email, staffUser2.password);
        await expect(page2.locator('.notification-badge')).toContainText('1');
        
        // View swap request
        await page2.click('.notification-icon');
        await page2.click('text=Shift swap request');
        
        // Should show swap details
        await expect(page2.locator('.swap-request')).toContainText('Aug 20, 2025');
        await expect(page2.locator('.swap-request')).toContainText('9:00 AM - 5:00 PM');
        await expect(page2.locator('.swap-request')).toContainText('Family emergency');

        // Accept swap
        await page2.click('button:has-text("Accept Swap")');
        await expect(page2.locator('.toast-success')).toContainText('Shift swap accepted');

        // Both calendars should update
        await page1.reload();
        await expect(page1.locator('.my-shifts')).not.toContainText('Aug 20, 2025');
        
        await page2.goto('/my-schedule');
        await expect(page2.locator('.my-shifts')).toContainText('Aug 20, 2025');

        await page2.close();
    });

    test('should manage team training and certifications', async ({ page }) => {
        await loginAsUser(page, adminUser.email, adminUser.password);
        
        // Navigate to team training
        await page.goto('/team/training');
        await waitForLoadingToFinish(page);

        // Create new training requirement
        await page.click('button:has-text("Add Training")');
        await page.fill('[data-testid="training-name"]', 'Customer Service Excellence');
        await page.fill('[data-testid="training-description"]', 'Advanced customer service techniques');
        await page.selectOption('[data-testid="training-type"]', 'online_course');
        await page.fill('[data-testid="duration-hours"]', '4');
        await page.fill('[data-testid="expiry-months"]', '12');
        
        // Assign to roles
        await page.check('[data-testid="assign-to-staff-role"]');
        
        await page.click('button:has-text("Create Training")');
        await expect(page.locator('.toast-success')).toContainText('Training created');

        // Assign specific training to staff member
        await page.click('button:has-text("Assign Training")');
        await page.click(`[data-staff-id="${staffUser1.id}"]`);
        await page.selectOption('[data-testid="training-select"]', 'Customer Service Excellence');
        await page.fill('[data-testid="due-date"]', '2025-09-01');
        await page.click('button:has-text("Assign")');

        // Switch to staff view
        await switchUser(page, staffUser1.email, staffUser1.password);
        await page.goto('/my-training');
        
        // Should see assigned training
        await expect(page.locator('.training-item')).toContainText('Customer Service Excellence');
        await expect(page.locator('.training-status')).toContainText('Not Started');
        await expect(page.locator('.due-date')).toContainText('Due: Sep 1, 2025');

        // Start training
        await page.click('button:has-text("Start Training")');
        // Would redirect to training platform or show content

        // Mark as complete (simulated)
        await page.goto('/my-training');
        await page.click('[data-training-id] button:has-text("Mark Complete")');
        await page.setInputFiles('[data-testid="certificate-upload"]', {
            name: 'certificate.pdf',
            mimeType: 'application/pdf',
            buffer: Buffer.from('mock certificate')
        });
        await page.click('button:has-text("Submit Completion")');

        await expect(page.locator('.training-status')).toContainText('Completed');
    });
});