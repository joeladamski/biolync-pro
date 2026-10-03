<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Support\HomeUi;
Storage::fake('local');
config(['advanced-config.buttons' => [['button'=>'github','title'=>'GitHub','link'=>'https://github.com','icon'=>'','custom_css'=>'']]]);
$controller = new App\Http\Controllers\AdminController;
$controller->editHomeUi(Request::create('/admin/home-ui','POST',['buttons'=>[['title'=>'Our project','link'=>'https://example.com']]]));
$buttons = HomeUi::buttons();
if ($buttons[0]['title'] !== 'Our project' || $buttons[0]['link'] !== 'https://example.com' || $buttons[0]['button'] !== 'github') throw new RuntimeException('Overrides lost or style changed');
foreach (['javascript:alert(1)', 'ftp://example.com', 'https://example.com\" onclick=\"alert(1)'] as $url) {
    try {
        $controller->editHomeUi(Request::create('/admin/home-ui','POST',['buttons'=>[['title'=>'Invalid','link'=>$url]]]));
        throw new RuntimeException('Unsafe URL accepted');
    } catch (Illuminate\Validation\ValidationException $e) {}
}
if (HomeUi::buttons()[0]['title'] !== 'Our project') throw new RuntimeException('Rejected submission changed saved settings');
echo "PASS: links persist, styles preserved, unsafe URLs rejected without overwriting settings\n";
