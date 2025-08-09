<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Illuminate\Validation\Validator;

class SaveMenuSectionData extends BaseData
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,
        
        #[DataCollectionOf(SaveMenuItemData::class)]
        public readonly DataCollection $items,
        
        #[Nullable, IntegerType]
        public readonly ?int $id = null,
        
        #[Nullable, StringType]
        public readonly ?string $description = null,
        
        #[Nullable, StringType]
        public readonly ?string $icon = null,
        
        #[BooleanType]
        #[MapInputName('isActive')]
        public readonly bool $isActive = true,
        
        #[BooleanType]
        #[MapInputName('isFeatured')]
        public readonly bool $isFeatured = false,
        
        #[BooleanType]
        #[MapInputName('isCollapsed')]
        public readonly ?bool $isCollapsed = false,
        
        #[IntegerType, Min(0)]
        #[MapInputName('sortOrder')]
        public readonly int $sortOrder = 0,
        
        #[DataCollectionOf(SaveMenuSectionData::class)]
        public readonly ?DataCollection $children = null,
    ) {}
    
    /**
     * Custom validation rules to ensure no duplicate items within the section
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'items' => [
                function ($attribute, mixed $value, \Closure $fail) {
                    if (!is_array($value)) {
                        return;
                    }
                    
                    $itemIds = [];
                    foreach ($value as $item) {
                        if (!isset($item['itemId'])) {
                            continue;
                        }
                        
                        $itemId = $item['itemId'];
                        if (in_array($itemId, $itemIds)) {
                            $fail("Section contains duplicate item with ID {$itemId}. Each item can only appear once per section.");
                            return;
                        }
                        $itemIds[] = $itemId;
                    }
                },
            ],
        ];
    }
    
    /**
     * Additional validation after data is created
     */
    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();
            
            // Check for duplicate items in child sections recursively
            if (isset($data['children']) && is_array($data['children'])) {
                foreach ($data['children'] as $childIndex => $child) {
                    if (!isset($child['items']) || !is_array($child['items'])) {
                        continue;
                    }
                    
                    $childItemIds = [];
                    foreach ($child['items'] as $item) {
                        if (!isset($item['itemId'])) {
                            continue;
                        }
                        
                        $itemId = $item['itemId'];
                        if (in_array($itemId, $childItemIds)) {
                            $validator->errors()->add(
                                "children.{$childIndex}.items",
                                "Child section '{$child['name']}' contains duplicate item with ID {$itemId}."
                            );
                            return;
                        }
                        $childItemIds[] = $itemId;
                    }
                }
            }
        });
    }
}