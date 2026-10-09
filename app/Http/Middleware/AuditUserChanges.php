<?php

namespace App\Http\Middleware;

use App\Support\ActivityAudit;
use Closure;
use Illuminate\Http\Request;

class AuditUserChanges
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$request->user() || $response->getStatusCode() >= 400 || !$this->isChange($request)) {
            return $response;
        }

        $route = $request->route()?->getName() ?: $request->path();
        if ($route === 'testActivityMonitoring') {
            return $response;
        }
        $meta = [];

        $settings = \App\Support\ActivityMonitoring::settings();
        if ($settings['include_ip'] ?? false) {
            $meta['ip'] = $request->ip();
        }
        if ($settings['include_user_agent'] ?? false) {
            $meta['user_agent'] = mb_substr((string)$request->userAgent(), 0, 300);
        }

        ActivityAudit::record(
            $this->actionName($route),
            array_merge($request->except(['_token']), $request->allFiles()),
            $meta
        );

        return $response;
    }

    private function isChange(Request $request): bool
    {
        if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $route = strtolower((string)($request->route()?->getName() ?: ''));
        foreach (['delete', 'clear', 'block', 'verify', 'uplink', 'remove'] as $word) {
            if (str_contains($route, $word)) return true;
        }
        return false;
    }

    private function actionName(string $route): string
    {
        $map = [
            'editPage' => 'profile.updated',
            'profileHeader' => 'profile.header_updated',
            'saveCreatorExperience' => 'creator.experience_updated',
            'addLink' => 'link.saved',
            'editLink' => 'link.updated',
            'deleteLink' => 'link.deleted',
            'sortLinks' => 'links.reordered',
            'editTheme' => 'profile.theme_updated',
            'savePhotoGallery' => 'profile.gallery_updated',
            'editProfile' => 'account.updated',
            'editUser' => 'admin.user_updated',
            'saveAiProfile' => 'ai_discovery.profile_updated',
            'saveAiSite' => 'ai_discovery.site_updated',
            'saveSeoProfile' => 'seo.profile_updated',
            'saveSeoSite' => 'seo.site_updated',
        ];

        return $map[$route] ?? 'route.' . str_replace(['/', ' '], ['.', '_'], $route);
    }
}
