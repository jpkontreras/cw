<?php

namespace Colame\Staff\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\RoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    public function index(): Response
    {
        return Inertia::render('staff/roles/index', [
            'roles' => $this->roleService->getAllRoles(),
            'permissions' => $this->roleService->getAllPermissions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('staff/roles/create', [
            'permissions' => $this->roleService->getAllPermissions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'hierarchy_level' => 'required|integer|min:1|max:100',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        $role = $this->roleService->createRole($validated);
        
        return redirect()
            ->route('staff.roles.show', $role->id)
            ->with('success', 'Role created successfully');
    }

    public function show(int $id): Response
    {
        $role = $this->roleService->getRoleById($id);
        
        if (!$role) {
            abort(404, 'Role not found');
        }
        
        return Inertia::render('staff/roles/show', [
            'role' => $role,
            'staffMembers' => $this->roleService->getStaffWithRole($id),
        ]);
    }

    public function edit(int $id): Response
    {
        $role = $this->roleService->getRoleById($id);
        
        if (!$role) {
            abort(404, 'Role not found');
        }
        
        return Inertia::render('staff/roles/edit', [
            'role' => $role,
            'permissions' => $this->roleService->getAllPermissions(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'hierarchy_level' => 'required|integer|min:1|max:100',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        $role = $this->roleService->updateRole($id, $validated);
        
        if (!$role) {
            abort(404, 'Role not found');
        }
        
        return redirect()
            ->route('staff.roles.show', $id)
            ->with('success', 'Role updated successfully');
    }

    public function destroy(int $id)
    {
        $role = $this->roleService->getRoleById($id);
        
        if (!$role) {
            abort(404, 'Role not found');
        }
        
        if ($role->isSystem) {
            return redirect()
                ->back()
                ->with('error', 'System roles cannot be deleted');
        }
        
        $this->roleService->deleteRole($id);
        
        return redirect()
            ->route('staff.roles.index')
            ->with('success', 'Role deleted successfully');
    }

    public function updatePermissions(Request $request, int $id)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        $result = $this->roleService->updateRolePermissions($id, $validated['permissions']);
        
        if (!$result) {
            abort(404, 'Role not found');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Permissions updated successfully');
    }
}