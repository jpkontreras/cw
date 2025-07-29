<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;

class ItemImageData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required, Numeric]
        public readonly int $itemId,
        
        #[Required]
        public readonly string $imagePath,
        
        public readonly ?string $thumbnailPath,
        
        public readonly ?string $altText,
        
        public readonly bool $isPrimary = false,
        
        #[Numeric, Min(0)]
        public readonly int $sortOrder = 0,
        
        public readonly ?Carbon $createdAt = null,
        
        public readonly ?Carbon $updatedAt = null,
    ) {}
    
    /**
     * Get full URL for the image
     */
    public function getImageUrl(): string
    {
        return asset('storage/' . $this->imagePath);
    }
    
    /**
     * Get full URL for the thumbnail
     */
    public function getThumbnailUrl(): string
    {
        if ($this->thumbnailPath) {
            return asset('storage/' . $this->thumbnailPath);
        }
        
        // Return main image if no thumbnail
        return $this->getImageUrl();
    }
}