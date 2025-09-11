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
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'user@company.com',
                        'required' => true,
                    ],
                    [
                        'type' => 'password',
                        'name' => 'password',
                        'label' => 'Password',
                        'placeholder' => 'Enter secure password',
                        'required' => true,
                        'minlength' => '8',
                    ],
                    [
                        'type' => 'password',
                        'name' => 'password_confirmation',
                        'label' => 'Confirm Password',
                        'placeholder' => 'Confirm password',
                        'required' => true,
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
                        'value' => 'user',
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
                        'value' => true,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'email_verified',
                        'label' => 'Email Verified',
                        'value' => false,
                        'help' => 'Mark email as verified (verification email will not be sent)',
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'send_welcome_email',
                        'label' => 'Send Welcome Email',
                        'value' => true,
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create User"
        description="Add a new user account to the system"
        action="{{ url('/admin/users') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create User"
        cancelUrl="/admin/users"
        layout="single"
    />
    
    <script>
        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirmation');
            
            function validatePassword() {
                if (password.value !== passwordConfirm.value) {
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