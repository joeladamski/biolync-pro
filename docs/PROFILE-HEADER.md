# BIOLYNC PHP Default — Build 004

Public profiles support an optional image/video cover, maximum 600px wide, 220px high on desktop and 190px on mobile. The avatar overlaps by 56px. Profiles without media keep their avatar and content, with a smaller top gap. Existing buttons are unchanged.

The four BioLync themes now apply their canonical `--biolync-bg` color to both HTML and body. Decorative gradients remain available as a header fallback. Explicit custom theme background images retain their existing behavior.

## Editing

Studio → Page → Public profile header. Upload JPEG/PNG/WebP or MP4/WebM (10MB maximum), optionally upload a video poster (2MB maximum), and select center/top/bottom crop. Empty file inputs preserve existing media. Remove header deletes both media and poster. Videos play muted inline, loop, expose a pause/play button, and do not autoplay when reduced motion is requested. Autoplay blocked by the browser leaves the poster and play button available.

## Deployment

No database migration. Metadata uses the existing users.image JSON data under `profile_header`. Files live in `assets/profile-media`, independently of avatars and theme backgrounds. Preserve this directory across deployments and backups. PHP must be able to create/write it; configure `upload_max_filesize` at least 10M and `post_max_size` above 12M. The server should serve these assets as static media without script execution. Standard MIME validation and generated filenames exclude executable uploads.

## Validation before release

CI lints the controller/routes and compiles/lints the modified Blade views. Runtime and browser acceptance remain required: authenticated upload/replace/remove; invalid MIME and oversize rejection; separate users' media; image and video with poster; reduced motion and blocked autoplay; no-cover profile; root HOME_URL and /@handle; each BioLync theme; 375px iPhone and desktop; custom backgrounds; persistence across deployment. No live Hostinger deployment has been performed by this change.
