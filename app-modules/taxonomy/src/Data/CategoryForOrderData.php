<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;

class CategoryForOrderData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?array $metadata = null,
    ) {}
    
    #[Computed]
    public function emoji(): string
    {
        if ($this->metadata && isset($this->metadata['emoji'])) {
            return $this->metadata['emoji'];
        }
        
        return $this->getDefaultEmoji();
    }
    
    #[Computed]
    public function color(): string
    {
        if ($this->metadata && isset($this->metadata['color'])) {
            return $this->metadata['color'];
        }
        
        return $this->getDefaultColor();
    }
    
    /**
     * Create from TaxonomyData
     */
    public static function fromTaxonomy(TaxonomyData $taxonomy): self
    {
        return new self(
            id: $taxonomy->id,
            name: $taxonomy->name,
            slug: $taxonomy->slug,
            metadata: $taxonomy->metadata,
        );
    }
    
    /**
     * Get default emoji based on category name
     */
    private function getDefaultEmoji(): string
    {
        $name = strtolower($this->name);
        
        $emojiMap = [
            'pizza' => '🍕',
            'burger' => '🍔',
            'sandwich' => '🥪',
            'salad' => '🥗',
            'pasta' => '🍝',
            'drink' => '🥤',
            'beverage' => '🥤',
            'dessert' => '🍰',
            'coffee' => '☕',
            'breakfast' => '🍳',
            'meat' => '🥩',
            'chicken' => '🍗',
            'fish' => '🐟',
            'seafood' => '🦐',
            'soup' => '🍲',
            'appetizer' => '🍟',
            'snack' => '🍿',
            'bread' => '🥖',
            'fruit' => '🍎',
            'vegetable' => '🥦',
        ];
        
        foreach ($emojiMap as $key => $emoji) {
            if (str_contains($name, $key)) {
                return $emoji;
            }
        }
        
        return '📦';
    }
    
    /**
     * Get default color gradient based on category name
     */
    private function getDefaultColor(): string
    {
        $name = strtolower($this->name);
        
        $colorMap = [
            'pizza' => 'from-yellow-400 to-orange-500',
            'burger' => 'from-orange-400 to-red-500',
            'salad' => 'from-green-400 to-green-600',
            'drink' => 'from-blue-400 to-blue-600',
            'dessert' => 'from-pink-400 to-purple-500',
            'coffee' => 'from-amber-600 to-amber-800',
            'breakfast' => 'from-yellow-300 to-orange-400',
            'meat' => 'from-red-500 to-red-700',
            'seafood' => 'from-cyan-400 to-blue-500',
        ];
        
        foreach ($colorMap as $key => $color) {
            if (str_contains($name, $key)) {
                return $color;
            }
        }
        
        // Generate consistent color based on name hash
        $colors = [
            'from-slate-400 to-slate-600',
            'from-gray-400 to-gray-600',
            'from-zinc-400 to-zinc-600',
            'from-stone-400 to-stone-600',
            'from-amber-400 to-amber-600',
            'from-emerald-400 to-emerald-600',
            'from-teal-400 to-teal-600',
            'from-indigo-400 to-indigo-600',
            'from-violet-400 to-violet-600',
            'from-fuchsia-400 to-fuchsia-600',
        ];
        
        $hash = crc32($this->name);
        return $colors[$hash % count($colors)];
    }
}