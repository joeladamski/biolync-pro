# Per-user photo gallery

Studio → Page → Photo gallery. Upload JPEG, PNG or WebP, up to 12 photos per user and 2 MB per photo (maximum 6000 pixels per side). Convert HEIC to JPEG before uploading. Captions double as alternative text. Set display order, remove individual images, and enable or hide the gallery. Save separately from the profile text.

Public galleries appear after profile links, with three CSS columns on desktop and two below 768 pixels. Images retain their proportions; column flow reads top to bottom, then across. Tap a photo to open the accessible native dialog viewer; close by button or Escape. Without dialog support or JavaScript, the image link opens directly.

Metadata is stored in the user's existing image JSON under photo_gallery; no migration. Writes use the authenticated user's ID and a database row lock. Paths are generated and checked for ownership before rendering or deletion. Preserve assets/profile-gallery during deployment along with existing profile-media and storage. Uploaded files have public URLs: hiding the gallery does not revoke previously shared direct links. Original files may contain camera metadata.

Configure PHP post_max_size for multi-file uploads (at least 32 MB), upload_max_filesize at least 2 MB, and max_file_uploads at least 12. Directory must be writable by PHP. Application validation rejects the whole batch if invalid or over the total photo limit. No image-processing extension added; native-sized originals are displayed as lazy-loaded thumbnails.

CI covers upload persistence, account isolation, ordering, escaped captions, format/size limits, total limit, visibility and selective deletion. Chromium fixture checks cover column counts, aspect ratio, overflow, viewer opening and Escape closing. Production checks: Studio saves under real auth/CSRF, batch upload under Hostinger limits, and iPhone Safari viewer/layout. Also verify server rules prevent script execution within uploaded asset directories.

## VIP access and editor

Photo Gallery has its own Personalization sidebar entry at `/studio/photo-gallery`, available to VIP and admin accounts. Standard users receive 403 from the editor and save endpoint. Public galleries are shown only while the profile owner is VIP or admin; downgrading hides the gallery while retaining photos for later restoration. The public profile needs the Show gallery checkbox enabled.

Metadata writes merge into a freshly locked database row, preserving header, gallery and other settings even if an older profile object was cached. Gallery saves return to the gallery editor.
