<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Illuminate\Contracts\Support\Arrayable;

class SaveMenuStructureData extends BaseData
{
    public function __construct(
        #[DataCollectionOf(SaveMenuSectionData::class)]
        public readonly Lazy|DataCollection $sections,
    ) {}

    /**
     * Override validateAndCreate to handle nested validation with name mapping
     */
    public static function validateAndCreate(Arrayable|array $payload): static
    {

        if ($payload instanceof \Illuminate\Http\Request) {
            $payload = $payload->all();
        }

        // Validate the basic structure first
        $validator = validator($payload, [
            'sections' => 'required|array',
            'sections.*.name' => 'required|string|max:255',
            'sections.*.items' => 'array',
            'sections.*.items.*.itemId' => 'required|integer', // Frontend sends camelCase
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Now create the data object with mapping
        return static::from($payload);
    }
}
