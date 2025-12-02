<?php
/**
 * Main Plugin class.
 *
 * @package Another\Plugin\Woo_Settings_MCP
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP;

/**
 * Main plugin singleton class.
 *
 * Handles plugin initialization, hooks registration, and component bootstrapping.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Plugin text domain.
	 *
	 * @var string
	 */
	private string $text_domain;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private string $plugin_name;

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private string $plugin_path;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * Settings handler instance.
	 *
	 * @var Settings_Handler|null
	 */
	private ?Settings_Handler $settings_handler = null;

	/**
	 * MCP server instance.
	 *
	 * @var MCP_Server|null
	 */
	private ?MCP_Server $mcp_server = null;

	/**
	 * REST controller instance.
	 *
	 * @var REST_Controller|null
	 */
	private ?REST_Controller $rest_controller = null;

	/**
	 * Get singleton instance.
	 *
	 * @param array<string, mixed> $params Plugin constructor parameters.
	 *
	 * @return Plugin
	 */
	public static function instance( array $params = [] ): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self( $params );
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $params Plugin parameters.
	 */
	private function __construct( array $params ) {
		$this->version     = $params['version'] ?? '1.0.0';
		$this->text_domain = $params['text_domain'] ?? 'woo-settings-mcp';
		$this->plugin_name = $params['plugin_name'] ?? 'WooCommerce Settings MCP';
		$this->plugin_file = $params['plugin_file'] ?? '';
		$this->plugin_path = $params['plugin_path'] ?? '';
		$this->plugin_url  = $params['plugin_url'] ?? '';
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		$this->settings_handler = new Settings_Handler();
		$this->mcp_server       = new MCP_Server( $this->settings_handler );
		$this->rest_controller  = new REST_Controller( $this->mcp_server );
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Register REST API routes.
		if ( null !== $this->rest_controller ) {
			add_action( 'rest_api_init', [ $this->rest_controller, 'register_routes' ] );
		}

		// Register WP-CLI commands if available.
		if ( defined( 'WP_CLI' ) && WP_CLI && null !== $this->mcp_server ) {
			$this->register_cli_commands();
		}

		// Register activation/deactivation hooks.
		register_activation_hook( $this->plugin_file, [ $this, 'activate' ] );
		register_deactivation_hook( $this->plugin_file, [ $this, 'deactivate' ] );

		// Declare HPOS compatibility.
		add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );
	}

	/**
	 * Register WP-CLI commands.
	 *
	 * @return void
	 */
	private function register_cli_commands(): void {
		if ( null === $this->mcp_server ) {
			return;
		}

		\WP_CLI::add_command(
			'woo-settings-mcp',
			new Transport_Stdio( $this->mcp_server ),
			[
				'shortdesc' => 'WooCommerce Settings MCP server via stdio.',
			]
		);
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate(): void {
		// Flush rewrite rules for REST API.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Declare High-Performance Order Storage compatibility.
	 *
	 * @return void
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				$this->plugin_file,
				true
			);
		}
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Get plugin text domain.
	 *
	 * @return string
	 */
	public function get_text_domain(): string {
		return $this->text_domain;
	}

	/**
	 * Get plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Get plugin file path.
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Get plugin directory path.
	 *
	 * @return string
	 */
	public function get_plugin_path(): string {
		return $this->plugin_path;
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function get_plugin_url(): string {
		return $this->plugin_url;
	}

	/**
	 * Get settings handler.
	 *
	 * @return Settings_Handler|null
	 */
	public function get_settings_handler(): ?Settings_Handler {
		return $this->settings_handler;
	}

	/**
	 * Get MCP server.
	 *
	 * @return MCP_Server|null
	 */
	public function get_mcp_server(): ?MCP_Server {
		return $this->mcp_server;
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception When attempting to unserialize.
	 * @return void
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}
}
