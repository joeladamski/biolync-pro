# Persistent profile media

Profile photos, theme backgrounds, headers/posters/video and gallery uploads now use `../biolync-runtime/profile-media` outside the application deployment root. Override with `ESTATE_PROFILE_MEDIA_PATH` only for a deliberate absolute storage location. Each estate has separate files. Back up this directory along with the database.

Existing `assets/img/...`, `assets/profile-media/...` and `assets/profile-gallery/...` public URLs and database metadata are preserved. The app serves missing static-file requests through constrained media routes; only numeric profile filenames, recognized image/video extensions and matching media content are served. Static repository images remain unchanged. Existing files remain readable until imported.

## Backup before deploying

In the Hostinger SSH terminal, this backs up all four directories outside `public_html`. Run it for each estate containing media before merging/deploying:

```bash
(
set -e
cd ~/domains/metalking.one
backup="profile-media-backups/$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup/assets"
for folder in img profile-media profile-gallery; do
  if [ -d "public_html/assets/$folder" ]; then
    cp -a "public_html/assets/$folder" "$backup/assets/"
  fi
done
printf 'Backup: %s/%s\n' "$PWD" "$backup"
)
```

`assets/img` includes `background-img`. Do not use a ZIP backup containing `.env` or credentials in GitHub.

## Import after deployment

```bash
cd ~/domains/metalking.one/public_html
php artisan optimize:clear
php artisan estate:import-profile-media --from=/absolute/path/to/profile-media-backups/TIMESTAMP
```

Use the exact backup path printed before deployment. The command copies recognized media, retains already-persistent files, and leaves backup files untouched. Metadata stays in MySQL; if it was previously truncated or removed, file import alone cannot reconstruct it. Check the imported count against expected media.

Verify public avatar, gallery, header/video and background rendering, then redeploy and repeat checks. Production acceptance is still required; CI covers file lifecycle and existing upload/ownership checks.

## Remaining persistence scope

`storage/app` JSON settings, other theme assets, and framework caches are outside this change. Preserve and audit user-controlled settings separately. This change does not retroactively recover deleted media without backups.
