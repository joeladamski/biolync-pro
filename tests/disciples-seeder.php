<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) {
    fwrite(STDERR, (string) $error);
    exit(1);
});

use App\Models\User;
use App\Support\ProfileGallery;
use Database\Seeders\DisciplesSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

config([
    'database.default' => 'sqlite',
    'database.connections.sqlite.database' => ':memory:',
    'app.url' => 'https://discipleship.earth',
    'app.key' => 'base64:' . base64_encode(str_repeat('x', 32)),
    'cache.default' => 'array',
    'session.driver' => 'array',
    'hashing.bcrypt.rounds' => 4,
    'linkstack.disable_random_user_ids' => 'false',
    'linkstack.user_id_length' => 6,
]);
DB::purge();
Mail::fake();
Event::fake([Registered::class]);

function disciplesCheck($ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo "PASS Disciples: $message\n";
}

function disciplesReject(string $message): void
{
    try {
        (new DisciplesSeeder)->run();
    } catch (RuntimeException $error) {
        disciplesCheck(str_contains($error->getMessage(), $message), 'rejects: ' . $message);
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $message);
}

disciplesReject('migrations');
Schema::create('users', function ($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('littlelink_name')->unique()->nullable();
    $table->text('littlelink_description')->nullable();
    $table->text('image')->nullable();
    $table->string('password');
    $table->timestamp('email_verified_at')->nullable();
    $table->string('role')->default('user');
    $table->string('block')->default('no');
    $table->string('theme')->nullable();
    $table->rememberToken();
    $table->timestamps();
});

disciplesReject('/setup-owner');
disciplesCheck(User::count() === 0, 'empty installation stays empty and owner setup remains available');

$owner = new User;
$owner->forceFill([
    'name' => 'Owner', 'email' => 'owner@example.test', 'littlelink_name' => 'owner',
    'password' => Hash::make('owner-test-secret'), 'role' => 'admin',
])->save();
$ownerBefore = $owner->fresh()->getRawOriginal();

foreach (['https://pinkkiss.love', 'https://discipleship.earth.evil.test', 'https://evil.test/discipleship.earth', 'discipleship.earth', ''] as $url) {
    config(['app.url' => $url]);
    disciplesReject('APP_URL');
    disciplesCheck(User::count() === 1, 'wrong estate writes no accounts');
}
config(['app.url' => 'https://discipleship.earth']);

// Exercise the same fully qualified Artisan entry point documented for production.
disciplesCheck(Artisan::call('db:seed', ['--class' => DisciplesSeeder::class, '--force' => true]) === 0, 'explicit Artisan seeding succeeds');
disciplesCheck(User::count() === 13, 'creates exactly twelve accounts alongside the owner');
disciplesCheck(User::find($owner->id)->getRawOriginal() === $ownerBefore, 'owner remains unchanged');
disciplesCheck(User::where('role', 'admin')->count() === 1, 'no disciple receives administrator permissions');

$passwords = [];
foreach (DisciplesSeeder::profiles() as $profile) {
    $user = User::where('littlelink_name', $profile['handle'])->firstOrFail();
    $metadata = json_decode($user->image, true, 512, JSON_THROW_ON_ERROR);
    disciplesCheck($user->email === $profile['handle'] . '@discipleship.earth', $profile['handle'] . ' uses the requested email convention');
    disciplesCheck($user->name === $profile['name'] && $user->id !== $owner->id && $user->id >= 100000, $profile['handle'] . ' preserves native random account IDs');
    disciplesCheck($user->role === 'vip' && $user->block === 'no' && ProfileGallery::canUse($user), $profile['handle'] . ' is publicly available and gallery-capable');
    disciplesCheck($metadata['vip_profile']['enabled'] === false && $metadata['checkmark'] === false, $profile['handle'] . ' has no VIP or verification badge');
    disciplesCheck($metadata['biblical_profile']['key'] === $profile['key'] && str_contains($user->littlelink_description, 'Matthew 10:' . $profile['verse'] . ' (KJV)'), $profile['handle'] . ' retains stable identity and Scripture attribution');
    disciplesCheck(Hash::info($user->password)['algoName'] !== 'unknown' && !Hash::check('12345678', $user->password) && !Hash::check('password', $user->password), $profile['handle'] . ' has a hashed, non-default password');
    $passwords[] = $user->password;
}
disciplesCheck(count(array_unique($passwords)) === 12, 'password hashes are independent');
disciplesCheck(!User::whereIn('littlelink_name', ['matthias', 'paul'])->exists(), 'roster is the original Twelve including Judas');

