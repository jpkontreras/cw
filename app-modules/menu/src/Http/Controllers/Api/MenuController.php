<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Core\Traits\HandlesPaginationBounds;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Contracts\MenuAvailabilityInterface;
use Colame\Menu\Data\CreateMenuData;
use Colame\Menu\Data\UpdateMenuData;
use Colame\Menu\Data\DuplicateMenuData;
use Colame\Menu\Data\MenuFilterData;
use Colame\Menu\Data\AvailabilityCheckData;
use Colame\Menu\Data\MenuLocationFilterData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MenuController extends Controller
{
    use HandlesPaginationBounds;
    
    public function __construct(
        private MenuServiceInterface $menuService,
        private MenuRepositoryInterface $menuRepository,
        private MenuAvailabilityInterface $availabilityService,
    ) {}
    
    /**
     * Get all menus with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        $filters = MenuFilterData::from($request);
        
        $menus = $this->menuRepository->all();
        
        // Filter by location if provided
        if ($filters->locationId !== null) {
            $menus = $menus->filter(function ($menu) use ($filters) {
                return $menu->locations->contains('location_id', $filters->locationId);
            });
        }
        
        // Filter by type if provided
        if ($filters->type !== null) {
            $menus = $menus->where('type', $filters->type);
        }
        
        // Filter by active status
        if ($filters->isActive !== null) {
            $menus = $menus->where('isActive', $filters->isActive);
        }
        
        // Filter by current availability
        if ($filters->availableNow === true) {
            $menus = $menus->filter(function ($menu) {
                return $this->availabilityService->isMenuAvailable($menu->id);
            });
        }
        
        // Add availability status to each menu
        $menus = $menus->map(function ($menu) {
            $menu->isCurrentlyAvailable = $this->availabilityService->isMenuAvailable($menu->id);
            return $menu;
        });
        
        return response()->json([
            'success' => true,
            'data' => $menus->values(),
        ]);
    }
    
    /**
     * Get a specific menu with full structure
     */
    public function show(int $id): JsonResponse
    {
        $menu = $this->menuRepository->findWithRelations($id);
        
        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }
        
        $structure = $this->menuService->getMenuStructure($id);
        $availability = $this->availabilityService->getMenuAvailability($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'menu' => $menu,
                'structure' => $structure,
                'availability' => $availability,
            ],
        ]);
    }
    
    /**
     * Get menu structure for POS display
     */
    public function structure(int $id): JsonResponse
    {
        $structure = $this->menuService->getMenuStructure($id);
        
        if (!$structure) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $structure,
        ]);
    }
    
    /**
     * Create a new menu
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = CreateMenuData::validateAndCreate($request);
            $menu = $this->menuService->createMenu($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Menu created successfully',
                'data' => $menu,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Update an existing menu
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = UpdateMenuData::validateAndCreate($request);
            $menu = $this->menuService->updateMenu($id, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully',
                'data' => $menu,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Delete a menu
     */
    public function destroy(int $id): Response
    {
        try {
            $this->menuService->deleteMenu($id);
            
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Activate a menu
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $this->menuRepository->activate($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Menu activated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate menu',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Deactivate a menu
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $this->menuRepository->deactivate($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Menu deactivated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate menu',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get menu availability for a specific location
     */
    public function availability(int $id, Request $request): JsonResponse
    {
        $data = AvailabilityCheckData::from($request);
        
        $locationId = $data->locationId;
        $datetime = $data->getDateTime();
        
        $isAvailable = $this->availabilityService->isMenuAvailableAtTime($id, $datetime, $locationId);
        $availability = $this->availabilityService->getMenuAvailability($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'is_available' => $isAvailable,
                'checked_at' => $datetime->toIso8601String(),
                'location_id' => $locationId,
                'availability_rules' => $availability['rules'] ?? [],
                'next_available' => $availability['nextAvailable'] ?? null,
            ],
        ]);
    }
    
    /**
     * Get active menus only
     */
    public function active(Request $request): JsonResponse
    {
        $filter = MenuLocationFilterData::from($request);
        
        $menus = $this->menuRepository->all()->where('isActive', true);
        
        // Filter by location if provided
        if ($filter->locationId !== null) {
            $menus = $menus->filter(function ($menu) use ($filter) {
                return $menu->locations->contains('location_id', $filter->locationId);
            });
        }
        
        return response()->json([
            'success' => true,
            'data' => $menus->values(),
        ]);
    }
    
    /**
     * Get available menus (active and currently available)
     */
    public function available(Request $request): JsonResponse
    {
        $menus = $this->menuRepository->all()
            ->where('isActive', true)
            ->filter(function ($menu) {
                return $this->availabilityService->isMenuAvailable($menu->id);
            });
        
        return response()->json([
            'success' => true,
            'data' => $menus->values(),
        ]);
    }
    
    /**
     * Get menus by location
     */
    public function byLocation(int $locationId): JsonResponse
    {
        $menus = $this->menuRepository->all()
            ->filter(function ($menu) use ($locationId) {
                return $menu->locations->contains('location_id', $locationId);
            });
        
        // Add availability status
        $menus = $menus->map(function ($menu) {
            $menu->isCurrentlyAvailable = $this->availabilityService->isMenuAvailable($menu->id);
            return $menu;
        });
        
        return response()->json([
            'success' => true,
            'data' => $menus->values(),
        ]);
    }
    
    /**
     * Get the default menu for a location
     */
    public function getDefault(Request $request): JsonResponse
    {
        $filter = MenuLocationFilterData::from($request);
        
        $locationId = $filter->locationId;
        
        // Get default menu, optionally filtered by location
        $menu = $this->menuRepository->all()
            ->where('isDefault', true)
            ->when($locationId, function ($collection) use ($locationId) {
                return $collection->filter(function ($menu) use ($locationId) {
                    return $menu->locations->contains('location_id', $locationId);
                });
            })
            ->first();
        
        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'No default menu found',
            ], 404);
        }
        
        $structure = $this->menuService->getMenuStructure($menu->id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'menu' => $menu,
                'structure' => $structure,
            ],
        ]);
    }
    
    /**
     * Duplicate an existing menu
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $data = DuplicateMenuData::validateAndCreate($request);
        
        try {
            $menu = $this->menuService->duplicateMenu($id, $data->name);
            
            return response()->json([
                'success' => true,
                'message' => 'Menu duplicated successfully',
                'data' => $menu,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate menu',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Export menu in various formats
     */
    public function export(int $id, string $format): Response|JsonResponse
    {
        if (!in_array($format, ['json', 'csv', 'pdf'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid export format. Supported formats: json, csv, pdf',
            ], 400);
        }
        
        try {
            $content = $this->menuService->exportMenu($id, $format);
            $menu = $this->menuRepository->find($id);
            
            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu not found',
                ], 404);
            }
            
            $filename = "menu-{$menu->slug}-" . now()->format('Y-m-d') . ".{$format}";
            
            return response($content)
                ->header('Content-Type', $this->getContentType($format))
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export menu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Public: Get menu by location (no auth required)
     */
    public function publicByLocation(int $locationId): JsonResponse
    {
        $menus = $this->menuRepository->all()
            ->where('isActive', true)
            ->filter(function ($menu) use ($locationId) {
                return $menu->locations->contains('location_id', $locationId);
            })
            ->filter(function ($menu) {
                return $this->availabilityService->isMenuAvailable($menu->id);
            });
        
        return response()->json([
            'success' => true,
            'data' => $menus->values(),
        ]);
    }
    
    /**
     * Public: Show menu by slug (no auth required)
     */
    public function publicShow(string $slug): JsonResponse
    {
        $menu = $this->menuRepository->all()
            ->where('slug', $slug)
            ->where('isActive', true)
            ->first();
        
        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }
        
        $structure = $this->menuService->getMenuStructure($menu->id);
        $availability = $this->availabilityService->getMenuAvailability($menu->id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'menu' => $menu,
                'structure' => $structure,
                'availability' => $availability,
            ],
        ]);
    }
    
    /**
     * Public: Get menu structure by slug (no auth required)
     */
    public function publicStructure(string $slug): JsonResponse
    {
        $menu = $this->menuRepository->all()
            ->where('slug', $slug)
            ->where('isActive', true)
            ->first();
        
        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }
        
        $structure = $this->menuService->getMenuStructure($menu->id);
        
        return response()->json([
            'success' => true,
            'data' => $structure,
        ]);
    }
    
    private function getContentType(string $format): string
    {
        return match($format) {
            'json' => 'application/json',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}