# Staff Module Implementation Plan

## Overview
Comprehensive staff management system for Chilean fair restaurants following strict modular architecture with interface-based communication, laravel-data patterns, and service layer separation.

## Core Features

### 1. Staff Management
- Employee CRUD operations
- Personal information management
- Contact details and emergency contacts
- Document management (contracts, certificates)
- Multi-location assignments

### 2. Role & Permission System
- Hierarchical role structure (Owner → Manager → Supervisor → Staff)
- Granular permission management
- Role templates for common positions
- Location-specific permissions

### 3. Shift Management
- Shift scheduling and templates
- Clock in/out functionality
- Break tracking
- Overtime calculation
- Shift swapping requests

### 4. Attendance & Time Tracking
- Biometric/PIN clock-in system
- Geolocation verification for mobile clock-in
- Attendance reports
- Late/absence tracking
- Leave management

### 5. Performance & Training
- Performance metrics tracking
- Training certifications
- Skill assessments
- Goal setting and reviews

## Technical Architecture

### Module Structure
```
app-modules/staff/
├── src/
│   ├── Contracts/                 # Public interfaces
│   │   ├── StaffRepositoryInterface.php
│   │   ├── RoleRepositoryInterface.php
│   │   ├── ShiftRepositoryInterface.php
│   │   ├── AttendanceRepositoryInterface.php
│   │   ├── StaffServiceInterface.php
│   │   ├── ShiftServiceInterface.php
│   │   └── AttendanceServiceInterface.php
│   ├── Data/                      # DTOs (camelCase properties)
│   │   ├── StaffMemberData.php
│   │   ├── CreateStaffMemberData.php
│   │   ├── UpdateStaffMemberData.php
│   │   ├── StaffWithRelationsData.php
│   │   ├── RoleData.php
│   │   ├── PermissionData.php
│   │   ├── ShiftData.php
│   │   ├── CreateShiftData.php
│   │   ├── ShiftTemplateData.php
│   │   ├── AttendanceRecordData.php
│   │   ├── ClockInData.php
│   │   ├── ClockOutData.php
│   │   ├── TimeOffRequestData.php
│   │   ├── PerformanceMetricData.php
│   │   └── EmergencyContactData.php
│   ├── Repositories/              # Interface implementations
│   │   ├── StaffRepository.php
│   │   ├── RoleRepository.php
│   │   ├── ShiftRepository.php
│   │   └── AttendanceRepository.php
│   ├── Services/                  # Business logic
│   │   ├── StaffService.php
│   │   ├── RoleService.php
│   │   ├── ShiftService.php
│   │   ├── AttendanceService.php
│   │   ├── SchedulingService.php
│   │   └── PayrollIntegrationService.php
│   ├── Models/                    # Eloquent models (internal)
│   │   ├── StaffMember.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── Shift.php
│   │   ├── ShiftTemplate.php
│   │   ├── AttendanceRecord.php
│   │   ├── TimeOffRequest.php
│   │   └── EmergencyContact.php
│   ├── Events/
│   │   ├── StaffMemberCreated.php
│   │   ├── ShiftAssigned.php
│   │   ├── ClockInRecorded.php
│   │   └── TimeOffRequested.php
│   ├── Exceptions/
│   │   ├── StaffException.php
│   │   ├── InvalidShiftException.php
│   │   └── AttendanceException.php
│   ├── Http/Controllers/
│   │   ├── Web/                   # Inertia responses
│   │   │   ├── StaffController.php
│   │   │   ├── ShiftController.php
│   │   │   ├── AttendanceController.php
│   │   │   └── RoleController.php
│   │   └── Api/                   # JSON responses
│   │       ├── StaffController.php
│   │       ├── ShiftController.php
│   │       ├── AttendanceController.php
│   │       └── ClockController.php
│   └── Providers/
│       └── StaffServiceProvider.php
├── database/
│   ├── migrations/
│   │   ├── create_staff_members_table.php
│   │   ├── create_roles_table.php
│   │   ├── create_permissions_table.php
│   │   ├── create_role_permissions_table.php
│   │   ├── create_staff_roles_table.php
│   │   ├── create_shifts_table.php
│   │   ├── create_shift_templates_table.php
│   │   ├── create_attendance_records_table.php
│   │   ├── create_time_off_requests_table.php
│   │   └── create_emergency_contacts_table.php
│   ├── factories/
│   └── seeders/
├── routes/
│   ├── web.php
│   └── api.php
├── config/
│   └── features.php
└── tests/
    ├── Feature/
    └── Unit/
```

