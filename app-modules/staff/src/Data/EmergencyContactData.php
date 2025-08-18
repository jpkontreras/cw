<?php

namespace Colame\Staff\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Rule;

class EmergencyContactData extends BaseData
{
    public function __construct(
        #[Required, Max(100)]
        public readonly string $name,
        
        #[Required, Rule('regex:/^[+]?[0-9]{10,15}$/')]
        public readonly string $phone,
        
        #[Required, Max(50)]
        public readonly string $relationship,
        
        #[Max(255)]
        public readonly ?string $email = null,
        
        #[Max(255)]
        public readonly ?string $address = null,
    ) {}
    
    public static function fromModel($contact): self
    {
        return new self(
            name: $contact->name ?? $contact['name'],
            phone: $contact->phone ?? $contact['phone'],
            relationship: $contact->relationship ?? $contact['relationship'],
            email: $contact->email ?? $contact['email'] ?? null,
            address: $contact->address ?? $contact['address'] ?? null,
        );
    }
    
    public static function collection($items): \Spatie\LaravelData\DataCollection
    {
        $collection = [];
        foreach ($items as $item) {
            $collection[] = self::fromModel($item);
        }
        return new \Spatie\LaravelData\DataCollection(self::class, $collection);
    }
}