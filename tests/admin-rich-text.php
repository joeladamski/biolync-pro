<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver'=>'array']);
set_exception_handler(function (Throwable $e) { fwrite(STDERR, (string) $e); exit(1); });

use App\Support\RichText;
use App\Support\SitePages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function checkRichText($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS rich text: $message\n";
}
$body = '<h2>Be found 💋</h2><p><strong>Bold</strong> and <em>italic</em></p><ul><li>First</li></ul><p style="text-align:right;color:#ff5dd3;font-size:80px">Aligned</p><a href="https://example.test" target="_blank">Link</a><table><tbody><tr><td>Cell</td></tr></tbody></table>';
$clean = RichText::render($body);
foreach (['<h2>Be found 💋</h2>', '<strong>Bold</strong>', '<li>First</li>', 'text-align:right', '<td>Cell</td>', 'noopener noreferrer'] as $expected) checkRichText(str_contains($clean, $expected), "preserves $expected");
checkRichText(!str_contains($clean, 'font-size'), 'public fonts and sizes remain controlled by site styles');
foreach ([
    '<script>alert(1)</script><p onclick="alert(1)">Safe</p>',
    '<a href="java&#x73;cript:alert(1)">Bad</a><img src="data:image/svg+xml,test" onerror="alert(1)">',
    '<svg><a href="javascript:alert(1)">Bad</a></svg><iframe src="https://example.test"></iframe>',
    '<p style="background:url(https://example.test);color:expression(alert(1))">Safe</p>',
    '<img src="//example.test/image.png"><a href="https:\\example.test">Bad</a>',
    '<math><mtext><table><mglyph><style><!--</style><img title="--><img src=x onerror=alert(1)>">',
] as $unsafe) {
    $safe = RichText::render($unsafe);
    checkRichText(!preg_match('/<script|<iframe|<svg|<math|onerror|onclick|javascript:|data:image|expression\(|url\(/i', $safe), 'strips active markup, unsafe attributes, URLs, and styles');
}
checkRichText(str_contains(RichText::render("<script>literal</script>\nLine two", 'text'), '&lt;script&gt;') && str_contains(RichText::render("One\nTwo", 'text'), '<br'), 'legacy plain text stays escaped with line breaks');
checkRichText(RichText::render($clean) === $clean, 'sanitization is stable across save and render');
Storage::fake('local');
$pageController = new App\Http\Controllers\SitePageController;
$data = ['title'=>'Editorial page','slug'=>'editorial','body'=>$body.'<script>alert(1)</script>', 'body_format'=>'html','published'=>1,'show_in_header'=>1,'order'=>0];
$pageController->save(Request::create('/admin/site-pages','POST',$data));
$saved = SitePages::all()[0];
checkRichText($saved['body_format'] === 'html' && $saved['body'] === $clean, 'site page formatting survives storage reload and is sanitized');
$html = $pageController->show('editorial')->render();
checkRichText(str_contains($html, '<strong>Bold</strong>') && !str_contains($html, 'alert(1)'), 'public site page renders sanitized formatting');
$data['id'] = $saved['id']; $data['body'] = '<p>Updated <u>page</u></p>';
$pageController->save(Request::create('/admin/site-pages','POST',$data));
checkRichText(SitePages::all()[0]['body'] === $data['body'], 'editing an existing rich page retains formatting');

config(['advanced-config.buttons'=>[['button'=>'github','title'=>'GitHub','link'=>'https://github.com','icon'=>'','custom_css'=>'']]]);
$admin = new App\Http\Controllers\AdminController;
$ui = ['buttons'=>[['title'=>'GitHub','link'=>'https://github.com']], 'preview_copy'=>['description'=>$body, 'description_format'=>'html']];
$admin->editHomeUi(Request::create('/admin/home-ui','POST',$ui));
checkRichText(App\Support\HomeUi::previewCopy()['description'] === $clean, 'homepage description saves rich HTML');
checkRichText(str_contains(view('components.home-preview-copy')->render(), '<strong>Bold</strong>'), 'homepage description renders formatting');
$ui['preview_copy']['description_format'] = 'invalid';
try { $admin->editHomeUi(Request::create('/admin/home-ui','POST',$ui)); throw new RuntimeException('Invalid format accepted'); }
catch (Illuminate\Validation\ValidationException $e) {}
checkRichText(App\Support\HomeUi::previewCopy()['description'] === $clean, 'invalid format does not overwrite saved copy');

config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:', 'session.driver'=>'array']);
DB::purge();
Schema::create('pages', function ($table) { $table->id(); $table->text('home_message')->nullable(); $table->timestamps(); });
Schema::create('users', function ($table) { $table->id(); $table->string('name'); });
App\Models\Page::create(['home_message'=>'default']);
$request = Request::create('/admin/site','POST',['message'=>$body.'<script>alert(1)</script>']);
$response = $admin->editSite($request);
checkRichText(App\Models\Page::first()->home_message === $clean && session('site_saved'), 'landing message saves to database with success feedback');

// Real Blade forms are used by the browser flow; no hand-written replacement editor.
app('session')->start();
@mkdir(base_path('test-output'));
$forms = '<h1>Admin content editor QA</h1><h2>Home message</h2><form action="/save-home" method="post"><textarea id="home-message" name="message" data-rich-text data-rich-text-label="Home message">'.e($clean).'</textarea><button>Save home</button></form>';
$forms .= '<h2>Site Pages</h2><details open><summary>Create a page</summary>'.view('components.config.site-page-form', ['editPage'=>[]])->render().'</details>';
$forms .= '<details><summary>Edit existing page</summary>'.view('components.config.site-page-form', ['editPage'=>$saved])->render().'</details>';
$forms .= '<section id="hidden-ui" hidden>'.view('components.config.home-ui')->render().'</section><button id="show-ui" onclick="document.getElementById(\'hidden-ui\').hidden=false">UI Controls</button>';
$fixtures = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Admin content editor QA</title><link rel="stylesheet" href="/assets/vendor/jodit/jodit.min.css"><link rel="stylesheet" href="/assets/css/admin-rich-text.css"><style>body{background:#232838;color:#fff;font-family:Arial;margin:0;padding:20px} form{margin-bottom:24px}textarea,input{max-width:100%}details{margin:16px 0}</style></head><body>'.$forms.'<script src="/assets/vendor/jodit/jodit.min.js"></script><script src="/assets/js/admin-rich-text.js"></script></body></html>';
file_put_contents(base_path('test-output/ui-rich-text.html'), $fixtures);
