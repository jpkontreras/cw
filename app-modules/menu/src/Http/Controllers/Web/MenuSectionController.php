<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuSectionRepositoryInterface;
use Colame\Menu\Data\CreateMenuSectionData;
use Colame\Menu\Data\UpdateMenuSectionData;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class MenuSectionController extends Controller
{
    public function __construct(
        private MenuServiceInterface $menuService,
        private MenuSectionRepositoryInterface $sectionRepository,
    ) {}
    
    /**
     * Get all sections for a menu (JSON response for AJAX)
     */
    public function index(int $menuId): JsonResponse
    {
        $sections = $this->sectionRepository->getByMenu($menuId);
        
        return response()->json([
            'sections' => $sections,
        ]);
    }
    
    /**
     * Store a new section
     */
    public function store(Request $request, int $menuId): JsonResponse
    {
        $data = CreateMenuSectionData::validateAndCreate(
            array_merge($request->all(), ['menuId' => $menuId])
        );
        
        $section = $this->sectionRepository->create($data->toArray());
        
        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }
    
    /**
     * Update a section
     */
    public function update(Request $request, int $menuId, int $sectionId): JsonResponse
    {
        $data = UpdateMenuSectionData::validateAndCreate($request);
        
        $section = $this->sectionRepository->update($sectionId, $data->toArray());
        
        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }
    
    /**
     * Delete a section
     */
    public function destroy(int $menuId, int $sectionId): JsonResponse
    {
        $this->sectionRepository->delete($sectionId);
        
        return response()->json([
            'success' => true,
        ]);
    }
    
    /**
     * Reorder sections
     */
    public function reorder(Request $request, int $menuId): JsonResponse
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|integer',
            'sections.*.sortOrder' => 'required|integer|min:0',
        ]);
        
        foreach ($request->input('sections') as $sectionData) {
            $this->sectionRepository->update($sectionData['id'], [
                'sortOrder' => $sectionData['sortOrder'],
            ]);
        }
        
        return response()->json([
            'success' => true,
        ]);
    }
}