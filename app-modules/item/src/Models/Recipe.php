<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'item_id',
        'item_variant_id',
        'instructions',
        'prep_time_minutes',
        'cook_time_minutes',
        'yield_quantity',
        'yield_unit',
        'notes',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'item_variant_id' => 'integer',
        'prep_time_minutes' => 'integer',
        'cook_time_minutes' => 'integer',
        'yield_quantity' => 'decimal:2',
    ];
    
    protected $attributes = [
        'prep_time_minutes' => 0,
        'cook_time_minutes' => 0,
        'yield_quantity' => 1,
        'yield_unit' => 'portion',
    ];
}