<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Order item model
 * 
 * Note: Following interface-based architecture, this model only stores foreign keys
 * and does not have cross-module relationships
 */
class OrderItem extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model
     */
    protected $table = 'order_items';
    
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Colame\Order\Database\Factories\OrderItemFactory::new();
    }

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'order_id',
        'item_id',
        'menu_section_id',
        'menu_item_id',
        'item_name',
        'base_item_name',
        'quantity',
        'base_price',
        'unit_price',
        'modifiers_total',
        'total_price',
        'status',
        'kitchen_status',
        'course',
        'notes',
        'special_instructions',
        'modifiers',
        'modifier_history',
        'modifier_count',
        'metadata',
        'modified_at',
        'prepared_at',
        'served_at',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'order_id' => 'string',  // UUID, not integer
        'item_id' => 'integer',
        'menu_section_id' => 'integer',
        'menu_item_id' => 'integer',
        'quantity' => 'integer',
        'base_price' => 'integer',  // Store as integer (minor units)
        'unit_price' => 'integer',  // Store as integer (minor units)
        'modifiers_total' => 'integer',  // Store as integer (minor units)
        'total_price' => 'integer',  // Store as integer (minor units)
        'modifiers' => 'array',
        'modifier_history' => 'array',
        'modifier_count' => 'integer',
        'metadata' => 'array',
        'modified_at' => 'datetime',
        'prepared_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    /**
     * Default values for attributes
     */
    protected $attributes = [
        'status' => 'pending',
        'kitchen_status' => 'pending',
        'quantity' => 1,
    ];

    /**
     * Item statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_SERVED = 'served';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Valid item statuses
     */
    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PREPARING,
        self::STATUS_PREPARED,
        self::STATUS_SERVED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Kitchen statuses
     */
    public const KITCHEN_STATUS_PENDING = 'pending';
    public const KITCHEN_STATUS_PREPARING = 'preparing';
    public const KITCHEN_STATUS_READY = 'ready';
    public const KITCHEN_STATUS_SERVED = 'served';

    /**
     * Course types
     */
    public const COURSE_STARTER = 'starter';
    public const COURSE_MAIN = 'main';
    public const COURSE_DESSERT = 'dessert';
    public const COURSE_BEVERAGE = 'beverage';

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Calculate total price before saving
        static::saving(function (OrderItem $item) {
            if (!$item->total_price) {
                $modifiersTotal = 0;
                if ($item->modifiers) {
                    foreach ($item->modifiers as $modifier) {
                        $modifiersTotal += (int)($modifier['price'] ?? 0); // Already in minor units
                    }
                }
                $item->total_price = ($item->unit_price + $modifiersTotal) * $item->quantity;
            }
        });

        // Set prepared_at when status changes to prepared
        static::updating(function (OrderItem $item) {
            if ($item->isDirty('status') && $item->status === self::STATUS_PREPARED) {
                $item->prepared_at = now();
            }
            
            // Set served_at when kitchen_status changes to served
            if ($item->isDirty('kitchen_status') && $item->kitchen_status === self::KITCHEN_STATUS_SERVED) {
                $item->served_at = now();
            }
        });
    }

    /**
     * Get the order
     * Note: This is only for internal module use, not exposed via interfaces
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}