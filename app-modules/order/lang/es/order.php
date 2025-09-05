<?php

return [
    'status' => [
        'draft' => 'Borrador de orden creado',
        'started' => 'Orden iniciada',
        'items_added' => 'Productos agregados a la orden',
        'items_validated' => 'Productos de la orden validados',
        'promotions_calculated' => 'Promociones calculadas',
        'price_calculated' => 'Precio de orden calculado',
        'confirmed' => 'Orden confirmada',
        'preparing' => 'Cocina preparando orden',
        'ready' => 'Orden lista para retirar',
        'delivering' => 'Orden en camino',
        'delivered' => 'Orden entregada',
        'completed' => 'Orden completada exitosamente',
        'cancelled' => 'Orden cancelada',
        'refunded' => 'Orden reembolsada',
    ],
    
    'transitions' => [
        'to_confirmed' => 'La orden ha sido confirmada',
        'to_preparing' => 'La cocina ha comenzado a preparar',
        'to_ready' => 'La orden está lista para retirar',
        'to_completed' => 'La orden ha sido completada',
        'to_cancelled' => 'La orden ha sido cancelada',
        'to_refunded' => 'La orden ha sido reembolsada',
    ],
    
    'actions' => [
        'create' => 'Crear Orden',
        'update' => 'Actualizar Orden',
        'confirm' => 'Confirmar Orden',
        'cancel' => 'Cancelar Orden',
        'complete' => 'Completar Orden',
        'refund' => 'Reembolsar Orden',
    ],
    
    'messages' => [
        'created' => 'Orden :number creada exitosamente',
        'updated' => 'Orden :number actualizada',
        'confirmed' => 'Orden :number confirmada',
        'cancelled' => 'Orden :number cancelada',
        'completed' => 'Orden :number completada',
        'not_found' => 'Orden no encontrada',
        'cannot_modify' => 'La orden no puede ser modificada en el estado actual',
        'cannot_cancel' => 'La orden no puede ser cancelada en el estado actual',
    ],
    
    'payment' => [
        'processed' => 'Pago procesado exitosamente',
        'failed' => 'Pago fallido: :reason',
        'pending' => 'Pago pendiente',
        'refunded' => 'Pago reembolsado',
    ],
    
    'customer' => [
        'info_updated' => 'Información del cliente actualizada',
        'delivery_address_updated' => 'Dirección de entrega actualizada',
    ],
    
    'items' => [
        'added' => 'Productos agregados a la orden',
        'updated' => 'Productos de la orden actualizados',
        'removed' => 'Productos eliminados de la orden',
        'validated' => 'Productos de la orden validados',
    ],
];