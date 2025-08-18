<?php

namespace Colame\Staff\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    public function index(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();
        
        return response()->json([
            'data' => $roles,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleById($id);
        
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
        
        return response()->json($role);
    }
}