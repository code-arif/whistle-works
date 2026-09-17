<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
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
     * Display the Roles & Permissions Management Hub.
     */
    public function index(Request $request): Response
    {
        $this->ensurePermissionsExist();

        $query = Role::with('permissions')->withCount('users');

        if ($search = trim($request->input('search', ''))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $roles = $query->orderByRaw("FIELD(name, 'admin', 'director', 'evaluator', 'referee') DESC")
            ->orderBy('name', 'asc')
            ->get()
            ->map(function (Role $role) {
                return [
                    'id'               => $role->id,
                    'name'             => $role->name,
                    'guard_name'       => $role->guard_name,
                    'users_count'      => $role->users_count,
                    'is_system'        => in_array($role->name, $this->protectedRoles),
                    'is_admin'         => $role->name === 'admin',
                    'permissions'      => $role->permissions->pluck('name')->toArray(),
                    'permissions_count'=> $role->permissions->count(),
                    'created_at'       => $role->created_at?->format('Y-m-d H:i') ?? 'N/A',
                ];
            });

        // Structure permissions by module for the editor & catalog
        $allPermissions = Permission::where('guard_name', 'web')->with('roles')->get();
        $groupedPermissions = [];
        $trackedPermNames = [];
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
                    $items[] = $permData;
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
        $availableModules = array_keys($this->permissionModules);
        $availableModules[] = 'Custom & Additional Permissions';

        // Summary KPI Metrics
        $metrics = [
            'total_roles'       => $roles->count(),
            'system_roles'      => $roles->where('is_system', true)->count(),
            'custom_roles'      => $roles->where('is_system', false)->count(),
            'total_permissions' => $allPermissions->count(),
            'system_permissions'=> count($trackedPermNames),
            'custom_permissions'=> $customPerms->count(),
            'total_assigned'    => $roles->sum('users_count'),
        ];

        return Inertia::render('Roles/Index', [
            'roles'                 => $roles,
            'groupedPermissions'    => $groupedPermissions,
            'allPermissionsCatalog' => $allPermissionsCatalog,
            'availableModules'      => $availableModules,
            'metrics'               => $metrics,
            'filters'               => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created role with assigned permissions.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_\-\s]+$/', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        try {
            $roleName = strtolower(trim(str_replace(' ', '-', $validated['name'])));

            $role = Role::create([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            if (!empty($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            return redirect()->back()->with('t-success', "Role [{$role->name}] was created successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing role and its permissions.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id)
            ],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        try {
            // Protect core system role names from being renamed
            if (!in_array($role->name, $this->protectedRoles)) {
                $role->name = strtolower(trim(str_replace(' ', '-', $validated['name'])));
                $role->save();
            }

            // Sync permissions
            $perms = $validated['permissions'] ?? [];

            // If updating 'admin', always ensure it maintains all permissions
            if ($role->name === 'admin') {
                $perms = Permission::all()->pluck('name')->toArray();
            }

            $role->syncPermissions($perms);

            return redirect()->back()->with('t-success', "Role [{$role->name}] was updated successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Delete a role with safeguards.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // 1. Safeguard: Block deletion of core system roles
        if (in_array($role->name, $this->protectedRoles)) {
            return redirect()->back()->with('t-error', "Core system role [{$role->name}] is protected and cannot be deleted.");
        }

        // 2. Safeguard: Block deletion if users are assigned
        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return redirect()->back()->with('t-error', "Cannot delete role [{$role->name}] because {$usersCount} user(s) are currently assigned to it. Reassign users first.");
        }

        try {
            $name = $role->name;
            $role->delete();

            return redirect()->back()->with('t-success', "Role [{$name}] was permanently deleted.");
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created custom permission.
     */
    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-zA-Z0-9_\-\s]+$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web'),
            ],
            'module' => ['nullable', 'string', 'max:60'],
        ]);

        try {
            $cleanName = strtolower(trim(preg_replace('/\s+/', ' ', $validated['name'])));

            $permission = Permission::create([
                'name'       => $cleanName,
                'guard_name' => 'web',
            ]);

            // Auto-grant new permission to admin role so superadmins immediately have it
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
            if ($adminRole) {
                $adminRole->givePermissionTo($permission);
            }

            return redirect()->back()->with('t-success', "Permission [{$cleanName}] created successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Delete a custom permission.
     */
    public function destroyPermission(int $id): RedirectResponse
    {
        $permission = Permission::where('id', $id)->where('guard_name', 'web')->first();

        if (!$permission) {
            return redirect()->back()->with('t-error', 'Permission not found.');
        }

        // Check if this permission is part of the system baseline permissions
        $baseline = [];
        foreach ($this->permissionModules as $perms) {
            $baseline = array_merge($baseline, $perms);
        }

        if (in_array($permission->name, $baseline)) {
            return redirect()->back()->with('t-error', "Permission [{$permission->name}] is a core system baseline permission and cannot be deleted.");
        }

        try {
            $name = $permission->name;
            $permission->delete();

            return redirect()->back()->with('t-success', "Custom permission [{$name}] was successfully deleted.");
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Ensure all predefined module permissions exist in the database.
     */
    protected function ensurePermissionsExist(): void
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
