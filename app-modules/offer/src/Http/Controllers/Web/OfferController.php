<?php

declare(strict_types=1);

namespace Colame\Offer\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Traits\HandlesPaginationBounds;
use Colame\Offer\Contracts\OfferServiceInterface;
use Colame\Offer\Contracts\OfferRepositoryInterface;
use Colame\Offer\Data\CreateOfferData;
use Colame\Offer\Data\UpdateOfferData;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    use HandlesPaginationBounds;
    
    public function __construct(
        private readonly OfferServiceInterface $service,
        private readonly OfferRepositoryInterface $repository,
    ) {}
    
    public function index(Request $request): Response|RedirectResponse
    {
        $filters = [
            'search' => $request->get('search'),
            'type' => $request->get('type'),
            'is_active' => $request->get('is_active'),
            'has_code' => $request->get('has_code'),
            'expiring' => $request->get('expiring'),
            'location_id' => $request->get('location_id'),
            'valid_only' => $request->get('valid_only', false),
            'sort_by' => $request->get('sort_by', 'priority'),
            'sort_direction' => $request->get('sort_direction', 'desc'),
        ];
        
        $perPage = (int) $request->get('per_page', 15);
        $paginatedOffers = $this->repository->paginate($perPage, $filters);
        $offersArray = $paginatedOffers->toArray();
        
        // Build metadata for DataTable
        $metadata = [
            'filters' => [
                [
                    'key' => 'search',
                    'label' => 'Search',
                    'placeholder' => 'Search by name, code, or description',
                    'filterType' => 'search',
                ],
                [
                    'key' => 'type',
                    'label' => 'Type',
                    'filterType' => 'select',
                    'options' => array_merge(
                        [['value' => '__all__', 'label' => 'All Types']],
                        $this->getOfferTypes()
                    ),
                ],
                [
                    'key' => 'is_active',
                    'label' => 'Status',
                    'filterType' => 'select',
                    'options' => [
                        ['value' => '__all__', 'label' => 'All Status'],
                        ['value' => '1', 'label' => 'Active'],
                        ['value' => '0', 'label' => 'Inactive'],
                    ],
                ],
            ],
            'defaultFilters' => ['search'],
            'columns' => [],
        ];
        
        // Calculate stats for Quick Filters
        $stats = $this->calculateOfferStats();
        
        $data = [
            'offers' => $offersArray['data'],
            'pagination' => $offersArray['pagination'],
            'metadata' => $metadata,
            'filters' => $filters,
            'types' => $this->getOfferTypes(),
            'stats' => $stats,
        ];
        
        // Handle out-of-bounds pagination
        if ($redirect = $this->handleOutOfBoundsPagination($data['pagination'], $request, 'offers.index')) {
            return $redirect;
        }
        
        return Inertia::render('offers/index', $data);
    }
    
    public function create(): Response
    {
        return Inertia::render('offers/create', [
            'types' => $this->getOfferTypes(),
            'recurringSchedules' => $this->getRecurringSchedules(),
            'customerSegments' => $this->getCustomerSegments(),
            'daysOfWeek' => $this->getDaysOfWeek(),
        ]);
    }
    
    public function store(Request $request): RedirectResponse
    {
        $data = CreateOfferData::validateAndCreate($request);
        $offer = $this->service->createOffer($data->toArray());
        
        return redirect()
            ->route('offers.show', $offer->id)
            ->with('success', 'Offer created successfully');
    }
    
    public function show(int $id): Response
    {
        $offer = $this->repository->find($id);
        
        if (!$offer) {
            abort(404, 'Offer not found');
        }
        
        $analytics = $this->service->getOfferAnalytics($id);
        
        return Inertia::render('offers/show', [
            'offer' => $offer,
            'analytics' => $analytics,
        ]);
    }
    
    public function edit(int $id): Response
    {
        $offer = $this->repository->find($id);
        
        if (!$offer) {
            abort(404, 'Offer not found');
        }
        
        return Inertia::render('offers/edit', [
            'offer' => $offer,
            'types' => $this->getOfferTypes(),
            'recurringSchedules' => $this->getRecurringSchedules(),
            'customerSegments' => $this->getCustomerSegments(),
            'daysOfWeek' => $this->getDaysOfWeek(),
        ]);
    }
    
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = UpdateOfferData::validateAndCreate($request);
        $offer = $this->service->updateOffer($id, $data->toArray());
        
        return redirect()
            ->route('offers.show', $offer->id)
            ->with('success', 'Offer updated successfully');
    }
    
    public function destroy(int $id): RedirectResponse
    {
        $this->service->deleteOffer($id);
        
        return redirect()
            ->route('offers.index')
            ->with('success', 'Offer deleted successfully');
    }
    
    public function duplicate(int $id): RedirectResponse
    {
        $newOffer = $this->service->duplicateOffer($id);
        
        return redirect()
            ->route('offers.edit', $newOffer->id)
            ->with('success', 'Offer duplicated successfully. Please review and update the details.');
    }
    
    public function activate(int $id): RedirectResponse
    {
        $this->repository->activate($id);
        
        return back()->with('success', 'Offer activated successfully');
    }
    
    public function deactivate(int $id): RedirectResponse
    {
        $this->repository->deactivate($id);
        
        return back()->with('success', 'Offer deactivated successfully');
    }
    
    public function bulkAction(Request $request): RedirectResponse
    {
        $action = $request->get('action');
        $offerIds = $request->get('offer_ids', []);
        
        if (empty($offerIds)) {
            return back()->with('error', 'No offers selected');
        }
        
        switch ($action) {
            case 'activate':
                $count = $this->service->bulkActivate($offerIds);
                return back()->with('success', "{$count} offers activated");
                
            case 'deactivate':
                $count = $this->service->bulkDeactivate($offerIds);
                return back()->with('success', "{$count} offers deactivated");
                
            case 'delete':
                $count = 0;
                foreach ($offerIds as $id) {
                    if ($this->service->deleteOffer($id)) {
                        $count++;
                    }
                }
                return back()->with('success', "{$count} offers deleted");
                
            default:
                return back()->with('error', 'Invalid action');
        }
    }
    
    public function analytics(Request $request, int $id): Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $offer = $this->repository->find($id);
        
        if (!$offer) {
            abort(404, 'Offer not found');
        }
        
        $analytics = $this->service->getOfferAnalytics($id, $startDate, $endDate);
        
        return Inertia::render('offers/analytics', [
            'offer' => $offer,
            'analytics' => $analytics,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
    
    private function getOfferTypes(): array
    {
        return [
            ['value' => 'percentage', 'label' => 'Percentage Discount'],
            ['value' => 'fixed', 'label' => 'Fixed Amount'],
            ['value' => 'buy_x_get_y', 'label' => 'Buy X Get Y'],
            ['value' => 'combo', 'label' => 'Combo Deal'],
            ['value' => 'happy_hour', 'label' => 'Happy Hour'],
            ['value' => 'early_bird', 'label' => 'Early Bird'],
            ['value' => 'loyalty', 'label' => 'Loyalty Reward'],
            ['value' => 'staff', 'label' => 'Staff Discount'],
        ];
    }
    
    private function getRecurringSchedules(): array
    {
        return [
            ['value' => 'daily', 'label' => 'Daily'],
            ['value' => 'weekly', 'label' => 'Weekly'],
            ['value' => 'monthly', 'label' => 'Monthly'],
            ['value' => 'weekdays', 'label' => 'Weekdays Only'],
            ['value' => 'weekends', 'label' => 'Weekends Only'],
        ];
    }
    
    private function getCustomerSegments(): array
    {
        return [
            ['value' => 'new', 'label' => 'New Customers'],
            ['value' => 'returning', 'label' => 'Returning Customers'],
            ['value' => 'vip', 'label' => 'VIP Customers'],
            ['value' => 'staff', 'label' => 'Staff Members'],
        ];
    }
    
    private function getDaysOfWeek(): array
    {
        return [
            ['value' => 'monday', 'label' => 'Monday'],
            ['value' => 'tuesday', 'label' => 'Tuesday'],
            ['value' => 'wednesday', 'label' => 'Wednesday'],
            ['value' => 'thursday', 'label' => 'Thursday'],
            ['value' => 'friday', 'label' => 'Friday'],
            ['value' => 'saturday', 'label' => 'Saturday'],
            ['value' => 'sunday', 'label' => 'Sunday'],
        ];
    }
    
    private function calculateOfferStats(): array
    {
        return $this->repository->getOfferStats();
    }
}