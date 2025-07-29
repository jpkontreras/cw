<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;

class ModifierGroupData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required]
        public readonly string $name,
        
        public readonly ?string $description,
        
        #[Required, In(['single', 'multiple'])]
        public readonly string $selectionType = 'multiple',
        
        public readonly bool $isRequired = false,
        
        #[Numeric, Min(0)]
        public readonly int $minSelections = 0,
        
        public readonly ?int $maxSelections = null,
        
        public readonly bool $isActive = true,
        
        public readonly ?Carbon $createdAt = null,
        
        public readonly ?Carbon $updatedAt = null,
        
        public readonly ?Carbon $deletedAt = null,
    ) {}
    
    /**
     * Check if the group allows multiple selections
     */
    public function allowsMultiple(): bool
    {
        return $this->selectionType === 'multiple';
    }
    
    /**
     * Validate selection count
     */
    public function validateSelectionCount(int $count): bool
    {
        if ($count < $this->minSelections) {
            return false;
        }
        
        if ($this->maxSelections !== null && $count > $this->maxSelections) {
            return false;
        }
        
        if ($this->selectionType === 'single' && $count > 1) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get selection rules as a readable string
     */
    public function getSelectionRules(): string
    {
        if ($this->selectionType === 'single') {
            return $this->isRequired ? 'Select exactly 1' : 'Select up to 1';
        }
        
        if ($this->minSelections > 0 && $this->maxSelections !== null) {
            return sprintf('Select %d to %d', $this->minSelections, $this->maxSelections);
        }
        
        if ($this->minSelections > 0) {
            return sprintf('Select at least %d', $this->minSelections);
        }
        
        if ($this->maxSelections !== null) {
            return sprintf('Select up to %d', $this->maxSelections);
        }
        
        return 'Select any number';
    }
}