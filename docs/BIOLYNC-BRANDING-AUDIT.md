# BioLync.Pro branding audit

Status: baseline audit for `build-002-biolync-branding-audit`

## Objective

Turn the public-facing LinkStack fork into BioLync.Pro without breaking the
internal namespaces, theme compatibility, update behavior, or the upstream
license obligations that make the application maintainable.

## Baseline findings

- `LinkStack`, `Linkstack`, `linkstack.org`, or `LinkStackOrg` appears in 91
  tracked files outside dependency lock files.
- 66 of those files are under `resources/`, including 52 Blade templates and
  the translation catalogues.
- Runtime assets, Blade view names, configuration keys, and PHP namespaces use
  `linkstack` as an internal identifier. Those identifiers are not all branding.
- User-uploaded profile images are written to `assets/img/`; custom backgrounds
  are written to `assets/img/background-img/`. Both directories are inside the
  deployment tree.
- `database/database.sqlite` is tracked by Git. It must not remain the
  production database after automated deployments are enabled.
- The project is licensed under AGPL-3.0. BioLync.Pro must retain the license,
  upstream copyright/provenance, source availability, and appropriate legal
  notices even when the product-facing branding changes.

## Replacement tiers

### Tier 1 — replace in the first branding build

These are visible product identity and should become BioLync.Pro:

- Browser titles, metadata, Open Graph defaults, and authentication copy.
- Installer and updater headings.
- Sidebar, footer, dashboard, editor, notification, and support copy.
- Default application name in `config/app.php`, `.env.example`, and installer
  form defaults.
- Default logo, animated logo, powered-by mark, favicon, and empty-state art.
- English translation values used by the active production interface.
- Repository README introduction and deployment instructions for this fork.

### Tier 2 — replace after asset and compatibility tests

- Asset directory names such as `assets/linkstack/`.
- Blade namespaces such as `resources/views/linkstack/`.
- Configuration filenames and keys such as `config/linkstack.php` and
  `config('linkstack.*')`.
- CSS font names, icon identifiers, cache keys, and theme comments.

These identifiers are embedded throughout themes and controller code. Renaming
them in the first pass creates failure risk without improving the visible brand.

### Tier 3 — retain as upstream provenance or integration references

- `LICENSE` and AGPL-3.0 notices.
- Historical copyright and contributor attribution.
- Explicit upstream references needed to explain that BioLync.Pro is a modified
  LinkStack fork.
- Upstream update/theme endpoints until BioLync.Pro owns tested replacements.
- Package lineage in Composer until a separate package identity is required and
  dependency/update tooling has been updated.

## Production persistence gate

Branding must not be deployed until production persistence is safe:

1. Back up `.env`, `database/database.sqlite`, `assets/img/`, and custom site
   assets from the live Hostinger account.
2. Point the production Git deployment at `main`.
3. Create a Hostinger MySQL database and migrate the live SQLite records.
4. Store uploaded media outside any directory Hostinger replaces during Git
   deployment, then expose it through an application-controlled path or symlink.
5. Remove `database/database.sqlite` from Git only after MySQL is verified.
6. Test login, the public profile, image rendering, and one controlled
   redeployment before accepting real users.

## First implementation slice

The first implementation PR should be deliberately boring and reversible:

1. Introduce centralized product metadata for name, URL, support URL, repository
   URL, and upstream attribution.
2. Replace hard-coded visible identity with those values.
3. Add BioLync.Pro logo/wordmark assets without renaming internal directories.
4. Disable upstream donation/update prompts that would confuse BioLync.Pro
   operators while preserving an About/Source notice.
5. Add smoke tests for login, dashboard, editor, public profile, metadata, and
   missing-image fallbacks.

## Definition of done

- No unintended LinkStack product branding is visible in normal BioLync.Pro
  user, administrator, installer, or public-profile flows.
- Upstream attribution and AGPL source access remain available.
- Existing LinkStack themes continue to render.
- A Git redeployment does not replace the production database or uploaded media.
- Login, profile editing, profile image upload, and public profile rendering pass
  after deployment.
