<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ModifierGroupWithModifiersData extends BaseData
{
    public function __construct(
        public readonly ModifierGroupData $modifierGroup,

        #[DataCollectionOf(ItemModifierData::class)]
        public readonly array $modifiers,

        #[Numeric, Min(0)]
        public readonly int $sortOrder = 0,
    ) {}

    /**
     * Get active modifiers only
     */
    public function getActiveModifiers(): array
    {
        return array_filter($this->modifiers, fn($modifier) => $modifier->isActive);
    }

    /**
     * Get default modifiers
     */
    public function getDefaultModifiers(): array
    {
        return array_filter($this->modifiers, fn($modifier) => $modifier->isDefault);
    }

    /**
     * Check if group has any modifiers
     */
    public function hasModifiers(): bool
    {
        return !empty($this->modifiers);
    }

    /**
     * Validate modifier selections
     */
    public function validateSelections(array $selectedModifierIds): bool
    {
        // Get IDs of active modifiers in this group
        $availableIds = array_map(fn($m) => $m->id, $this->getActiveModifiers());

        // Check all selected modifiers belong to this group
        $selectedInGroup = array_intersect($selectedModifierIds, $availableIds);
        $selectionCount = count($selectedInGroup);

        // Validate selection count
        return $this->modifierGroup->validateSelectionCount($selectionCount);
    }
}
