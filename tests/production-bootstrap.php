<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e){fwrite(STDERR,(string)$e);exit(1);});

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Models\User;

config([
    'database.default'=>'sqlite',
    'database.connections.sqlite.database'=>':memory:',
    'cache.default'=>'array',
    'session.driver'=>'array',
    'app.key'=>'base64:'.base64_encode(str_repeat('x',32)),
    'linkstack.disable_random_user_ids'=>'true',
]);

DB::purge();
Schema::create('users',function($t){
    $t->id();
    $t->string('name')->unique();
    $t->string('email')->unique();
    $t->timestamp('email_verified_at')->nullable();
    $t->string('password');
    $t->string('littlelink_name')->unique()->nullable();
    $t->text('littlelink_description')->nullable();
    $t->string('role')->default('user');
    $t->string('block')->default('no');
    $t->rememberToken();
    $t->timestamps();
});

function bootstrapCheck($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS Bootstrap: $message\n";}

$installed=storage_path('app/ISINSTALLED');
$installing=base_path('INSTALLING');
$installerLock=base_path('INSTALLERLOCK');
@unlink($installed);@unlink($installing);@unlink($installerLock);

$controller=new App\Http\Controllers\InstallerController;
$method=new ReflectionMethod($controller,'bootstrapAvailable');
$method->setAccessible(true);
bootstrapCheck($method->invoke($controller)===true,'zero-user bootstrap is available before install finalization');

$auth=File::get(base_path('routes/auth.php'));
bootstrapCheck(str_contains($auth,"Route::get('/setup-owner'"),'zero-user state exposes a collision-safe first-owner route');
bootstrapCheck(str_contains($auth,"return redirect()->route('setupOwner');"),'ordinary registration redirects to first-owner setup while users=0');
bootstrapCheck(str_contains($auth,"$bootstrapAvailable = static function (): bool"),'bootstrap database state is deferred until request time');
bootstrapCheck(!str_contains($auth,"$bootstrapAvailable = !File::exists"),'route registration does not query bootstrap database state eagerly');
bootstrapCheck(!str_contains($auth,"Route::get('/bootstrap'"),'public first-owner route does not collide with Laravel bootstrap directory');
bootstrapCheck(str_contains($auth,"Route::post('/create-admin'"),'bootstrap exposes first-owner creation without requiring INSTALLING');
bootstrapCheck(str_contains($auth,"Route::get('/setup-owner/finalize'"),'finalization route is statically registered');
bootstrapCheck(str_contains($auth,"->name('setupOwnerFinalize')"),'createAdmin redirect target is a defined named route');
bootstrapCheck(str_contains($auth,"->name('setupOwnerOptions')"),'owner finalization POST has a unique route name');
bootstrapCheck(!str_contains($auth,"Route::post('/setup-owner/options', [InstallerController::class, 'options'])\n    ->middleware('auth')\n    ->name('options')"),'owner finalization does not reuse the legacy options route name');

$showRequest=Request::create('/setup-owner','GET');
$showResponse=$controller->showInstaller($showRequest);
bootstrapCheck($showResponse instanceof Illuminate\View\View,'GET /setup-owner directly renders a view');
bootstrapCheck($showResponse->name()==='installer/owner-bootstrap','GET /setup-owner renders the dedicated owner creation view');

$legacyRequest=Request::create('/setup-owner?4','GET');
try {
    $controller->showInstaller($legacyRequest);
    bootstrapCheck(false,'legacy query-string bootstrap cannot bypass canonical entry');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
    bootstrapCheck($e->getStatusCode()===404,'legacy query-string bootstrap cannot bypass canonical entry');
}

$request=Request::create('/create-admin','POST',[
    'name'=>'Owner',
    'handle'=>'owner',
    'email'=>'owner@example.test',
    'password'=>'password123',
]);
$response=$controller->createAdmin($request);
bootstrapCheck($response instanceof Illuminate\Http\RedirectResponse,'createAdmin returns a redirect response');
bootstrapCheck(str_contains($response->getTargetUrl(),'/setup-owner/finalize'),'createAdmin redirects to the finalization route');
$owner=User::first();
bootstrapCheck($owner!==null && $owner->role==='admin','first bootstrap account receives admin role');
bootstrapCheck(File::exists($installerLock),'first-owner creation enters native installer finalization state');
bootstrapCheck($method->invoke($controller)===false,'bootstrap is unavailable after first account exists');

$finalizeResponse=$controller->showOwnerFinalize(Request::create('/setup-owner/finalize','GET'));
bootstrapCheck($finalizeResponse instanceof Illuminate\Http\Response,'owner finalization returns an HTTP response');
bootstrapCheck($finalizeResponse->getStatusCode()===200,'owner finalization returns HTTP 200');
bootstrapCheck(strlen((string) $finalizeResponse->getContent())>0,'owner finalization response body is not empty');
bootstrapCheck(str_contains((string) $finalizeResponse->getContent(),'owner-finalize-form'),'owner finalization response contains the configuration form');

try {
    $controller->showInstaller(Request::create('/setup-owner','GET'));
    bootstrapCheck(false,'existing user blocks first-owner bootstrap');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
    bootstrapCheck($e->getStatusCode()===404,'existing user blocks first-owner bootstrap');
}

$ordinary=User::create([
    'name'=>'Ordinary',
    'email'=>'ordinary@example.test',
    'littlelink_name'=>'ordinary',
    'password'=>Illuminate\Support\Facades\Hash::make('password123'),
]);
bootstrapCheck($ordinary->fresh()->role==='user','ordinary user creation remains non-admin');

File::put($installed,'');
bootstrapCheck($method->invoke($controller)===false,'installed marker prevents bootstrap');

try {
    $controller->showInstaller(Request::create('/setup-owner','GET'));
    bootstrapCheck(false,'ISINSTALLED blocks first-owner bootstrap');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
    bootstrapCheck($e->getStatusCode()===404,'ISINSTALLED blocks first-owner bootstrap');
}
@unlink($installed);
@unlink($installerLock);

bootstrapCheck(str_contains($auth,"FILTER_VALIDATE_BOOLEAN"),'ALLOW_REGISTRATION is parsed as an explicit boolean');
$webRoutes=File::get(base_path('routes/web.php'));
bootstrapCheck(str_contains($webRoutes,"'middleware' => env('REGISTER_AUTH')"),'REGISTER_AUTH remains middleware-valued');
bootstrapCheck(str_contains($webRoutes,"if(file_exists(base_path('INSTALLING')))"),'legacy installer activates from INSTALLING');
bootstrapCheck(!str_contains($webRoutes,"if(file_exists(base_path('INSTALLING')) or file_exists(base_path('INSTALLERLOCK')))"),'INSTALLERLOCK alone cannot activate the legacy installer catch-all');
$installerController=File::get(base_path('app/Http/Controllers/InstallerController.php'));
bootstrapCheck(str_contains($installerController,'$value = "verified"') && str_contains($installerController,'$value = "auth"'),'installer preserves auth/verified REGISTER_AUTH values');
bootstrapCheck(str_contains($installerController,"EnvEditor::addKey('HOME_URL', $value)"),'installer creates HOME_URL when missing');
bootstrapCheck(str_contains($installerController,"EnvEditor::addKey('APP_NAME', $appName)"),'installer creates APP_NAME when missing');

$analyticsView=File::get(base_path('resources/views/layouts/analytics.blade.php'));
bootstrapCheck(str_contains($analyticsView,"(string) config('advanced-config.analytics', '')"),'fresh installs tolerate a missing analytics config value');

$ignore=File::get(base_path('.gitignore'));
bootstrapCheck(str_contains($ignore,'/config/advanced-config.php'),'generated advanced config is ignored');
bootstrapCheck(File::exists(base_path('storage/templates/advanced-config.php')),'advanced config template remains version-controlled');

echo "PASS Bootstrap: runtime-state regression checks complete\n";
