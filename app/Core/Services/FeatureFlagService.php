<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Support\Arr;

/**
 * Feature flag service implementation
 */
class FeatureFlagService implements FeatureFlagInterface
{
    /**
     * In-memory overrides for testing
     */
    private array $overrides = [];

    /**
     * Feature configuration cache
     */
    private array $features = [];

    public function __construct()
    {
        $this->loadFeatures();
    }

    /**
     * Check if a feature is enabled
     */
    public function isEnabled(string $feature, array $context = []): bool
    {
        // Check overrides first (for testing)
        if (isset($this->overrides[$feature])) {
            return $this->overrides[$feature];
        }

        $config = $this->getFeatureConfig($feature);
        if (!$config) {
            return false;
        }

        // Simple enabled check
        if (!isset($config['rollout'])) {
            return $config['enabled'] ?? false;
        }

        // Handle rollout strategies
        $rollout = $config['rollout'];
        switch ($rollout['type'] ?? '') {
            case 'percentage':
                return $this->checkPercentageRollout($rollout['value'] ?? 0, $context);
            
            case 'locations':
                return isset($context['location_id']) && 
                       in_array($context['location_id'], $rollout['locations'] ?? []);
            
            case 'users':
                return isset($context['user_id']) && 
                       in_array($context['user_id'], $rollout['users'] ?? []);
            
            default:
                return $config['enabled'] ?? false;
        }
    }

    /**
     * Check if a feature is enabled for a specific location
     */
    public function isEnabledForLocation(string $feature, int $locationId): bool
    {
        return $this->isEnabled($feature, ['location_id' => $locationId]);
    }

    /**
     * Check if a feature is enabled for a specific user
     */
    public function isEnabledForUser(string $feature, int $userId): bool
    {
        return $this->isEnabled($feature, ['user_id' => $userId]);
    }

    /**
     * Get feature variant for A/B testing
     */
    public function getVariant(string $feature, array $context = []): ?string
    {
        if (!$this->isEnabled($feature, $context)) {
            return null;
        }

        $config = $this->getFeatureConfig($feature);
        $variants = $config['variants'] ?? null;
        
        if (!$variants || !is_array($variants)) {
            return 'default';
        }

        // Simple hash-based variant selection
        $hash = crc32($feature . json_encode($context));
        $index = $hash % count($variants);
        
        return $variants[$index] ?? 'default';
    }

    /**
     * Enable a feature (for testing)
     */
    public function enable(string $feature): void
    {
        $this->overrides[$feature] = true;
    }

    /**
     * Disable a feature (for testing)
     */
    public function disable(string $feature): void
    {
        $this->overrides[$feature] = false;
    }

    /**
     * Get all enabled features
     */
    public function getEnabledFeatures(): array
    {
        $enabled = [];
        
        foreach ($this->features as $module => $features) {
            foreach ($features as $featureName => $config) {
                $fullName = "{$module}.{$featureName}";
                if ($this->isEnabled($fullName)) {
                    $enabled[] = $fullName;
                }
            }
        }
        
        return $enabled;
    }

    /**
     * Check if a feature exists in configuration
     */
    public function featureExists(string $feature): bool
    {
        return $this->getFeatureConfig($feature) !== null;
    }

    /**
     * Load features from configuration
     */
    private function loadFeatures(): void
    {
        // Load from main config
        $this->features = config('features', []);
        
        // Load from each module's features config
        $modulesPath = base_path('app-modules');
        if (is_dir($modulesPath)) {
            foreach (scandir($modulesPath) as $module) {
                if ($module === '.' || $module === '..') {
                    continue;
                }
                
                $featuresFile = "{$modulesPath}/{$module}/config/features.php";
                if (file_exists($featuresFile)) {
                    $moduleFeatures = require $featuresFile;
                    $this->features = array_merge($this->features, $moduleFeatures);
                }
            }
        }
    }

    /**
     * Get feature configuration
     */
    private function getFeatureConfig(string $feature): ?array
    {
        return Arr::get($this->features, $feature);
    }

    /**
     * Check percentage rollout
     */
    private function checkPercentageRollout(int $percentage, array $context): bool
    {
        if ($percentage >= 100) {
            return true;
        }
        
        if ($percentage <= 0) {
            return false;
        }
        
        // Use context to generate consistent hash
        $hash = crc32(json_encode($context));
        return ($hash % 100) < $percentage;
    }
}