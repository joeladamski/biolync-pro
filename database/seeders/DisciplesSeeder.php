<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/** Explicitly invoked, estate-specific account scaffolding; never part of DatabaseSeeder. */
class DisciplesSeeder extends Seeder
{
    private const COLLECTION = 'discipleship-original-twelve';

    /** Stable identity keys survive later changes to a profile's handle or email. */
    public static function profiles(): array
    {
        return [
            ['key' => 'simon-peter', 'name' => 'Simon Peter', 'handle' => 'peter', 'verse' => 2],
            ['key' => 'andrew', 'name' => 'Andrew', 'handle' => 'andrew', 'verse' => 2],
            ['key' => 'james-zebedee', 'name' => 'James (son of Zebedee)', 'handle' => 'jamesz', 'verse' => 2],
            ['key' => 'john', 'name' => 'John', 'handle' => 'john', 'verse' => 2],
            ['key' => 'philip', 'name' => 'Philip', 'handle' => 'philip', 'verse' => 3],
            ['key' => 'bartholomew', 'name' => 'Bartholomew', 'handle' => 'bartholomew', 'verse' => 3],
            ['key' => 'thomas', 'name' => 'Thomas', 'handle' => 'thomas', 'verse' => 3],
            ['key' => 'matthew', 'name' => 'Matthew', 'handle' => 'matthew', 'verse' => 3],
            ['key' => 'james-alphaeus', 'name' => 'James (son of Alphaeus)', 'handle' => 'jamesa', 'verse' => 3],
            ['key' => 'thaddaeus', 'name' => 'Lebbaeus (Thaddaeus)', 'handle' => 'thaddaeus', 'verse' => 3],
            ['key' => 'simon-canaanite', 'name' => 'Simon the Canaanite', 'handle' => 'simon', 'verse' => 4],
            ['key' => 'judas-iscariot', 'name' => 'Judas Iscariot', 'handle' => 'judas', 'verse' => 4],
        ];
    }

    public function run()
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if (!in_array($host, ['discipleship.earth', 'www.discipleship.earth'], true)) {
            throw new RuntimeException('DisciplesSeeder only runs when APP_URL belongs to discipleship.earth.');
        }
        if (!Schema::hasTable('users')) {
            throw new RuntimeException('Run the application migrations and complete /setup-owner before seeding the Twelve.');
        }

        $rows = DB::transaction(function () {
            // Never let these accounts consume first-owner setup or the reserved first ID.
            if (!User::where('role', 'admin')->lockForUpdate()->first()) {
                throw new RuntimeException('Create your administrator through /setup-owner before seeding the Twelve.');
            }
            if (!is_file(storage_path('app/ISINSTALLED'))
                || file_exists(base_path('INSTALLING'))
                || file_exists(base_path('INSTALLERLOCK'))) {
                throw new RuntimeException('Complete owner setup and finalization before seeding the Twelve (ISINSTALLED must exist and installer locks must be cleared).');
            }

            $managed = [];
            foreach (User::where('image', 'like', '%' . self::COLLECTION . '%')->lockForUpdate()->get() as $user) {
                $metadata = json_decode($user->image ?? '{}', true);
                $identity = is_array($metadata) ? ($metadata['biblical_profile'] ?? null) : null;
                if (!is_array($identity) || ($identity['collection'] ?? null) !== self::COLLECTION) {
                    continue;
                }
                $key = $identity['key'] ?? null;
                if (!is_string($key)) {
                    continue;
                }
                if (isset($managed[$key])) {
                    throw new RuntimeException('Duplicate seeded identity: ' . $key . '. Resolve it before running the seeder.');
                }
                $managed[$key] = $user;
            }

            // Check the entire roster before writing; never adopt an unrelated account.
            foreach (self::profiles() as $profile) {
                if (isset($managed[$profile['key']])) {
                    continue;
                }
                $email = $profile['handle'] . '@discipleship.earth';
                $collision = User::where(function ($query) use ($profile, $email) {
                    $query->whereRaw('LOWER(email) = ?', [$email])
                        ->orWhereRaw('LOWER(littlelink_name) = ?', [$profile['handle']]);
                })->lockForUpdate()->first();
                if ($collision) {
                    throw new RuntimeException('Cannot seed @' . $profile['handle'] . ': its handle or email belongs to another account. No accounts were created.');
                }
            }

            $rows = [];
            foreach (self::profiles() as $profile) {
                $user = $managed[$profile['key']] ?? null;
                $status = 'Preserved';
                if (!$user) {
                    $reference = 'Matthew 10:' . $profile['verse'];
                    $metadata = [
                        'biblical_profile' => [
                            'collection' => self::COLLECTION,
                            'key' => $profile['key'],
                            'version' => 1,
                            'type' => 'biblical-character',
                            'scripture_reference' => $reference,
                            'translation' => 'KJV',
                        ],
                        'checkmark' => false,
                        'vip_profile' => ['enabled' => false],
                    ];
                    $user = new User;
                    $user->forceFill([
                        'name' => $profile['name'],
                        'email' => $profile['handle'] . '@discipleship.earth',
                        'littlelink_name' => $profile['handle'],
                        'littlelink_description' => self::scripture($profile['verse']) . "\n\n" . $reference . ' (KJV)',
                        // Generate no shared credential and disclose no plaintext password.
                        'password' => Hash::make(Str::random(64)),
                        'email_verified_at' => now(),
                        'role' => 'vip',
                        'block' => 'no',
                        'theme' => 'default',
                        'image' => json_encode($metadata, JSON_THROW_ON_ERROR),
                    ])->save();
                    $status = 'Created';
                }
                $rows[] = [$status, $user->name, '@' . $user->littlelink_name, $user->email];
            }

            return $rows;
        });

        if ($this->command) {
            $this->command->table(['Status', 'Name', 'Handle', 'Email'], $rows);
            $this->command->info('The original Twelve are ready. Manage profiles through Admin Users / Auth As; no mail was sent.');
        }
    }

    private static function scripture(int $verse): string
    {
        return [
            2 => "Now the names of the twelve apostles are these; The first, Simon, who is called Peter, and Andrew his brother; James the son of Zebedee, and John his brother;",
            3 => "Philip, and Bartholomew; Thomas, and Matthew the publican; James the son of Alphaeus, and Lebbaeus, whose surname was Thaddaeus;",
            4 => "Simon the Canaanite, and Judas Iscariot, who also betrayed him.",
        ][$verse];
    }
}
