# Site pages and public navigation

Admin → Config → Site Pages creates up to 20 plain-text pages at `/pages/{address}`. Each page has a title, optional navigation label, content, order, Published and Show in header controls. Drafts return 404. Published pages can remain accessible without a menu link. Existing Terms, Privacy and Contact pages stay in Footer Pages and their addresses are reserved.

The homepage and these new pages share a sticky, responsive header. Mobile navigation scrolls when the menu is taller than the available screen. The header occupies normal document space so it does not cover the initial content.

Pages are stored in `storage/app/site-pages.json`; preserve this file across deployments and include it in backups. The PHP process needs write access to `storage/app`. No database migration is required. Content is escaped plain text with line breaks retained.

CI checks page lifecycle and the shipped homepage CSS across portrait, landscape and desktop viewport sizes. Actual iPhone Safari and the deployed admin flow still need production acceptance checks.
