import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import { QueryClient, QueryClientProvider } from 'react-query';
import { BrowserRouter } from 'react-router-dom';
import SettingsView from '../../../resources/js/Pages/Portal/SettingsView';
import axios from 'axios';

// Mock axios
vi.mock('axios');

const createTestQueryClient = () => new QueryClient({
    defaultOptions: {
        queries: { retry: false },
        mutations: { retry: false }
    }
});

const TestWrapper = ({ children }) => (
    <QueryClientProvider client={createTestQueryClient()}>
        <BrowserRouter>
            {children}
        </BrowserRouter>
    </QueryClientProvider>
);

describe('SettingsView', () => {
    const mockSettings = {
        data: {
            settings: {
                general: {
                    company_name: 'Test Company',
                    contact_email: 'contact@test.com',
                    phone: '+1234567890',
                    timezone: 'America/New_York',
                    date_format: 'MM/DD/YYYY',
                    time_format: '12h',
                    currency: 'USD',
                    language: 'en'
                },
                notifications: {
                    email_notifications: true,
                    sms_notifications: false,
                    appointment_reminders: true,
                    reminder_hours: 24,
                    marketing_emails: false,
                    system_alerts: true
                },
                booking: {
                    allow_online_booking: true,
                    require_confirmation: true,
                    booking_buffer_minutes: 15,
                    max_advance_days: 30,
                    min_advance_hours: 2,
                    cancellation_hours: 24,
                    auto_confirm: false
                },
                integrations: {
                    google_calendar: {
                        enabled: true,
                        connected: true,
                        email: 'company@gmail.com'
                    },
                    stripe: {
                        enabled: true,
                        connected: true,
                        account_id: 'acct_123456'
                    },
                    twilio: {
                        enabled: false,
                        connected: false
                    }
                },
                security: {
                    two_factor_auth: true,
                    session_timeout: 60,
                    ip_whitelist: [],
                    password_expiry_days: 90,
                    require_strong_passwords: true
                }
            },
            user_preferences: {
                dashboard_layout: 'grid',
                theme: 'light',
                notifications_sound: true,
                auto_refresh: true,
                refresh_interval: 30
            }
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        axios.get.mockResolvedValue({ data: mockSettings });
    });

    it('should render settings tabs', async () => {
        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Settings')).toBeInTheDocument();
            expect(screen.getByText('General')).toBeInTheDocument();
            expect(screen.getByText('Notifications')).toBeInTheDocument();
            expect(screen.getByText('Booking')).toBeInTheDocument();
            expect(screen.getByText('Integrations')).toBeInTheDocument();
            expect(screen.getByText('Security')).toBeInTheDocument();
            expect(screen.getByText('Preferences')).toBeInTheDocument();
        });
    });

    it('should display general settings', async () => {
        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByDisplayValue('Test Company')).toBeInTheDocument();
            expect(screen.getByDisplayValue('contact@test.com')).toBeInTheDocument();
            expect(screen.getByDisplayValue('+1234567890')).toBeInTheDocument();
        });
    });

    it('should update general settings', async () => {
        const user = userEvent.setup();
        axios.put.mockResolvedValue({
            data: {
                success: true,
                message: 'Settings updated successfully'
            }
        });

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByDisplayValue('Test Company')).toBeInTheDocument();
        });

        // Update company name
        const companyNameInput = screen.getByLabelText('Company Name');
        await user.clear(companyNameInput);
        await user.type(companyNameInput, 'Updated Company');

        // Save changes
        const saveButton = screen.getByText('Save Changes');
        await user.click(saveButton);

        await waitFor(() => {
            expect(axios.put).toHaveBeenCalledWith(
                '/api/settings/general',
                expect.objectContaining({
                    company_name: 'Updated Company'
                })
            );
        });
    });

    it('should switch between setting tabs', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('General')).toBeInTheDocument();
        });

        // Click on Notifications tab
        const notificationsTab = screen.getByText('Notifications');
        await user.click(notificationsTab);

        // Should show notification settings
        expect(screen.getByText('Email Notifications')).toBeInTheDocument();
        expect(screen.getByText('SMS Notifications')).toBeInTheDocument();
        expect(screen.getByText('Appointment Reminders')).toBeInTheDocument();
    });

    it('should toggle notification settings', async () => {
        const user = userEvent.setup();
        axios.put.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        // Navigate to notifications tab
        const notificationsTab = screen.getByText('Notifications');
        await user.click(notificationsTab);

        await waitFor(() => {
            expect(screen.getByText('Email Notifications')).toBeInTheDocument();
        });

        // Toggle SMS notifications
        const smsToggle = screen.getByLabelText('SMS Notifications');
        await user.click(smsToggle);

        await waitFor(() => {
            expect(axios.put).toHaveBeenCalledWith(
                '/api/settings/notifications',
                expect.objectContaining({
                    sms_notifications: true
                })
            );
        });
    });

    it('should display booking settings', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        // Navigate to booking tab
        const bookingTab = screen.getByText('Booking');
        await user.click(bookingTab);

        await waitFor(() => {
            expect(screen.getByText('Allow Online Booking')).toBeInTheDocument();
            expect(screen.getByText('Require Confirmation')).toBeInTheDocument();
            expect(screen.getByDisplayValue('15')).toBeInTheDocument(); // Buffer minutes
            expect(screen.getByDisplayValue('30')).toBeInTheDocument(); // Max advance days
        });
    });

    it('should manage integrations', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        // Navigate to integrations tab
        const integrationsTab = screen.getByText('Integrations');
        await user.click(integrationsTab);

        await waitFor(() => {
            // Google Calendar - connected
            expect(screen.getByText('Google Calendar')).toBeInTheDocument();
            expect(screen.getByText('Connected')).toBeInTheDocument();
            expect(screen.getByText('company@gmail.com')).toBeInTheDocument();

            // Stripe - connected
            expect(screen.getByText('Stripe')).toBeInTheDocument();
            expect(screen.getByText('acct_123456')).toBeInTheDocument();

            // Twilio - not connected
            expect(screen.getByText('Twilio')).toBeInTheDocument();
            expect(screen.getByText('Connect')).toBeInTheDocument();
        });
    });

    it('should disconnect integration', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        // Navigate to integrations tab
        const integrationsTab = screen.getByText('Integrations');
        await user.click(integrationsTab);

        await waitFor(() => {
            expect(screen.getByText('Google Calendar')).toBeInTheDocument();
        });

        // Disconnect Google Calendar
        const disconnectButtons = screen.getAllByText('Disconnect');
        await user.click(disconnectButtons[0]);

        // Confirm disconnection
        const confirmModal = screen.getByRole('dialog');
        const confirmButton = within(confirmModal).getByText('Disconnect');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/settings/integrations/google_calendar/disconnect'
            );
        });
    });

    it('should update security settings', async () => {
        const user = userEvent.setup();
        axios.put.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        // Navigate to security tab
        const securityTab = screen.getByText('Security');
        await user.click(securityTab);

        await waitFor(() => {
            expect(screen.getByText('Two-Factor Authentication')).toBeInTheDocument();
            expect(screen.getByDisplayValue('60')).toBeInTheDocument(); // Session timeout
        });

        // Update session timeout
        const timeoutInput = screen.getByLabelText('Session Timeout (minutes)');
        await user.clear(timeoutInput);
        await user.type(timeoutInput, '120');

        // Save changes
        const saveButton = screen.getByText('Save Security Settings');
        await user.click(saveButton);

        await waitFor(() => {
            expect(axios.put).toHaveBeenCalledWith(
                '/api/settings/security',
                expect.objectContaining({
                    session_timeout: 120
                })
            );
        });
    });

    it('should update user preferences', async () => {
        const user = userEvent.setup();
        axios.put.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        // Navigate to preferences tab
        const preferencesTab = screen.getByText('Preferences');
        await user.click(preferencesTab);

        await waitFor(() => {
            expect(screen.getByText('Dashboard Layout')).toBeInTheDocument();
            expect(screen.getByText('Theme')).toBeInTheDocument();
        });

        // Change theme
        const themeSelect = screen.getByLabelText('Theme');
        await user.click(themeSelect);
        await user.click(screen.getByText('Dark'));

        await waitFor(() => {
            expect(axios.put).toHaveBeenCalledWith(
                '/api/settings/preferences',
                expect.objectContaining({
                    theme: 'dark'
                })
            );
        });
    });

    it('should export settings', async () => {
        const user = userEvent.setup();
        
        // Mock blob download
        global.URL.createObjectURL = vi.fn(() => 'blob:url');
        const mockClick = vi.fn();
        const mockRemove = vi.fn();
        
        vi.spyOn(document, 'createElement').mockImplementation((tag) => {
            if (tag === 'a') {
                return {
                    click: mockClick,
                    remove: mockRemove,
                    setAttribute: vi.fn(),
                    style: {}
                };
            }
            return document.createElement(tag);
        });

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Settings')).toBeInTheDocument();
        });

        // Click export button
        const exportButton = screen.getByText('Export Settings');
        await user.click(exportButton);

        expect(mockClick).toHaveBeenCalled();
    });

    it('should import settings', async () => {
        const user = userEvent.setup();
        const file = new File(['{}'], 'settings.json', { type: 'application/json' });
        
        axios.post.mockResolvedValue({
            data: { success: true, message: 'Settings imported successfully' }
        });

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Settings')).toBeInTheDocument();
        });

        // Upload file
        const fileInput = screen.getByLabelText('Import Settings');
        await user.upload(fileInput, file);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/settings/import',
                expect.any(FormData),
                expect.objectContaining({
                    headers: { 'Content-Type': 'multipart/form-data' }
                })
            );
        });
    });

    it('should show validation errors', async () => {
        const user = userEvent.setup();
        axios.put.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    errors: {
                        contact_email: ['The email must be a valid email address.']
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByDisplayValue('contact@test.com')).toBeInTheDocument();
        });

        // Update with invalid email
        const emailInput = screen.getByLabelText('Contact Email');
        await user.clear(emailInput);
        await user.type(emailInput, 'invalid-email');

        // Save changes
        const saveButton = screen.getByText('Save Changes');
        await user.click(saveButton);

        await waitFor(() => {
            expect(screen.getByText('The email must be a valid email address.')).toBeInTheDocument();
        });
    });

    it('should show loading state', () => {
        axios.get.mockImplementation(() => new Promise(() => {})); // Never resolves

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        expect(screen.getByText('Loading settings...')).toBeInTheDocument();
    });

    it('should show error state', async () => {
        axios.get.mockRejectedValue(new Error('Failed to load settings'));

        render(
            <TestWrapper>
                <SettingsView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Failed to load settings')).toBeInTheDocument();
            expect(screen.getByText('Try Again')).toBeInTheDocument();
        });
    });
});