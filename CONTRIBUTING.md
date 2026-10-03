# Contributing to BioLync.Pro

BioLync.Pro is a modified [LinkStack](https://github.com/LinkStackOrg/LinkStack) fork. Contributions to this repository should target BioLync.Pro; the upstream project's Discord, issues, and maintainers are separate.

For non-sensitive bugs and feature proposals, [open an issue in this repository](https://github.com/joeladamski/biolync-pro/issues). Do not post vulnerability details publicly; see [SECURITY.md](SECURITY.md).

## Pull requests

1. Branch from `main` and describe the purpose and compatibility impact of your change.
2. Use a local development environment and test the relevant flows. Do not point migrations, seeders, or an installer at production data.
3. For dependency changes, update `composer.json` and `composer.lock` together and document the install/test result.
4. For branding changes, distinguish visible copy from internal `linkstack` identifiers used by themes, configuration, and routes. See the [branding audit](docs/BIOLYNC-BRANDING-AUDIT.md).
5. Preserve applicable copyright/license notices and clearly mark modified files as required by the license. Include tests or manual verification steps in the PR.

The application is licensed under the [GNU Affero General Public License v3](LICENSE). Contributions incorporated into this work need to be compatible with that license. Review [UPSTREAM.md](UPSTREAM.md) for provenance and credits.
