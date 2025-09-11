<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'User Details',
                'description' => 'Basic user account information',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Full Name',
                        'placeholder' => 'Enter user full name',
                        'required' => true,
                        'value' => $record->name ?? '',
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'user@company.com',
                        'required' => true,
                        'value' => $record->email ?? '',
                    ],
                    [
                        'type' => 'password',
                        'name' => 'password',
                        'label' => 'New Password',
                        'placeholder' => 'Leave blank to keep current password',
                        'minlength' => '8',
                        'help' => 'Leave blank to keep current password',
                    ],
                    [
                        'type' => 'password',
                        'name' => 'password_confirmation',
                        'label' => 'Confirm New Password',
                        'placeholder' => 'Confirm new password',
                        'minlength' => '8',
                    ],
                ],
            ],
            [
                'title' => 'Role & Permissions',
                'description' => 'User role and access level',
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'role',
                        'label' => 'User Role',
                        'required' => true,
                        'value' => $record->role ?? 'user',
                        'options' => [
                            ['value' => 'admin', 'label' => 'Administrator'],
                            ['value' => 'manager', 'label' => 'Manager'],
                            ['value' => 'staff', 'label' => 'Staff Member'],
                            ['value' => 'user', 'label' => 'Regular User'],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'name' => 'tenant_id',
                        'label' => 'Tenant',
                        'placeholder' => 'Select tenant',
                        'required' => true,
                        'value' => $record->tenant_id ?? '',
                        'options' => \App\Models\Tenant::pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                ],
            ],
            [
                'title' => 'Account Settings',
                'description' => 'User account status and preferences',
                'fields' => [
                    [
                        'type' => 'toggle',
                        'name' => 'is_active',
                        'label' => 'Active Account',
                        'value' => $record->is_active ?? true,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'email_verified',
                        'label' => 'Email Verified',
                        'value' => $record->email_verified_at !== null,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'force_password_change',
                        'label' => 'Force Password Change',
                        'value' => false,
                        'help' => 'User will be required to change password on next login',
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Edit User"
        description="Update user account information"
        action="{{ url('/admin/users/' . $record->id, $record) }}"
        method="PUT"
        :sections="$sections"
        submitLabel="Update User"
        cancelUrl="/admin/users"
        layout="single"
        :showDelete="true"
    />
    
    <script>
        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirmation');
            
            function validatePassword() {
                if (password.value && password.value !== passwordConfirm.value) {
                    passwordConfirm.setCustomValidity('Passwords do not match');
                } else {
                    passwordConfirm.setCustomValidity('');
                }
            }
            
            password?.addEventListener('change', validatePassword);
            passwordConfirm?.addEventListener('keyup', validatePassword);
        });
    </script>
</x-filament-panels::page>