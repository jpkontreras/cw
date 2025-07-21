<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order model
 * 
 * Note: Following interface-based architecture, this model only stores foreign keys
 * and does not have cross-module relationships
 */
class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model
     */
    protected $table = 'orders';
    
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Colame\Order\Database\Factories\OrderFactory::new();
    }

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'location_id',
        'status',
        'type',
        'priority',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'table_number',
        'waiter_id',
        'subtotal',
        'tax_amount',
        'tip_amount',
        'discount_amount',
        'total_amount',
        'payment_status',
        'notes',
        'special_instructions',
        'cancel_reason',
        'metadata',
        'placed_at',
        'confirmed_at',
        'preparing_at',
        'ready_at',
        'delivering_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'scheduled_at',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'user_id' => 'integer',
        'location_id' => 'integer',
        'waiter_id' => 'integer',
        'table_number' => 'integer',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'tip_amount' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'metadata' => 'array',
        'placed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivering_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /**
     * Default values for attributes
     */
    protected $attributes = [
        'status' => 'draft',
        'type' => 'dine_in',
        'priority' => 'normal',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'tax_amount' => 0,
        'tip_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 0,
    ];

    /**
     * Order statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PLACED = 'placed';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Valid order statuses
     */
    public const VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PLACED,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_DELIVERING,
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    /**
     * Order types
     */
    public const TYPE_DINE_IN = 'dine_in';
    public const TYPE_TAKEOUT = 'takeout';
    public const TYPE_DELIVERY = 'delivery';
    public const TYPE_CATERING = 'catering';

    /**
     * Valid order types
     */
    public const VALID_TYPES = [
        self::TYPE_DINE_IN,
        self::TYPE_TAKEOUT,
        self::TYPE_DELIVERY,
        self::TYPE_CATERING,
    ];

    /**
     * Payment statuses
     */
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_REFUNDED = 'refunded';

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Set placed_at when status changes to placed
        static::updating(function (Order $order) {
            if ($order->isDirty('status')) {
                switch ($order->status) {
                    case self::STATUS_PLACED:
                        $order->placed_at = now();
                        break;
                    case self::STATUS_CONFIRMED:
                        $order->confirmed_at = now();
                        break;
                    case self::STATUS_PREPARING:
                        $order->preparing_at = now();
                        break;
                    case self::STATUS_READY:
                        $order->ready_at = now();
                        break;
                    case self::STATUS_COMPLETED:
                        $order->completed_at = now();
                        break;
                    case self::STATUS_CANCELLED:
                        $order->cancelled_at = now();
                        break;
                }
            }
        });
    }

    /**
     * Get the order items
     * Note: This is only for internal module use, not exposed via interfaces
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payments for the order.
     */
    public function payments()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get the waiter for the order.
     * Note: This relationship is commented out to follow strict module boundaries
     * Use UserRepositoryInterface in services to get waiter details
     */
    // public function waiter()
    // {
    //     return $this->belongsTo(\App\Models\User::class, 'waiter_id');
    // }

    /**
     * Check if order can be modified
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PLACED]);
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PLACED,
            self::STATUS_CONFIRMED
        ]);
    }
}