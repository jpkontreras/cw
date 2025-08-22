<?php

declare(strict_types=1);

namespace Colame\Onboarding\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CompleteOnboardingData extends BaseData
{
    public function __construct(
        public readonly AccountSetupData $account,
        public readonly BusinessSetupData $business,
        public readonly LocationSetupData $location,
        public readonly ConfigurationSetupData $configuration,
    ) {}
    
    public static function fromSteps(array $stepsData): self
    {
        return new self(
            account: AccountSetupData::from($stepsData['account'] ?? []),
            business: BusinessSetupData::from($stepsData['business'] ?? []),
            location: LocationSetupData::from($stepsData['location'] ?? []),
            configuration: ConfigurationSetupData::from($stepsData['configuration'] ?? []),
        );
    }
}