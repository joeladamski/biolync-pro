@php($monitor = \App\Support\ActivityMonitoring::settings())
<h2 class="mb-3 card-header">Activity & Discord Monitoring</h2>
<p>Record successful authenticated changes and optionally mirror a sanitized notification to Discord. Passwords, tokens, secrets and webhook credentials are always redacted.</p>
@if(session('activity_monitoring_saved'))<div class="alert alert-success">Activity monitoring settings saved.</div>@endif
@if(session('activity_monitoring_test_ok'))<div class="alert alert-success">Discord test delivered successfully.</div>@endif
@if(session('activity_monitoring_test_fail'))<div class="alert alert-danger">Discord test could not be delivered. Check the webhook URL and server connectivity.</div>@endif

<form method="POST" action="{{ route('saveActivityMonitoring') }}" class="mb-3">
  @csrf
  <div class="form-check form-switch mb-3">
    <input type="hidden" name="enabled" value="0">
    <input class="form-check-input" type="checkbox" id="audit-enabled" name="enabled" value="1" @checked($monitor['enabled'])>
    <label class="form-check-label" for="audit-enabled">Enable platform activity log</label>
  </div>
  <div class="form-check form-switch mb-3">
    <input type="hidden" name="discord_enabled" value="0">
    <input class="form-check-input" type="checkbox" id="discord-enabled" name="discord_enabled" value="1" @checked($monitor['discord_enabled'])>
    <label class="form-check-label" for="discord-enabled">Send activity notifications to Discord</label>
  </div>

  <label class="form-label" for="discord-webhook">Discord webhook URL</label>
  <input class="form-control mb-2" type="url" id="discord-webhook" name="discord_webhook" placeholder="{{ !empty($monitor['webhook_encrypted']) ? 'Webhook saved — leave blank to keep it' : 'https://discord.com/api/webhooks/…' }}">
  <div class="form-text mb-3">Stored encrypted with the application key. The webhook URL is never written to the activity log or Discord payload.</div>

  <div class="form-check form-switch mb-2">
    <input type="hidden" name="include_ip" value="0">
    <input class="form-check-input" type="checkbox" id="include-ip" name="include_ip" value="1" @checked($monitor['include_ip'])>
    <label class="form-check-label" for="include-ip">Include IP address in local audit metadata</label>
  </div>
  <div class="form-check form-switch mb-3">
    <input type="hidden" name="include_user_agent" value="0">
    <input class="form-check-input" type="checkbox" id="include-agent" name="include_user_agent" value="1" @checked($monitor['include_user_agent'])>
    <label class="form-check-label" for="include-agent">Include browser user-agent in local audit metadata</label>
  </div>

  <button class="btn btn-primary" type="submit">Save monitoring settings</button>
</form>

<form method="POST" action="{{ route('testActivityMonitoring') }}" data-no-save-wipe="1">
  @csrf
  <button class="btn btn-outline-primary" type="submit">Send Discord test</button>
</form>

@if(\Illuminate\Support\Facades\Schema::hasTable('activity_logs'))
  @php($recentActivity = \App\Models\ActivityLog::latest()->limit(25)->get())
  <hr class="my-4">
  <h3 class="h5">Recent platform activity</h3>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Route</th></tr></thead>
      <tbody>
        @forelse($recentActivity as $event)
          @php($eventUser = $event->actor_user_id ? \App\Models\User::find($event->actor_user_id) : null)
          <tr>
            <td>{{ $event->created_at?->format('M j, g:i A') }}</td>
            <td>{{ $eventUser ? '@'.($eventUser->littlelink_name ?: $eventUser->name) : 'System' }}</td>
            <td><code>{{ $event->action }}</code></td>
            <td>{{ $event->route }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-muted">No activity has been recorded yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@else
  <div class="alert alert-info mt-4 mb-0">Run the current database migrations to activate the persistent activity log.</div>
@endif
