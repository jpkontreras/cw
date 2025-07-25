<?php

declare(strict_types=1);

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Item modifier group model
 */
class ItemModifierGroup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model
     */
    protected $table = 'item_modifier_groups';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'is_required',
        'min_selections',
        'max_selections',
        'sort_order',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'is_required' => 'boolean',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Group types
     */
    public const TYPE_SINGLE = 'single';
    public const TYPE_MULTIPLE = 'multiple';

    /**
     * Get modifiers in this group (internal use only)
     */
    public function modifiers()
    {
        return $this->hasMany(ItemModifier::class, 'group_id')->orderBy('sort_order');
    }

    /**
     * Get items using this modifier group (internal use only)
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_modifier_group_items')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}