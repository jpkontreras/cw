<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Contracts\MenuAvailabilityInterface;
use Colame\Menu\Data\CreateMenuData;
use Colame\Menu\Data\UpdateMenuData;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function __construct(
        private MenuServiceInterface $menuService,
        private MenuRepositoryInterface $menuRepository,
        private MenuAvailabilityInterface $availabilityService,
    ) {}
    
    public function index(Request $request): Response
    {
        $menus = $this->menuRepository->all();
        
        // Add availability status to each menu
        $menus = $menus->map(function ($menu) {
            $menu->isCurrentlyAvailable = $this->availabilityService->isMenuAvailable($menu->id);
            return $menu;
        });
        
        return Inertia::render('menu/index', [
            'menus' => $menus,
            'canCreate' => $request->user()->can('create-menus'),
        ]);
    }
    
    public function create(): Response
    {
        return Inertia::render('menu/create', [
            'menuTypes' => [
                'regular' => 'Regular Menu',
                'breakfast' => 'Breakfast Menu',
                'lunch' => 'Lunch Menu',
                'dinner' => 'Dinner Menu',
                'event' => 'Event Menu',
                'seasonal' => 'Seasonal Menu',
            ],
        ]);
    }
    
    public function store(Request $request): RedirectResponse
    {
        $data = CreateMenuData::validateAndCreate($request);
        
        $menu = $this->menuService->createMenu($data);
        
        return redirect()->route('menu.show', $menu->id)
            ->with('success', 'Menu created successfully');
    }
    
    public function show(int $id): Response
    {
        $menu = $this->menuRepository->findWithRelations($id);
        
        if (!$menu) {
            abort(404, 'Menu not found');
        }
        
        $availability = $this->availabilityService->getMenuAvailability($id);
        $structure = $this->menuService->getMenuStructure($id);
        
        return Inertia::render('menu/show', [
            'menu' => $menu,
            'structure' => $structure,
            'availability' => $availability,
        ]);
    }
    
    public function edit(int $id): Response
    {
        $menu = $this->menuRepository->findWithRelations($id);
        
        if (!$menu) {
            abort(404, 'Menu not found');
        }
        
        return Inertia::render('menu/edit', [
            'menu' => $menu,
            'menuTypes' => [
                'regular' => 'Regular Menu',
                'breakfast' => 'Breakfast Menu',
                'lunch' => 'Lunch Menu',
                'dinner' => 'Dinner Menu',
                'event' => 'Event Menu',
                'seasonal' => 'Seasonal Menu',
            ],
        ]);
    }
    
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = UpdateMenuData::validateAndCreate($request);
        
        $this->menuService->updateMenu($id, $data);
        
        return redirect()->route('menu.show', $id)
            ->with('success', 'Menu updated successfully');
    }
    
    public function destroy(int $id): RedirectResponse
    {
        $this->menuService->deleteMenu($id);
        
        return redirect()->route('menu.index')
            ->with('success', 'Menu deleted successfully');
    }
    
    public function activate(int $id): RedirectResponse
    {
        $this->menuRepository->activate($id);
        
        return back()->with('success', 'Menu activated successfully');
    }
    
    public function deactivate(int $id): RedirectResponse
    {
        $this->menuRepository->deactivate($id);
        
        return back()->with('success', 'Menu deactivated successfully');
    }
    
    public function duplicate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $menu = $this->menuService->duplicateMenu($id, $request->name);
        
        return redirect()->route('menu.edit', $menu->id)
            ->with('success', 'Menu duplicated successfully');
    }
    
    public function setDefault(int $id): RedirectResponse
    {
        $this->menuRepository->setAsDefault($id);
        
        return back()->with('success', 'Menu set as default');
    }
    
    public function preview(int $id): Response
    {
        $structure = $this->menuService->getMenuStructure($id);
        
        return Inertia::render('menu/preview', [
            'menu' => $structure,
        ]);
    }
    
    public function export(int $id, string $format)
    {
        if (!in_array($format, ['json', 'csv', 'pdf'])) {
            abort(400, 'Invalid export format');
        }
        
        $content = $this->menuService->exportMenu($id, $format);
        
        $menu = $this->menuRepository->find($id);
        $filename = "menu-{$menu->slug}-" . now()->format('Y-m-d') . ".{$format}";
        
        return response($content)
            ->header('Content-Type', $this->getContentType($format))
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
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