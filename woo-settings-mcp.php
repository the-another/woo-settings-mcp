<?php
/**
 * Plugin Name: WooCommerce Settings MCP
 * Plugin URI: https://github.com/the-another/woo-settings-mcp
 * Description: Exposes WooCommerce general settings via MCP (Model Context Protocol) for LLM integration.
 * Author: Nemanja Cimbaljevic <wpcimba@pm.me>
 * Version: 1.0.2
 * Author URI: https://cimba.blog/
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Text Domain: woo-settings-mcp
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Another\Plugin\Woo_Settings_MCP
 */

declare( strict_types = 1 );

use Another\Plugin\Woo_Settings_MCP\Plugin;

if ( ! defined( 'WPINC' ) ) {
	exit;
}

// Define plugin constants.
define( 'WOO_SETTINGS_MCP_VERSION', '1.0.2' );
define( 'WOO_SETTINGS_MCP_FILE', __FILE__ );
define( 'WOO_SETTINGS_MCP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_SETTINGS_MCP_URL', plugin_dir_url( __FILE__ ) );

// Autoloader for prefixed dependencies (Mozart).
if ( file_exists( WOO_SETTINGS_MCP_PATH . 'includes/Dependencies/autoload.php' ) ) {
	require_once WOO_SETTINGS_MCP_PATH . 'includes/Dependencies/autoload.php';
}

// Autoloader for plugin classes.
if ( file_exists( WOO_SETTINGS_MCP_PATH . 'vendor/autoload.php' ) ) {
	require_once WOO_SETTINGS_MCP_PATH . 'vendor/autoload.php';
}

( static function (): void {
	add_action(
		'plugins_loaded',
		static function (): void {
			// Check if WooCommerce is active.
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action(
					'admin_notices',
					static function (): void {
						?>
						<div class="notice notice-error">
							<p>
								<?php
								esc_html_e(
									'WooCommerce Settings MCP requires WooCommerce to be installed and active.',
									'woo-settings-mcp'
								);
								?>
							</p>
						</div>
						<?php
					}
				);
				return;
			}

			Woo_Settings_MCP(
				[
					'version'     => WOO_SETTINGS_MCP_VERSION,
					'text_domain' => 'woo-settings-mcp',
					'plugin_name' => 'WooCommerce Settings MCP',
					'plugin_file' => WOO_SETTINGS_MCP_FILE,
					'plugin_path' => WOO_SETTINGS_MCP_PATH,
					'plugin_url'  => WOO_SETTINGS_MCP_URL,
				]
			)->init();
		}
	);
} )();

/**
 * Main plugin function.
 *
 * @param array<string, mixed> $params Plugin constructor parameters.
 *
 * @return Plugin
 */
function Woo_Settings_MCP( array $params = [] ): Plugin {
	return Plugin::instance( $params );
}

