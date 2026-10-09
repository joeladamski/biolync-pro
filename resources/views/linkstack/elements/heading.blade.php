<?php use App\Models\UserData; ?>
<div class="pk-profile-heading">
  <h1 class="fadein dynamic-contrast" style="display:inline">
    {{ $info->name }}
    @if(theme('disable_verification_badge') != "true" && env('HIDE_VERIFICATION_CHECKMARK') != true && UserData::getData($userinfo->id, 'checkmark') == true)
      <span title="{{ __('messages.Verified user') }}">@include('components.verify-svg')</span>
    @endif
  </h1>
  @include('linkstack.elements.vip-status')
</div>
