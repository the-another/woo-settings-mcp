# Contributing to WooCommerce Settings MCP

Thank you for your interest in contributing to this project!

## Branch Strategy

| Branch | Purpose |
|--------|---------|
| `develop` | Main development branch. All work happens here. |
| `master` | Release-only branch. Contains only versioned releases. |

**Important:** The `master` branch is protected. Only the GitHub Actions workflow can push to it during releases.

## Commit Message Convention

This project uses [Conventional Commits](https://www.conventionalcommits.org/) for automatic semantic versioning.

### Commit Message Format

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

### Types

| Type | Description | Version Bump |
|------|-------------|--------------|
| `feat` | A new feature | Minor |
| `fix` | A bug fix | Patch |
| `docs` | Documentation only changes | None |
| `style` | Code style changes (formatting, etc.) | None |
| `refactor` | Code refactoring without feature changes | None |
| `perf` | Performance improvements | Patch |
| `test` | Adding or updating tests | None |
| `build` | Build system or dependencies | None |
| `ci` | CI/CD configuration | None |
| `chore` | Other changes | None |

### Breaking Changes

To trigger a major version bump, add `BREAKING CHANGE:` in the commit footer or append `!` after the type:

```
feat!: remove deprecated settings API

BREAKING CHANGE: The old settings API has been removed in favor of the new MCP-based API.
```

### Examples

```bash
# Patch release (1.0.0 -> 1.0.1)
git commit -m "fix: correct currency validation for edge cases"

# Minor release (1.0.0 -> 1.1.0)
git commit -m "feat: add support for shipping settings"

# Major release (1.0.0 -> 2.0.0)
git commit -m "feat!: redesign MCP protocol implementation"
```

## Development Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```

3. Run tests:
   ```bash
   composer test
   ```

4. Check code style:
   ```bash
   composer phpcs
   ```

5. Run static analysis:
   ```bash
   composer phpstan
   ```

## Pull Request Process

1. Fork the repository
2. Create a feature branch from `develop`
3. Make your changes following the coding standards
4. Ensure all tests pass
5. Submit a pull request to `develop`

## Release Process

Releases are **manually triggered** via GitHub Actions, allowing you to batch multiple changes into a single release.

### How to Create a Release

1. Go to **Actions** → **Release** workflow
2. Click **Run workflow**
3. Select `develop` branch
4. Optionally enable **Dry run** to preview what would be released
5. Click **Run workflow**

```
develop branch                              master branch
    │                                            │
    │  Accumulate commits with                   │
    │  conventional commit messages              │
    │                                            │
    │  ┌─────────────────────────┐               │
    │  │ fix: bug fix            │               │
    │  │ feat: new feature       │               │
    │  │ fix: another fix        │               │
    │  └─────────────────────────┘               │
    │                                            │
    ▼                                            │
[Manual trigger: Actions → Release → Run]        │
    │                                            │
    ▼                                            │
┌─────────────────────────────────┐              │
│  CI checks run                  │              │
│  - Tests (PHP 8.0-8.3)          │              │
│  - PHPCS                        │              │
│  - PHPStan                      │              │
└─────────────────────────────────┘              │
    │                                            │
    ▼                                            │
┌─────────────────────────────────┐              │
│  Semantic Release               │              │
│  - Analyzes ALL commits since   │              │
│    last release                 │              │
│  - Determines version bump      │              │
│  - Updates CHANGELOG.md         │              │
│  - Updates version in files     │              │
│  - Creates Git tag              │              │
│  - Creates GitHub Release       │              │
└─────────────────────────────────┘              │
    │                                            │
    │          Push release to master ──────────►│
    │                                            │
    ▼                                            │
┌─────────────────────────────────┐              │
│  Build Job                      │              │
│  - Creates plugin ZIP           │              │
│  - Uploads to GitHub Release    │              │
└─────────────────────────────────┘              │
```

### What Happens During Release

1. **Manual trigger** - You start the release workflow from GitHub Actions
2. **CI checks run** - Tests, PHPCS, and PHPStan must pass
3. **Semantic Release analyzes commits** - All commits since the last release are analyzed
4. **Version is automatically determined** - Based on `fix:`, `feat:`, or breaking changes
5. **Files are updated** - `CHANGELOG.md`, `woo-settings-mcp.php`, `composer.json`
6. **Git tag is created** - e.g., `v1.2.0`
7. **GitHub Release is created** - With auto-generated release notes
8. **Release is pushed to `master`** - The workflow pushes the release commit to master
9. **Plugin archive is built** - ZIP file is attached to the GitHub Release

### Dry Run Mode

Use the **dry run** option to preview what would be released without making any changes. This shows:
- What version would be created
- What commits would be included
- What the changelog would look like

## Protecting the Master Branch

To ensure only the workflow can push to `master`, configure branch protection rules in GitHub:

1. Go to **Settings** → **Branches** → **Add branch protection rule**
2. Branch name pattern: `master`
3. Enable:
   - ✅ Require a pull request before merging
   - ✅ Require status checks to pass before merging
   - ✅ Do not allow bypassing the above settings
4. Under "Restrict who can push to matching branches":
   - Add only `github-actions[bot]` or use a PAT with appropriate permissions

This ensures `master` only receives updates from the automated release workflow.