### Database Schema

#### staff_members
- id (bigint)
- employee_code (string, unique)
- first_name (string)
- last_name (string)
- email (string, unique)
- phone (string)
- address (json)
- date_of_birth (date)
- hire_date (date)
- national_id (string)
- emergency_contacts (json)
- bank_details (json, encrypted)
- status (enum: active, inactive, suspended, terminated)
- metadata (json)
- created_at
- updated_at

#### roles
- id
- name
- slug
- description
- hierarchy_level (int)
- is_system (boolean)
- created_at
- updated_at

#### permissions
- id
- name
- slug
- module
- description
- created_at
- updated_at

#### staff_roles
- staff_member_id
- role_id
- location_id (nullable)
- assigned_at
- assigned_by
- expires_at (nullable)

#### shifts
- id
- staff_member_id
- location_id
- start_time
- end_time
- break_duration (minutes)
- status (scheduled, in_progress, completed, cancelled)
- actual_start
- actual_end
- notes
- created_by
- approved_by
- created_at
- updated_at

#### attendance_records
- id
- staff_member_id
- shift_id (nullable)
- location_id
- clock_in_time
- clock_out_time
- clock_in_method (biometric, pin, mobile, manual)
- clock_in_location (json)
- clock_out_location (json)
- break_start
- break_end
- overtime_minutes
- status (present, late, absent, holiday)
- notes
- created_at
- updated_at

## Interface Definitions

### StaffRepositoryInterface
```php
interface StaffRepositoryInterface
{
    public function find(int $id): ?StaffMemberData;
    public function findByEmployeeCode(string $code): ?StaffMemberData;
    public function findByEmail(string $email): ?StaffMemberData;
    public function create(CreateStaffMemberData $data): StaffMemberData;
    public function update(int $id, UpdateStaffMemberData $data): StaffMemberData;
    public function delete(int $id): bool;
    public function paginateWithFilters(array $filters, int $perPage): PaginatedResourceData;
    public function getByLocation(int $locationId): DataCollection;
    public function getByRole(int $roleId): DataCollection;
    public function getActiveStaff(): DataCollection;
}
```

### ShiftRepositoryInterface
```php
interface ShiftRepositoryInterface
{
    public function find(int $id): ?ShiftData;
    public function create(CreateShiftData $data): ShiftData;
    public function update(int $id, array $data): ShiftData;
    public function delete(int $id): bool;
    public function getByStaffMember(int $staffId, ?Carbon $from, ?Carbon $to): DataCollection;
    public function getByLocation(int $locationId, Carbon $date): DataCollection;
    public function getUpcoming(int $staffId, int $days = 7): DataCollection;
    public function findConflicts(int $staffId, Carbon $start, Carbon $end): DataCollection;
}
```

## Data Transfer Objects

### StaffMemberData
```php
class StaffMemberData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $employeeCode,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly array $address,
        public readonly ?Carbon $dateOfBirth,
        public readonly Carbon $hireDate,
        public readonly string $nationalId,
        public readonly StaffStatus $status,
        public readonly array $metadata,
        public Lazy|DataCollection $roles,
        public Lazy|DataCollection $shifts,
        public Lazy|DataCollection $attendanceRecords,
        public Lazy|DataCollection $emergencyContacts,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    #[Computed]
    public function yearsOfService(): int
    {
        return $this->hireDate->diffInYears(now());
    }
}
```

