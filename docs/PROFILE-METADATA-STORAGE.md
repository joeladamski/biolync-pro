# Required profile media storage upgrade

The original users.image column is VARCHAR(255). BioLync stores JSON settings, header media references and gallery metadata in it. With the inherited non-strict MySQL configuration, oversized JSON can be silently truncated, leaving invalid JSON. SQLite tests did not enforce the VARCHAR limit.

After deploying this change, back up the database and apply this migration from the application directory:

```bash
php artisan migrate --path=database/migrations/2026_10_03_200000_expand_profile_metadata_storage.php --force
php artisan optimize:clear
```

This expands users.image to nullable LONGTEXT while preserving its current contents. The path restricts the command to this upgrade. Deployment of code alone does not change the database. Migration rollback deliberately retains the wider column to avoid truncating saved profiles.

Existing truncated JSON cannot be reconstructed by widening the column. After upgrading, re-save or re-upload the affected header and gallery; restore metadata from a database backup if needed. Preserve assets/profile-media and assets/profile-gallery during deployment. Other profile settings in already truncated JSON may also need to be saved again.

CI now reproduces silent truncation on MySQL 8 with the inherited non-strict configuration, applies the migration, and uploads a header plus a 12-photo gallery with full captions. It verifies complete JSON, both public media sections, and preservation after unrelated settings saves.
