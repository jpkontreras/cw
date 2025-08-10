<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;

class DietaryLabelsData extends BaseData
{
    public function __construct(
        public bool $vegan = false,
        public bool $vegetarian = false,
        public bool $glutenFree = false,
        public bool $dairyFree = false,
        public bool $nutFree = false,
        public bool $halal = false,
        public bool $kosher = false,
        public bool $organic = false,
        public bool $lowCarb = false,
        public bool $keto = false,
        public bool $paleo = false,
        public bool $sugarFree = false,
        public bool $lowSodium = false,
        public bool $highProtein = false,
        public bool $wholeFoods = false,
        public ?array $customLabels = [],
    ) {}
    
    /**
     * Get active dietary labels as an array of strings
     */
    public function getActiveLabels(): array
    {
        $labels = [];
        
        if ($this->vegan) $labels[] = 'vegan';
        if ($this->vegetarian) $labels[] = 'vegetarian';
        if ($this->glutenFree) $labels[] = 'gluten_free';
        if ($this->dairyFree) $labels[] = 'dairy_free';
        if ($this->nutFree) $labels[] = 'nut_free';
        if ($this->halal) $labels[] = 'halal';
        if ($this->kosher) $labels[] = 'kosher';
        if ($this->organic) $labels[] = 'organic';
        if ($this->lowCarb) $labels[] = 'low_carb';
        if ($this->keto) $labels[] = 'keto';
        if ($this->paleo) $labels[] = 'paleo';
        if ($this->sugarFree) $labels[] = 'sugar_free';
        if ($this->lowSodium) $labels[] = 'low_sodium';
        if ($this->highProtein) $labels[] = 'high_protein';
        if ($this->wholeFoods) $labels[] = 'whole_foods';
        
        if ($this->customLabels) {
            $labels = array_merge($labels, $this->customLabels);
        }
        
        return $labels;
    }
    
    /**
     * Create from array of label strings
     */
    public static function fromArray(array $labels): self
    {
        $data = new self();
        
        foreach ($labels as $label) {
            $property = lcfirst(str_replace('_', '', ucwords($label, '_')));
            if (property_exists($data, $property)) {
                $data->$property = true;
            } else {
                $data->customLabels[] = $label;
            }
        }
        
        return $data;
    }
}