### CreateStaffMemberData
```php
class CreateStaffMemberData extends Data
{
    public function __construct(
        #[Required, Email, Unique('staff_members')]
        public readonly string $email,
        
        #[Required, Min(2), Max(50)]
        public readonly string $firstName,
        
        #[Required, Min(2), Max(50)]
        public readonly string $lastName,
        
        #[Required, Unique('staff_members', 'employee_code')]
        public readonly string $employeeCode,
        
        #[Required]
        public readonly string $nationalId,
        
        #[Phone]
        public readonly ?string $phone,
        
        #[Required, Date, Before('today')]
        public readonly Carbon $dateOfBirth,
        
        #[Required, Date]
        public readonly Carbon $hireDate,
        
        public readonly array $address = [],
        public readonly array $emergencyContacts = [],
        public readonly array $roleIds = [],
        public readonly ?int $primaryLocationId = null,
    ) {}

    public static function rules(): array
    {
        return [
            'dateOfBirth' => ['before:' . now()->subYears(16)->format('Y-m-d')],
            'hireDate' => ['after_or_equal:dateOfBirth'],
        ];
    }
}
```

## Service Layer

### StaffService
```php
class StaffService implements StaffServiceInterface
{
    public function __construct(
        private StaffRepositoryInterface $staffRepo,
        private RoleRepositoryInterface $roleRepo,
        private ?LocationRepositoryInterface $locationRepo = null,
    ) {}

    public function createStaffMember(CreateStaffMemberData $data): StaffMemberData
    {
        // Validate location exists if provided
        if ($data->primaryLocationId && $this->locationRepo) {
            $location = $this->locationRepo->find($data->primaryLocationId);
            if (!$location) {
                throw new StaffException('Invalid location');
            }
        }

        // Create staff member
        $staff = $this->staffRepo->create($data);

        // Assign roles
        foreach ($data->roleIds as $roleId) {
            $this->assignRole($staff->id, $roleId, $data->primaryLocationId);
        }

        // Dispatch event
        event(new StaffMemberCreated($staff));

        return $staff;
    }

    public function assignRole(int $staffId, int $roleId, ?int $locationId = null): void
    {
        // Implementation
    }

    public function getStaffSchedule(int $staffId, Carbon $from, Carbon $to): array
    {
        // Implementation
    }
}
```

### ShiftService
```php
class ShiftService implements ShiftServiceInterface
{
    public function __construct(
        private ShiftRepositoryInterface $shiftRepo,
        private StaffRepositoryInterface $staffRepo,
        private AttendanceRepositoryInterface $attendanceRepo,
    ) {}

    public function scheduleShift(CreateShiftData $data): ShiftData
    {
        // Check for conflicts
        $conflicts = $this->shiftRepo->findConflicts(
            $data->staffMemberId,
            $data->startTime,
            $data->endTime
        );

        if ($conflicts->isNotEmpty()) {
            throw new InvalidShiftException('Shift conflicts with existing schedule');
        }

        // Create shift
        $shift = $this->shiftRepo->create($data);

        // Dispatch event
        event(new ShiftAssigned($shift));

        return $shift;
    }

    public function clockIn(ClockInData $data): AttendanceRecordData
    {
        // Implementation
    }

    public function clockOut(int $attendanceId, ClockOutData $data): AttendanceRecordData
    {
        // Implementation
    }
}
```

## Controllers

### Web/StaffController
```php
class StaffController extends Controller
{
    use HandlesPaginationBounds;

    public function __construct(
        private StaffServiceInterface $staffService
    ) {}

    public function index(Request $request): Response
    {
        $filters = StaffFilterData::from($request->query());
        $data = $this->staffService->getPaginatedStaff(
            $filters->toArray(),
            $request->integer('per_page', 15)
        );

        if ($redirect = $this->handleOutOfBoundsPagination(
            $data->pagination,
            $request,
            'staff.index'
        )) {
            return $redirect;
        }

        return Inertia::render('staff/index', $data->toArray());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = CreateStaffMemberData::validateAndCreate($request);
        $staff = $this->staffService->createStaffMember($data);

        return redirect()
            ->route('staff.show', $staff->id)
            ->with('success', 'Staff member created successfully');
    }

    public function show(int $id): Response
    {
        $staff = $this->staffService->getStaffMemberWithRelations($id);

        if (!$staff) {
            abort(404);
        }

        return Inertia::render('staff/show', [
            'staff' => $staff->toArray(),
        ]);
    }
}
```

