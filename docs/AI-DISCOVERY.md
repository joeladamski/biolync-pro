# Featured profiles and AI Discovery

Admin → Config → Homepage UI Controls selects the featured public profile for the existing phone preview. Empty selection uses the demo; blocked, deleted or unnamed accounts also fall back to the demo. Selecting a profile does not automatically publish its AI files.

Admin → Config → AI Discovery enables site-wide public files and edits the introduction and context. Admin → Users → Edit User → AI Discovery controls each profile's inclusion, summary override and context. Reset clears overrides while retaining the publication checkbox. Each form saves separately from account settings.

Public files:
- `/llms.txt`: site overview, published site pages, explicitly enabled profile links.
- `/llms-full.txt`: expanded public site pages and enabled profiles.
- `/@handle/llms.txt`: profile summary, context and public HTTP/HTTPS links.
- `/@handle/llms-full.txt`: summary plus current public bio and enabled public gallery images/captions.

Publication is off by default. The global switch and per-profile checkbox must both be enabled for profile endpoints. Blocked or missing profiles return 404, including in the site index. Admin previews remain available with publication off. Public responses are plain text with no-store caching. Normal profile content is generated dynamically; account email, password, roles, private metadata and draft site pages are excluded. Admin overrides are public when enabled, so only enter information intended for visitors. A VIP membership is not identity verification.

These files are an AI-facing content guide, not a ranking guarantee or an authorization mechanism. Existing access controls still govern the application. The summary/full pairing is a BioLync publishing convention.

Site configuration persists in `storage/app/ai-discovery.json`; preserve it across deployments. Profile overrides persist under `ai_discovery` in users.image. The previously applied LONGTEXT storage upgrade is required. This feature adds no new migration. After deployment, clear compiled views/routes as usual, choose a featured profile, enable publication, opt in an intended user, and check both public files in a signed-out browser.
