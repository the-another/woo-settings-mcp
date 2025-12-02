<?php
/**
 * Stdio Transport class.
 *
 * @package Another\Plugin\Woo_Settings_MCP
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP;

/**
 * Stdio transport for MCP communication via WP-CLI.
 *
 * Reads JSON-RPC messages from stdin and writes responses to stdout.
 */
class Transport_Stdio {

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
	 * Run the MCP server via stdio.
	 *
	 * This is the main WP-CLI command handler.
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		// Set up non-blocking input.
		stream_set_blocking( STDIN, false );

		// Buffer for incomplete messages.
		$buffer = '';

		\WP_CLI::debug( 'WooCommerce Settings MCP server started.' );

		// Main loop - read from stdin.
		while ( true ) {
			$input = fgets( STDIN );

			if ( false === $input ) {
				// Check if stdin is closed.
				if ( feof( STDIN ) ) {
					\WP_CLI::debug( 'EOF received, shutting down.' );
					break;
				}

				// No input available, sleep briefly and continue.
				usleep( 10000 ); // 10ms
				continue;
			}

			$buffer .= $input;

			// Try to parse complete JSON messages.
			while ( true ) {
				$message = $this->extract_message( $buffer );

				if ( null === $message ) {
					break;
				}

				$this->handle_message( $message );
			}
		}
	}

	/**
	 * Extract a complete JSON message from the buffer.
	 *
	 * @param string $buffer The input buffer (passed by reference).
	 *
	 * @return string|null The extracted message or null if incomplete.
	 */
	private function extract_message( string &$buffer ): ?string {
		$buffer = trim( $buffer );

		if ( '' === $buffer ) {
			return null;
		}

		// Try to decode the buffer as JSON.
		$decoded = json_decode( $buffer, true );

		if ( null !== $decoded && JSON_ERROR_NONE === json_last_error() ) {
			$message = $buffer;
			$buffer  = '';
			return $message;
		}

		// Check for newline-delimited JSON.
		$newline_pos = strpos( $buffer, "\n" );
		if ( false !== $newline_pos ) {
			$potential_message = substr( $buffer, 0, $newline_pos );
			$decoded           = json_decode( $potential_message, true );

			if ( null !== $decoded && JSON_ERROR_NONE === json_last_error() ) {
				$buffer = substr( $buffer, $newline_pos + 1 );
				return $potential_message;
			}
		}

		return null;
	}

	/**
	 * Handle a complete MCP message.
	 *
	 * @param string $message The JSON message.
	 *
	 * @return void
	 */
	private function handle_message( string $message ): void {
		\WP_CLI::debug( 'Received: ' . $message );

		$response = $this->mcp_server->process_message( $message );

		if ( null !== $response ) {
			$this->send_response( $response );
		}
	}

	/**
	 * Send a response to stdout.
	 *
	 * @param string $response The JSON response.
	 *
	 * @return void
	 */
	private function send_response( string $response ): void {
		\WP_CLI::debug( 'Sending: ' . $response );

		// Write to stdout with newline.
		fwrite( STDOUT, $response . "\n" );
		fflush( STDOUT );
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
