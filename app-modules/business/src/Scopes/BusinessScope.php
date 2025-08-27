<?php

declare(strict_types=1);

namespace Colame\Business\Scopes;

use Colame\Business\Contracts\BusinessContextInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope to filter models by current business context
 */
class BusinessScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if multi-tenancy is enforced
        if (!config('features.business.multi_tenancy.enforce_business_context', false)) {
            return;
        }

        // Get current business context
        $context = app(BusinessContextInterface::class);
        $businessId = $context->getCurrentBusinessId();

        if ($businessId) {
            $builder->where($model->getTable() . '.business_id', $businessId);
        }
    }
}