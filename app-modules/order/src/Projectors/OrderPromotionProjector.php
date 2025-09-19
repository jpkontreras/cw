<?php

declare(strict_types=1);

namespace Colame\Order\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Events\OrderEvents\PromotionApplied;
use Colame\Order\Events\OrderEvents\PromotionRemoved;
use Colame\Order\Models\OrderPromotion;

class OrderPromotionProjector extends Projector
{
    /**
     * Handle promotion applied
     */
    public function onPromotionApplied(PromotionApplied $event): void
    {
        OrderPromotion::create([
            'order_id' => $event->orderId,
            'promotion_id' => $event->promotionId,
            'code' => $event->code,
            'type' => $event->type,
            'value' => $event->value,
            'discount_amount' => $event->discountAmount,
            'metadata' => $event->metadata ?? [],
        ]);
    }

    /**
     * Handle promotion removed
     */
    public function onPromotionRemoved(PromotionRemoved $event): void
    {
        OrderPromotion::where('order_id', $event->orderId)
            ->where('promotion_id', $event->promotionId)
            ->delete();
    }
}