<?php

return [
    'status' => [
        'draft' => 'Order draft created',
        'started' => 'Order started',
        'items_added' => 'Items added to order',
        'items_validated' => 'Order items validated',
        'promotions_calculated' => 'Promotions calculated',
        'price_calculated' => 'Order price calculated',
        'confirmed' => 'Order confirmed',
        'preparing' => 'Kitchen started preparing order',
        'ready' => 'Order ready for pickup',
        'delivering' => 'Order out for delivery',
        'delivered' => 'Order delivered',
        'completed' => 'Order completed successfully',
        'cancelled' => 'Order cancelled',
        'refunded' => 'Order refunded',
    ],
    
    'transitions' => [
        'to_confirmed' => 'Order has been confirmed',
        'to_preparing' => 'Kitchen has started preparing',
        'to_ready' => 'Order is ready for pickup',
        'to_completed' => 'Order has been completed',
        'to_cancelled' => 'Order has been cancelled',
        'to_refunded' => 'Order has been refunded',
    ],
    
    'actions' => [
        'create' => 'Create Order',
        'update' => 'Update Order',
        'confirm' => 'Confirm Order',
        'cancel' => 'Cancel Order',
        'complete' => 'Complete Order',
        'refund' => 'Refund Order',
    ],
    
    'messages' => [
        'created' => 'Order :number has been created successfully',
        'updated' => 'Order :number has been updated',
        'confirmed' => 'Order :number has been confirmed',
        'cancelled' => 'Order :number has been cancelled',
        'completed' => 'Order :number has been completed',
        'not_found' => 'Order not found',
        'cannot_modify' => 'Order cannot be modified in current status',
        'cannot_cancel' => 'Order cannot be cancelled in current status',
    ],
    
    'payment' => [
        'processed' => 'Payment processed successfully',
        'failed' => 'Payment failed: :reason',
        'pending' => 'Payment pending',
        'refunded' => 'Payment refunded',
    ],
    
    'customer' => [
        'info_updated' => 'Customer information updated',
        'delivery_address_updated' => 'Delivery address updated',
    ],
    
    'items' => [
        'added' => 'Items added to order',
        'updated' => 'Order items updated',
        'removed' => 'Items removed from order',
        'validated' => 'Order items validated',
    ],
];