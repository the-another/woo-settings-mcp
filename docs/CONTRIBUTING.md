# Contributing to WooCommerce Settings MCP

Thank you for your interest in contributing to this project!

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
2. Create a feature branch from `main`
3. Make your changes following the coding standards
4. Ensure all tests pass
5. Submit a pull request to `main`

## Release Process

Releases are automated via GitHub Actions:

1. Push commits to `main` branch
2. Semantic Release analyzes commit messages
3. Version is automatically determined and bumped
4. CHANGELOG.md is updated
5. GitHub release is created with plugin archive

