<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;

class AllergenInfoData extends BaseData
{
    public function __construct(
        public bool $containsMilk = false,
        public bool $containsEggs = false,
        public bool $containsFish = false,
        public bool $containsShellfish = false,
        public bool $containsTreeNuts = false,
        public bool $containsPeanuts = false,
        public bool $containsWheat = false,
        public bool $containsSoy = false,
        public bool $containsSesame = false,
        public bool $containsCelery = false,
        public bool $containsMustard = false,
        public bool $containsLupin = false,
        public bool $containsMolluscs = false,
        public bool $containsSulphites = false,
        public ?array $crossContaminationRisk = [],
        public ?string $allergenStatement = null,
        public ?array $customAllergens = [],
    ) {}
    
    /**
     * Get list of contained allergens
     */
    public function getContainedAllergens(): array
    {
        $allergens = [];
        
        if ($this->containsMilk) $allergens[] = 'milk';
        if ($this->containsEggs) $allergens[] = 'eggs';
        if ($this->containsFish) $allergens[] = 'fish';
        if ($this->containsShellfish) $allergens[] = 'shellfish';
        if ($this->containsTreeNuts) $allergens[] = 'tree_nuts';
        if ($this->containsPeanuts) $allergens[] = 'peanuts';
        if ($this->containsWheat) $allergens[] = 'wheat';
        if ($this->containsSoy) $allergens[] = 'soy';
        if ($this->containsSesame) $allergens[] = 'sesame';
        if ($this->containsCelery) $allergens[] = 'celery';
        if ($this->containsMustard) $allergens[] = 'mustard';
        if ($this->containsLupin) $allergens[] = 'lupin';
        if ($this->containsMolluscs) $allergens[] = 'molluscs';
        if ($this->containsSulphites) $allergens[] = 'sulphites';
        
        if ($this->customAllergens) {
            $allergens = array_merge($allergens, $this->customAllergens);
        }
        
        return $allergens;
    }
    
    /**
     * Check if item contains any major allergens
     */
    public function hasAllergens(): bool
    {
        return !empty($this->getContainedAllergens());
    }
    
    /**
     * Create from array of allergen strings
     */
    public static function fromArray(array $allergens): self
    {
        $data = new self();
        
        foreach ($allergens as $allergen) {
            $property = 'contains' . ucfirst(str_replace('_', '', ucwords($allergen, '_')));
            if (property_exists($data, $property)) {
                $data->$property = true;
            } else {
                $data->customAllergens[] = $allergen;
            }
        }
        
        return $data;
    }
}