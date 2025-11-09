<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Config;

class TenantPageAuthorization
{
    /**
     * Resolve the permission slug for a tenant page key.
     */
    public static function permissionForPage(string $pageKey): string
    {
        return 'view_tenant_page_'.strtolower($pageKey);
    }

    /**
     * List the tenant pages accessible to the provided user.
     *
     * @return array<string, string>
     */
    public static function accessiblePages(?User $user): array
    {
        $categories = Config::get('tenant.categories', []);
        $globalAccess = $user && $user->hasPermission('view_tenant_pages');
        $accessible = [];

        foreach ($categories as $category) {
            foreach (($category['pages'] ?? []) as $pageKey => $pageTitle) {
                if ($globalAccess || ($user && $user->hasPermission(self::permissionForPage($pageKey)))) {
                    $accessible[$pageKey] = $pageTitle;
                }
            }
        }

        return $accessible;
    }

    /**
     * Provide tenant page categories filtered for the user's access.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function accessibleCategories(?User $user): array
    {
        $categories = Config::get('tenant.categories', []);
        $accessiblePages = self::accessiblePages($user);
        $filtered = [];

        foreach ($categories as $categoryKey => $category) {
            $pages = [];

            foreach (($category['pages'] ?? []) as $pageKey => $pageTitle) {
                if (array_key_exists($pageKey, $accessiblePages)) {
                    $pages[$pageKey] = $pageTitle;
                }
            }

            if (! empty($pages)) {
                $category['pages'] = $pages;
                $filtered[$categoryKey] = $category;
            }
        }

        return $filtered;
    }

    /**
     * Determine whether the user may access a tenant page.
     */
    public static function canAccessPage(?User $user, string $pageKey): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasPermission('view_tenant_pages')) {
            return true;
        }

        return $user->hasPermission(self::permissionForPage($pageKey));
    }
}
