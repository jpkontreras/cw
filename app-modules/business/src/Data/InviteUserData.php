<?php

declare(strict_types=1);

namespace Colame\Business\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class InviteUserData extends BaseData
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        
        #[Required, In(['owner', 'admin', 'manager', 'member'])]
        public readonly string $role = 'member',
        
        public readonly ?string $message = null,
    ) {}
}