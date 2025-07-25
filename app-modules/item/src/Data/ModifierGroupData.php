<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Illuminate\Support\Collection;

/**
 * Modifier group data transfer object
 * 
 * Represents groups of modifiers (e.g., "Toppings", "Size", "Add-ons")
 */
class ModifierGroupData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $type, // 'single' or 'multiple'
        public readonly bool $isRequired,
        public readonly ?int $minSelections,
        public readonly ?int $maxSelections,
        public readonly int $sortOrder,
        /** @var Collection<ItemModifierData> */
        public readonly Collection $modifiers,
    ) {}

    /**
     * Check if this is a single-selection group
     */
    public function isSingleChoice(): bool
    {
        return $this->type === 'single';
    }

    /**
     * Check if this is a multiple-selection group
     */
    public function isMultipleChoice(): bool
    {
        return $this->type === 'multiple';
    }

    /**
     * Get the default modifier if any
     */
    public function getDefaultModifier(): ?ItemModifierData
    {
        return $this->modifiers->firstWhere('isDefault', true);
    }

    /**
     * Validate selection count
     */
    public function isValidSelectionCount(int $count): bool
    {
        if ($this->minSelections !== null && $count < $this->minSelections) {
            return false;
        }

        if ($this->maxSelections !== null && $count > $this->maxSelections) {
            return false;
        }

        return true;
    }
}