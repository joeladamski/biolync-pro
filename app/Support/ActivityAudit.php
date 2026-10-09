<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ActivityAudit
{
    private const SENSITIVE = [
        'password', 'password_confirmation', 'current_password', 'token', '_token',
        'api_key', 'apikey', 'secret', 'client_secret', 'access_token', 'refresh_token',
        'remember_token', 'discord_webhook', 'webhook', 'webhook_url'
    ];

    public static function sanitize(array $input): array
    {
        $clean = [];
        foreach ($input as $key => $value) {
            $lower = strtolower((string)$key);
            $isSensitive = in_array($lower, self::SENSITIVE, true)
                || str_contains($lower, 'password')
                || str_contains($lower, 'secret')
                || str_contains($lower, 'token');

            if ($isSensitive) {
                $clean[$key] = '[REDACTED]';
                continue;
            }

            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $clean[$key] = [
                    'file' => $value->getClientOriginalName(),
                    'mime' => $value->getClientMimeType(),
                    'size' => $value->getSize(),
                ];
            } elseif (is_array($value)) {
                $clean[$key] = self::sanitize($value);
            } elseif (is_scalar($value) || $value === null) {
                $text = is_string($value) ? $value : $value;
                $clean[$key] = is_string($text) && mb_strlen($text) > 600
                    ? mb_substr($text, 0, 600) . '…'
                    : $text;
            }
        }
        return $clean;
    }

    public static function record(string $action, array $changes = [], array $meta = []): void
    {
        $settings = ActivityMonitoring::settings(true);
        if (!($settings['enabled'] ?? false)) {
            return;
        }

        $actor = auth()->user();
        $safeChanges = self::sanitize($changes);
        $safeMeta = self::sanitize($meta);

        if (Schema::hasTable('activity_logs')) {
            ActivityLog::create([
                'actor_user_id' => $actor?->id,
                'action' => $action,
                'route' => request()->route()?->getName(),
                'method' => request()->method(),
                'changes' => $safeChanges,
                'meta' => $safeMeta,
            ]);
        }

        if (($settings['discord_enabled'] ?? false) && !empty($settings['webhook'])) {
            self::sendDiscord($settings['webhook'], $action, $actor, $safeChanges, $safeMeta);
        }
    }

    public static function sendTest(): bool
    {
        $settings = ActivityMonitoring::settings(true);
        if (empty($settings['webhook'])) {
            return false;
        }

        try {
            $response = Http::timeout(3)->asJson()->post($settings['webhook'], [
                'embeds' => [[
                    'title' => '💋 PinkKiss.Love Activity Monitor',
                    'description' => 'Discord activity monitoring is connected.',
                    'color' => 16735699,
                    'timestamp' => now()->toIso8601String(),
                ]]
            ]);
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function sendDiscord(string $webhook, string $action, $actor, array $changes, array $meta): void
    {
        try {
            $details = [];
            foreach ($changes as $key => $value) {
                if ($value === '[REDACTED]') {
                    $details[] = "**{$key}:** changed";
                    continue;
                }
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                $details[] = "**{$key}:** " . mb_substr((string)$value, 0, 220);
                if (count($details) >= 8) break;
            }

            Http::timeout(2)->asJson()->post($webhook, [
                'embeds' => [[
                    'title' => '💋 ' . str_replace(['.', '_'], ' ', ucwords($action, '._')),
                    'description' => implode("\n", $details) ?: 'A platform change was saved.',
                    'color' => 16735699,
                    'fields' => [
                        ['name' => 'User', 'value' => $actor ? '@' . ($actor->littlelink_name ?: $actor->name) : 'System', 'inline' => true],
                        ['name' => 'Role', 'value' => $actor?->role ?: 'system', 'inline' => true],
                        ['name' => 'Route', 'value' => request()->route()?->getName() ?: request()->path(), 'inline' => false],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]]
            ]);
        } catch (\Throwable $e) {
            // Monitoring must never break a user's save.
        }
    }
}
