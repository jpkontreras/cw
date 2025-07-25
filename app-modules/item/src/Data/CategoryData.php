<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use App\Core\Data\BaseData;

/**
 * Category data transfer object
 * 
 * Simple DTO for category information
 */
class CategoryData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?int $parentId,
        public readonly string $slug,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly ?string $image,
    ) {}
}