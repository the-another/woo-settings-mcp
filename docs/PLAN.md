# WooCommerce Settings MCP Plugin

## Project Structure

```
woo-settings-mcp/
├── woo-settings-mcp.php          # Minimal bootstrap file
├── composer.json                  # Composer with Mozart, WPCS, PHPStan
├── phpcs.xml.dist                # PHPCS configuration
├── phpstan.neon                  # PHPStan configuration
├── includes/
│   ├── class-plugin.php          # Main plugin singleton
│   ├── class-mcp-server.php      # MCP protocol handler
│   ├── class-settings-handler.php # WooCommerce settings access
│   ├── class-transport-stdio.php # stdio transport (WP-CLI)
│   ├── class-transport-http.php  # HTTP transport
│   └── class-rest-controller.php # REST API endpoint
└── tests/
    ├── bootstrap.php
    └── unit/
        └── SettingsHandlerTest.php
```

## Coding Conventions

- Namespace: `Another\Plugin\Woo_Settings_MCP`
- All PHP files use `declare( strict_types = 1 );`
- PHP 8.0+ features allowed
- Automattic/WordPress Coding Standards (WPCS)
- PSR-4 autoloading

## WooCommerce General Settings - Supported Options

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
| `woocommerce_ship_to_countries` | string | `''` (to selling locations), `all`, `specific`, `disabled` |
| `woocommerce_specific_ship_to_countries` | array | Country codes |
| `woocommerce_default_customer_address` | string | `''`, `base`, `geolocation`, `geolocation_ajax` |

*Note: Tax and coupon settings excluded - separate feature.*

### Currency Options

| Option Name | Type | Allowed Values / Filter |
|-------------|------|-------------------------|
| `woocommerce_currency` | string | Use `get_woocommerce_currencies()` |
| `woocommerce_currency_pos` | string | `left`, `right`, `left_space`, `right_space` |
| `woocommerce_price_thousand_sep` | string | Any character |
| `woocommerce_price_decimal_sep` | string | Any character |
| `woocommerce_price_num_decimals` | int | 0-8 |

### Filters to Implement

- `woocommerce_currencies` - Filter for available currencies list
- `woocommerce_countries` - Filter for countries list
- `woocommerce_allowed_countries` - Filter for selling countries
- `woocommerce_specific_allowed_countries` - Filter for specific countries

## Implementation Details

### Main Plugin File (`woo-settings-mcp.php`)

Minimal bootstrap following [another-wishlist pattern](https://github.com/the-another/another-wishlist/blob/master/another-wishlist.php):

- Plugin headers, `declare( strict_types = 1 );`
- Autoloader includes (`vendor_prefixed/` + `vendor/`)
- IIFE with `plugins_loaded` hook
- `Woo_Settings_MCP()` helper function

### Settings Handler Validation

Each setting update will:

1. Check if option exists in WooCommerce settings schema
2. Validate value against allowed values using WooCommerce filters
3. Apply same sanitization as WooCommerce admin UI
4. Return appropriate error if validation fails

### Composer Configuration

```json
{
  "autoload": {
    "psr-4": { "Another\\Plugin\\Woo_Settings_MCP\\": "includes/" }
  },
  "require-dev": {
    "coenjacobs/mozart": "^0.7",
    "wp-coding-standards/wpcs": "^3.0",
    "phpstan/phpstan": "^1.10",
    "szepeviktor/phpstan-wordpress": "^1.3",
    "php-stubs/wordpress-stubs": "^6.4",
    "php-stubs/woocommerce-stubs": "^8.0",
    "brain/monkey": "^2.6",
    "mockery/mockery": "^1.6"
  }
}
```

### MCP Tools

| Tool | Description |
|------|-------------|
| `list_settings` | List all general settings with current values and allowed values |
| `get_setting` | Get a specific setting by option name |
| `update_setting` | Update a setting (validates against WooCommerce rules) |

### Transports

| Transport | Implementation |
|-----------|----------------|
| stdio | WP-CLI command reading from stdin, writing to stdout |
| HTTP | REST API at `/wp-json/woo-settings-mcp/v1/mcp` |

## Testing Strategy

### Brain Monkey Unit Tests

- **PluginTest.php** - Singleton, initialization, hooks
- **SettingsHandlerTest.php** - Schema, CRUD operations
- **SettingsValidationTest.php** - Edge cases, validation rules
- **McpServerTest.php** - JSON-RPC protocol, tools
- **TransportHttpTest.php** - HTTP transport, permissions
- **RestControllerTest.php** - REST API routes, endpoints

### Edge Cases Covered

- Empty arrays for country lists
- Invalid country/currency codes
- Malformed JSON-RPC requests
- Missing required MCP parameters
- WooCommerce not active
- Permission/capability checks
- Type coercion (string to int)
- Unicode characters in addresses
- Boundary values (decimals 0-8)

