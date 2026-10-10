<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e) { fwrite(STDERR, (string) $e); exit(1); });

use App\Models\User;
use App\Models\UserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:',
    'cache.default'=>'array', 'session.driver'=>'array', 'linkstack.disable_random_user_ids'=>'true']);
DB::purge();
Schema::create('users', function ($t) {
    $t->id(); $t->string('name'); $t->string('email')->unique(); $t->string('password');
    $t->string('littlelink_name')->nullable()->unique(); $t->text('littlelink_description')->nullable();
    $t->string('theme')->default('default'); $t->string('role')->default('user');
    $t->text('image')->nullable(); $t->timestamps();
});
function checkUserSave($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS admin user save: $message\n";
}
$user = User::create(['name'=>'Creator', 'email'=>'creator@example.test',
    'password'=>Hash::make('original-password'), 'littlelink_name'=>'creator']);
UserData::saveData($user->id, 'unrelated', ['preserved'=>true]);
$controller = new App\Http\Controllers\AdminController;
$originalHash = $user->password;
foreach (['vip', 'admin', 'user'] as $role) {
    $payload = ['id'=>$user->id, 'name'=>'Creator '.$role, 'email'=>'creator@example.test',
        'littlelink_name'=>'creator', 'littlelink_description'=>'Description '.$role,
        'theme'=>'custom-'.$role, 'role'=>$role, 'password'=>null,
        'show_checkmark'=>1, 'links_new_tab'=>1, 'vip_badge_enabled'=>1,
        'vip_headline'=>'Recognized creator', 'vip_cta_url'=>null];
    $response = $controller->editUser(Request::create('/admin/edit-user/'.$user->id, 'POST', $payload));
    checkUserSave($response->getTargetUrl() === route('showUser', ['id'=>$user->id]), 'Save stays on the same user editor');
    $saved = $user->fresh();
    checkUserSave($saved->role === $role && $saved->theme === 'custom-'.$role &&
        $saved->littlelink_description === 'Description '.$role && $saved->name === 'Creator '.$role,
        "$role, theme, description, and name survive database reload");
    checkUserSave($saved->password === $originalHash, 'blank password leaves existing hash unchanged');
    checkUserSave(UserData::getData($user->id, 'vip_profile')['enabled'] === ($role === 'vip'),
        'VIP recognition follows persisted role');
    checkUserSave(UserData::getData($user->id, 'unrelated')['preserved'] === true,
        'existing profile metadata survives save');
}
$payload['save_action'] = 'exit';
$response = $controller->editUser(Request::create('/admin/edit-user/'.$user->id, 'POST', $payload));
checkUserSave($response->getTargetUrl() === route('showUsers'), 'Save Exit returns to Manage Users');
$payload['save_action'] = 'stay';
$response = $controller->editUser(Request::create('/admin/edit-user/'.$user->id, 'POST', $payload));
checkUserSave($response->getTargetUrl() === route('showUser', ['id'=>$user->id]), 'explicit Save stays on editor');
$preview = view('components.admin-user-preview', ['user'=>$user->fresh()])->render();
checkUserSave(str_contains($preview, 'pk-user-preview') && str_contains($preview, '/@creator'), 'preview targets the edited user');
$user->block = 'yes';
checkUserSave(!str_contains(view('components.admin-user-preview', ['user'=>$user])->render(), '<iframe'), 'blocked profile shows explanation instead of broken preview');
$user->block = 'no';
$payload['password'] = 'replacement-password';
$controller->editUser(Request::create('/admin/edit-user/'.$user->id, 'POST', $payload));
checkUserSave(Hash::check('replacement-password', $user->fresh()->password), 'replacement password is hashed');
$before = $user->fresh()->getAttributes();
$payload['role'] = 'unsupported';
try {
    $controller->editUser(Request::create('/admin/edit-user/'.$user->id, 'POST', $payload));
    throw new RuntimeException('Invalid role was accepted');
} catch (ValidationException $e) {
    checkUserSave(isset($e->errors()['role']) && $user->fresh()->getAttributes() === $before,
        'invalid role fails validation without changing account');
}
$user = $user->fresh();
$user->fill(['role'=>'admin']);
checkUserSave($user->role === 'user', 'role remains guarded outside the explicit admin save');

// Render the real components for the browser layout and interaction checks.
$user->role = 'vip';
$user->save();
UserData::saveData($user->id, 'vip_profile', ['enabled'=>true]);
@mkdir(base_path('test-output'));
$header = view('linkstack.elements.profile-header', ['userinfo'=>$user])->render();
$avatar = view('linkstack.elements.avatar', ['userinfo'=>$user])->render();
$vip = view('linkstack.elements.vip-status', ['userinfo'=>$user])->render();
foreach (['BioLyncOcean', 'BioLyncPaper', 'BioLyncMidnight', 'BioLyncRose', 'default'] as $theme) {
    $css = $theme === 'default'
        ? file_get_contents(base_path('assets/linkstack/css/skeleton-dark.css')).file_get_contents(base_path('assets/linkstack/css/brands.css'))
        : file_get_contents(base_path('themes/'.$theme.'/skeleton-auto.css'));
    $profile = '<div class="container biolync-profile"><div class="row"><div class="column biolync-profile-column">'.$header.'<div class="biolync-avatar">'.$avatar.$vip.'</div><h1>Creator profile</h1><div class="description-parent"><p>Creator biography and public links.</p></div><div class="row social-icon-div"><a class="social-link" href="#social"><i class="social-icon">◎</i></a></div><a class="button" href="#links">Profile link</a></div></div></div>';
    file_put_contents(base_path('test-output/ui-'.$theme.'.html'), '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Profile UI QA</title><style>body{margin:0}'.$css.'</style></head><body>'.$profile.'</body></html>');
}
$user->block = 'no';
$preview = view('components.admin-user-preview', ['user'=>$user])->render();
$adminCss = file_get_contents(base_path('assets/css/hope-ui.min.css'));
$editorSource = file_get_contents(base_path('resources/views/panel/edit-user.blade.php'));
preg_match_all('/<button[^>]+name="save_action"[^>]*>.*?<\/button>/', $editorSource, $saveControls);
$saveControls = \Illuminate\Support\Facades\Blade::render(implode('', $saveControls[0]));
file_put_contents(base_path('test-output/ui-admin.html'), '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Admin preview QA</title><style>'.$adminCss.'</style></head><body><div class="container-fluid p-4"><h1>Edit User</h1><div class="row g-4"><div class="col-xl-8 order-2 order-xl-1"><form><label>Name <input name="name" value="Creator"></label>'.$saveControls.'</form></div><aside class="col-xl-4 order-1 order-xl-2">'.$preview.'</aside></div></div></body></html>');
