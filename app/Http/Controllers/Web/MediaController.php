<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Get all categorized default images for the media manager.
     * This is used by the web frontend's media manager component.
     */
    public function defaultImages(): JsonResponse
    {
        $categories = $this->getCategories();
        $images = [];

        foreach ($categories as $category) {
            $categoryImages = $this->getImagesForCategory($category);
            if (!empty($categoryImages)) {
                $images[$category] = $categoryImages;
            }
        }

        return response()->json([
            'categories' => array_keys($images),
            'images' => $images,
        ]);
    }

    /**
     * Get all available categories.
     */
    private function getCategories(): array
    {
        $path = storage_path('app/public/foods/categorized');

        if (!File::exists($path)) {
            return [];
        }

        $directories = File::directories($path);

        return array_map(function ($dir) {
            return basename($dir);
        }, $directories);
    }

    /**
     * Get images for a specific category.
     */
    private function getImagesForCategory(string $category): array
    {
        $basePath = storage_path("app/public/foods/categorized/{$category}");
        $images = [];

        // Get images from the medium size directory (600x600) for optimal display
        $mediumPath = "{$basePath}/medium";

        if (!File::exists($mediumPath)) {
            return [];
        }

        $files = File::files($mediumPath);

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $name = $this->formatImageName($filename);

            // Build URLs relative to the public storage
            // These are actual static asset URLs, not API endpoints
            $relativePath = "storage/foods/categorized/{$category}";

            // Get base filename without size suffix for other sizes
            $baseFilename = preg_replace('/-\d+x\d+/', '', $filename);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $nameWithoutExt = pathinfo($baseFilename, PATHINFO_FILENAME);

            $images[] = [
                'id' => Str::slug($name) . '-' . $category,
                'name' => $name,
                'category' => $this->formatCategoryName($category),
                'filename' => $filename,
                'url' => asset("{$relativePath}/medium/{$filename}"),
                'thumbnail' => asset("{$relativePath}/thumbnail/{$nameWithoutExt}-150x150.{$extension}"),
                'sizes' => [
                    'thumbnail' => asset("{$relativePath}/thumbnail/{$nameWithoutExt}-150x150.{$extension}"),
                    'small' => asset("{$relativePath}/small/{$nameWithoutExt}-300x300.{$extension}"),
                    'medium' => asset("{$relativePath}/medium/{$nameWithoutExt}-600x600.{$extension}"),
                    'large' => asset("{$relativePath}/large/{$nameWithoutExt}-1200x1200.{$extension}"),
                ],
            ];
        }

        return $images;
    }

    /**
     * Format image filename to readable name.
     */
    private function formatImageName(string $filename): string
    {
        // Remove file extension and size suffix
        $name = preg_replace('/(-\d+x\d+)?\.png$/i', '', $filename);

        // Replace hyphens with spaces and capitalize words
        return Str::title(str_replace('-', ' ', $name));
    }

    /**
     * Format category name for display.
     */
    private function formatCategoryName(string $category): string
    {
        $categoryNames = [
            'asian' => 'Asian Cuisine',
            'bakery' => 'Bakery & Bread',
            'beverages' => 'Beverages',
            'desserts' => 'Desserts',
            'fastfood' => 'Fast Food',
            'main-dishes' => 'Main Dishes',
        ];

        return $categoryNames[$category] ?? Str::title(str_replace('-', ' ', $category));
    }
}