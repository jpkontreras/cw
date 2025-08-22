<?php

namespace Colame\Offer\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Colame\Offer\Models\Offer;
use Colame\Offer\Services\OfferService;
use Colame\Offer\Services\OfferCalculatorService;
use Colame\Offer\Services\OfferValidatorService;
use Colame\Offer\Repositories\OfferRepository;
use Carbon\Carbon;

class OfferServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private OfferService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $repository = new OfferRepository();
        $calculator = new OfferCalculatorService();
        $validator = new OfferValidatorService();
        
        $this->service = new OfferService($repository, $calculator, $validator);
    }
    
    /** @test */
    public function it_can_create_an_offer()
    {
        $offerData = [
            'name' => 'Test Offer',
            'description' => 'Test description',
            'type' => 'percentage',
            'value' => 20,
            'isActive' => true,
        ];
        
        $offer = $this->service->createOffer($offerData);
        
        $this->assertNotNull($offer->id);
        $this->assertEquals('Test Offer', $offer->name);
        $this->assertEquals('percentage', $offer->type);
        $this->assertEquals(20, $offer->value);
        $this->assertTrue($offer->isActive);
    }
    
    /** @test */
    public function it_can_update_an_offer()
    {
        $offer = Offer::create([
            'name' => 'Original Name',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);
        
        $updatedData = [
            'name' => 'Updated Name',
            'value' => 15,
        ];
        
        $updatedOffer = $this->service->updateOffer($offer->id, $updatedData);
        
        $this->assertEquals('Updated Name', $updatedOffer->name);
        $this->assertEquals(15, $updatedOffer->value);
    }
    
    /** @test */
    public function it_can_delete_an_offer()
    {
        $offer = Offer::create([
            'name' => 'Test Offer',
            'type' => 'fixed',
            'value' => 5,
            'is_active' => true,
        ]);
        
        $result = $this->service->deleteOffer($offer->id);
        
        $this->assertTrue($result);
        $this->assertSoftDeleted('offers', ['id' => $offer->id]);
    }
    
    /** @test */
    public function it_can_duplicate_an_offer()
    {
        $originalOffer = Offer::create([
            'name' => 'Original Offer',
            'type' => 'percentage',
            'value' => 25,
            'code' => 'ORIGINAL',
            'is_active' => true,
        ]);
        
        $duplicatedOffer = $this->service->duplicateOffer($originalOffer->id);
        
        $this->assertNotEquals($originalOffer->id, $duplicatedOffer->id);
        $this->assertEquals('Original Offer', $duplicatedOffer->name);
        $this->assertEquals('percentage', $duplicatedOffer->type);
        $this->assertEquals(25, $duplicatedOffer->value);
        $this->assertFalse($duplicatedOffer->isActive); // Should be inactive by default
        $this->assertStringContainsString('ORIGINAL_copy_', $duplicatedOffer->code);
    }
    
    /** @test */
    public function it_calculates_percentage_discount_correctly()
    {
        $offer = Offer::create([
            'name' => 'Percentage Offer',
            'type' => 'percentage',
            'value' => 20,
            'is_active' => true,
        ]);
        
        $orderData = [
            'total_amount' => 100,
        ];
        
        $discount = $this->service->calculateDiscount(
            $this->service->repository->find($offer->id),
            $orderData
        );
        
        $this->assertEquals(20, $discount);
    }
    
    /** @test */
    public function it_respects_maximum_discount_cap()
    {
        $offer = Offer::create([
            'name' => 'Capped Offer',
            'type' => 'percentage',
            'value' => 50,
            'max_discount' => 25,
            'is_active' => true,
        ]);
        
        $orderData = [
            'total_amount' => 100,
        ];
        
        $discount = $this->service->calculateDiscount(
            $this->service->repository->find($offer->id),
            $orderData
        );
        
        $this->assertEquals(25, $discount); // Should be capped at 25
    }
    
    /** @test */
    public function it_validates_offer_date_range()
    {
        $offer = Offer::create([
            'name' => 'Future Offer',
            'type' => 'fixed',
            'value' => 10,
            'is_active' => true,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addWeek(),
        ]);
        
        $isValid = $this->service->validateOfferForOrder($offer->id, []);
        
        $this->assertFalse($isValid); // Offer hasn't started yet
    }
    
    /** @test */
    public function it_validates_minimum_amount_requirement()
    {
        $offer = Offer::create([
            'name' => 'Minimum Amount Offer',
            'type' => 'fixed',
            'value' => 5,
            'is_active' => true,
            'minimum_amount' => 50,
        ]);
        
        $orderData = [
            'total_amount' => 30,
        ];
        
        $isValid = $this->service->validateOfferForOrder($offer->id, $orderData);
        
        $this->assertFalse($isValid); // Order amount is below minimum
    }
    
    /** @test */
    public function it_can_apply_best_offer()
    {
        // Create multiple offers
        Offer::create([
            'name' => 'Small Discount',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);
        
        Offer::create([
            'name' => 'Medium Discount',
            'type' => 'percentage',
            'value' => 20,
            'is_active' => true,
        ]);
        
        Offer::create([
            'name' => 'Large Discount',
            'type' => 'fixed',
            'value' => 25,
            'is_active' => true,
        ]);
        
        $orderData = [
            'total_amount' => 100,
        ];
        
        $appliedOffer = $this->service->applyBestOfferToOrder($orderData);
        
        $this->assertNotNull($appliedOffer);
        $this->assertEquals(25, $appliedOffer->discountAmount); // Fixed $25 is best
    }
    
    /** @test */
    public function it_tracks_offer_usage()
    {
        $offer = Offer::create([
            'name' => 'Limited Offer',
            'type' => 'fixed',
            'value' => 10,
            'is_active' => true,
            'usage_limit' => 5,
        ]);
        
        $this->assertEquals(0, $offer->usage_count);
        
        $this->service->recordUsage($offer->id, 1, 10);
        
        $offer->refresh();
        $this->assertEquals(1, $offer->usage_count);
    }
    
    /** @test */
    public function it_can_bulk_activate_offers()
    {
        $offer1 = Offer::create([
            'name' => 'Offer 1',
            'type' => 'fixed',
            'value' => 5,
            'is_active' => false,
        ]);
        
        $offer2 = Offer::create([
            'name' => 'Offer 2',
            'type' => 'fixed',
            'value' => 10,
            'is_active' => false,
        ]);
        
        $count = $this->service->bulkActivate([$offer1->id, $offer2->id]);
        
        $this->assertEquals(2, $count);
        
        $offer1->refresh();
        $offer2->refresh();
        
        $this->assertTrue($offer1->is_active);
        $this->assertTrue($offer2->is_active);
    }
}