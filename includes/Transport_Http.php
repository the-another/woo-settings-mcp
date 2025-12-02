<?php
/**
 * HTTP Transport class.
 *
 * @package Another\Plugin\Woo_Settings_MCP
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP;

/**
 * HTTP transport for MCP communication via REST API.
 *
 * Processes JSON-RPC messages received via HTTP POST requests.
 */
class Transport_Http {

	/**
	 * MCP server instance.
	 *
	 * @var MCP_Server
	 */
	private MCP_Server $mcp_server;

	/**
	 * Constructor.
	 *
	 * @param MCP_Server $mcp_server The MCP server instance.
	 */
	public function __construct( MCP_Server $mcp_server ) {
		$this->mcp_server = $mcp_server;
	}

	/**
	 * Handle an HTTP request containing an MCP message.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_request( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$body = $request->get_body();

		if ( empty( $body ) ) {
			return new \WP_Error(
				'empty_body',
				__( 'Request body is empty.', 'woo-settings-mcp' ),
				[ 'status' => 400 ]
			);
		}

		// Validate JSON.
		$decoded = json_decode( $body, true );
		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_REST_Response(
				[
					'jsonrpc' => '2.0',
					'id'      => null,
					'error'   => [
						'code'    => -32700,
						'message' => 'Parse error: ' . json_last_error_msg(),
					],
				],
				200
			);
		}

		// Process the message.
		$response = $this->mcp_server->process_message( $body );

		if ( null === $response ) {
			// Notification - no response expected.
			return new \WP_REST_Response( null, 204 );
		}

		// Decode the response to return as array.
		$response_data = json_decode( $response, true );

		return new \WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Validate the request for permission.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_permission( \WP_REST_Request $request ): bool|\WP_Error {
		// For read operations, require read capability.
		// For write operations, the MCP server will check manage_woocommerce.
		if ( ! current_user_can( 'read' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'woo-settings-mcp' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get the MCP server instance.
	 *
	 * @return MCP_Server
	 */
	public function get_mcp_server(): MCP_Server {
		return $this->mcp_server;
	}
}
