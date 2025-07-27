<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Data\ResourceMetadata;

/**
 * Interface for services that provide resource metadata
 */
interface ResourceMetadataInterface
{
    /**
     * Get metadata for the resource
     * 
     * @param array $context Additional context for metadata generation
     * @return ResourceMetadata
     */
    public function getResourceMetadata(array $context = []): ResourceMetadata;

    /**
     * Get filter presets for the resource
     * 
     * @return array
     */
    public function getFilterPresets(): array;

    /**
     * Get available actions for the resource
     * 
     * @param array $context Additional context (e.g., user permissions)
     * @return array
     */
    public function getAvailableActions(array $context = []): array;

    /**
     * Get export configuration for the resource
     * 
     * @return array
     */
    public function getExportConfiguration(): array;
}