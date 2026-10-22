# Discipleship.Earth: the original Twelve

`DisciplesSeeder` creates twelve administrator-managed biblical character
accounts, including Judas Iscariot. It does not include Matthias or Paul.
It is opt-in and is deliberately absent from `DatabaseSeeder` and migrations.
Deploying this code to another estate creates no accounts there.

## Accounts

| Display name | Handle | Account email |
| --- | --- | --- |
| Simon Peter | `@peter` | `peter@discipleship.earth` |
| Andrew | `@andrew` | `andrew@discipleship.earth` |
| James (son of Zebedee) | `@jamesz` | `jamesz@discipleship.earth` |
| John | `@john` | `john@discipleship.earth` |
| Philip | `@philip` | `philip@discipleship.earth` |
| Bartholomew | `@bartholomew` | `bartholomew@discipleship.earth` |
| Thomas | `@thomas` | `thomas@discipleship.earth` |
| Matthew | `@matthew` | `matthew@discipleship.earth` |
| James (son of Alphaeus) | `@jamesa` | `jamesa@discipleship.earth` |
| Lebbaeus (Thaddaeus) | `@thaddaeus` | `thaddaeus@discipleship.earth` |
| Simon the Canaanite | `@simon` | `simon@discipleship.earth` |
| Judas Iscariot | `@judas` | `judas@discipleship.earth` |

Names and the initial Scripture descriptions follow Matthew 10:2–4, KJV:
https://www.linkedword.com/matt/10/

## Deploy and seed

Complete migrations and the normal `/setup-owner` workflow first. The seed
refuses to run without an administrator or before owner finalization completes.
It requires `storage/app/ISINSTALLED` and the absence of the root `INSTALLING`
and `INSTALLERLOCK` markers, preserving the installer's single-owner state.
`APP_URL` must have the exact host `discipleship.earth` or
`www.discipleship.earth`; other estates and lookalike hosts are rejected.

After merging the PR, pull through the estate's existing deployment process,
then run from its application directory:

```bash
cd /home/u338954535/domains/discipleship.earth/public_html
git pull --ff-only origin main
composer install --no-dev --prefer-dist --no-interaction
php artisan config:clear
php artisan db:seed --class='Database\Seeders\DisciplesSeeder' --force
```

The command prints each account's actual name, handle and email, labeled
Created or Preserved. Run it again safely to verify idempotence.
No additional schema migration is introduced by this change.

## Profile editing and access

Use Admin → Users to edit these accounts and the existing Auth As control
to enter a disciple's Studio for links, avatar and gallery work. Every account
has the existing VIP role because Photo Gallery requires VIP or admin access.
No account receives admin permissions. VIP badges and verification checkmarks
are initially disabled; profile pages are unblocked and publicly accessible.
The existing default theme is selected.

Each account gets a unique random password which is hashed immediately. No
plaintext password is stored in source, printed, or sent by email. These are
ordinary account records, not a new login-disabled account type: an administrator
can deliberately set credentials later. Email verification is administratively
initialized for these managed records; the seed does not prove mailbox ownership.
The email addresses are account identifiers. This does not provision mailboxes.

## Rerun and collision behavior

A stable identity marker is stored in the existing `users.image` metadata JSON
under `biblical_profile`. An already seeded profile is left entirely untouched,
even after its name, email or handle is changed through admin. Uploaded media,
gallery settings, role, descriptions, credentials and timestamps are preserved.
Keep this metadata marker when doing manual database imports or rewrites.

The seed checks new identities for case-insensitive handle or email collisions
before creating any accounts. An unrelated existing account is never adopted or
overwritten. Resolve a collision manually, then rerun. Duplicate identity markers
also stop the seed. Creation is transactional, so a failed run leaves no partial
roster. A deleted seeded identity will be recreated by the next explicit run.

## Content phase

This change establishes the account identities and initial KJV descriptions.
Avatars, gallery images, the sectioned 380-word narratives, interleaved buttons,
and demographic cards are subsequent content work. No placeholder photos,
invented ages, biographical claims, or broken media links are seeded.

## Validation

`php tests/disciples-seeder.php` exercises the real Laravel seeder and User model
against an isolated in-memory SQLite schema. It checks estate/owner gates, the
original roster, pending finalization and installer locks, role/gallery eligibility,
native random IDs, passwords, Scripture
attribution, rerun preservation, renamed identity tracking, case-insensitive
collisions, transaction rollback and the absence of registration email events.
The dedicated pull-request workflow runs these checks on PHP 8.2.
