<?php

namespace App\Services;

class FeatureService
{
    /**
     * Check if a feature is enabled.
     *
     * @param  string  $feature  Path in dot notation (e.g., 'auth.two_factor', 'workspace.billing')
     */
    public static function isEnabled(string $feature): bool
    {
        return config("features.{$feature}", true);
    }

    /**
     * Check if a feature is disabled.
     *
     * @param  string  $feature  Path in dot notation
     */
    public static function isDisabled(string $feature): bool
    {
        return ! self::isEnabled($feature);
    }

    /**
     * Get all enabled features for a category.
     *
     * @param  string  $category  e.g., 'auth', 'workspace', 'admin'
     * @return array<string, bool>
     */
    public static function enabledInCategory(string $category): array
    {
        $features = config("features.{$category}", []);

        return array_filter($features, fn (bool $enabled) => $enabled);
    }

    /**
     * Get all features configuration.
     *
     * @return array<string, array<string, bool>>
     */
    public static function all(): array
    {
        return config('features', []);
    }

    /**
     * Get features formatted for frontend consumption.
     * Flattens the nested structure into dot notation.
     *
     * @return array<string, bool>
     */
    public static function forFrontend(): array
    {
        $features = self::all();
        $flattened = [];

        foreach ($features as $category => $items) {
            foreach ($items as $key => $enabled) {
                $flattened["{$category}.{$key}"] = $enabled;
            }
        }

        return $flattened;
    }

    /**
     * Require a feature to be enabled, throw exception if disabled.
     *
     * @throws \RuntimeException
     */
    public static function require(string $feature): void
    {
        if (self::isDisabled($feature)) {
            throw new \RuntimeException("Feature '{$feature}' is disabled.");
        }
    }

    /**
     * Check if any of the given features are enabled.
     *
     * @param  array<string>  $features
     */
    public static function anyEnabled(array $features): bool
    {
        foreach ($features as $feature) {
            if (self::isEnabled($feature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all of the given features are enabled.
     *
     * @param  array<string>  $features
     */
    public static function allEnabled(array $features): bool
    {
        foreach ($features as $feature) {
            if (self::isDisabled($feature)) {
                return false;
            }
        }

        return true;
    }
}
