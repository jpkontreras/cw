<?php

declare(strict_types=1);

namespace App\Core\Traits;

/**
 * Trait for validating pagination parameters
 * Use this in repositories that implement FilterableRepositoryInterface
 */
trait ValidatesPagination
{
    /**
     * Get allowed per-page options
     * Override this method to customize per-resource
     * 
     * @return array<int>
     */
    protected function getPerPageOptions(): array
    {
        return [10, 20, 50, 100];
    }
    
    /**
     * Get default per-page value
     * Override this method to customize per-resource
     * 
     * @return int
     */
    protected function getDefaultPerPage(): int
    {
        return 20;
    }
    
    /**
     * Validate and normalize per-page parameter
     * 
     * @param int $perPage
     * @return int
     */
    protected function validatePerPage(int $perPage): int
    {
        $options = $this->getPerPageOptions();
        
        if (!in_array($perPage, $options)) {
            return $this->getDefaultPerPage();
        }
        
        return $perPage;
    }
}