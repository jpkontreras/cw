<?php

namespace Colame\Staff\Data;

use Carbon\Carbon;
use Colame\Staff\Enums\ClockMethod;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ClockInData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $staffMemberId,
        
        #[Required]
        public readonly int $locationId,
        
        #[Nullable]
        public readonly ?int $shiftId,
        
        #[Required, In(ClockMethod::class)]
        public readonly ClockMethod $method,
        
        #[Nullable]
        public readonly ?array $location, // GPS coordinates [lat, lng]
        
        #[Nullable]
        public readonly ?string $deviceId,
        
        #[Nullable]
        public readonly ?string $ipAddress,
        
        #[Nullable]
        public readonly ?string $notes,
        
        public readonly Carbon $timestamp,
    ) {}
    
    public static function fromRequest($request): self
    {
        return new self(
            staffMemberId: $request->integer('staffMemberId'),
            locationId: $request->integer('locationId'),
            shiftId: $request->integer('shiftId'),
            method: ClockMethod::from($request->string('method', 'manual')),
            location: $request->input('location'),
            deviceId: $request->string('deviceId'),
            ipAddress: $request->ip(),
            notes: $request->string('notes'),
            timestamp: now(),
        );
    }
}