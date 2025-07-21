<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for updating orders
 */
#[TypeScript]
class UpdateOrderData extends BaseData
{
    public function __construct(
        #[StringType]
        public readonly string|Optional $notes = new Optional(),
        
        #[StringType]
        public readonly string|Optional $customerName = new Optional(),
        
        #[StringType]
        public readonly string|Optional $customerPhone = new Optional(),
        
        #[ArrayType]
        public readonly array|Optional $metadata = new Optional(),
        
        #[ArrayType]
        public readonly array|Optional $items = new Optional(),
    ) {}

    /**
     * Custom validation rules
     */
    public static function rules(): array
    {
        return [
            'customerPhone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]+$/'],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:0'],
        ];
    }

    /**
     * Check if update has items modifications
     */
    public function hasItemsUpdate(): bool
    {
        return !($this->items instanceof Optional);
    }

    /**
     * Get fields that are being updated
     */
    public function getUpdatedFields(): array
    {
        $fields = [];
        
        foreach (get_object_vars($this) as $key => $value) {
            if (!($value instanceof Optional)) {
                $fields[] = $key;
            }
        }
        
        return $fields;
    }
}