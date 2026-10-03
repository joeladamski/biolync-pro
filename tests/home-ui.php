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

$fields = ['buttons'=>[['title'=>'Our project','link'=>'https://example.com']], 'preview_copy'=>['title'=>'Discover BioLync', 'tagline'=>'Your links, together', 'description'=>"<script>alert(1)</script>\nSecond line"]];
$controller->editHomeUi(Request::create('/admin/home-ui','POST',$fields));
if (HomeUi::previewCopy()['title'] !== 'Discover BioLync' || HomeUi::buttons()[0]['link'] !== 'https://example.com') throw new RuntimeException('Preview text or links lost');
$html = view('components.home-preview-copy')->render();
if (str_contains($html,'<script>') || !str_contains($html,'&lt;script&gt;') || !str_contains($html,'Second line')) throw new RuntimeException('Preview text not escaped or missing');
try {
 $bad=$fields;$bad['preview_copy']['title']=str_repeat('x',121);
 $controller->editHomeUi(Request::create('/admin/home-ui','POST',$bad));
 throw new RuntimeException('Oversized title accepted');
} catch (Illuminate\Validation\ValidationException $e) {}
if(HomeUi::previewCopy()['title'] !== 'Discover BioLync') throw new RuntimeException('Rejected submission overwrote preview text');
$fields['preview_copy']=['title'=>'','tagline'=>'','description'=>''];
$controller->editHomeUi(Request::create('/admin/home-ui','POST',$fields));
if(str_contains(view('components.home-preview-copy')->render(),'home-preview-copy')) throw new RuntimeException('Blank preview text should be hidden');
echo "PASS: preview text saves, escapes markup, validates limits and hides blank fields\n";
