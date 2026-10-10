<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e) { fwrite(STDERR, (string) $e); exit(1); });

use App\Support\PublicSiteStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

Storage::fake('local');
$controller = new App\Http\Controllers\PublicSiteStyleController;
$settings = array_replace(PublicSiteStyle::defaults(), ['heading_font'=>'playfair-display', 'body_font'=>'inter']);
$controller->save(Request::create('/admin/public-site-styles', 'POST', $settings));
if (PublicSiteStyle::settings()['heading_font'] !== 'playfair-display' || PublicSiteStyle::settings()['body_font'] !== 'inter') {
    throw new RuntimeException('Google font choices did not persist');
}
$html = view('components.public-site-styles')->render();
if (!str_contains($html, 'family=Inter:wght@400;700') || !str_contains($html, 'family=Playfair+Display:wght@400;700') || !str_contains($html, 'display=swap') || !str_contains($html, '"Playfair Display", serif')) {
    throw new RuntimeException('Saved Google fonts were not loaded and applied');
}
foreach (['system', 'serif', 'modern', 'clean'] as $font) {
    if (PublicSiteStyle::googleFontsUrl([$font]) !== null) throw new RuntimeException('Local font caused external request');
}
$url = PublicSiteStyle::googleFontsUrl(['inter', 'inter', 'unknown']);
if (substr_count($url, 'family=') !== 1) throw new RuntimeException('Font requests not deduplicated');
foreach (['https://untrusted.example/font.css', 'inter";color:red;', 'unknown'] as $invalid) {
    try {
        $controller->save(Request::create('/admin/public-site-styles', 'POST', array_replace($settings, ['heading_font'=>$invalid])));
        throw new RuntimeException('Unsafe font accepted');
    } catch (ValidationException $e) {
        if (!isset($e->errors()['heading_font'])) throw $e;
    }
}
if (PublicSiteStyle::settings()['heading_font'] !== 'playfair-display') throw new RuntimeException('Rejected font overwrote settings');
$controller->reset();
if (PublicSiteStyle::googleFontsUrl([PublicSiteStyle::settings()['heading_font'], PublicSiteStyle::settings()['body_font']]) !== null) {
    throw new RuntimeException('Reset did not restore local default fonts');
}
echo "PASS public fonts: persistence, rendering, local fallback, deduplication, validation, and reset\n";
