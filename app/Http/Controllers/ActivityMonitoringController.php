<?php

namespace App\Http\Controllers;

use App\Support\ActivityAudit;
use App\Support\ActivityMonitoring;
use Illuminate\Http\Request;

class ActivityMonitoringController extends Controller
{
    public function save(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'discord_enabled' => 'nullable|boolean',
            'discord_webhook' => ['nullable', 'url', 'max:2048', 'regex:#^https://(discord\\.com|discordapp\\.com)/api/webhooks/#i'],
            'include_ip' => 'nullable|boolean',
            'include_user_agent' => 'nullable|boolean',
        ]);

        ActivityMonitoring::save([
            'enabled' => $request->boolean('enabled'),
            'discord_enabled' => $request->boolean('discord_enabled'),
            'include_ip' => $request->boolean('include_ip'),
            'include_user_agent' => $request->boolean('include_user_agent'),
        ], $data['discord_webhook'] ?? null);

        return redirect(url('/admin/config') . '#activity-monitoring')->with('activity_monitoring_saved', true);
    }

    public function test()
    {
        $ok = ActivityAudit::sendTest();

        return redirect(url('/admin/config') . '#activity-monitoring')->with(
            $ok ? 'activity_monitoring_test_ok' : 'activity_monitoring_test_fail',
            true
        );
    }
}
