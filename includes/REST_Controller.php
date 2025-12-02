<?php
/**
 * REST Controller class.
 *
 * @package Another\Plugin\Woo_Settings_MCP
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP;

/**
 * REST API controller for MCP HTTP transport.
 *
 * Registers and handles REST API endpoints for MCP communication.
 */
class REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'woo-settings-mcp/v1';

	/**
	 * MCP server instance.
	 *
	 * @var MCP_Server
	 */
	private MCP_Server $mcp_server;

	/**
	 * HTTP transport instance.
	 *
	 * @var Transport_Http|null
	 */
	private ?Transport_Http $transport = null;

	/**
	 * Constructor.
	 *
	 * @param MCP_Server $mcp_server The MCP server instance.
	 */
	public function __construct( MCP_Server $mcp_server ) {
		$this->mcp_server = $mcp_server;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->transport = new Transport_Http( $this->mcp_server );

		// Main MCP endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/mcp',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this->transport, 'handle_request' ],
					'permission_callback' => [ $this->transport, 'check_permission' ],
					'args'                => [],
				],
			]
		);

		// Health check endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/health',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'health_check' ],
					'permission_callback' => '__return_true',
				],
			]
		);

		// Schema endpoint for discovering available settings.
		register_rest_route(
			self::NAMESPACE,
			'/schema',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_schema' ],
					'permission_callback' => [ $this, 'check_read_permission' ],
				],
			]
		);
	}

	/**
	 * Health check endpoint handler.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function health_check( \WP_REST_Request $request ): \WP_REST_Response {
		$woocommerce_active = class_exists( 'WooCommerce' );

		return new \WP_REST_Response(
			[
				'status'             => 'ok',
				'version'            => WOO_SETTINGS_MCP_VERSION,
				'woocommerce_active' => $woocommerce_active,
				'mcp_protocol'       => '2024-11-05',
			],
			200
		);
	}

	/**
	 * Schema endpoint handler.
	 *
	 * Returns the available settings schema.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_schema( \WP_REST_Request $request ): \WP_REST_Response {
		$settings_handler = $this->mcp_server->get_settings_handler();
		$schema           = $settings_handler->get_schema();

		return new \WP_REST_Response(
			[
				'settings' => $schema,
			],
			200
		);
	}

	/**
	 * Check read permission.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_read_permission( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view WooCommerce settings.', 'woo-settings-mcp' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get the REST API namespace.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return self::NAMESPACE;
	}

	/**
	 * Get the MCP server instance.
	 *
	 * @return MCP_Server
	 */
	public function get_mcp_server(): MCP_Server {
		return $this->mcp_server;
	}

	/**
	 * Get the HTTP transport instance.
	 *
	 * @return Transport_Http|null
	 */
	public function get_transport(): ?Transport_Http {
		return $this->transport;
	}
}
