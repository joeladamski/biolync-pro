<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e) { fwrite(STDERR, (string) $e); exit(1); });

use App\Models\User;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Middleware\CheckBlockedUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:',
    'cache.default'=>'array', 'session.driver'=>'array', 'app.key'=>'base64:'.base64_encode(str_repeat('x',32)),
    'linkstack.disable_random_user_ids'=>'true']);
DB::purge();
Schema::create('users', function ($t) {
    $t->id(); $t->string('name'); $t->string('email')->unique(); $t->string('password')->nullable();
    $t->string('littlelink_name')->nullable(); $t->text('image')->nullable();
    $t->string('role')->default('user'); $t->string('block')->default('no');
    $t->timestamp('email_verified_at')->nullable(); $t->rememberToken(); $t->timestamps();
});
Schema::create('social_accounts', function ($t) {
    $t->id(); $t->unsignedBigInteger('user_id'); $t->string('provider_name');
    $t->string('provider_id'); $t->timestamps();
});
function approvalCheck($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS approval: $message\n";
}
function approvalRequest($data = []) {
    $request = Request::create('/register', 'POST', $data);
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(fn() => Auth::user());
    app()->instance('request', $request);
    return $request;
}
Notification::fake();
$mailCount = 0;
Mail::shouldReceive('send')->andReturnUsing(function () use (&$mailCount) { $mailCount++; });
$registered = [];
Event::listen(Registered::class, function ($event) use (&$registered) {
    approvalCheck($event->user instanceof User, 'Registered event contains the saved User');
    $registered[] = $event->user->id;
});
$createdBlocks = [];
User::created(function ($user) use (&$createdBlocks) { $createdBlocks[$user->id] = $user->block; });

foreach ([false, true] as $manual) {
    foreach ([false, true] as $email) {
        config(['registration.manual_verification'=>$manual, 'registration.email_verification'=>$email]);
        Auth::forgetGuards();
        $key = (int)$manual.'_'.(int)$email;
        $request = approvalRequest(['name'=>'Signup '.$key, 'email'=>$key.'@example.test',
            'littlelink_name'=>'signup_'.$key, 'password'=>'test-password']);
        $beforeMail = $mailCount;
        $beforeEvents = count($registered);
        $response = (new RegisteredUserController)->store($request);
        $user = User::where('email', $key.'@example.test')->firstOrFail();
        $expected = $manual ? 'yes' : 'no';
        approvalCheck($user->block === $expected && $createdBlocks[$user->id] === $expected,
            "password signup $key persists approval status before login");
        approvalCheck($mailCount - $beforeMail === (int)$manual, 'admin notice depends on manual approval only');
        approvalCheck(count($registered) - $beforeEvents === (int)$email, 'email verification is sent once only when enabled');
        approvalCheck($response->getTargetUrl() === ($manual ? route('blocked') : url('dashboard')), 'signup redirects to correct destination');
        if ($manual) {
            // Confirming email must not grant administrative approval.
            $verify = Mockery::mock(EmailVerificationRequest::class)->makePartial();
            $verify->shouldReceive('user')->andReturn($user);
            (new VerifyEmailController)->__invoke($verify);
            approvalCheck($user->fresh()->hasVerifiedEmail() && $user->fresh()->block === 'yes', 'email confirmation preserves manual hold');
            Auth::setUser($user->fresh());
            $blocked = (new CheckBlockedUser)->handle($request, fn() => 'allowed');
            approvalCheck($blocked->getTargetUrl() === route('blocked'), 'pending account cannot access dashboard');
            $user->block = 'no'; $user->save(); Auth::setUser($user);
            approvalCheck((new CheckBlockedUser)->handle($request, fn() => 'allowed') === 'allowed', 'admin approval releases the hold');
        }
    }
}

foreach ([true, false] as $manual) {
    config(['registration.manual_verification'=>$manual]);
    Auth::forgetGuards(); approvalRequest();
    $key = 'social_'.(int)$manual;
    $social = Mockery::mock(Laravel\Socialite\Contracts\User::class);
    $social->shouldReceive('getId')->andReturn($key);
    $social->shouldReceive('getEmail')->andReturn($key.'@example.test');
    $social->shouldReceive('getName')->andReturn($key);
    $social->shouldReceive('getAvatar')->andReturn(null);
    $social->shouldReceive('getNickname')->andReturn($key);
    $provider = Mockery::mock(Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($social);
    $socialite = Mockery::mock();
    $socialite->shouldReceive('driver')->with('github')->andReturn($provider);
    Laravel\Socialite\Facades\Socialite::swap($socialite);
    (new SocialLoginController)->providerCallback('github');
    $user = User::where('email', $key.'@example.test')->firstOrFail();
    approvalCheck($user->block === ($manual ? 'yes' : 'no') && $user->hasVerifiedEmail(), 'social email ownership does not bypass approval policy');
    // Existing social accounts retain their approval status when the policy changes.
    config(['registration.manual_verification'=>!$manual]);
    (new SocialLoginController)->providerCallback('github');
    approvalCheck($user->fresh()->block === ($manual ? 'yes' : 'no'), 'returning social login preserves existing approval');
}
config(['registration.manual_verification'=>true]);
$failedMailer = Mockery::mock();
$failedMailer->shouldReceive('send')->andThrow(new RuntimeException('Simulated mail outage'));
Mail::swap($failedMailer);
$user = new User(['name'=>'Mail outage', 'email'=>'outage@example.test']);
App\Support\RegistrationApproval::create($user);
approvalCheck($user->fresh()->block === 'yes', 'mail failure leaves signup pending');
