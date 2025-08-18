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
}