$peter = User::where('littlelink_name', 'peter')->firstOrFail();
$metadata = json_decode($peter->image, true);
$metadata['photo_gallery'] = ['enabled' => false, 'photos' => [['caption' => 'Admin gallery edit']]];
$metadata['custom_setting'] = 'keep';
$peter->forceFill([
    'name' => 'Peter edited', 'email' => 'peter-edited@discipleship.earth',
    'littlelink_name' => 'peter-edited', 'littlelink_description' => 'Admin Scripture edit',
    'password' => Hash::make('changed-by-admin'), 'theme' => 'galaxy',
    'image' => json_encode($metadata),
])->save();
$beforeRerun = DB::table('users')->orderBy('id')->get()->toJson();
disciplesCheck(Artisan::call('db:seed', ['--class' => DisciplesSeeder::class, '--force' => true]) === 0, 'rerun succeeds after admin renames and media edits');
disciplesCheck(DB::table('users')->orderBy('id')->get()->toJson() === $beforeRerun, 'rerun changes no IDs, credentials, descriptions, names, handles, email, media, or timestamps');

config(['app.url' => 'https://WWW.DISCIPLESHIP.EARTH']);
(new DisciplesSeeder)->run();
disciplesCheck(User::count() === 13, 'www estate alias is accepted without duplicates');
config(['app.url' => 'https://discipleship.earth']);

User::where('role', '!=', 'admin')->delete();
foreach ([['judas', 'JUDAS@discipleship.earth', 'outsider'], ['peter', 'outsider@example.test', 'PETER']] as [$key, $email, $handle]) {
    $collision = new User;
    $collision->forceFill(['name' => 'Unrelated account', 'email' => $email, 'littlelink_name' => $handle, 'password' => Hash::make('unrelated-secret')])->save();
    $before = DB::table('users')->orderBy('id')->get()->toJson();
    disciplesReject('belongs to another account');
    disciplesCheck(DB::table('users')->orderBy('id')->get()->toJson() === $before, 'case-insensitive ' . $key . ' collision never adopts or overwrites a user and leaves no partial roster');
    $collision->delete();
}

$failCreation = true;
User::creating(function ($user) use (&$failCreation) {
    if ($failCreation && $user->littlelink_name === 'john') {
        throw new RuntimeException('Injected write failure');
    }
});
disciplesReject('Injected write failure');
disciplesCheck(User::count() === 1, 'mid-write failure rolls back every created disciple');
$failCreation = false;
(new DisciplesSeeder)->run();
disciplesCheck(User::count() === 13, 'seeder recovers cleanly after a rolled-back failure');

$duplicate = new User;
$duplicate->forceFill([
    'name' => 'Duplicate identity', 'email' => 'duplicate@example.test', 'littlelink_name' => 'duplicate',
    'password' => Hash::make('duplicate-secret'),
    'image' => User::where('littlelink_name', 'peter')->value('image'),
])->save();
disciplesReject('Duplicate seeded identity');

Mail::assertNothingSent();
Mail::assertNothingQueued();
Event::assertNotDispatched(Registered::class);
disciplesCheck(!str_contains(file_get_contents(__DIR__ . '/../database/seeders/DatabaseSeeder.php'), 'DisciplesSeeder'), 'default database seeding never runs this estate-specific seed');
echo "PASS Disciples: account seeding regression checks complete\n";
