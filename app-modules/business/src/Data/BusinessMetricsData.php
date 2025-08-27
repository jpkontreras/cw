<?php

declare(strict_types=1);

namespace Colame\Business\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BusinessMetricsData extends BaseData
{
    public function __construct(
        public readonly int $businessId,
        public readonly int $totalUsers,
        public readonly int $totalLocations,
        public readonly int $totalOrders,
        public readonly int $totalItems,
        public readonly int $totalStaff,
        public readonly float $totalRevenue,
        public readonly array $ordersByStatus,
        public readonly array $recentActivity,
        public readonly array $usageLimits,
        public readonly array $currentUsage,
        public readonly float $usagePercentage,
        public readonly string $subscriptionStatus,
        public readonly ?int $daysUntilRenewal,
        public readonly array $monthlyStats,
    ) {}
}