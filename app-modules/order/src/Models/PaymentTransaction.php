<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Payment transaction model
 * 
 * @property int $id
 * @property int $order_id
 * @property string $payment_method
 * @property float $amount
 * @property string $status
 * @property string|null $reference_number
 * @property \DateTime|null $processed_at
 * @property array|null $metadata
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class PaymentTransaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'payment_method',
        'amount',
        'status',
        'reference_number',
        'processed_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Payment statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Payment methods
     */
    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_OTHER = 'other';

    /**
     * Get the order that owns the payment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}