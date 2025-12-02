# WooCommerce Settings MCP

A WordPress plugin that exposes WooCommerce general settings via the Model Context Protocol (MCP), enabling LLMs and AI assistants to read and modify store configuration.

## Requirements

- PHP 8.0 or higher
- WordPress 6.0 or higher
- WooCommerce 8.0 or higher

## Installation

1. Clone or download the plugin to your `wp-content/plugins/` directory
2. Run `composer install` to install dependencies
3. Activate the plugin in WordPress admin

```bash
cd wp-content/plugins/woo-settings-mcp
composer install
```

## Features

- **MCP Protocol Support**: Full JSON-RPC 2.0 implementation
- **Two Transport Layers**:
  - **stdio**: Via WP-CLI for local MCP clients
  - **HTTP**: Via REST API for remote MCP clients
- **Read/Write Operations**: List, get, and update WooCommerce settings
- **Validation**: All updates validated against WooCommerce's rules
- **High-Performance Order Storage (HPOS)**: Compatible with WooCommerce HPOS

## Supported Settings

### Store Address

| Option Name | Type | Description |
|-------------|------|-------------|
| `woocommerce_store_address` | string | Address line 1 |
| `woocommerce_store_address_2` | string | Address line 2 |
| `woocommerce_store_city` | string | City |
| `woocommerce_default_country` | string | Country:State (e.g., "US:CA") |
| `woocommerce_store_postcode` | string | Postcode/ZIP |

### General Options

| Option Name | Type | Allowed Values |
|-------------|------|----------------|
| `woocommerce_allowed_countries` | string | `all`, `all_except`, `specific` |
| `woocommerce_all_except_countries` | array | Country codes (when `all_except`) |
| `woocommerce_specific_allowed_countries` | array | Country codes (when `specific`) |
| `woocommerce_ship_to_countries` | string | `""` (selling locations), `all`, `specific`, `disabled` |
| `woocommerce_specific_ship_to_countries` | array | Country codes |
| `woocommerce_default_customer_address` | string | `""`, `base`, `geolocation`, `geolocation_ajax` |

### Currency Options

| Option Name | Type | Allowed Values |
|-------------|------|----------------|
| `woocommerce_currency` | string | Any valid currency code (e.g., `USD`, `EUR`) |
| `woocommerce_currency_pos` | string | `left`, `right`, `left_space`, `right_space` |
| `woocommerce_price_thousand_sep` | string | Any character |
| `woocommerce_price_decimal_sep` | string | Any character |
| `woocommerce_price_num_decimals` | int | 0-8 |

## Usage

### Via WP-CLI (stdio transport)

```bash
wp woo-settings-mcp
```

This starts the MCP server in stdio mode, reading JSON-RPC messages from stdin and writing responses to stdout.

### Via REST API (HTTP transport)

**Endpoint**: `POST /wp-json/woo-settings-mcp/v1/mcp`

