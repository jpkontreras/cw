<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class UserItemFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'order_position',
    ];

    protected $casts = [
        'order_position' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}