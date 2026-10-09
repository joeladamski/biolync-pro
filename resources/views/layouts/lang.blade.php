@php
$pkFirstPaint = '#33003B';
try {
    if (request()->is('admin/*') || request()->is('studio/*') || request()->is('dashboard') || request()->is('panel/*')) {
        $pkFirstPaint = '#202637';
    } else {
        $pkFirstPaint = \App\Support\PublicSiteStyle::settings()['background'] ?? '#33003B';
    }
} catch (\Throwable $e) {
    $pkFirstPaint = '#33003B';
}
@endphp
<html lang="{{ config('app.locale') }}" style="background-color:{{ $pkFirstPaint }};">
