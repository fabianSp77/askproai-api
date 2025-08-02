<?php

namespace App\Services\MCP;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TeamMCPServer
{
    protected string $name = 'Team Management MCP Server';
    protected string $version = '1.0.0';
    
    /**
     * Get available tools for this MCP server.
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'listTeamMembers',
                'description' => 'List all team members with filtering and pagination',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'page' => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 20],
                        'branch_id' => ['type' => 'integer'],
                        'role' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'all']],
                        'search' => ['type' => 'string'],
                        'include_performance' => ['type' => 'boolean', 'default' => false]
                    ]
                ]
            ],
            [
                'name' => 'getTeamMember',
                'description' => 'Get detailed information about a team member',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'include_performance' => ['type' => 'boolean', 'default' => true],
                        'include_permissions' => ['type' => 'boolean', 'default' => true]
                    ],
                    'required' => ['user_id']
                ]
            ],
            [
                'name' => 'inviteTeamMember',
                'description' => 'Invite a new team member',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'email' => ['type' => 'string', 'format' => 'email', 'required' => true],
                        'name' => ['type' => 'string', 'required' => true],
                        'role' => ['type' => 'string', 'required' => true],
                        'branch_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                        'permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'send_invitation_email' => ['type' => 'boolean', 'default' => true]
                    ],
                    'required' => ['email', 'name', 'role']
                ]
            ],
            [
                'name' => 'updateTeamMember',
                'description' => 'Update team member information',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string', 'format' => 'email'],
                        'role' => ['type' => 'string'],
                        'branch_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                        'permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'is_active' => ['type' => 'boolean']
                    ],
                    'required' => ['user_id']
                ]
            ],
            [
                'name' => 'removeTeamMember',
                'description' => 'Remove a team member (soft delete)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'reassign_to' => ['type' => 'integer'],
                        'reason' => ['type' => 'string']
                    ],
                    'required' => ['user_id']
                ]
            ],
            [
                'name' => 'getTeamPerformance',
                'description' => 'Get team performance metrics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'enum' => ['today', 'week', 'month', 'quarter'], 'default' => 'month'],
                        'branch_id' => ['type' => 'integer'],
                        'user_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                        'metrics' => ['type' => 'array', 'items' => ['type' => 'string']]
                    ]
                ]
            ],
            [
                'name' => 'getWorkload',
                'description' => 'Get current workload distribution',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'branch_id' => ['type' => 'integer'],
                        'include_recommendations' => ['type' => 'boolean', 'default' => true]
                    ]
                ]
            ],
            [
                'name' => 'getRoles',
                'description' => 'Get available roles and permissions',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ],
            [
                'name' => 'updatePermissions',
                'description' => 'Update user permissions',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'permissions' => ['type' => 'array', 'items' => ['type' => 'string'], 'required' => true],
                        'mode' => ['type' => 'string', 'enum' => ['replace', 'add', 'remove'], 'default' => 'replace']
                    ],
                    'required' => ['user_id', 'permissions']
                ]
            ],
            [
                'name' => 'getSchedule',
                'description' => 'Get team member availability and schedule',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                        'date_from' => ['type' => 'string', 'format' => 'date'],
                        'date_to' => ['type' => 'string', 'format' => 'date']
                    ]
                ]
            ],
            [
                'name' => 'assignWorkload',
                'description' => 'Automatically assign workload based on availability and skills',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'task_type' => ['type' => 'string', 'enum' => ['calls', 'appointments'], 'required' => true],
                        'item_ids' => ['type' => 'array', 'items' => ['type' => 'string'], 'required' => true],
                        'strategy' => ['type' => 'string', 'enum' => ['round_robin', 'least_busy', 'skills_based'], 'default' => 'least_busy']
                    ],
                    'required' => ['task_type', 'item_ids']
                ]
            ]
        ];
    }

    /**
     * Handle tool execution.
     */
    public function executeTool(string $name, array $arguments): array
    {
        try {
            switch ($name) {
                case 'listTeamMembers':
                    return $this->listTeamMembers($arguments);
                case 'getTeamMember':
                    return $this->getTeamMember($arguments);
                case 'inviteTeamMember':
                    return $this->inviteTeamMember($arguments);
                case 'updateTeamMember':
                    return $this->updateTeamMember($arguments);
                case 'removeTeamMember':
                    return $this->removeTeamMember($arguments);
                case 'getTeamPerformance':
                    return $this->getTeamPerformance($arguments);
                case 'getWorkload':
                    return $this->getWorkload($arguments);
                case 'getRoles':
                    return $this->getRoles($arguments);
                case 'updatePermissions':
                    return $this->updatePermissions($arguments);
                case 'getSchedule':
                    return $this->getSchedule($arguments);
                case 'assignWorkload':
                    return $this->assignWorkload($arguments);
                default:
                    throw new \Exception("Unknown tool: {$name}");
            }
        } catch (\Exception $e) {
            Log::error("TeamMCPServer error in {$name}", [
                'error' => $e->getMessage(),
                'arguments' => $arguments
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List team members with filtering.
     */
    protected function listTeamMembers(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $query = User::where('company_id', $user->company_id)
                     ->with(['roles', 'branches']);

        // Apply filters
        if (!empty($params['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($params) {
                $q->where('branches.id', $params['branch_id']);
            });
        }

        if (!empty($params['role'])) {
            $query->whereHas('roles', function ($q) use ($params) {
                $q->where('name', $params['role']);
            });
        }

        if (isset($params['status']) && $params['status'] !== 'all') {
            $isActive = $params['status'] === 'active';
            $query->where('is_active', $isActive);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Paginate
        $perPage = $params['per_page'] ?? 20;
        $members = $query->orderBy('name')->paginate($perPage);

        // Transform data
        $transformedMembers = $members->map(function ($member) use ($params) {
            $data = [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->roles->first()->name ?? 'member',
                'branches' => $member->branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name
                    ];
                }),
                'is_active' => $member->is_active,
                'created_at' => $member->created_at->toIso8601String(),
                'last_login_at' => $member->last_login_at
            ];

            if ($params['include_performance'] ?? false) {
                $data['performance'] = $this->getMemberPerformance($member->id);
            }

            return $data;
        });

        return [
            'success' => true,
            'data' => $transformedMembers,
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total()
            ]
        ];
    }

    /**
     * Get detailed team member information.
     */
    protected function getTeamMember(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $member = User::where('company_id', $user->company_id)
                      ->where('id', $params['user_id'])
                      ->with(['roles', 'branches', 'permissions'])
                      ->firstOrFail();

        $data = [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'phone' => $member->phone,
            'role' => $member->roles->first()->name ?? 'member',
            'branches' => $member->branches,
            'is_active' => $member->is_active,
            'created_at' => $member->created_at->toIso8601String(),
            'last_login_at' => $member->last_login_at,
            'two_factor_enabled' => !empty($member->two_factor_secret)
        ];

        if ($params['include_performance'] ?? true) {
            $data['performance'] = $this->getDetailedPerformance($member->id);
        }

        if ($params['include_permissions'] ?? true) {
            $data['permissions'] = $member->getAllPermissions()->pluck('name');
        }

        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Invite a new team member.
     */
    protected function inviteTeamMember(array $params): array
    {
        $user = Auth::guard('portal')->user();
        
        // Check if user already exists
        $existingUser = User::where('email', $params['email'])->first();
        if ($existingUser) {
            if ($existingUser->company_id === $user->company_id) {
                throw new \Exception('A team member with this email already exists');
            } else {
                throw new \Exception('This email is already registered with another company');
            }
        }

        DB::beginTransaction();
        try {
            // Create user
            $invitationToken = Str::random(64);
            $member = new User();
            $member->name = $params['name'];
            $member->email = $params['email'];
            $member->company_id = $user->company_id;
            $member->password = Hash::make(Str::random(32)); // Temporary password
            $member->invitation_token = $invitationToken;
            $member->invited_by = $user->id;
            $member->invited_at = now();
            $member->is_active = false; // Inactive until invitation accepted
            $member->save();

            // Assign role
            $role = Role::where('name', $params['role'])->first();
            if ($role) {
                $member->assignRole($role);
            }

            // Assign branches
            if (!empty($params['branch_ids'])) {
                $member->branches()->attach($params['branch_ids']);
            }

            // Assign custom permissions
            if (!empty($params['permissions'])) {
                foreach ($params['permissions'] as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission) {
                        $member->givePermissionTo($permission);
                    }
                }
            }

            // Send invitation email
            if ($params['send_invitation_email'] ?? true) {
                // TODO: Dispatch invitation email job
                Log::info('Team member invitation email queued', [
                    'member_id' => $member->id,
                    'email' => $member->email
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'user_id' => $member->id,
                    'invitation_token' => $invitationToken,
                    'invitation_url' => url("/business/invitation/{$invitationToken}")
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update team member information.
     */
    protected function updateTeamMember(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $member = User::where('company_id', $user->company_id)
                      ->where('id', $params['user_id'])
                      ->firstOrFail();

        // Prevent self-demotion
        if ($member->id === $user->id && !empty($params['role'])) {
            $currentRole = $member->roles->first();
            if ($currentRole && $currentRole->name === 'admin' && $params['role'] !== 'admin') {
                throw new \Exception('You cannot remove your own admin role');
            }
        }

        DB::beginTransaction();
        try {
            // Update basic info
            if (!empty($params['name'])) {
                $member->name = $params['name'];
            }

            if (!empty($params['email']) && $params['email'] !== $member->email) {
                // Check if new email is already taken
                $emailExists = User::where('email', $params['email'])
                                  ->where('id', '!=', $member->id)
                                  ->exists();
                if ($emailExists) {
                    throw new \Exception('This email is already in use');
                }
                $member->email = $params['email'];
                $member->email_verified_at = null; // Require re-verification
            }

            if (isset($params['is_active'])) {
                $member->is_active = $params['is_active'];
            }

            $member->save();

            // Update role
            if (!empty($params['role'])) {
                $member->syncRoles([$params['role']]);
            }

            // Update branches
            if (isset($params['branch_ids'])) {
                $member->branches()->sync($params['branch_ids']);
            }

            // Update permissions
            if (isset($params['permissions'])) {
                $member->syncPermissions($params['permissions']);
            }

            DB::commit();

            // Clear cached permissions
            Cache::forget("user.{$member->id}.permissions");

            return [
                'success' => true,
                'data' => [
                    'user_id' => $member->id,
                    'updated_fields' => array_keys($params)
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove a team member.
     */
    protected function removeTeamMember(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $member = User::where('company_id', $user->company_id)
                      ->where('id', $params['user_id'])
                      ->firstOrFail();

        // Prevent self-removal
        if ($member->id === $user->id) {
            throw new \Exception('You cannot remove yourself');
        }

        // Check if this is the last admin
        if ($member->hasRole('admin')) {
            $adminCount = User::where('company_id', $user->company_id)
                             ->whereHas('roles', function ($q) {
                                 $q->where('name', 'admin');
                             })
                             ->where('is_active', true)
                             ->count();
            
            if ($adminCount <= 1) {
                throw new \Exception('Cannot remove the last admin');
            }
        }

        DB::beginTransaction();
        try {
            // Reassign items if specified
            if (!empty($params['reassign_to'])) {
                $reassignTo = User::where('company_id', $user->company_id)
                                 ->where('id', $params['reassign_to'])
                                 ->firstOrFail();

                // Reassign calls
                DB::table('call_portal_data')
                    ->where('assigned_to', $member->id)
                    ->update(['assigned_to' => $reassignTo->id]);

                // Reassign appointments
                Appointment::where('staff_id', $member->id)
                           ->update(['staff_id' => $reassignTo->id]);
            }

            // Soft delete the user
            $member->is_active = false;
            $member->deleted_at = now();
            $member->deleted_by = $user->id;
            $member->deletion_reason = $params['reason'] ?? null;
            $member->save();

            // Log the removal
            Log::info('Team member removed', [
                'removed_user_id' => $member->id,
                'removed_by' => $user->id,
                'reason' => $params['reason'] ?? 'Not specified'
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'user_id' => $member->id,
                    'reassigned_to' => $params['reassign_to'] ?? null
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get team performance metrics.
     */
    protected function getTeamPerformance(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $period = $params['period'] ?? 'month';
        
        // Build base query
        $query = User::where('company_id', $user->company_id)
                     ->where('is_active', true);

        if (!empty($params['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($params) {
                $q->where('branches.id', $params['branch_id']);
            });
        }

        if (!empty($params['user_ids'])) {
            $query->whereIn('id', $params['user_ids']);
        }

        $members = $query->get();

        // Calculate performance for each member
        $performance = $members->map(function ($member) use ($period) {
            return [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'role' => $member->roles->first()->name ?? 'member'
                ],
                'metrics' => $this->calculateMemberMetrics($member->id, $period)
            ];
        });

        // Calculate team totals
        $teamMetrics = $this->calculateTeamMetrics($user->company_id, $period, $params);

        return [
            'success' => true,
            'data' => [
                'period' => $period,
                'team_metrics' => $teamMetrics,
                'member_performance' => $performance,
                'top_performers' => $this->getTopPerformers($performance)
            ]
        ];
    }

    /**
     * Get current workload distribution.
     */
    protected function getWorkload(array $params): array
    {
        $user = Auth::guard('portal')->user();
        
        $query = User::where('company_id', $user->company_id)
                     ->where('is_active', true);

        if (!empty($params['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($params) {
                $q->where('branches.id', $params['branch_id']);
            });
        }

        $members = $query->get();

        $workload = $members->map(function ($member) {
            // Count assigned calls
            $openCalls = DB::table('call_portal_data')
                ->where('assigned_to', $member->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            // Count upcoming appointments
            $upcomingAppointments = Appointment::where('staff_id', $member->id)
                ->where('starts_at', '>=', now())
                ->where('starts_at', '<=', now()->addDays(7))
                ->count();

            // Calculate workload score (0-100)
            $workloadScore = min(100, ($openCalls * 10) + ($upcomingAppointments * 5));

            return [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name
                ],
                'open_calls' => $openCalls,
                'upcoming_appointments' => $upcomingAppointments,
                'workload_score' => $workloadScore,
                'capacity_status' => $this->getCapacityStatus($workloadScore)
            ];
        });

        $recommendations = [];
        if ($params['include_recommendations'] ?? true) {
            $recommendations = $this->getWorkloadRecommendations($workload);
        }

        return [
            'success' => true,
            'data' => [
                'workload_distribution' => $workload->sortByDesc('workload_score')->values(),
                'average_workload' => round($workload->avg('workload_score'), 1),
                'recommendations' => $recommendations
            ]
        ];
    }

    /**
     * Get available roles and permissions.
     */
    protected function getRoles(array $params): array
    {
        $roles = Role::with('permissions')->get();
        
        $transformedRoles = $roles->map(function ($role) {
            return [
                'name' => $role->name,
                'display_name' => ucfirst($role->name),
                'description' => $this->getRoleDescription($role->name),
                'permissions' => $role->permissions->pluck('name'),
                'is_system' => in_array($role->name, ['admin', 'manager', 'member'])
            ];
        });

        // Get all available permissions grouped by category
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return [
            'success' => true,
            'data' => [
                'roles' => $transformedRoles,
                'permissions' => $permissions->map(function ($group) {
                    return $group->pluck('name');
                })
            ]
        ];
    }

    /**
     * Update user permissions.
     */
    protected function updatePermissions(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $member = User::where('company_id', $user->company_id)
                      ->where('id', $params['user_id'])
                      ->firstOrFail();

        // Check permission to modify permissions
        if (!$user->hasPermission('team.manage_permissions')) {
            throw new \Exception('You do not have permission to manage team permissions');
        }

        $mode = $params['mode'] ?? 'replace';

        switch ($mode) {
            case 'replace':
                $member->syncPermissions($params['permissions']);
                break;
            case 'add':
                foreach ($params['permissions'] as $permission) {
                    $member->givePermissionTo($permission);
                }
                break;
            case 'remove':
                foreach ($params['permissions'] as $permission) {
                    $member->revokePermissionTo($permission);
                }
                break;
        }

        // Clear cached permissions
        Cache::forget("user.{$member->id}.permissions");

        return [
            'success' => true,
            'data' => [
                'user_id' => $member->id,
                'current_permissions' => $member->getAllPermissions()->pluck('name')
            ]
        ];
    }

    /**
     * Get team schedule.
     */
    protected function getSchedule(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $dateFrom = Carbon::parse($params['date_from'] ?? now()->startOfWeek());
        $dateTo = Carbon::parse($params['date_to'] ?? now()->endOfWeek());

        $query = User::where('company_id', $user->company_id)
                     ->where('is_active', true);

        if (!empty($params['user_ids'])) {
            $query->whereIn('id', $params['user_ids']);
        }

        $members = $query->get();

        $schedule = $members->map(function ($member) use ($dateFrom, $dateTo) {
            // Get appointments
            $appointments = Appointment::where('staff_id', $member->id)
                ->whereBetween('starts_at', [$dateFrom, $dateTo])
                ->get();

            // Get working hours (simplified - would need actual working hours model)
            $workingHours = $this->getMemberWorkingHours($member->id);

            // Calculate availability
            $availability = $this->calculateAvailability($member->id, $dateFrom, $dateTo, $appointments);

            return [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name
                ],
                'working_hours' => $workingHours,
                'appointments' => $appointments->map(function ($apt) {
                    return [
                        'id' => $apt->id,
                        'starts_at' => $apt->starts_at->toIso8601String(),
                        'ends_at' => $apt->ends_at->toIso8601String(),
                        'customer_name' => $apt->customer->name ?? 'Unknown'
                    ];
                }),
                'availability_percentage' => $availability['percentage'],
                'available_slots' => $availability['slots']
            ];
        });

        return [
            'success' => true,
            'data' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'schedule' => $schedule
            ]
        ];
    }

    /**
     * Automatically assign workload.
     */
    protected function assignWorkload(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $strategy = $params['strategy'] ?? 'least_busy';
        $assignments = [];

        DB::beginTransaction();
        try {
            // Get available team members
            $members = User::where('company_id', $user->company_id)
                          ->where('is_active', true)
                          ->get();

            if ($members->isEmpty()) {
                throw new \Exception('No active team members available');
            }

            // Get current workload for each member
            $memberWorkloads = $this->calculateCurrentWorkloads($members);

            foreach ($params['item_ids'] as $itemId) {
                // Select member based on strategy
                $selectedMember = $this->selectMemberByStrategy($members, $memberWorkloads, $strategy);

                // Assign based on task type
                if ($params['task_type'] === 'calls') {
                    DB::table('call_portal_data')
                        ->updateOrInsert(
                            ['call_id' => $itemId],
                            [
                                'assigned_to' => $selectedMember->id,
                                'assigned_by' => $user->id,
                                'assigned_at' => now(),
                                'status' => 'assigned'
                            ]
                        );
                } elseif ($params['task_type'] === 'appointments') {
                    Appointment::where('id', $itemId)
                               ->update(['staff_id' => $selectedMember->id]);
                }

                // Update workload tracking
                $memberWorkloads[$selectedMember->id]++;

                $assignments[] = [
                    'item_id' => $itemId,
                    'assigned_to' => $selectedMember->id,
                    'assigned_to_name' => $selectedMember->name
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'strategy_used' => $strategy,
                    'total_assigned' => count($assignments),
                    'assignments' => $assignments
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Helper: Get member performance metrics.
     */
    protected function getMemberPerformance(int $userId): array
    {
        $last30Days = now()->subDays(30);
        
        // Calls handled
        $callsHandled = DB::table('call_portal_data')
            ->where('assigned_to', $userId)
            ->where('created_at', '>=', $last30Days)
            ->count();

        // Calls completed
        $callsCompleted = DB::table('call_portal_data')
            ->where('assigned_to', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $last30Days)
            ->count();

        // Average resolution time
        $avgResolutionHours = DB::table('call_portal_data')
            ->where('assigned_to', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $last30Days)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours')
            ->value('avg_hours') ?? 0;

        return [
            'calls_handled' => $callsHandled,
            'calls_completed' => $callsCompleted,
            'completion_rate' => $callsHandled > 0 ? round(($callsCompleted / $callsHandled) * 100, 1) : 0,
            'avg_resolution_hours' => round($avgResolutionHours, 1)
        ];
    }

    /**
     * Helper: Get detailed performance metrics.
     */
    protected function getDetailedPerformance(int $userId): array
    {
        $performance = $this->getMemberPerformance($userId);
        
        // Add more detailed metrics
        $performance['appointments_completed'] = Appointment::where('staff_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $performance['customer_satisfaction'] = $this->calculateSatisfactionScore($userId);
        $performance['productivity_score'] = $this->calculateProductivityScore($userId);
        $performance['skill_ratings'] = $this->getSkillRatings($userId);

        return $performance;
    }

    /**
     * Helper: Calculate member metrics for a period.
     */
    protected function calculateMemberMetrics(int $userId, string $period): array
    {
        $dateFrom = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            default => now()->startOfMonth()
        };

        return [
            'calls_handled' => DB::table('call_portal_data')
                ->where('assigned_to', $userId)
                ->where('created_at', '>=', $dateFrom)
                ->count(),
            
            'appointments_completed' => Appointment::where('staff_id', $userId)
                ->where('status', 'completed')
                ->where('created_at', '>=', $dateFrom)
                ->count(),
            
            'average_rating' => 4.5, // Placeholder - would calculate from actual ratings
            
            'revenue_generated' => 0 // Placeholder - would calculate from actual data
        ];
    }

    /**
     * Helper: Calculate team-wide metrics.
     */
    protected function calculateTeamMetrics(int $companyId, string $period, array $params): array
    {
        $dateFrom = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            default => now()->startOfMonth()
        };

        $baseQuery = function($table) use ($companyId, $dateFrom, $params) {
            $query = DB::table($table)->where('company_id', $companyId)
                      ->where('created_at', '>=', $dateFrom);
            
            if (!empty($params['branch_id'])) {
                $query->where('branch_id', $params['branch_id']);
            }
            
            return $query;
        };

        return [
            'total_calls' => $baseQuery('calls')->count(),
            'total_appointments' => $baseQuery('appointments')->count(),
            'average_response_time' => '2.5 minutes', // Placeholder
            'team_efficiency_score' => 85 // Placeholder percentage
        ];
    }

    /**
     * Helper: Get capacity status based on workload score.
     */
    protected function getCapacityStatus(int $workloadScore): string
    {
        if ($workloadScore < 30) return 'available';
        if ($workloadScore < 70) return 'moderate';
        if ($workloadScore < 90) return 'busy';
        return 'overloaded';
    }

    /**
     * Helper: Get workload recommendations.
     */
    protected function getWorkloadRecommendations($workload): array
    {
        $recommendations = [];
        
        $overloaded = $workload->filter(fn($w) => $w['workload_score'] > 80);
        $available = $workload->filter(fn($w) => $w['workload_score'] < 30);

        if ($overloaded->isNotEmpty() && $available->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'redistribution',
                'message' => 'Consider redistributing workload from overloaded team members to those with capacity',
                'from_users' => $overloaded->pluck('user.id')->toArray(),
                'to_users' => $available->pluck('user.id')->toArray()
            ];
        }

        if ($workload->avg('workload_score') > 70) {
            $recommendations[] = [
                'type' => 'capacity',
                'message' => 'Team is operating at high capacity. Consider hiring additional staff.',
                'urgency' => 'high'
            ];
        }

        return $recommendations;
    }

    /**
     * Helper: Get role description.
     */
    protected function getRoleDescription(string $role): string
    {
        return match($role) {
            'admin' => 'Full system access and team management',
            'manager' => 'Team oversight and reporting capabilities',
            'member' => 'Standard team member with basic permissions',
            'viewer' => 'Read-only access to reports and data',
            default => 'Custom role with specific permissions'
        };
    }

    /**
     * Helper: Get member working hours.
     */
    protected function getMemberWorkingHours(int $userId): array
    {
        // Simplified - would need actual working hours from database
        return [
            'monday' => ['start' => '09:00', 'end' => '17:00'],
            'tuesday' => ['start' => '09:00', 'end' => '17:00'],
            'wednesday' => ['start' => '09:00', 'end' => '17:00'],
            'thursday' => ['start' => '09:00', 'end' => '17:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
            'saturday' => null,
            'sunday' => null
        ];
    }

    /**
     * Helper: Calculate availability.
     */
    protected function calculateAvailability(int $userId, Carbon $from, Carbon $to, $appointments): array
    {
        $totalWorkingHours = 40; // Simplified - would calculate from actual working hours
        $bookedHours = $appointments->sum(function ($apt) {
            return $apt->starts_at->diffInHours($apt->ends_at);
        });
        
        $availableHours = max(0, $totalWorkingHours - $bookedHours);
        $percentage = round(($availableHours / $totalWorkingHours) * 100, 1);

        // Generate available slots (simplified)
        $slots = [];
        // Would implement actual slot calculation based on working hours and appointments

        return [
            'percentage' => $percentage,
            'available_hours' => $availableHours,
            'slots' => $slots
        ];
    }

    /**
     * Helper: Calculate current workloads for team members.
     */
    protected function calculateCurrentWorkloads($members): array
    {
        $workloads = [];
        
        foreach ($members as $member) {
            $workloads[$member->id] = DB::table('call_portal_data')
                ->where('assigned_to', $member->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();
        }
        
        return $workloads;
    }

    /**
     * Helper: Select member based on assignment strategy.
     */
    protected function selectMemberByStrategy($members, array $workloads, string $strategy)
    {
        switch ($strategy) {
            case 'least_busy':
                // Find member with lowest workload
                $minWorkload = min($workloads);
                $candidateIds = array_keys($workloads, $minWorkload);
                $selectedId = $candidateIds[array_rand($candidateIds)];
                return $members->find($selectedId);
                
            case 'round_robin':
                // Simple round-robin - would need to track last assignment
                return $members->random();
                
            case 'skills_based':
                // Would implement skill matching logic
                return $members->first();
                
            default:
                return $members->first();
        }
    }

    /**
     * Helper: Calculate satisfaction score.
     */
    protected function calculateSatisfactionScore(int $userId): float
    {
        // Placeholder - would calculate from actual customer feedback
        return 4.5;
    }

    /**
     * Helper: Calculate productivity score.
     */
    protected function calculateProductivityScore(int $userId): int
    {
        // Placeholder - would calculate from various metrics
        return 85;
    }

    /**
     * Helper: Get skill ratings.
     */
    protected function getSkillRatings(int $userId): array
    {
        // Placeholder - would get from skill assessment system
        return [
            'communication' => 4.5,
            'problem_solving' => 4.0,
            'technical_knowledge' => 4.2,
            'customer_service' => 4.8
        ];
    }

    /**
     * Helper: Get top performers.
     */
    protected function getTopPerformers($performance): array
    {
        return $performance->sortByDesc('metrics.calls_handled')
                          ->take(3)
                          ->map(function ($p) {
                              return [
                                  'user' => $p['user'],
                                  'score' => $p['metrics']['calls_handled']
                              ];
                          })
                          ->values()
                          ->toArray();
    }
}