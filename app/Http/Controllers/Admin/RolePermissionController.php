<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RolePermission\StorePermissionRequest;
use App\Http\Requests\Admin\RolePermission\StoreRoleRequest;
use App\Http\Requests\Admin\RolePermission\UpdateRoleRequest;
use App\Services\Admin\RolePermissionService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(
        protected RolePermissionService $service
    ) {}

    /**
     * Display the Roles & Permissions Management Hub.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->input('search', ''));
        $data   = $this->service->getRolesHubData($search);

        return Inertia::render('Roles/Index', $data);
    }

    /**
     * Store a newly created role with assigned permissions.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        try {
            $role = $this->service->createRole($request->validated());

            return redirect()->back()->with('t-success', "Role [{$role->name}] was created successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing role and its permissions.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $role = $this->service->updateRole($role, $request->validated());

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
        $result = $this->service->deleteRole($role);

        if (!$result['success']) {
            return redirect()->back()->with('t-error', $result['message']);
        }

        return redirect()->back()->with('t-success', $result['message']);
    }

    /**
     * Store a newly created custom permission.
     */
    public function storePermission(StorePermissionRequest $request): RedirectResponse
    {
        try {
            $permission = $this->service->createPermission($request->validated());

            return redirect()->back()->with('t-success', "Permission [{$permission->name}] created successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Delete a custom permission.
     */
    public function destroyPermission(int $id): RedirectResponse
    {
        $result = $this->service->deletePermission($id);

        if (!$result['success']) {
            return redirect()->back()->with('t-error', $result['message']);
        }

        return redirect()->back()->with('t-success', $result['message']);
    }
}
