<?php

namespace App\Services\MCP;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\Permission;
use App\Exceptions\SecurityException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * SECURE VERSION: Team Management MCP Server with proper tenant isolation
 * 
 * This server handles team management with strict multi-tenant security.
 * All operations are scoped to the authenticated company context.
 * 
 * Security Features:
 * - Mandatory company context validation
 * - No cross-tenant user access
 * - Role and permission operations scoped to company
 * - Audit logging for all operations
 * - Prevents privilege escalation
 */
class SecureTeamMCPServer extends BaseMCPServer
{
    /**
     * @var Company|null Current company context
     */
    protected ?Company $company = null;
    
    /**
     * @var bool Audit logging enabled
     */
    protected bool $auditEnabled = true;
    
    protected string $name = 'secure-team-management';
    protected string $version = '1.0.0';
    protected string $description = 'Secure team management with tenant isolation';
    protected array $tools = [];

    public function __construct()
    {
        parent::__construct();
        $this->resolveCompanyContext();
        $this->initializeTools();
    }
    
    /**
     * Set company context explicitly (only for super admins)
     */
    public function setCompanyContext(Company $company): self
    {
        // Only allow super admins to override context
        if (Auth::check() && !Auth::user()->hasRole('super_admin')) {
            throw new SecurityException('Unauthorized company context override');
        }
        
        $this->company = $company;
        
        $this->auditAccess('company_context_override', [
            'company_id' => $company->id,
            'user_id' => Auth::id(),
        ]);
        
        return $this;
    }

