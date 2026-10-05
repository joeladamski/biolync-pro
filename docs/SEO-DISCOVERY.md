# SEO Discovery

SEO Discovery is BioLync's conventional-search optimization layer. It is intentionally separate from AI Discovery.

Admin → Config → SEO Discovery controls site-level search metadata:
- managed title and meta description
- canonical URL
- robots directive
- Open Graph title, description and image
- JSON-LD structured data

Blank values remain automatic and resolve from the current site name, home message and artwork. This avoids maintaining a second copy of public content.

Admin → Users → Edit User → SEO Discovery provides profile-specific controls:
- indexable / noindex
- title override
- description override
- canonical override
- robots override

Profile fields default to the public name and bio. Global SEO Discovery must be enabled before BioLync replaces the legacy metadata rendering. When disabled, existing LinkStack/BioLync metadata behavior remains unchanged.

Site configuration persists in `storage/app/seo-discovery.json`; preserve it across deployments. Profile overrides persist under `seo_discovery` in users.image through UserData. No database migration is required.

SEO Discovery and AI Discovery are designed to operate in tandem:
- SEO Discovery targets crawl, indexing, search presentation and conventional search engines.
- AI Discovery targets machine-readable context through llms.txt and llms-full.txt.
- Both derive from the same canonical public site/profile data.

The admin UI includes resolved previews and lightweight diagnostics. These are implementation checks, not ranking guarantees.