### Api/StaffController
```php
class StaffController extends Controller
{
    public function __construct(
        private StaffServiceInterface $staffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = StaffFilterData::from($request->query());
        $data = $this->staffService->getPaginatedStaff(
            $filters->toArray(),
            $request->integer('per_page', 15)
        );

        return response()->json($data->toArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = CreateStaffMemberData::validateAndCreate($request);
        $staff = $this->staffService->createStaffMember($data);

        return response()->json($staff->toArray(), 201);
    }

    public function show(int $id): JsonResponse
    {
        $staff = $this->staffService->getStaffMemberWithRelations($id);

        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }

        return response()->json($staff->toArray());
    }
}
```

### Api/ClockController
```php
class ClockController extends Controller
{
    public function __construct(
        private ShiftServiceInterface $shiftService
    ) {}

    public function clockIn(Request $request): JsonResponse
    {
        $data = ClockInData::validateAndCreate($request);
        $attendance = $this->shiftService->clockIn($data);

        return response()->json($attendance->toArray());
    }

    public function clockOut(Request $request, int $attendanceId): JsonResponse
    {
        $data = ClockOutData::validateAndCreate($request);
        $attendance = $this->shiftService->clockOut($attendanceId, $data);

        return response()->json($attendance->toArray());
    }
}
```

## Frontend Components

### Pages Structure
```
resources/js/pages/staff/
├── index.tsx           # Staff list with filters
├── show.tsx           # Staff member detail view
├── create.tsx         # New staff member form
├── edit.tsx           # Edit staff member
├── schedule/
│   ├── index.tsx      # Schedule calendar view
│   ├── create.tsx     # Create shift form
│   └── weekly.tsx     # Weekly schedule view
├── attendance/
│   ├── index.tsx      # Attendance records
│   ├── clock.tsx      # Clock in/out interface
│   └── report.tsx     # Attendance reports
└── roles/
    ├── index.tsx      # Role management
    └── permissions.tsx # Permission matrix
```

### Module Components
```
resources/js/components/modules/staff/
├── StaffCard.tsx
├── StaffTable.tsx
├── StaffFilters.tsx
├── ShiftCalendar.tsx
├── ShiftCard.tsx
├── AttendanceChart.tsx
├── ClockInterface.tsx
├── RoleSelector.tsx
└── PermissionMatrix.tsx
```

## Cross-Module Dependencies

### Required Interfaces
- `LocationRepositoryInterface` - For multi-location staff assignments
- `BusinessRepositoryInterface` - For business-level configurations

### Optional Interfaces
- `OrderRepositoryInterface` - For performance metrics based on orders
- `ItemRepositoryInterface` - For tracking staff product knowledge

## Feature Flags

```php
// config/features.php
return [
    'staff.biometric_clock' => env('FEATURE_STAFF_BIOMETRIC', false),
    'staff.mobile_clock' => env('FEATURE_STAFF_MOBILE_CLOCK', true),
    'staff.shift_swapping' => env('FEATURE_SHIFT_SWAPPING', true),
    'staff.performance_tracking' => env('FEATURE_PERFORMANCE_TRACKING', false),
    'staff.training_modules' => env('FEATURE_TRAINING_MODULES', false),
    'staff.payroll_integration' => env('FEATURE_PAYROLL_INTEGRATION', false),
];
```

## Testing Strategy

### Unit Tests
- Repository methods
- Service business logic
- DTO validation
- Model relationships

