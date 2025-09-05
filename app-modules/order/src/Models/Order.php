<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Illuminate\Support\Str;
use Spatie\ModelStates\HasStates;
use Colame\Order\States\OrderState;
use Colame\Order\States\Transitions\ToConfirmed;
use Colame\Order\States\Transitions\ToCancelled;

/**
 * Order model
 * 
 * Note: Following interface-based architecture, this model only stores foreign keys
 * and does not have cross-module relationships
 */
class Order extends Model
{
    use HasFactory, SoftDeletes, Searchable, HasStates;

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
        'uuid',
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
        'tax',
        'tip',
        'discount',
        'total',
        'payment_status',
        'notes',
        'special_instructions',
        'cancel_reason',
        'cancellation_reason',
        'payment_method',
        'modification_count',
        'last_modified_at',
        'last_modified_by',
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
        'status' => OrderState::class,  // Cast to state object
        'user_id' => 'integer',
        'location_id' => 'integer',
        'waiter_id' => 'integer',
        'table_number' => 'integer',
        'subtotal' => 'integer',  // Store as integer (minor units)
        'tax' => 'integer',       // Store as integer (minor units)
        'tip' => 'integer',       // Store as integer (minor units)
        'discount' => 'integer',  // Store as integer (minor units)
        'total' => 'integer',     // Store as integer (minor units)
        'modification_count' => 'integer',
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
        'last_modified_at' => 'datetime',
    ];

    /**
     * Default values for attributes
     */
    protected $attributes = [
        'status' => 'draft',  // Default state string
        'type' => 'dine_in',
        'priority' => 'normal',
        'payment_status' => 'pending',
        'subtotal' => 0,
        'tax' => 0,
        'tip' => 0,
        'discount' => 0,
        'total' => 0,
    ];

    /**
     * Register model states
     */
    protected function registerStates(): void
    {
        $this->addState('status', OrderState::config()
            ->default(\Colame\Order\States\DraftState::class)
            ->registerTransition(ToConfirmed::class)
            ->registerTransition(ToCancelled::class)
        );
    }
    
    /**
     * Order statuses (legacy constants for compatibility)
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

        // Auto-generate UUID for new orders
        static::creating(function (Order $order) {
            if (empty($order->uuid)) {
                $order->uuid = Str::uuid()->toString();
            }
        });

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
        // Use state object method
        return $this->status->canBeModified();
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        // Use state object method
        return $this->status->canBeCancelled();
    }
    
    /**
     * Check if order can add items
     */
    public function canAddItems(): bool
    {
        return $this->status->canAddItems();
    }
    
    /**
     * Check if payment can be processed
     */
    public function canProcessPayment(): bool
    {
        return $this->status->canProcessPayment();
    }
    
    /**
     * Check if order affects kitchen
     */
    public function affectsKitchen(): bool
    {
        return $this->status->affectsKitchen();
    }
    
    /**
     * Transition to a new state
     */
    public function transitionTo(string $stateClass): self
    {
        $this->status->transitionTo($stateClass);
        return $this;
    }

    /**
     * Get the indexable data array for the model.
     * This is what gets sent to MeiliSearch for indexing.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'type' => $this->type,
            'priority' => $this->priority,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'table_number' => $this->table_number,
            'location_id' => $this->location_id,
            'waiter_id' => $this->waiter_id,
            'total' => $this->total,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'special_instructions' => $this->special_instructions,
            'created_at' => $this->created_at?->timestamp,
            'placed_at' => $this->placed_at?->timestamp,
            'view_count' => $this->view_count ?? 0,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        // Don't index draft orders
        return $this->status !== self::STATUS_DRAFT;
    }
}
