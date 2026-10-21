<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:type" content="{{ isset($userinfo) ? 'profile' : 'website' }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $seo['og_title'] }}">
<meta property="og:description" content="{{ $seo['og_description'] }}">
<meta property="og:image" content="{{ $seo['og_image'] }}">
<meta property="og:image:alt" content="{{ $seo['og_title'] }}">
@if(str_contains($seo['og_image'], '/social-preview/'))
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['og_title'] }}">
<meta name="twitter:description" content="{{ $seo['og_description'] }}">
<meta name="twitter:image" content="{{ $seo['og_image'] }}">
