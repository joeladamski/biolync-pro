# Persistent estate branding

Hostinger redeployment removed Metal King's uploaded logo and favicon even when the deployed commit changed no files. Those uploads must not live in the replaceable release tree.

New uploads go to `../biolync-runtime/branding` relative to the application root. With the current Hostinger layout this is `/home/u338954535/domains/<domain>/biolync-runtime/branding`, outside `public_html`. Each estate has its own directory. Override with `ESTATE_BRANDING_PATH` only when an alternate absolute path is needed; preserve it across deployments and include it in backups. No APP_KEY rotation or database migration is needed.

Public `/site-branding/avatar` and `/site-branding/favicon` endpoints serve only the two branding images. Templates use these endpoints with a content-version query. Images remain readable from the legacy asset directory until imported. PNG/JPG/WebP/GIF uploads up to 2 MB are supported; SVG and ICO uploads are not supported by this change.

## Metal King first deployment

The source files have already been verified in the external backup below. Deploy the merged fix, then:

```bash
cd ~/domains/metalking.one/public_html
php artisan optimize:clear
php artisan estate:import-branding --from=/home/u338954535/domains/metalking.one/branding-backups/images-20261013-174601
```

The command retains any existing persistent logo or favicon. It copies from the backup without deleting it. Confirm both images load on homepage and login, then redeploy once and verify the images and persistent files still exist. The verified branding-backups directory survived earlier redeployments; the new persistent directory still needs this explicit production check.

For other estates, back up branding outside `public_html` **before** deploying this fix, then import from that backup. Do not assume legacy uploads will still exist after deployment.

## Other runtime files

This change protects estate logo/favicon only. Profile avatars (`assets/img`), headers (`assets/profile-media`), galleries (`assets/profile-gallery`), theme backgrounds, and `storage/app/*.json` settings still require a persistence audit and backup. Do not claim those files are protected by this branding fix.
