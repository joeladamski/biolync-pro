        <!-- Your Image Here -->
        @if(file_exists(\App\Support\RuntimeMedia::path(findAvatar($userinfo->id))))
        <img alt="avatar" id="avatar" class="rounded-avatar fadein" src="{{ url(findAvatar($userinfo->id)) }}" height="150px" width="150px" style="object-fit: cover;">
        @elseif(\App\Support\SiteBranding::exists('avatar'))
        <img alt="avatar" id="avatar" class="fadein" src="{{ \App\Support\SiteBranding::url('avatar') }}" height="150px" width="150px" style="object-fit: cover;">
        @else
        <img alt="avatar" id="avatar" class="fadein" src="{{ asset('assets/linkstack/images/logo.svg') }}" height="150px" style="width:auto;min-width:150px;object-fit: cover;">
        @endif