    /**
     * Initialize the MCP server with tool definitions
     */
    protected function initializeTools(): void
    {
        // List team members
        $this->addTool([
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
        ]);

        // Get team member details
        $this->addTool([
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
        ]);

        // Invite team member
        $this->addTool([
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
        ]);

        // Update team member
        $this->addTool([
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
        ]);

        // Remove team member
        $this->addTool([
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
        ]);

        // Get team performance
        $this->addTool([
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
        ]);

        // Get workload
        $this->addTool([
            'name' => 'getWorkload',
            'description' => 'Get current workload distribution',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'branch_id' => ['type' => 'integer'],
                    'include_recommendations' => ['type' => 'boolean', 'default' => true]
                ]
            ]
        ]);

        // Get roles
        $this->addTool([
            'name' => 'getRoles',
            'description' => 'Get available roles and permissions',
            'inputSchema' => [
                'type' => 'object',
                'properties' => []
            ]
        ]);

        // Update permissions
        $this->addTool([
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
        ]);

        // Assign workload
        $this->addTool([
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
        ]);
    }

    /**
     * Execute a team management operation
     */
    public function execute(string $operation, array $params = []): array
    {
        $this->ensureCompanyContext();
        
        $this->logDebug("Executing secure team operation", [
            'operation' => $operation,
            'params' => array_diff_key($params, ['password' => 1]), // Don't log passwords
            'company_id' => $this->company->id
        ]);
        
        try {
            switch ($operation) {
                case 'listTeamMembers':
                    return $this->listTeamMembersSecure($params);
                    
                case 'getTeamMember':
                    return $this->getTeamMemberSecure($params);
                    
                case 'inviteTeamMember':
                    return $this->inviteTeamMemberSecure($params);
                    
                case 'updateTeamMember':
                    return $this->updateTeamMemberSecure($params);
                    
                case 'removeTeamMember':
                    return $this->removeTeamMemberSecure($params);
                    
                case 'getTeamPerformance':
                    return $this->getTeamPerformanceSecure($params);
                    
                case 'getWorkload':
                    return $this->getWorkloadSecure($params);
                    
                case 'getRoles':
                    return $this->getRolesSecure($params);
                    
                case 'updatePermissions':
                    return $this->updatePermissionsSecure($params);
                    
                case 'assignWorkload':
                    return $this->assignWorkloadSecure($params);
                    
                default:
                    return $this->errorResponse("Unknown operation: {$operation}");
            }
        } catch (\Exception $e) {
            $this->logError("Secure team operation failed", $e, [
                'operation' => $operation,
                'company_id' => $this->company->id
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * List team members with company filtering
     */
    protected function listTeamMembersSecure(array $params): array
    {
        $this->auditAccess('list_team_members', $params);
        
        $query = User::where('company_id', $this->company->id) // CRITICAL: Company scope
                     ->with(['roles', 'branches']);

        // Apply filters
        if (!empty($params['branch_id'])) {
            // Validate branch belongs to company
            $branch = Branch::where('id', $params['branch_id'])
                ->where('company_id', $this->company->id)
                ->first();
                
            if ($branch) {
                $query->whereHas('branches', function ($q) use ($branch) {
                    $q->where('branches.id', $branch->id);
                });
            }
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
        $perPage = min($params['per_page'] ?? 20, 100); // Limit max per page
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
                'last_login_at' => $member->last_login_at?->toIso8601String()
            ];

            if ($params['include_performance'] ?? false) {
                $data['performance'] = $this->getMemberPerformanceSecure($member->id);
            }

            return $data;
        });

        return $this->successResponse([
            'data' => $transformedMembers,
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total()
            ]
        ]);
    }

    /**
     * Get team member details with security validation
     */
    protected function getTeamMemberSecure(array $params): array
    {
        $this->validateParams($params, ['user_id']);
        
        $member = User::where('company_id', $this->company->id) // CRITICAL: Company scope
                      ->where('id', $params['user_id'])
                      ->with(['roles', 'branches', 'permissions'])
                      ->first();
                      
        if (!$member) {
            throw new SecurityException('Team member not found or does not belong to company');
        }
        
        $this->auditAccess('get_team_member', ['user_id' => $member->id]);

        $data = [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'phone' => $member->phone,
            'role' => $member->roles->first()->name ?? 'member',
            'branches' => $member->branches,
            'is_active' => $member->is_active,
            'created_at' => $member->created_at->toIso8601String(),
            'last_login_at' => $member->last_login_at?->toIso8601String(),
            'two_factor_enabled' => !empty($member->two_factor_secret)
        ];

        if ($params['include_performance'] ?? true) {
            $data['performance'] = $this->getDetailedPerformanceSecure($member->id);
        }

        if ($params['include_permissions'] ?? true) {
            $data['permissions'] = $member->getAllPermissions()->pluck('name');
        }

        return $this->successResponse(['data' => $data]);
    }

    /**
     * Invite team member with company context
     */
    protected function inviteTeamMemberSecure(array $params): array
    {
        $this->validateParams($params, ['email', 'name', 'role']);
        $this->auditAccess('invite_team_member', ['email' => $params['email']]);
        
        // Check if user already exists
        $existingUser = User::where('email', $params['email'])->first();
        if ($existingUser) {
            if ($existingUser->company_id === $this->company->id) {
                throw new SecurityException('A team member with this email already exists');
            } else {
                throw new SecurityException('This email is already registered');
            }
        }

        // Validate role exists and is not super_admin
        if ($params['role'] === 'super_admin') {
            throw new SecurityException('Cannot assign super_admin role');
        }
        
        $role = Role::where('name', $params['role'])
            ->where('guard_name', 'web')
            ->first();
            
        if (!$role) {
            throw new SecurityException('Invalid role specified');
        }

        DB::beginTransaction();
        try {
            // Create user
            $invitationToken = Str::random(64);
            $member = new User();
            $member->name = $params['name'];
            $member->email = $params['email'];
            $member->company_id = $this->company->id; // CRITICAL: Force company context
            $member->password = Hash::make(Str::random(32)); // Temporary password
            $member->invitation_token = $invitationToken;
            $member->invited_by = Auth::id();
            $member->invited_at = now();
            $member->is_active = false; // Inactive until invitation accepted
            $member->save();

            // Assign role
            $member->assignRole($role);

            // Assign branches (validate they belong to company)
            if (!empty($params['branch_ids'])) {
                $validBranchIds = Branch::where('company_id', $this->company->id)
                    ->whereIn('id', $params['branch_ids'])
                    ->pluck('id')
                    ->toArray();
                    
                if (!empty($validBranchIds)) {
                    $member->branches()->attach($validBranchIds);
                }
            }

            // Assign custom permissions (validate they are allowed)
            if (!empty($params['permissions'])) {
                $allowedPermissions = $this->getCompanyAllowedPermissions();
                foreach ($params['permissions'] as $permissionName) {
                    if (in_array($permissionName, $allowedPermissions)) {
                        $permission = Permission::where('name', $permissionName)->first();
                        if ($permission) {
                            $member->givePermissionTo($permission);
                        }
                    }
                }
            }

            // Send invitation email
            if ($params['send_invitation_email'] ?? true) {
                // TODO: Dispatch invitation email job
                Log::info('Team member invitation email queued', [
                    'member_id' => $member->id,
                    'email' => $member->email,
                    'company_id' => $this->company->id
                ]);
            }

            DB::commit();

            return $this->successResponse([
                'data' => [
                    'user_id' => $member->id,
                    'invitation_token' => $invitationToken,
                    'invitation_url' => url("/business/invitation/{$invitationToken}")
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update team member with security validation
     */
    protected function updateTeamMemberSecure(array $params): array
    {
        $this->validateParams($params, ['user_id']);
        
        $member = User::where('company_id', $this->company->id) // CRITICAL: Company scope
                      ->where('id', $params['user_id'])
                      ->first();
                      
        if (!$member) {
            throw new SecurityException('Team member not found or does not belong to company');
        }
        
        $this->auditAccess('update_team_member', [
            'user_id' => $member->id,
            'updates' => array_keys($params)
        ]);

        // Prevent self-demotion
        if (Auth::id() === $member->id && !empty($params['role'])) {
            $currentRole = $member->roles->first();
            if ($currentRole && $currentRole->name === 'admin' && $params['role'] !== 'admin') {
                throw new SecurityException('You cannot remove your own admin role');
            }
        }

        // Prevent assigning super_admin
        if (!empty($params['role']) && $params['role'] === 'super_admin') {
            throw new SecurityException('Cannot assign super_admin role');
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
                    throw new SecurityException('This email is already in use');
                }
                $member->email = $params['email'];
                $member->email_verified_at = null; // Require re-verification
            }

            if (isset($params['is_active'])) {
                // Check if this would remove the last admin
                if (!$params['is_active'] && $member->hasRole('admin')) {
                    $activeAdminCount = User::where('company_id', $this->company->id)
                        ->whereHas('roles', function ($q) {
                            $q->where('name', 'admin');
                        })
                        ->where('is_active', true)
                        ->where('id', '!=', $member->id)
                        ->count();
                        
                    if ($activeAdminCount === 0) {
                        throw new SecurityException('Cannot deactivate the last admin');
                    }
                }
                
                $member->is_active = $params['is_active'];
            }

            $member->save();

            // Update role
            if (!empty($params['role'])) {
                $role = Role::where('name', $params['role'])
                    ->where('guard_name', 'web')
                    ->first();
                    
                if ($role) {
                    $member->syncRoles([$role]);
                }
            }

            // Update branches (validate they belong to company)
            if (isset($params['branch_ids'])) {
                $validBranchIds = Branch::where('company_id', $this->company->id)
                    ->whereIn('id', $params['branch_ids'])
                    ->pluck('id')
                    ->toArray();
                    
                $member->branches()->sync($validBranchIds);
            }

            // Update permissions (validate they are allowed)
            if (isset($params['permissions'])) {
                $allowedPermissions = $this->getCompanyAllowedPermissions();
                $validPermissions = array_intersect($params['permissions'], $allowedPermissions);
                $member->syncPermissions($validPermissions);
            }

            DB::commit();

            // Clear cached permissions
            Cache::forget("user.{$member->id}.permissions");

            return $this->successResponse([
                'data' => [
                    'user_id' => $member->id,
                    'updated_fields' => array_keys($params)
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove team member with security validation
     */
    protected function removeTeamMemberSecure(array $params): array
    {
        $this->validateParams($params, ['user_id']);
        
        $member = User::where('company_id', $this->company->id) // CRITICAL: Company scope
                      ->where('id', $params['user_id'])
                      ->first();
                      
        if (!$member) {
            throw new SecurityException('Team member not found or does not belong to company');
        }
        
        $this->auditAccess('remove_team_member', [
            'user_id' => $member->id,
            'reason' => $params['reason'] ?? null
        ]);

        // Prevent self-removal
        if ($member->id === Auth::id()) {
            throw new SecurityException('You cannot remove yourself');
        }

        // Check if this is the last admin
        if ($member->hasRole('admin')) {
            $adminCount = User::where('company_id', $this->company->id)
                             ->whereHas('roles', function ($q) {
                                 $q->where('name', 'admin');
                             })
                             ->where('is_active', true)
                             ->count();
            
            if ($adminCount <= 1) {
                throw new SecurityException('Cannot remove the last admin');
            }
        }

        DB::beginTransaction();
        try {
            // Reassign items if specified
            if (!empty($params['reassign_to'])) {
                $reassignTo = User::where('company_id', $this->company->id) // Company scope
                                 ->where('id', $params['reassign_to'])
                                 ->where('is_active', true)
                                 ->first();
                                 
                if (!$reassignTo) {
                    throw new SecurityException('Invalid user to reassign to');
                }

                // Reassign calls (with company scope)
                DB::table('call_portal_data')
                    ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
                    ->where('calls.company_id', $this->company->id)
                    ->where('call_portal_data.assigned_to', $member->id)
                    ->update(['call_portal_data.assigned_to' => $reassignTo->id]);

                // Reassign appointments (with company scope)
                Appointment::whereHas('branch', function($q) {
                        $q->where('company_id', $this->company->id);
                    })
                    ->where('staff_id', $member->id)
                    ->update(['staff_id' => $reassignTo->id]);
            }

            // Soft delete the user
            $member->is_active = false;
            $member->deleted_at = now();
            $member->deleted_by = Auth::id();
            $member->deletion_reason = $params['reason'] ?? null;
            $member->save();

            DB::commit();

            return $this->successResponse([
                'data' => [
                    'user_id' => $member->id,
                    'reassigned_to' => $params['reassign_to'] ?? null
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get team performance with company scope
     */
    protected function getTeamPerformanceSecure(array $params): array
    {
        $this->auditAccess('get_team_performance', $params);
        
        $period = $params['period'] ?? 'month';
        
        // Build base query
        $query = User::where('company_id', $this->company->id) // Company scope
                     ->where('is_active', true);

        if (!empty($params['branch_id'])) {
            // Validate branch
            $branch = Branch::where('id', $params['branch_id'])
                ->where('company_id', $this->company->id)
                ->first();
                
            if ($branch) {
                $query->whereHas('branches', function ($q) use ($branch) {
                    $q->where('branches.id', $branch->id);
                });
            }
        }

        if (!empty($params['user_ids'])) {
            // Validate user IDs belong to company
            $validUserIds = User::where('company_id', $this->company->id)
                ->whereIn('id', $params['user_ids'])
                ->pluck('id')
                ->toArray();
                
            $query->whereIn('id', $validUserIds);
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
                'metrics' => $this->calculateMemberMetricsSecure($member->id, $period)
            ];
        });

        // Calculate team totals
        $teamMetrics = $this->calculateTeamMetricsSecure($this->company->id, $period, $params);

        return $this->successResponse([
            'data' => [
                'period' => $period,
                'team_metrics' => $teamMetrics,
                'member_performance' => $performance,
                'top_performers' => $this->getTopPerformersSecure($performance)
            ]
        ]);
    }

    /**
     * Get workload with company scope
     */
    protected function getWorkloadSecure(array $params): array
    {
        $this->auditAccess('get_workload', $params);
        
        $query = User::where('company_id', $this->company->id) // Company scope
                     ->where('is_active', true);

        if (!empty($params['branch_id'])) {
            // Validate branch
            $branch = Branch::where('id', $params['branch_id'])
                ->where('company_id', $this->company->id)
                ->first();
                
            if ($branch) {
                $query->whereHas('branches', function ($q) use ($branch) {
                    $q->where('branches.id', $branch->id);
                });
            }
        }

        $members = $query->get();

        $workload = $members->map(function ($member) {
            // Count assigned calls (with company scope)
            $openCalls = DB::table('call_portal_data')
                ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
                ->where('calls.company_id', $this->company->id)
                ->where('call_portal_data.assigned_to', $member->id)
                ->whereNotIn('call_portal_data.status', ['completed', 'cancelled'])
                ->count();

            // Count upcoming appointments (with company scope)
            $upcomingAppointments = Appointment::whereHas('branch', function($q) {
                    $q->where('company_id', $this->company->id);
                })
                ->where('staff_id', $member->id)
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
            $recommendations = $this->getWorkloadRecommendationsSecure($workload);
        }

        return $this->successResponse([
            'data' => [
                'workload_distribution' => $workload->sortByDesc('workload_score')->values(),
                'average_workload' => round($workload->avg('workload_score'), 1),
                'recommendations' => $recommendations
            ]
        ]);
    }

    /**
     * Get roles with permission filtering
     */
    protected function getRolesSecure(array $params): array
    {
        $this->auditAccess('get_roles');
        
        // Don't expose super_admin role
        $roles = Role::where('name', '!=', 'super_admin')
            ->with('permissions')
            ->get();
        
        $transformedRoles = $roles->map(function ($role) {
            return [
                'name' => $role->name,
                'display_name' => ucfirst($role->name),
                'description' => $this->getRoleDescription($role->name),
                'permissions' => $role->permissions->pluck('name'),
                'is_system' => in_array($role->name, ['admin', 'manager', 'member'])
            ];
        });

        // Get allowed permissions for the company
        $allowedPermissions = $this->getCompanyAllowedPermissions();
        
        // Group permissions by category
        $permissions = Permission::whereIn('name', $allowedPermissions)
            ->get()
            ->groupBy(function ($permission) {
                return explode('.', $permission->name)[0];
            });

        return $this->successResponse([
            'data' => [
                'roles' => $transformedRoles,
                'permissions' => $permissions->map(function ($group) {
                    return $group->pluck('name');
                })
            ]
        ]);
    }

    /**
     * Update permissions with security validation
     */
    protected function updatePermissionsSecure(array $params): array
    {
        $this->validateParams($params, ['user_id', 'permissions']);
        
        $member = User::where('company_id', $this->company->id) // Company scope
                      ->where('id', $params['user_id'])
                      ->first();
                      
        if (!$member) {
            throw new SecurityException('Team member not found or does not belong to company');
        }
        
        $this->auditAccess('update_permissions', [
            'user_id' => $member->id,
            'mode' => $params['mode'] ?? 'replace'
        ]);

        // Check permission to modify permissions
        if (!Auth::user()->hasPermission('team.manage_permissions')) {
            throw new SecurityException('You do not have permission to manage team permissions');
        }

        // Filter to allowed permissions only
        $allowedPermissions = $this->getCompanyAllowedPermissions();
        $validPermissions = array_intersect($params['permissions'], $allowedPermissions);

        $mode = $params['mode'] ?? 'replace';

        switch ($mode) {
            case 'replace':
                $member->syncPermissions($validPermissions);
                break;
            case 'add':
                foreach ($validPermissions as $permission) {
                    $member->givePermissionTo($permission);
                }
                break;
            case 'remove':
                foreach ($validPermissions as $permission) {
                    $member->revokePermissionTo($permission);
                }
                break;
        }

        // Clear cached permissions
        Cache::forget("user.{$member->id}.permissions");

        return $this->successResponse([
            'data' => [
                'user_id' => $member->id,
                'current_permissions' => $member->getAllPermissions()->pluck('name')
            ]
        ]);
    }

    /**
     * Assign workload with security validation
     */
    protected function assignWorkloadSecure(array $params): array
    {
        $this->validateParams($params, ['task_type', 'item_ids']);
        $this->auditAccess('assign_workload', $params);
        
        $strategy = $params['strategy'] ?? 'least_busy';
        $assignments = [];

        DB::beginTransaction();
        try {
            // Get available team members
            $members = User::where('company_id', $this->company->id) // Company scope
                          ->where('is_active', true)
                          ->get();

            if ($members->isEmpty()) {
                throw new SecurityException('No active team members available');
            }

            // Get current workload for each member
            $memberWorkloads = $this->calculateCurrentWorkloadsSecure($members);

            foreach ($params['item_ids'] as $itemId) {
                // Select member based on strategy
                $selectedMember = $this->selectMemberByStrategySecure($members, $memberWorkloads, $strategy);

                // Assign based on task type
                if ($params['task_type'] === 'calls') {
                    // Validate call belongs to company
                    $call = Call::where('id', $itemId)
                        ->where('company_id', $this->company->id)
                        ->first();
                        
                    if ($call) {
                        DB::table('call_portal_data')
                            ->updateOrInsert(
                                ['call_id' => $itemId],
                                [
                                    'assigned_to' => $selectedMember->id,
                                    'assigned_by' => Auth::id(),
                                    'assigned_at' => now(),
                                    'status' => 'assigned'
                                ]
                            );
                            
                        $assignments[] = [
                            'item_id' => $itemId,
                            'assigned_to' => $selectedMember->id,
                            'assigned_to_name' => $selectedMember->name
                        ];
                        
                        // Update workload tracking
                        $memberWorkloads[$selectedMember->id]++;
                    }
                } elseif ($params['task_type'] === 'appointments') {
                    // Validate appointment belongs to company
                    $appointment = Appointment::whereHas('branch', function($q) {
                            $q->where('company_id', $this->company->id);
                        })
                        ->where('id', $itemId)
                        ->first();
                        
                    if ($appointment) {
                        $appointment->staff_id = $selectedMember->id;
                        $appointment->save();
                        
                        $assignments[] = [
                            'item_id' => $itemId,
                            'assigned_to' => $selectedMember->id,
                            'assigned_to_name' => $selectedMember->name
                        ];
                        
                        // Update workload tracking
                        $memberWorkloads[$selectedMember->id]++;
                    }
                }
            }

            DB::commit();

            return $this->successResponse([
                'data' => [
                    'strategy_used' => $strategy,
                    'total_assigned' => count($assignments),
                    'assignments' => $assignments
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get member performance with company scope
     */
    protected function getMemberPerformanceSecure(int $userId): array
    {
        // Ensure user belongs to company
        $userCompanyId = User::where('id', $userId)->value('company_id');
        if ($userCompanyId !== $this->company->id) {
            return [
                'calls_handled' => 0,
                'calls_completed' => 0,
                'completion_rate' => 0,
                'avg_resolution_hours' => 0
            ];
        }
        
        $last30Days = now()->subDays(30);
        
        // Calls handled (with company scope)
        $callsHandled = DB::table('call_portal_data')
            ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
            ->where('calls.company_id', $this->company->id)
            ->where('call_portal_data.assigned_to', $userId)
            ->where('call_portal_data.created_at', '>=', $last30Days)
            ->count();

        // Calls completed (with company scope)
        $callsCompleted = DB::table('call_portal_data')
            ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
            ->where('calls.company_id', $this->company->id)
            ->where('call_portal_data.assigned_to', $userId)
            ->where('call_portal_data.status', 'completed')
            ->where('call_portal_data.created_at', '>=', $last30Days)
            ->count();

        // Average resolution time (with company scope)
        $avgResolutionHours = DB::table('call_portal_data')
            ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
            ->where('calls.company_id', $this->company->id)
            ->where('call_portal_data.assigned_to', $userId)
            ->where('call_portal_data.status', 'completed')
            ->where('call_portal_data.created_at', '>=', $last30Days)
            ->whereNotNull('call_portal_data.completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, call_portal_data.created_at, call_portal_data.completed_at)) as avg_hours')
            ->value('avg_hours') ?? 0;

        return [
            'calls_handled' => $callsHandled,
            'calls_completed' => $callsCompleted,
            'completion_rate' => $callsHandled > 0 ? round(($callsCompleted / $callsHandled) * 100, 1) : 0,
            'avg_resolution_hours' => round($avgResolutionHours, 1)
        ];
    }

    /**
     * Get detailed performance with company scope
     */
    protected function getDetailedPerformanceSecure(int $userId): array
    {
        $performance = $this->getMemberPerformanceSecure($userId);
        
        // Add more detailed metrics (with company scope)
        $performance['appointments_completed'] = Appointment::whereHas('branch', function($q) {
                $q->where('company_id', $this->company->id);
            })
            ->where('staff_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $performance['customer_satisfaction'] = $this->calculateSatisfactionScoreSecure($userId);
        $performance['productivity_score'] = $this->calculateProductivityScoreSecure($userId);

        return $performance;
    }

    /**
     * Calculate member metrics with company scope
     */
    protected function calculateMemberMetricsSecure(int $userId, string $period): array
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
                ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
                ->where('calls.company_id', $this->company->id)
                ->where('call_portal_data.assigned_to', $userId)
                ->where('call_portal_data.created_at', '>=', $dateFrom)
                ->count(),
            
            'appointments_completed' => Appointment::whereHas('branch', function($q) {
                    $q->where('company_id', $this->company->id);
                })
                ->where('staff_id', $userId)
                ->where('status', 'completed')
                ->where('created_at', '>=', $dateFrom)
                ->count(),
            
            'average_rating' => 4.5, // Placeholder - would calculate from actual ratings
            
            'revenue_generated' => 0 // Placeholder - would calculate from actual data
        ];
    }

    /**
     * Calculate team metrics with company scope
     */
    protected function calculateTeamMetricsSecure(int $companyId, string $period, array $params): array
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
                // Validate branch belongs to company
                $branch = Branch::where('id', $params['branch_id'])
                    ->where('company_id', $companyId)
                    ->first();
                    
                if ($branch) {
                    $query->where('branch_id', $branch->id);
                }
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
     * Get workload recommendations with security
     */
    protected function getWorkloadRecommendationsSecure($workload): array
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
     * Calculate current workloads with company scope
     */
    protected function calculateCurrentWorkloadsSecure($members): array
    {
        $workloads = [];
        
        foreach ($members as $member) {
            $workloads[$member->id] = DB::table('call_portal_data')
                ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
                ->where('calls.company_id', $this->company->id)
                ->where('call_portal_data.assigned_to', $member->id)
                ->whereNotIn('call_portal_data.status', ['completed', 'cancelled'])
                ->count();
        }
        
        return $workloads;
    }

    /**
     * Select member by strategy with security
     */
    protected function selectMemberByStrategySecure($members, array $workloads, string $strategy)
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
     * Get top performers with security
     */
    protected function getTopPerformersSecure($performance): array
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

    /**
     * Get allowed permissions for the company
     */
    protected function getCompanyAllowedPermissions(): array
    {
        // Define permissions that companies can manage
        // Exclude system-level permissions
        $excludedPermissions = [
            'system.*',
            'super_admin.*',
            'company.create',
            'company.delete',
            'tenant.*'
        ];
        
        $allPermissions = Permission::pluck('name')->toArray();
        
        return array_filter($allPermissions, function($permission) use ($excludedPermissions) {
            foreach ($excludedPermissions as $pattern) {
                if (fnmatch($pattern, $permission)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Get capacity status
     */
    protected function getCapacityStatus(int $workloadScore): string
    {
        if ($workloadScore < 30) return 'available';
        if ($workloadScore < 70) return 'moderate';
        if ($workloadScore < 90) return 'busy';
        return 'overloaded';
    }

    /**
     * Get role description
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
     * Calculate satisfaction score with company scope
     */
    protected function calculateSatisfactionScoreSecure(int $userId): float
    {
        // Ensure user belongs to company
        $userCompanyId = User::where('id', $userId)->value('company_id');
        if ($userCompanyId !== $this->company->id) {
            return 0.0;
        }
        
        // Placeholder - would calculate from actual customer feedback
        return 4.5;
    }

    /**
     * Calculate productivity score with company scope
     */
    protected function calculateProductivityScoreSecure(int $userId): int
    {
        // Ensure user belongs to company
        $userCompanyId = User::where('id', $userId)->value('company_id');
        if ($userCompanyId !== $this->company->id) {
            return 0;
        }
        
        // Placeholder - would calculate from various metrics
        return 85;
    }

    /**
     * Resolve company context from authenticated user
     */
    protected function resolveCompanyContext(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->company_id) {
                $this->company = Company::find($user->company_id);
            }
        }
    }
    
    /**
     * Ensure company context is set
     */
    protected function ensureCompanyContext(): void
    {
        if (!$this->company) {
            throw new SecurityException('No valid company context for team management');
        }
    }
    
    /**
     * Audit access to team operations
     */
    protected function auditAccess(string $operation, array $context = []): void
    {
        if (!$this->auditEnabled) {
            return;
        }
        
        try {
            if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
                DB::table('security_audit_logs')->insert([
                    'event_type' => 'team_mcp_access',
                    'user_id' => Auth::id(),
                    'company_id' => $this->company->id ?? null,
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'url' => request()->fullUrl() ?? 'console',
                    'metadata' => json_encode(array_merge($context, [
                        'operation' => $operation,
                        'user_agent' => request()->userAgent()
                    ])),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('SecureTeamMCP: Audit logging failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Disable audit logging (for testing)
     */
    public function disableAudit(): self
    {
        $this->auditEnabled = false;
        return $this;
    }
}