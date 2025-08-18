<?php

namespace Colame\Staff\Tests\Unit;

use Tests\TestCase;
use Colame\Staff\Services\StaffService;
use Colame\Staff\Contracts\StaffRepositoryInterface;
use Colame\Staff\Data\StaffMemberData;
use Colame\Staff\Data\CreateStaffMemberData;
use Mockery;

class StaffServiceTest extends TestCase
{
    protected StaffService $service;
    protected $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(StaffRepositoryInterface::class);
        $this->service = new StaffService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_get_staff_member_by_id()
    {
        $staffData = new StaffMemberData(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: '+1234567890',
            employeeCode: 'EMP001',
            status: 'active',
            hireDate: now(),
            createdAt: now(),
            updatedAt: now(),
        );
        
        $this->mockRepository
            ->shouldReceive('find')
            ->with(1)
            ->once()
            ->andReturn($staffData);
        
        $result = $this->service->getStaffMemberById(1);
        
        $this->assertInstanceOf(StaffMemberData::class, $result);
        $this->assertEquals('John', $result->firstName);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function test_returns_null_for_non_existent_staff()
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->with(999)
            ->once()
            ->andReturn(null);
        
        $result = $this->service->getStaffMemberById(999);
        
        $this->assertNull($result);
    }

    public function test_can_activate_staff_member()
    {
        $staffData = new StaffMemberData(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: '+1234567890',
            employeeCode: 'EMP001',
            status: 'active',
            hireDate: now(),
            createdAt: now(),
            updatedAt: now(),
        );
        
        $this->mockRepository
            ->shouldReceive('update')
            ->with(1, ['status' => 'active'])
            ->once()
            ->andReturn($staffData);
        
        $result = $this->service->activateStaffMember(1);
        
        $this->assertTrue($result);
    }

    public function test_can_deactivate_staff_member()
    {
        $staffData = new StaffMemberData(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: '+1234567890',
            employeeCode: 'EMP001',
            status: 'inactive',
            hireDate: now(),
            createdAt: now(),
            updatedAt: now(),
        );
        
        $this->mockRepository
            ->shouldReceive('update')
            ->with(1, ['status' => 'inactive'])
            ->once()
            ->andReturn($staffData);
        
        $result = $this->service->deactivateStaffMember(1);
        
        $this->assertTrue($result);
    }

    public function test_can_get_staff_stats()
    {
        $this->mockRepository
            ->shouldReceive('count')
            ->once()
            ->andReturn(100);
        
        $this->mockRepository
            ->shouldReceive('countByStatus')
            ->with('active')
            ->once()
            ->andReturn(75);
        
        $this->mockRepository
            ->shouldReceive('countByStatus')
            ->with('on_leave')
            ->once()
            ->andReturn(10);
        
        $this->mockRepository
            ->shouldReceive('countByStatus')
            ->with('inactive')
            ->once()
            ->andReturn(15);
        
        $stats = $this->service->getStaffStats();
        
        $this->assertEquals(100, $stats['total']);
        $this->assertEquals(75, $stats['active']);
        $this->assertEquals(10, $stats['on_leave']);
        $this->assertEquals(15, $stats['inactive']);
    }
}