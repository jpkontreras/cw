<?php

declare(strict_types=1);

namespace Colame\Business\Data;

use App\Core\Data\BaseData;
use Colame\Business\Enums\BusinessUserRole;
use Colame\Business\Models\BusinessUser;
use DateTime;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BusinessUserData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $businessId,
        public readonly int $userId,
        public readonly BusinessUserRole $role,
        public readonly ?array $permissions,
        public readonly string $status,
        public readonly bool $isOwner,
        public readonly ?string $invitationToken,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $invitedAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $joinedAt,
        public readonly ?int $invitedBy,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $lastAccessedAt,
        public readonly ?array $preferences,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly DateTime $createdAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly DateTime $updatedAt,
        // Related data (lazy loaded)
        public readonly ?string $userName,
        public readonly ?string $userEmail,
        public readonly ?string $businessName,
    ) {}

    #[Computed]
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    #[Computed]
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    #[Computed]
    public function canManageUsers(): bool
    {
        return $this->role->hasPermission('manage_users');
    }

    #[Computed]
    public function canManageSettings(): bool
    {
        return $this->role->hasPermission('manage_settings');
    }

    public static function from(mixed ...$payloads): static
    {
        if (count($payloads) === 1 && $payloads[0] instanceof BusinessUser) {
            return self::fromModel($payloads[0]);
        }
        
        return parent::from(...$payloads);
    }
    
    public static function fromModel(BusinessUser $businessUser): self
    {
        return new self(
            id: $businessUser->id,
            businessId: $businessUser->business_id,
            userId: $businessUser->user_id,
            role: BusinessUserRole::from($businessUser->role),
            permissions: $businessUser->permissions,
            status: $businessUser->status,
            isOwner: $businessUser->is_owner,
            invitationToken: $businessUser->invitation_token,
            invitedAt: $businessUser->invited_at,
            joinedAt: $businessUser->joined_at,
            invitedBy: $businessUser->invited_by,
            lastAccessedAt: $businessUser->last_accessed_at,
            preferences: $businessUser->preferences,
            createdAt: $businessUser->created_at,
            updatedAt: $businessUser->updated_at,
            userName: $businessUser->user?->name,
            userEmail: $businessUser->user?->email,
            businessName: $businessUser->business?->name,
        );
    }
}