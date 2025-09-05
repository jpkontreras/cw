<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Item extends Model
{
    use SoftDeletes, Searchable;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sku',
        'barcode',
        'base_price',
        'sale_price',
        'cost',
        'preparation_time',
        'is_active',
        'is_available',
        'is_featured',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'type',
        'allergens',
        'nutritional_info',
        'sort_order',
        'available_from',
        'available_until',
    ];
    
    protected $casts = [
        'base_price' => 'integer',  // Store as integer (minor units)
        'sale_price' => 'integer',  // Store as integer (minor units)
        'cost' => 'integer',         // Store as integer (minor units)
        'preparation_time' => 'integer',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'track_inventory' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'allergens' => 'array',
        'nutritional_info' => 'array',
        'sort_order' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];
    
    protected $attributes = [
        'cost' => 0,
        'preparation_time' => 0,
        'is_active' => true,
        'is_available' => true,
        'is_featured' => false,
        'track_inventory' => false,
        'stock_quantity' => 0,
        'low_stock_threshold' => 10,
        'type' => 'product',
        'sort_order' => 0,
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            if (empty($item->slug)) {
                $item->slug = static::generateUniqueSlug($item->name);
            }
        });
        
        static::updating(function ($item) {
            if ($item->isDirty('name') && !$item->isDirty('slug')) {
                $item->slug = static::generateUniqueSlug($item->name, $item->id);
            }
        });
    }
    
    /**
     * Generate a unique slug
     */
    protected static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;
        
        while (static::where('slug', $slug)
            ->when($excludeId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        
        return $slug;
    }
    
    /**
     * Scope for active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for available items
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', now());
            });
    }
    
    /**
     * Scope for featured items
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    /**
     * Scope for items that track inventory
     */
    public function scopeTracksInventory($query)
    {
        return $query->where('track_inventory', true);
    }
    
    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->tracksInventory()
            ->whereRaw('stock_quantity <= low_stock_threshold');
    }
    
    /**
     * Scope for out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->tracksInventory()
            ->where('stock_quantity', '<=', 0);
    }
    
    /**
     * Get the indexable data array for the model.
     * This is what gets sent to MeiliSearch for indexing.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'category' => 'Uncategorized', // TODO: get from categories relation
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
            'is_available' => $this->is_available,
            'preparation_time' => $this->preparation_time,
            'stock_quantity' => $this->stock_quantity,
            'search_keywords' => $this->search_keywords ?? '',
            'order_frequency' => 0, // TODO: track this field
        ];
    }
    
    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }
}