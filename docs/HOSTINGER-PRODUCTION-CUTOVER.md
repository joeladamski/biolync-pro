# Hostinger production cutover

This runbook moves BioLync.Pro from a Git-tracked SQLite installation to a
deployment-safe Hostinger configuration.

## Why this is required

The upstream source repository tracks three files that are runtime state on a
production server:

- `.env` contains the application key and service credentials.
- `database/database.sqlite` contains user and application data.
- `INSTALLING` enables the public setup workflow.

A Git deployment must never replace the first two or restore the third.

## Pre-deployment gates

Do not deploy this branch until all gates are satisfied:

1. Verify a private backup of the live environment, SQLite database, and uploaded
   assets outside `public_html`.
2. Verify SQLite with `PRAGMA integrity_check`.
3. Create a Hostinger MySQL database assigned to the BioLync.Pro website.
4. Initialize and verify the MySQL schema and application account.
5. Preserve the production `.env` outside `public_html` during the first
   deployment of this change.

## First deployment

The first deployment that removes the tracked runtime files is a coordinated
cutover. Keep the existing SSH session open.

1. Copy the production `.env` to a private path outside `public_html`.
2. Confirm the application is using MySQL and the login works.
3. Deploy the merged persistence commit from `main`.
4. Restore `.env` from the private copy if the deploy removed it.
5. Run `php artisan optimize:clear`.
6. Confirm that `INSTALLING` and `INSTALLERLOCK` do not exist.
7. Test login, profile editing, the public profile, and the uploaded image.

## Ongoing deployment contract

- `.env`, `database/database.sqlite`, `INSTALLING`, and `INSTALLERLOCK`
  remain ignored and untracked.
- Production uses MySQL; the SQLite file is not recreated.
- User media under ignored asset paths must be checked after one controlled
  redeployment before automatic deployment is considered safe.
- A database backup and media backup are required before schema migrations.

## Automatic cache cleanup on subsequent deployments

Normal `composer install`, `composer update`, and `composer dump-autoload`
executions now clear Laravel's configuration, route, and compiled Blade view
caches after package discovery. This also runs when the lock file has no new
packages to install. Each estate keeps its own saved settings; deployments do
not enable the device reflection or overwrite homepage configuration.

Deploy the updated `main` once to each estate through its existing deployment
process. A successful GitHub merge does not update a live checkout by itself.
If a deployment fails before Composer finishes, that estate has not completed
this cleanup. Resolve the failed deployment before retrying it.

Deployments using `--no-scripts`, or copying files without running Composer,
must explicitly run the following from the estate's application directory:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

These commands clear generated framework caches. They do not flush the
application data cache, erase uploaded media, or change saved estate settings.
