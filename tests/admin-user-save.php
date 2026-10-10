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
