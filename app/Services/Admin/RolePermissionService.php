<?php

namespace App\Services\Admin;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionService
{
    /**
     * Core system roles that cannot be deleted.
     */
    protected array $protectedRoles = [
        'admin',
        'director',
        'evaluator',
        'referee',
    ];

    /**
     * Standard module grouping for granular permissions.
     */
    protected array $permissionModules = [
        'User Management' => [
            'view users',
            'create users',
            'edit users',
            'delete users',
        ],
        'Camp Operations' => [
            'view camps',
            'create camps',
            'edit camps',
            'delete camps',
        ],
        'Schedules & Game Slots' => [
            'view schedules',
            'create schedules',
            'edit schedules',
            'delete schedules',
            'view game-slots',
            'create game-slots',
            'edit game-slots',
            'delete game-slots',
        ],
        'Referee Crews' => [
            'view crews',
            'create crews',
            'edit crews',
            'delete crews',
        ],
        'Monetization & Coupons' => [
            'view payments',
            'export payments',
            'view coupons',
            'create coupons',
            'edit coupons',
            'delete coupons',
        ],
        'Content Management (CMS)' => [
            'manage cms',
        ],
        'System Governance' => [
            'manage settings',
            'view logs',
        ],
    ];

    /**
     * Get all structured data for the Roles & Permissions Hub screen.
     */
    public function getRolesHubData(string $search = ''): array
    {
        $this->ensurePermissionsExist();

        $query = Role::with('permissions')->withCount('users');

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $roles = $query->orderByRaw("FIELD(name, 'admin', 'director', 'evaluator', 'referee') DESC")
            ->orderBy('name', 'asc')
            ->get()
            ->map(function (Role $role) {
                return [
                    'id'                => $role->id,
                    'name'              => $role->name,
                    'guard_name'        => $role->guard_name,
                    'users_count'       => $role->users_count,
                    'is_system'         => in_array($role->name, $this->protectedRoles),
                    'is_admin'          => $role->name === 'admin',
                    'permissions'       => $role->permissions->pluck('name')->toArray(),
                    'permissions_count' => $role->permissions->count(),
                    'created_at'        => $role->created_at?->format('Y-m-d H:i') ?? 'N/A',
                ];
            });

        // Structure permissions by module for catalog and editor
        $allPermissions        = Permission::where('guard_name', 'web')->with('roles')->get();
        $groupedPermissions    = [];
        $trackedPermNames      = [];
        $allPermissionsCatalog = [];

        foreach ($this->permissionModules as $module => $permNames) {
            $items = [];
            foreach ($permNames as $name) {
                $perm = $allPermissions->firstWhere('name', $name);
                if ($perm) {
                    $trackedPermNames[] = $name;
                    $permData = [
                        'id'          => $perm->id,
                        'name'        => $perm->name,
                        'label'       => ucwords(str_replace(['-', '_'], ' ', $perm->name)),
                        'module'      => $module,
                        'is_system'   => true,
                        'roles_count' => $perm->roles->count(),
                        'roles'       => $perm->roles->pluck('name')->toArray(),
                    ];
                    $items[]                 = $permData;
                    $allPermissionsCatalog[] = $permData;
                }
            }
            if (!empty($items)) {
                $groupedPermissions[] = [
                    'module'      => $module,
                    'permissions' => $items,
                ];
            }
        }

        // Dynamically group custom / newly-created permissions
        $customPerms = $allPermissions->reject(fn($p) => in_array($p->name, $trackedPermNames));
        if ($customPerms->isNotEmpty()) {
            $customItems = $customPerms->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'label'       => ucwords(str_replace(['-', '_'], ' ', $p->name)),
                'module'      => 'Custom & Additional Permissions',
                'is_system'   => false,
                'roles_count' => $p->roles->count(),
                'roles'       => $p->roles->pluck('name')->toArray(),
            ])->values()->toArray();

            $groupedPermissions[] = [
                'module'      => 'Custom & Additional Permissions',
                'permissions' => $customItems,
            ];

