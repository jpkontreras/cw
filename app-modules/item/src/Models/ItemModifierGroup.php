<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class ItemModifierGroup extends Model
{
    protected $fillable = [
        'item_id',
        'modifier_group_id',
        'sort_order',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'modifier_group_id' => 'integer',
        'sort_order' => 'integer',
    ];
    
    protected $attributes = [
        'sort_order' => 0,
    ];
}