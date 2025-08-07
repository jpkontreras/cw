<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\MenuVersion;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\In;

class MenuVersionData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required, IntegerType]
        public readonly int $menuId,
        public readonly int $versionNumber,
        public readonly ?string $versionName,
        public readonly array $snapshot,
        #[Required, In(['created', 'updated', 'published', 'archived'])]
        public readonly string $changeType,
        public readonly ?string $changeDescription,
        public readonly ?int $createdBy,
        public readonly ?\DateTimeInterface $publishedAt,
        public readonly ?\DateTimeInterface $archivedAt,
        public readonly ?array $metadata,
        public readonly ?\DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $updatedAt,
    ) {}
    
    public static function fromModel(MenuVersion $version): self
    {
        return new self(
            id: $version->id,
            menuId: $version->menu_id,
            versionNumber: $version->version_number,
            versionName: $version->version_name,
            snapshot: $version->snapshot,
            changeType: $version->change_type,
            changeDescription: $version->change_description,
            createdBy: $version->created_by,
            publishedAt: $version->published_at,
            archivedAt: $version->archived_at,
            metadata: $version->metadata,
            createdAt: $version->created_at,
            updatedAt: $version->updated_at,
        );
    }
}