### Feature Tests
- Web controller flows
- API endpoints
- Authentication & authorization
- Event dispatching
- Cross-module integration

### Test Examples
```php
// StaffServiceTest.php
public function test_creates_staff_member_with_roles()
{
    $data = CreateStaffMemberData::from([
        'email' => 'john@example.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'employeeCode' => 'EMP001',
        'nationalId' => '12345678-9',
        'dateOfBirth' => now()->subYears(25),
        'hireDate' => now(),
        'roleIds' => [1, 2],
    ]);

    $staff = $this->service->createStaffMember($data);

    $this->assertInstanceOf(StaffMemberData::class, $staff);
    $this->assertEquals('john@example.com', $staff->email);
    $this->assertCount(2, $staff->roles);
}

// ShiftServiceTest.php
public function test_prevents_conflicting_shifts()
{
    $existingShift = Shift::factory()->create([
        'staff_member_id' => 1,
        'start_time' => now()->setHour(9),
        'end_time' => now()->setHour(17),
    ]);

    $data = CreateShiftData::from([
        'staffMemberId' => 1,
        'startTime' => now()->setHour(14),
        'endTime' => now()->setHour(22),
    ]);

    $this->expectException(InvalidShiftException::class);
    $this->service->scheduleShift($data);
}
```

## Implementation Phases

### Phase 1: Core Staff Management (Week 1)
- [ ] Create module structure
- [ ] Implement basic CRUD for staff members
- [ ] Set up roles and permissions
- [ ] Create web and API controllers
- [ ] Build basic frontend views

### Phase 2: Shift Management (Week 2)
- [ ] Implement shift scheduling
- [ ] Add shift templates
- [ ] Create calendar view
- [ ] Add conflict detection
- [ ] Build shift assignment UI

### Phase 3: Attendance Tracking (Week 3)
- [ ] Implement clock in/out functionality
- [ ] Add attendance records
- [ ] Create attendance reports
- [ ] Add break tracking
- [ ] Build mobile clock interface

### Phase 4: Advanced Features (Week 4)
- [ ] Add time-off requests
- [ ] Implement shift swapping
- [ ] Add performance metrics
- [ ] Create analytics dashboard
- [ ] Add export functionality

## Security Considerations

1. **Data Protection**
   - Encrypt sensitive data (bank details, national IDs)
   - Implement audit logging for all changes
   - Use proper authorization checks

2. **Authentication**
   - PIN/password for clock-in
   - Biometric support (optional)
   - Session management for mobile apps

3. **Authorization**
   - Role-based access control
   - Location-specific permissions
   - Hierarchical permission inheritance

4. **API Security**
   - Rate limiting on clock endpoints
   - Geolocation verification
   - Device fingerprinting for mobile clock-in

## Performance Optimizations

1. **Database**
   - Index on employee_code, email
   - Composite index on (staff_member_id, shift_date)
   - Partial index on active staff

2. **Caching**
   - Cache role permissions
   - Cache staff schedules
   - Cache location assignments

3. **Query Optimization**
   - Eager load relationships
   - Use database views for complex reports
   - Implement query result pagination

## Integration Points

### Payroll Systems
- Export attendance data
- Calculate overtime and deductions
- Generate payroll reports

### HR Systems
- Sync employee data
- Import/export staff records
- Training and certification tracking

### Time Clock Hardware
- Biometric device integration
- RFID/NFC card readers
- PIN pad terminals

## Monitoring & Analytics

### Key Metrics
- Staff utilization rate
- Attendance rate
- Overtime hours
- Shift coverage
- Labor cost percentage

### Reports
- Daily attendance report
- Weekly schedule summary
- Monthly payroll report
- Performance reviews
- Training compliance

## Compliance & Regulations

### Chilean Labor Law Requirements
- Working hours limits
- Overtime calculations
- Break requirements
- Holiday entitlements
- Contract management

### Data Privacy (GDPR/Local Laws)
- Consent management
- Data retention policies
- Right to access/delete
- Data portability