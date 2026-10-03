# BioLync.Pro

BioLync.Pro is a self-hosted profile and link-sharing application built as a modified fork of [LinkStack](https://github.com/LinkStackOrg/LinkStack). This repository contains the BioLync.Pro source; it is not an official LinkStack release.

The September 2026 branding baseline introduced BioLync identity and a native theme pack. Some internal identifiers and compatibility integrations still use LinkStack names. Those are not a claim that the two projects have identical releases or support channels.

## Status and installation

This repository does not currently publish a BioLync.Pro release archive or Docker image. Do not use an upstream LinkStack release, Docker image, or one-click update as a BioLync.Pro deployment: those artifacts may replace fork-specific changes.

For an existing Hostinger installation, follow the [production cutover runbook](docs/HOSTINGER-PRODUCTION-CUTOVER.md) and back up the database, environment, and uploaded media before a controlled deployment. GitHub source alone does not establish the live Hostinger state. The [branding audit](docs/BIOLYNC-BRANDING-AUDIT.md) records the compatibility and persistence gates. Generalized installation instructions will be published after the deployment path is verified.

## Themes

The repository includes native BioLync.Pro themes. Existing LinkStack-compatible themes and internal paths may still be used for compatibility; do not rename those identifiers as a cosmetic change.

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md) for this fork's development workflow. Non-sensitive bugs and feature requests can use this repository's GitHub issues. Do not publish vulnerability details in a public issue; the fork still needs a verified private security-reporting contact (see [SECURITY.md](SECURITY.md)).

## License and source

BioLync.Pro is a modified derivative of LinkStack licensed under the [GNU Affero General Public License v3](LICENSE). Preserve applicable upstream copyright and license notices, mark modifications as required by the license, and provide the Corresponding Source to users interacting remotely with a deployed modified version. The public source repository is [joeladamski/biolync-pro](https://github.com/joeladamski/biolync-pro); the operator must ensure the source offered for a deployment corresponds to the version actually running.

See [UPSTREAM.md](UPSTREAM.md) for upstream provenance and third-party acknowledgments. The GitHub fork relationship is intentionally retained.
