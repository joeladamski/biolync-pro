# BioLync Native Blocks — Foundation 001

## Purpose

BioLync.Pro evolves the link-in-bio model into a richer public identity surface while preserving the existing LinkStack block behavior and upstream provenance.

The first reference persona is **Sloane Mercer**, COO of BioLync.Pro. Her profile is the UX proving ground for each BioLync-native capability.

## Reference persona

**Public description**

> COO @ BioLync.Pro. Creative director, traveler, and hospitality storyteller connecting interesting people, beautiful places, ambitious brands, and opportunities worth exploring.

## Product rule

A new block earns its place only when it makes a person's digital identity meaningfully richer than an ordinary link.

## Build 013 scope

Introduce three BioLync-native block concepts without replacing or breaking the existing LinkStack blocks:

### 1. Profile Card

Structured public identity card.

Initial fields:
- display title / role
- organization
- short bio
- location label (optional)
- status / availability (optional)
- CTA label + URL (optional)

Future-safe fields may include verification state, credentials and profile-to-profile relationships, but those are explicitly out of scope for this build.

### 2. Gallery

Use the existing BioLync profile gallery implementation as the media foundation rather than creating a duplicate image system.

Existing behavior to preserve:
- up to 12 photos
- 3 columns desktop / 2 columns mobile
- original image proportions
- captions / descriptions
- ordering
- JPEG, PNG and WebP uploads

Build direction:
- surface Gallery as a first-class BioLync block/category in the Add Block UX
- keep existing gallery storage and upload safety rules
- do not migrate or duplicate existing gallery data in this build

### 3. Featured Card

Editorial content card for a project, destination, article, business, campaign or opportunity.

Initial fields:
- image
- eyebrow / category (optional)
- title
- description
- CTA label
- CTA URL

Presentation should be visually distinct from a normal link button and responsive across mobile and desktop.

## Add Block information architecture

Preserve all existing block types. Add a visible **BioLync** group for native capabilities:

- Profile Card
- Gallery
- Featured Card

This is the beginning of an extensibility layer, not a rewrite of LinkStack's block system.

## Architecture constraints

1. Keep upstream LinkStack blocks operational and recognizable.
2. Prefer BioLync-specific classes, views, routes and storage over invasive upstream rewrites.
3. Reuse the existing `ProfileGallery` implementation for Gallery.
4. Do not put secrets, user uploads or runtime persistence into Git.
5. Any new upload path must enforce MIME/type, size and ownership checks.
6. Public rendering must escape user-authored text by default; rich HTML is not required for these blocks.
7. External URLs must be validated and rendered safely.
8. The implementation must remain compatible with the repository's AGPL-3.0/upstream provenance obligations.

## Acceptance criteria

- Existing Predefined Site, Custom Link, vCard, E-mail, Telephone, Heading, Spacer and Text flows continue to work.
- Add Block clearly identifies the three BioLync-native capabilities.
- Gallery reuses the current gallery data rather than creating a second gallery store.
- Profile Card and Featured Card have owner-only create/edit/delete operations.
- All three render on the public profile responsively.
- Empty/disabled blocks do not render publicly.
- No production-only Hostinger state is assumed by the code.
- No change is merged to `main` until the implementation is reviewed and deployment/persistence behavior is verified.

## Seed profile: Sloane Mercer

Use Sloane as the reference UX account after deployment:

**Profile Card**
- Role: COO
- Organization: BioLync.Pro
- Bio: Creative director, traveler, and hospitality storyteller connecting interesting people, beautiful places, ambitious brands, and opportunities worth exploring.

**Gallery**
- Use the existing gallery feature and the approved Sloane visual set.

**Featured Card**
- First card should demonstrate a real BioLync use case rather than filler content; suggested category: `BioLync.Pro` with a product/mission CTA.

## Not in Build 013

Do not add AI chat, bookings, feeds, social ingestion, recommendations, maps, profile graphs or analytics yet. Those should be informed by what we learn from the three foundation blocks.
