<?php

declare(strict_types=1);

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Item modifier model
 */
class ItemModifier extends Model
{
    use HasFactory;

    /**
     * The table associated with the model
     */
    protected $table = 'item_modifiers';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'group_id',
        'name',
        'description',
        'price',
        'is_available',
        'is_default',
        'sort_order',
        'max_quantity',
        'metadata',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'group_id' => 'integer',
        'price' => 'float',
        'is_available' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'max_quantity' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the modifier group (internal use only)
     */
    public function group()
    {
        return $this->belongsTo(ItemModifierGroup::class, 'group_id');
    }
}