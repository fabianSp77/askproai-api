import React from 'react';
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import { QueryClient, QueryClientProvider } from 'react-query';
import { BrowserRouter } from 'react-router-dom';
import TeamView from '../../../resources/js/Pages/Portal/TeamView';
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

describe('TeamView', () => {
    const mockTeamData = {
        data: {
            users: [
                {
                    id: 1,
                    name: 'John Doe',
                    email: 'john@example.com',
                    role: 'admin',
                    status: 'active',
                    branches: [{ id: 1, name: 'Main Branch' }],
                    last_login: '2025-08-01T10:00:00Z',
                    created_at: '2025-01-01T00:00:00Z',
                    avatar_url: null,
                    permissions: [
                        'manage_appointments',
                        'manage_customers',
                        'manage_settings',
                        'manage_billing'
                    ]
                },
                {
                    id: 2,
                    name: 'Jane Smith',
                    email: 'jane@example.com',
                    role: 'staff',
                    status: 'active',
                    branches: [
                        { id: 1, name: 'Main Branch' },
                        { id: 2, name: 'Second Branch' }
                    ],
                    last_login: '2025-07-31T15:00:00Z',
                    created_at: '2025-02-01T00:00:00Z',
                    avatar_url: 'https://example.com/avatar.jpg',
                    permissions: [
                        'manage_appointments',
                        'view_customers'
                    ]
                },
                {
                    id: 3,
                    name: 'Bob Wilson',
                    email: 'bob@example.com',
                    role: 'staff',
                    status: 'inactive',
                    branches: [{ id: 2, name: 'Second Branch' }],
                    last_login: '2025-06-01T10:00:00Z',
                    created_at: '2025-03-01T00:00:00Z',
                    avatar_url: null,
                    permissions: ['view_appointments']
                }
            ],
            roles: [
                {
                    name: 'admin',
                    display_name: 'Administrator',
                    permissions: [
                        'manage_appointments',
                        'manage_customers',
                        'manage_settings',
                        'manage_billing',
                        'manage_team'
                    ]
                },
                {
                    name: 'staff',
                    display_name: 'Staff Member',
                    permissions: [
                        'manage_appointments',
                        'view_customers'
                    ]
                },
                {
                    name: 'viewer',
                    display_name: 'Viewer',
                    permissions: ['view_appointments']
                }
            ],
            branches: [
                { id: 1, name: 'Main Branch' },
                { id: 2, name: 'Second Branch' }
            ],
            invitations: [
                {
                    id: 1,
                    email: 'newuser@example.com',
                    role: 'staff',
                    branches: [{ id: 1, name: 'Main Branch' }],
                    invited_by: { id: 1, name: 'John Doe' },
                    created_at: '2025-07-30T10:00:00Z',
                    expires_at: '2025-08-06T10:00:00Z',
                    status: 'pending'
                }
            ],
            stats: {
                total_users: 3,
                active_users: 2,
                pending_invitations: 1,
                users_by_role: {
                    admin: 1,
                    staff: 2,
                    viewer: 0
                }
            }
        }
    };

    beforeEach(() => {
        vi.clearAllMocks();
        axios.get.mockResolvedValue({ data: mockTeamData });
    });

    it('should render team members list', async () => {
        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Team Management')).toBeInTheDocument();
            expect(screen.getByText('John Doe')).toBeInTheDocument();
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
            expect(screen.getByText('Bob Wilson')).toBeInTheDocument();
        });
    });

    it('should display team statistics', async () => {
        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('3')).toBeInTheDocument(); // Total users
            expect(screen.getByText('Total Members')).toBeInTheDocument();
            expect(screen.getByText('2')).toBeInTheDocument(); // Active users
            expect(screen.getByText('Active')).toBeInTheDocument();
            expect(screen.getByText('1')).toBeInTheDocument(); // Pending invitations
            expect(screen.getByText('Pending Invitations')).toBeInTheDocument();
        });
    });

    it('should filter team members by status', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Filter by active status
        const statusFilter = screen.getByLabelText('Filter by status');
        await user.click(statusFilter);
        await user.click(screen.getByText('Active'));

        // Should show only active users
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        expect(screen.queryByText('Bob Wilson')).not.toBeInTheDocument();
    });

    it('should filter team members by role', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
        });

        // Filter by staff role
        const roleFilter = screen.getByLabelText('Filter by role');
        await user.click(roleFilter);
        await user.click(screen.getByText('Staff Member'));

        // Should show only staff members
        expect(screen.queryByText('John Doe')).not.toBeInTheDocument();
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        expect(screen.getByText('Bob Wilson')).toBeInTheDocument();
    });

    it('should invite new team member', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: {
                success: true,
                invitation: {
                    id: 2,
                    email: 'newmember@example.com',
                    status: 'pending'
                }
            }
        });

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Team Management')).toBeInTheDocument();
        });

        // Click invite button
        const inviteButton = screen.getByText('Invite Member');
        await user.click(inviteButton);

        // Fill invitation form
        const modal = screen.getByRole('dialog');
        const emailInput = within(modal).getByLabelText('Email');
        const roleSelect = within(modal).getByLabelText('Role');
        
        await user.type(emailInput, 'newmember@example.com');
        await user.click(roleSelect);
        await user.click(screen.getByText('Staff Member'));

        // Select branches
        const branchesSelect = within(modal).getByLabelText('Branches');
        await user.click(branchesSelect);
        await user.click(screen.getByText('Main Branch'));

        // Send invitation
        const sendButton = within(modal).getByText('Send Invitation');
        await user.click(sendButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/team/invite',
                expect.objectContaining({
                    email: 'newmember@example.com',
                    role: 'staff',
                    branches: expect.any(Array)
                })
            );
        });
    });

    it('should edit team member', async () => {
        const user = userEvent.setup();
        axios.put.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        });

        // Click edit button for Jane
        const janeRow = screen.getByText('Jane Smith').closest('tr');
        const editButton = within(janeRow).getByLabelText('Edit member');
        await user.click(editButton);

        // Update role
        const modal = screen.getByRole('dialog');
        const roleSelect = within(modal).getByLabelText('Role');
        await user.click(roleSelect);
        await user.click(screen.getByText('Administrator'));

        // Save changes
        const saveButton = within(modal).getByText('Save Changes');
        await user.click(saveButton);

        await waitFor(() => {
            expect(axios.put).toHaveBeenCalledWith(
                '/api/team/users/2',
                expect.objectContaining({
                    role: 'admin'
                })
            );
        });
    });

    it('should deactivate team member', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        });

        // Click more actions for Jane
        const janeRow = screen.getByText('Jane Smith').closest('tr');
        const moreButton = within(janeRow).getByLabelText('More actions');
        await user.click(moreButton);

        // Click deactivate
        const deactivateButton = screen.getByText('Deactivate');
        await user.click(deactivateButton);

        // Confirm deactivation
        const confirmModal = screen.getByRole('dialog');
        const confirmButton = within(confirmModal).getByText('Deactivate');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith('/api/team/users/2/deactivate');
        });
    });

    it('should reactivate team member', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Bob Wilson')).toBeInTheDocument();
        });

        // Click more actions for Bob (inactive)
        const bobRow = screen.getByText('Bob Wilson').closest('tr');
        const moreButton = within(bobRow).getByLabelText('More actions');
        await user.click(moreButton);

        // Click reactivate
        const reactivateButton = screen.getByText('Reactivate');
        await user.click(reactivateButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith('/api/team/users/3/activate');
        });
    });

    it('should resend invitation', async () => {
        const user = userEvent.setup();
        axios.post.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Pending Invitations')).toBeInTheDocument();
        });

        // Switch to invitations tab
        const invitationsTab = screen.getByText('Invitations');
        await user.click(invitationsTab);

        await waitFor(() => {
            expect(screen.getByText('newuser@example.com')).toBeInTheDocument();
        });

        // Resend invitation
        const resendButton = screen.getByText('Resend');
        await user.click(resendButton);

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith('/api/team/invitations/1/resend');
        });
    });

    it('should cancel invitation', async () => {
        const user = userEvent.setup();
        axios.delete.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        // Switch to invitations tab
        const invitationsTab = screen.getByText('Invitations');
        await user.click(invitationsTab);

        await waitFor(() => {
            expect(screen.getByText('newuser@example.com')).toBeInTheDocument();
        });

        // Cancel invitation
        const cancelButton = screen.getByText('Cancel');
        await user.click(cancelButton);

        // Confirm cancellation
        const confirmModal = screen.getByRole('dialog');
        const confirmButton = within(confirmModal).getByText('Cancel Invitation');
        await user.click(confirmButton);

        await waitFor(() => {
            expect(axios.delete).toHaveBeenCalledWith('/api/team/invitations/1');
        });
    });

    it('should manage user permissions', async () => {
        const user = userEvent.setup();
        axios.put.mockResolvedValue({
            data: { success: true }
        });

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        });

        // Click permissions button for Jane
        const janeRow = screen.getByText('Jane Smith').closest('tr');
        const permissionsButton = within(janeRow).getByLabelText('Manage permissions');
        await user.click(permissionsButton);

        // Modal should show current permissions
        const modal = screen.getByRole('dialog');
        expect(within(modal).getByText('Manage Permissions')).toBeInTheDocument();
        expect(within(modal).getByLabelText('Manage Appointments')).toBeChecked();
        expect(within(modal).getByLabelText('View Customers')).toBeChecked();
        expect(within(modal).getByLabelText('Manage Settings')).not.toBeChecked();

        // Toggle permission
        const settingsPermission = within(modal).getByLabelText('Manage Settings');
        await user.click(settingsPermission);

        // Save changes
        const saveButton = within(modal).getByText('Save Permissions');
        await user.click(saveButton);

        await waitFor(() => {
            expect(axios.put).toHaveBeenCalledWith(
                '/api/team/users/2/permissions',
                expect.objectContaining({
                    permissions: expect.arrayContaining(['manage_settings'])
                })
            );
        });
    });

    it('should export team data', async () => {
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
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Team Management')).toBeInTheDocument();
        });

        // Click export button
        const exportButton = screen.getByText('Export');
        await user.click(exportButton);

        // Select CSV format
        const csvOption = screen.getByText('Export as CSV');
        await user.click(csvOption);

        expect(mockClick).toHaveBeenCalled();
    });

    it('should search team members', async () => {
        const user = userEvent.setup();
        
        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('John Doe')).toBeInTheDocument();
            expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        });

        // Search for Jane
        const searchInput = screen.getByPlaceholderText('Search team members...');
        await user.type(searchInput, 'Jane');

        // Should only show Jane
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        expect(screen.queryByText('John Doe')).not.toBeInTheDocument();
        expect(screen.queryByText('Bob Wilson')).not.toBeInTheDocument();
    });

    it('should show loading state', () => {
        axios.get.mockImplementation(() => new Promise(() => {})); // Never resolves

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        expect(screen.getByText('Loading team members...')).toBeInTheDocument();
    });

    it('should show error state', async () => {
        axios.get.mockRejectedValue(new Error('Failed to load team data'));

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('Failed to load team members')).toBeInTheDocument();
            expect(screen.getByText('Try Again')).toBeInTheDocument();
        });
    });

    it('should show empty state', async () => {
        axios.get.mockResolvedValue({
            data: {
                data: {
                    users: [],
                    roles: mockTeamData.data.roles,
                    branches: mockTeamData.data.branches,
                    invitations: [],
                    stats: {
                        total_users: 0,
                        active_users: 0,
                        pending_invitations: 0,
                        users_by_role: {
                            admin: 0,
                            staff: 0,
                            viewer: 0
                        }
                    }
                }
            }
        });

        render(
            <TestWrapper>
                <TeamView />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('No team members yet')).toBeInTheDocument();
            expect(screen.getByText('Invite your first team member')).toBeInTheDocument();
        });
    });
});