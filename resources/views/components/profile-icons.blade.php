{{-- A profile's own avatar identifies browser tabs and saved iPhone shortcuts. --}}
<link rel="icon" type="image/png" sizes="32x32" href="{{ \App\Support\ProfileIcon::url($userinfo, 32) }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ \App\Support\ProfileIcon::url($userinfo, 180) }}">
<meta name="apple-mobile-web-app-title" content="{{ $userinfo->name }}">
