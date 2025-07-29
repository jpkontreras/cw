<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_id',
        'ingredient_id',
        'quantity',
        'unit',
        'is_optional',
    ];
    
    protected $casts = [
        'recipe_id' => 'integer',
        'ingredient_id' => 'integer',
        'quantity' => 'decimal:3',
        'is_optional' => 'boolean',
    ];
    
    protected $attributes = [
        'is_optional' => false,
    ];
}