            foreach ($customItems as $cItem) {
                $allPermissionsCatalog[] = $cItem;
            }
        }

        // Available modules for dropdowns
        $availableModules   = array_keys($this->permissionModules);
        $availableModules[] = 'Custom & Additional Permissions';

        // Summary KPI Metrics
        $metrics = [
            'total_roles'        => $roles->count(),
            'system_roles'       => $roles->where('is_system', true)->count(),
            'custom_roles'       => $roles->where('is_system', false)->count(),
            'total_permissions'  => $allPermissions->count(),
            'system_permissions' => count($trackedPermNames),
            'custom_permissions' => $customPerms->count(),
            'total_assigned'     => $roles->sum('users_count'),
        ];

        return [
            'roles'                 => $roles,
            'groupedPermissions'    => $groupedPermissions,
            'allPermissionsCatalog' => $allPermissionsCatalog,
            'availableModules'      => $availableModules,
            'metrics'               => $metrics,
            'filters'               => [
                'search' => $search,
            ],
        ];
    }

    /**
     * Create a new role and sync initial permissions.
     */
    public function createRole(array $data): Role
    {
        $roleName = strtolower(trim(str_replace(' ', '-', $data['name'])));

        $role = Role::create([
            'name'       => $roleName,
            'guard_name' => 'web',
        ]);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role;
    }

    /**
     * Update an existing role and sync its permissions.
     */
    public function updateRole(Role $role, array $data): Role
    {
        // Protect core system role names from being renamed
        if (!in_array($role->name, $this->protectedRoles)) {
            $role->name = strtolower(trim(str_replace(' ', '-', $data['name'])));
            $role->save();
        }

        // Sync permissions
        $perms = $data['permissions'] ?? [];

        // If updating 'admin', always ensure it maintains all permissions
        if ($role->name === 'admin') {
            $perms = Permission::all()->pluck('name')->toArray();
        }

        $role->syncPermissions($perms);

        return $role;
    }

    /**
     * Delete a role with system safeguards.
     */
    public function deleteRole(Role $role): array
    {
        if (in_array($role->name, $this->protectedRoles)) {
            return [
                'success' => false,
                'message' => "Core system role [{$role->name}] is protected and cannot be deleted.",
            ];
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete role [{$role->name}] because {$usersCount} user(s) are currently assigned to it. Reassign users first.",
            ];
        }

        $name = $role->name;
        $role->delete();

        return [
            'success' => true,
            'message' => "Role [{$name}] was permanently deleted.",
        ];
    }

    /**
     * Create a new custom permission.
     */
    public function createPermission(array $data): Permission
    {
        $cleanName = strtolower(trim(preg_replace('/\s+/', ' ', $data['name'])));

        $permission = Permission::create([
            'name'       => $cleanName,
            'guard_name' => 'web',
        ]);

        // Auto-grant new permission to admin role
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        return $permission;
    }

    /**
     * Delete a custom permission with baseline safeguard.
     */
    public function deletePermission(int $id): array
    {
        $permission = Permission::where('id', $id)->where('guard_name', 'web')->first();

        if (!$permission) {
            return [
                'success' => false,
                'message' => 'Permission not found.',
            ];
        }

        $baseline = [];
        foreach ($this->permissionModules as $perms) {
            $baseline = array_merge($baseline, $perms);
        }

        if (in_array($permission->name, $baseline)) {
            return [
                'success' => false,
                'message' => "Permission [{$permission->name}] is a core system baseline permission and cannot be deleted.",
            ];
        }

        $name = $permission->name;
        $permission->delete();

        return [
            'success' => true,
            'message' => "Custom permission [{$name}] was successfully deleted.",
        ];
    }

    /**
     * Ensure all predefined module permissions exist in the database.
     */
    public function ensurePermissionsExist(): void
    {
        foreach ($this->permissionModules as $perms) {
            foreach ($perms as $permName) {
                Permission::firstOrCreate([
                    'name'       => $permName,
                    'guard_name' => 'web',
                ]);
            }
        }
    }
}
