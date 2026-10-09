<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class ActivityMonitoring
{
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'discord_enabled' => false,
            'webhook_encrypted' => '',
            'include_ip' => false,
            'include_user_agent' => false,
        ];
    }

    public static function settings(bool $withWebhook = false): array
    {
        $settings = self::defaults();
        if (Storage::disk('local')->exists('activity-monitoring.json')) {
            $decoded = json_decode(Storage::disk('local')->get('activity-monitoring.json'), true);
            if (is_array($decoded)) {
                $settings = array_replace($settings, $decoded);
            }
        }

        if ($withWebhook) {
            $settings['webhook'] = '';
            if (!empty($settings['webhook_encrypted'])) {
                try {
                    $settings['webhook'] = Crypt::decryptString($settings['webhook_encrypted']);
                } catch (\Throwable $e) {
                    $settings['webhook'] = '';
                }
            }
        }

        return $settings;
    }

    public static function save(array $data, ?string $webhook = null): void
    {
        $current = self::settings();
        $current['enabled'] = (bool)($data['enabled'] ?? false);
        $current['discord_enabled'] = (bool)($data['discord_enabled'] ?? false);
        $current['include_ip'] = (bool)($data['include_ip'] ?? false);
        $current['include_user_agent'] = (bool)($data['include_user_agent'] ?? false);

        if ($webhook !== null && trim($webhook) !== '') {
            $current['webhook_encrypted'] = Crypt::encryptString(trim($webhook));
        }

        Storage::disk('local')->put(
            'activity-monitoring.json',
            json_encode($current, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }
}