Send JSON-RPC 2.0 requests to the endpoint. Authentication required (see [Testing with Postman](#testing-with-postman)).

**Health Check**: `GET /wp-json/woo-settings-mcp/v1/health`

**Schema**: `GET /wp-json/woo-settings-mcp/v1/schema`

## MCP Tools

### list_settings

List all WooCommerce general settings with their current values.

```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "list_settings",
    "arguments": {
      "group": "currency_options"
    }
  },
  "id": 1
}
```

Optional `group` parameter: `store_address`, `general_options`, `currency_options`

### get_setting

Get a specific setting by option name.

```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "get_setting",
    "arguments": {
      "option_name": "woocommerce_currency"
    }
  },
  "id": 2
}
```

### update_setting

Update a setting value. Requires `manage_woocommerce` capability.

```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "update_setting",
    "arguments": {
      "option_name": "woocommerce_currency",
      "value": "EUR"
    }
  },
  "id": 3
}
```

## MCP Protocol Methods

| Method | Description |
|--------|-------------|
| `initialize` | Initialize MCP session |
| `initialized` | Notification after initialization |
| `tools/list` | List available tools |
| `tools/call` | Call a tool |
| `ping` | Health check |

## Testing with Postman

### Step 1: Generate WordPress Application Password

WordPress 5.6+ includes Application Passwords for REST API authentication.

1. Log in to WordPress admin
2. Go to **Users** → **Profile** (or edit a user with `manage_woocommerce` capability)
3. Scroll down to **Application Passwords**
4. Enter a name (e.g., `Postman MCP Testing`)
5. Click **Add New Application Password**
6. **Copy the generated password immediately** (it won't be shown again)
   - Format: `xxxx xxxx xxxx xxxx xxxx xxxx` (24 characters with spaces)

### Step 2: Configure Postman Authentication

1. Open Postman and create a new request
2. Go to the **Authorization** tab
3. Select **Type**: `Basic Auth`
4. Enter:
   - **Username**: Your WordPress username
   - **Password**: The application password (with or without spaces)

### Step 3: Test the Health Endpoint

**Request:**
```
GET {{site_url}}/wp-json/woo-settings-mcp/v1/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "version": "1.0.0",
  "woocommerce_active": true,
  "mcp_protocol": "2024-11-05"
}
```

### Step 4: Send MCP Requests

**Initialize Session:**
```
POST {{site_url}}/wp-json/woo-settings-mcp/v1/mcp
Content-Type: application/json
```

```json
{
  "jsonrpc": "2.0",
  "method": "initialize",
  "params": {
    "protocolVersion": "2024-11-05",
    "clientInfo": {
      "name": "postman",
      "version": "1.0.0"
    }
  },
  "id": 1
}
```

**List Available Tools:**
```json
{
  "jsonrpc": "2.0",
  "method": "tools/list",
  "id": 2
}
```

**Get All Settings:**
```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "list_settings",
    "arguments": {}
  },
  "id": 3
}
```

**Get Specific Setting:**
```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "get_setting",
    "arguments": {
      "option_name": "woocommerce_currency"
    }
  },
  "id": 4
}
```

**Update Setting:**
```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "update_setting",
    "arguments": {
      "option_name": "woocommerce_currency",
      "value": "EUR"
    }
  },
  "id": 5
}
```

### Postman Environment Variables

Create environment variables for easier testing:

| Variable | Example Value |
|----------|---------------|
| `site_url` | `https://your-site.com` |
| `wp_username` | `admin` |
| `wp_app_password` | `xxxx xxxx xxxx xxxx xxxx xxxx` |

### Troubleshooting Authentication

| Error | Solution |
|-------|----------|
| `401 Unauthorized` | Check username and application password |
| `403 Forbidden` | User lacks `manage_woocommerce` capability |
| `rest_forbidden` | Application passwords may be disabled |
| `invalid_username` | Username doesn't exist |

**Enable Application Passwords** (if disabled):

Add to `wp-config.php`:
```php
define( 'WP_ENVIRONMENT_TYPE', 'local' ); // For local development
```

Or add to your theme's `functions.php`:
```php
add_filter( 'wp_is_application_passwords_available', '__return_true' );
```

## Development

### Install Dependencies

```bash
composer install
```

### Run Tests

```bash
composer test
```

### Run Tests with Coverage

```bash
composer test:coverage
```

### Code Style

```bash
# Check code style
composer phpcs

# Fix code style
composer phpcbf
```

### Static Analysis

```bash
composer phpstan
```

## CI/CD

This project uses GitHub Actions for continuous integration and automatic releases.

### Workflows

| Workflow | Trigger | Description |
|----------|---------|-------------|
| CI | Push/PR to main, develop | Runs tests, PHPCS, PHPStan on PHP 8.0-8.3 |
| Release | Push to main | Automatic semantic versioning and release |

### Automatic Versioning

Version bumps are determined automatically from commit messages using [Conventional Commits](https://www.conventionalcommits.org/):

| Commit Type | Example | Version Bump |
|-------------|---------|--------------|
| `fix:` | `fix: correct currency validation` | Patch (1.0.0 → 1.0.1) |
| `feat:` | `feat: add shipping settings` | Minor (1.0.0 → 1.1.0) |
| `feat!:` or `BREAKING CHANGE:` | `feat!: redesign API` | Major (1.0.0 → 2.0.0) |

### Release Process

1. Push commits to `main` branch
2. GitHub Actions analyzes commit messages
3. Version is automatically bumped in:
   - `woo-settings-mcp.php` (plugin header + constant)
   - `composer.json`
4. CHANGELOG.md is updated
5. Git tag and GitHub release are created
6. Plugin ZIP archive is attached to release

### GitHub Token Configuration

The CI/CD workflows use `GITHUB_TOKEN` for authentication. By default, GitHub Actions provides this token automatically, but you may need to configure permissions.

#### Option 1: Use Default GITHUB_TOKEN (Recommended)

The default token works if your repository settings allow it:

1. Go to **Repository Settings** → **Actions** → **General**
2. Under **Workflow permissions**, select:
   - **Read and write permissions**
   - Check **Allow GitHub Actions to create and approve pull requests**
3. Click **Save**

The workflows already include the required permissions block:
```yaml
permissions:
  contents: write
  pull-requests: write
```

#### Option 2: Use Personal Access Token (PAT)

If you need the release to trigger other workflows (e.g., deployment), create a PAT:

1. Go to **GitHub Settings** → **Developer settings** → **Personal access tokens** → **Fine-grained tokens**
2. Click **Generate new token**
3. Configure:
   - **Token name**: `WOO_SETTINGS_MCP_RELEASE`
   - **Expiration**: Set as needed (recommend 90 days minimum)
   - **Repository access**: Select your repository
   - **Permissions**:
     | Permission | Access |
     |------------|--------|
     | Contents | Read and write |
     | Metadata | Read-only |
     | Pull requests | Read and write |
4. Click **Generate token** and copy it
5. Add to repository secrets:
   - Go to **Repository Settings** → **Secrets and variables** → **Actions**
   - Click **New repository secret**
   - Name: `PAT_TOKEN`
   - Value: Paste your token
6. Update `.github/workflows/release.yml`:
   ```yaml
   - name: Checkout code
     uses: actions/checkout@v4
     with:
       token: ${{ secrets.PAT_TOKEN }}
   ```

#### Troubleshooting

| Issue | Solution |
|-------|----------|
| "Resource not accessible by integration" | Enable write permissions in repository settings |
| Release not triggering other workflows | Use a PAT instead of GITHUB_TOKEN |
| "refusing to allow GitHub App to create or update workflow file" | PAT needs `workflow` permission for modifying workflow files |

## Hooks

### Actions

- `woo_settings_mcp_setting_updated` - Fired after a setting is updated via MCP
  - Parameters: `$option_name`, `$sanitized_value`, `$original_value`

## Security

- All update operations require `manage_woocommerce` capability
- All values are validated against WooCommerce's settings schema
- All input is sanitized using WordPress sanitization functions
- REST API endpoints require authentication

## License

GPL-2.0-or-later

## Author

Nemanja Cimbaljevic <wpcimba@pm.me>

